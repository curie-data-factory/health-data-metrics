##################################################
# Credits Institut Curie
# Ce Script est un générateur procédural de règles pour HDM

# Armand Leopold
# 10/05/2021
##################################################

#!/usr/bin/env python
# coding: utf-8

# # Rule Generator Basic :
# * Author : Armand LEOPOLD
# * Date : 18/01/2021
#
# > Le role de rule generator est de pouvoir générer de façon systemique des règles afin
# de générer des alertes bas niveau (colonne / données) sans avoir à ecrire toutes les règles à la main.

import pandas as pd
import base64
import time
import json
import sys
import os

from datetime import datetime
from time import strftime
from sqlalchemy.sql import text
from sqlalchemy import create_engine

rootConfFolder = "."
now = datetime.now()
now = now.strftime("%Y-%m-%d")

# Connexion à la base de donnée et récupération de la liste des tables :
conf = None
with open(rootConfFolder+'/conf.json', 'r') as confFile:
    conf = json.load(confFile)

print("[INFO] Recovering environnment variables.")
try:
    env = os.environ['ENV']
except:
    print("[ERROR] ERROR GETTING JOBTORUN ENVIRONMENT VARIABLE, please check if it is correctly setup !")
    sys.exit()

dbUser = conf[env]['user']
dbPassword = conf[env]['password']
dbHost = conf[env]['host']
dbPort = conf[env]['port']
dbName = conf[env]['database']

# creating rule tables if not exist :
metricsDB = create_engine('mysql://'+dbUser+':'+dbPassword+'@'+dbHost+':'+dbPort+'/'+dbName).connect()
file = open('../create-table/create.sql')
for query in file.read().split(";"):
    metricsDB.execute(text(query))
metricsDB.close()

# Loading configuration
metricsDB = create_engine('mysql://'+dbUser+':'+dbPassword+'@'+dbHost+':'+dbPort+'/'+dbName).connect()
dbKeys = pd.read_sql('SELECT * FROM hdm_core_table_corr_db_rp WHERE rp_key = "basic"',metricsDB)
mpConf = pd.read_sql('SELECT * FROM hdm_pack_metric_conf WHERE pack_name = "basic" AND pack_version != "master" ORDER BY `pack_version` DESC ',metricsDB)
metricsDB.close()

mpConf = json.loads(base64.b64decode(mpConf.iloc[0]['pack_config']).decode("utf-8"))

dbList = []
for db in dbKeys['db_key'].apply(lambda x: x.split(':')).to_list():
    dbList.append({'database':db[0],'host':db[1],'port':db[2],'user':db[3],'ssl':db[4]})
dbList = pd.DataFrame(dbList).merge(pd.DataFrame(conf['databases']),on=['database','host','port','user','ssl']).to_json(orient="records")
dbList = json.loads(dbList)


# ## #1 Cas d'usage : Détecter les doublons dans les clés primaires/étrangères
#
# Afin de détecter les doublons, il est nécéssaire de récupérer le schéma de la base de donnée et de créer une règle sur le nombre de valeurs uniques ou non à partir des métriques.

### Connexion à la base de donnée
databaseSchemas = []

for database in dbList:
    print("[Info]["+database['database']+"] Fetching Schema From database : "+database['database'])
    time.sleep(1)
    try:
        remoteDB = create_engine('mysql://'+database['user']+':'+database['password']+'@'+database['host']+':'+database['port']+'/'+database['database']).connect()
        query = "SELECT * FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = \""+database['database']+"\""
        print("[Info] "+query)
        dfSchema = pd.read_sql(con=remoteDB,sql=query)
        databaseSchemas.append(dfSchema)
        remoteDB.close()
    except:
        print("[Error]["+database['database']+"] Impossible de se connecter à la base de donnée.", sys.exc_info())
        remoteDB.close()

ruleList = []
for schema in databaseSchemas:
    for elem in schema.iterrows():
        dictElem = {"rule_name":"doublons_ids","rule_type":"conditionnelle","alert_level":"High","alert_class":"METRIQUE","alert_message":"Doublons dans la clé Primaire","alert_scope":"column","condition_trigger":"returnTrue","condition_scope":"metrics","database":elem[1]["CONSTRAINT_SCHEMA"],"table":elem[1]["TABLE_NAME"],"column":elem[1]["COLUMN_NAME"],"rule_content":'{"metric":"highest_frequency","condition":">","conditionValue":"1","conditionTrigger":"returnTrue"}'}
        ruleList.append(dictElem)

rulesDf = pd.DataFrame(ruleList,columns=["rule_name","rule_type","alert_level","alert_class","alert_message","alert_scope","condition_trigger","condition_scope","database","table","column","rule_content"])

#### Put new rules to rule tables :
try:
    # Publishing metrics to MySQL :
    print("[Info] Put rules to database MySQL")
    metricsDB = create_engine('mysql://'+dbUser+':'+dbPassword+'@'+dbHost+':'+dbPort+'/'+dbName).connect()
    rulesDf.to_sql(con=metricsDB, name='rule_basic', if_exists='append',index=False)
    metricsDB.close()
except:
    raise


# ## #2 Cas d'usage : Détecter les colonnes vides
#

metricsDB = create_engine('mysql://'+dbUser+':'+dbPassword+'@'+dbHost+':'+dbPort+'/'+dbName).connect()
query = "SELECT * FROM `metric_basic` WHERE `date` IN ('"+now+"')"
print("[Info] "+query)
dfMetrics = pd.read_sql(con=metricsDB,sql=query)
metricsDB.close()

ruleList = []
for index, elem in dfMetrics[['database','table','column']].iterrows():
    dictElem = {"rule_name":"100% missing values","rule_type":"conditionnelle","alert_level":"High","alert_class":"METRIQUE","alert_message":"La colonne est vide.","alert_scope":"column","condition_trigger":"returnTrue","condition_scope":"metrics","database":elem["database"],"table":elem["table"],"column":elem["column"],"rule_content":'{"metric":"percent_na_values","condition":"==","conditionValue":"100","conditionTrigger":"returnTrue"}'}
    ruleList.append(dictElem)

rulesDf = pd.DataFrame(ruleList,columns=["rule_name","rule_type","alert_level","alert_class","alert_message","alert_scope","condition_trigger","condition_scope","database","table","column","rule_content"])

#### Put new rules to rule tables :
try:
    # Publishing metrics to MySQL :
    print("[Info] Put rules to database MySQL")
    metricsDB = create_engine('mysql://'+dbUser+':'+dbPassword+'@'+dbHost+':'+dbPort+'/'+dbName).connect()
    rulesDf.to_sql(con=metricsDB, name='rule_basic', if_exists='append',index=False)
    metricsDB.close()
except:
    raise


# # Dropping doublons dans les règles

try:
    print("[Info] Dé-duplication des règles.")
    metricsDB = create_engine('mysql://'+dbUser+':'+dbPassword+'@'+dbHost+':'+dbPort+'/'+dbName).connect()
    df = pd.read_sql('SELECT * FROM rule_basic',metricsDB)
    df = df.drop(['id_rule','date_modif','date_creation'], axis=1)
    df = df.drop_duplicates()
    metricsDB.execute(text("TRUNCATE `rule_basic`;"))
    df.to_sql(con=metricsDB, name='rule_basic', if_exists='append',index=False)
    metricsDB.close()
    print("[Info] Fin process Rule Pack Basic.")
except:
    raise

