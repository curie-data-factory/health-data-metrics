##################################################
# Credits Institut Curie 
# Ce Script est un interpréteur de règles HDM, il créer ensuite des alertes.

# Armand Leopold
# 10/05/2021
##################################################

#!/usr/bin/env python
# coding: utf-8

# # Rule Interpreter Basic :
# * Author : Armand LEOPOLD 
# * Date : 19/01/2021
# 
# > Script qui interprete les règles enregistrés dans metricsRules.

import pandas as pd
import multiprocessing
import sys
import json
import os
import base64

from datetime import datetime, timedelta
from time import gmtime, strftime
from multiprocessing import Pool

from sqlalchemy import create_engine
from sqlalchemy.sql import text

now = datetime.now()
now = now.strftime("%Y-%m-%d")

rootConfFolder = "."

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

# Loading configuration
metricsDB = create_engine('mysql://'+dbUser+':'+dbPassword+'@'+dbHost+':'+dbPort+'/'+dbName).connect()
dbKeys = pd.read_sql('SELECT * FROM hdm_core_table_corr_db_rp WHERE rp_key = "basic"',metricsDB)
mpConf = pd.read_sql('SELECT * FROM hdm_pack_metric_conf WHERE pack_name = "basic" AND pack_version != "master" ORDER BY `pack_version` DESC ',metricsDB)
metricsDB.close()

mpConf = json.loads(base64.b64decode(mpConf.iloc[0]['pack_config']).decode("utf-8"))

dbList = []
for db in dbKeys['db_key'].apply(lambda x: x.split(':')).to_list():
    dbList.append({'database':db[0],'host':db[1],'type':db[2],'port':db[3],'user':db[4],'ssl':db[5]})
dbList = pd.DataFrame(dbList).merge(pd.DataFrame(conf['databases']),on=['database','host','type','port','user','ssl']).to_json(orient="records")
dbList = json.loads(dbList)


# # Récupération des règles :

# Connexion à la base de donnée
metricsDB = create_engine('mysql://'+dbUser+':'+dbPassword+'@'+dbHost+':'+dbPort+'/'+dbName).connect()

query = "SELECT * FROM `rule_basic`"
print("[Info] Query "+query)
dfRules = pd.DataFrame()
try:
    dfRules = pd.read_sql(con=metricsDB,sql=query)
except:
    print("[Error] ", sys.exc_info())
    raise
query = "SELECT * FROM `metric_basic` WHERE `date` IN ('"+now+"')"
    
print("[Info] Query "+query)
dfMetrics = pd.read_sql(con=metricsDB,sql=query)


metricsDB.close()

ruleConditions = pd.DataFrame()
ruleSQL = pd.DataFrame()
try:
    ruleConditions = dfRules[dfRules['rule_type'] == "conditionnelle"]
    ruleSQL = dfRules[dfRules['rule_type'] == "sql"]
except:
    print("[Error] ", sys.exc_info())
    raise


# # Execution des regles :

# Fonction qui évalue les règles conditionnelles sur les données : 
def ruleInterpreterConditionnalOnData(elem) :
    database = elem[0]
    version = elem[1]
    rule = pd.Series(elem[2][1])
    compareMode = elem[3]
    resultsCondition = pd.DataFrame()
    
    # Traduction de la regle en requete SQL :
    sqlRoot = "SELECT * FROM `metrics_levels` WHERE `date` IN ('"+now+"') "
    sql = sqlRoot
    
    # Ajout du scope
    if(rule['alert_scope'] == "database"):
        sql += "AND `database` = '"+rule.database+"' "
    elif(rule['alert_scope'] == "table"):
        sql += "AND `database` = '"+rule.database+"' AND `table` = '"+rule.table+"'"
    elif(rule['alert_scope'] == "column"):
        sql += "AND `database` = '"+rule.database+"' AND `table` = '"+rule.table+"' AND `column` = '"+rule.column+"'"

    # recupération du contenu de la regle : 
    ruleContent = json.loads(rule['rule_content'])

    sql += " AND `value` "+ ruleContent['condition']
    sql += " \'"+ruleContent['conditionValue']+"\'"
    query = sql
    metricsDB = create_engine('mysql://'+dbUser+':'+dbPassword+'@'+dbHost+':'+dbPort+'/'+dbName).connect()
    results = pd.read_sql(con=metricsDB,sql=query)
    print("[Info] "+query)
    metricsDB.close()

    return results

# Interpretation des regles conditionnelles :
def ruleInterpreterConditionnal(elem):
    database = elem[0]
    version = elem[1]
    rule = pd.Series(elem[2][1])
    compareMode = elem[3]
    resultsCondition = pd.DataFrame()

    # On regarde si les regles s'appliques sur des données ou des métriques :
    if(rule['alert_class'] == "DATA"):
        ################################################################
        # Données (il va falloir aller les chercher) :
        resultsCondition = ruleInterpreterConditionnalOnData(elem)
        
    else:
        ################################################################
        # Metriques : 

        # Par défaut on prend les métriques calculés sur les bases
        metricDf = pd.DataFrame()
        
        # On prend en compte le chargement des métriques de comparaisons de bases si on est en mode comparaison et qu'on évalue une règle de comparaison
        if((compareMode == 1) & (rule['alert_class'] == "METRICCOMPARE")):
            metricDf = dfMetricsCompare
        elif((compareMode == 1) & (rule['alert_class'] != "METRICCOMPARE")):
            return pd.DataFrame()
        elif((compareMode == 0) & (rule['alert_class'] == "METRICCOMPARE")):
            return pd.DataFrame()
        elif((compareMode == 0) & (rule['alert_class'] != "METRICCOMPARE")):
            metricDf = dfMetrics
        
        # recupération de la regle : 
        ruleContent = json.loads(rule['rule_content'])

        if(rule['alert_scope'] == "all"):
            metricDf = metricDf
        elif(rule['alert_scope'] == "database"):
            metricDf = metricDf.loc[metricDf.database == rule.database]
        elif(rule['alert_scope'] == "table"):
            metricDf = metricDf.loc[(metricDf.database == rule.database) & (metricDf.table == rule.table)]
        elif(rule['alert_scope'] == "column"):
            metricDf = metricDf.loc[(metricDf.database == rule.database) & (metricDf.table == rule.table) & (metricDf.column == rule.column)]

        ########################## EXECUTION D'UNE REGLE : 
        # condtitions switch : 
        valueMetric = metricDf[ruleContent['metric']]

        # On ne prend que les données non vide
        if(not valueMetric.empty):
            
            valueMetric = pd.Series(valueMetric).astype('float64')
            
            # Prise en compte de la condition de déclenchement de la regle :
            if(rule['condition_trigger'] == "returnTrue"):
                if(ruleContent['condition'] == "=="):
                    resultsCondition = metricDf.loc[valueMetric == float(ruleContent['conditionValue'])]
                elif(ruleContent['condition'] == ">="):
                    resultsCondition = metricDf.loc[valueMetric >= float(ruleContent['conditionValue'])]
                elif(ruleContent['condition'] == "<"):
                    resultsCondition = metricDf.loc[valueMetric < float(ruleContent['conditionValue'])]    
                elif(ruleContent['condition'] == ">"):
                    resultsCondition = metricDf.loc[valueMetric > float(ruleContent['conditionValue'])]    
                elif(ruleContent['condition'] == "<="):
                    resultsCondition = metricDf.loc[valueMetric <= float(ruleContent['conditionValue'])] 
                elif(ruleContent['condition'] == "!="):
                    resultsCondition = metricDf.loc[valueMetric != float(ruleContent['conditionValue'])]    
            elif(rule['condition_trigger'] == "returnFalse"):
                # Cela revient à inverser les conditions : 
                if(ruleContent['condition'] == "=="):
                    resultsCondition = metricDf.loc[valueMetric != float(ruleContent['conditionValue'])]
                elif(ruleContent['condition'] == ">="):
                    resultsCondition = metricDf.loc[valueMetric < float(ruleContent['conditionValue'])]
                elif(ruleContent['condition'] == "<"):
                    resultsCondition = metricDf.loc[valueMetric >= float(ruleContent['conditionValue'])]
                elif(ruleContent['condition'] == ">"):
                    resultsCondition = metricDf.loc[valueMetric <= float(ruleContent['conditionValue'])]
                elif(ruleContent['condition'] == "<="):
                    resultsCondition = metricDf.loc[valueMetric > float(ruleContent['conditionValue'])]
                elif(ruleContent['condition'] == "!="):
                    resultsCondition = metricDf.loc[valueMetric == float(ruleContent['conditionValue'])]
                    
    if(len(resultsCondition) > 0):
        # results conditions result agreggation meta data
        resultsCondition = resultsCondition[['database','dbversion','table','column','date']]

        resultsCondition['alert_level'] = rule['alert_level']
        resultsCondition['alert_message'] = rule['alert_message']
        resultsCondition['alert_class'] = rule['alert_class']
        resultsCondition['alert_scope'] = rule['alert_scope']
        resultsCondition['rule_id'] = rule['id_rule']
        resultsCondition['index'] = resultsCondition['table'].astype(str) +'-'+ resultsCondition['column'].astype(str) +'-'+ resultsCondition['date'].astype(str) +'-'+ resultsCondition['rule_id'].astype(str)

        # results cleanning data table
        resultsCondition = resultsCondition[['database','dbversion','table','column','date','alert_level','alert_message','alert_class','alert_scope','rule_id']]
        resultsCondition.reset_index(inplace=True,drop=True)
        
        return resultsCondition
    
# Interpretaion des regles SQL :
def ruleInterpreterSQL(elem):
    database = elem[0]
    dbversion = elem[1]
    rule = pd.Series(elem[2][1])
    compareMode = elem[3]
    
    # On rejette des regles SQL en mode comparaison. Cela n'a aucuns sens pour le moment.
    if(compareMode == 1):
        return None
    
    try:
        remoteDB = create_engine('mysql://'+database['user']+':'+database['password']+'@'+database['host']+':'+database['port']+'/'+database['database']).connect()
    except:
        print("[Error]["+database['database']+"] Impossible de se connecter à la base : "+database['database'], sys.exc_info())
        remoteDB.close()
        
    query = rule['rule_content']
    
    resultQuery = pd.DataFrame()
    logHistory = []
    try:
        resultSQL = pd.read_sql(con=remoteDB,sql=query)
        logHistory.append({'datetime':strftime("%Y-%m-%d", gmtime()),'rule_id':rule['id_rule'],'result':'ok'})
    except Exception as e:
        logHistory.append({'datetime':strftime("%Y-%m-%d", gmtime()),'rule_id':rule['id_rule'],'result':'ko'})

        resultQueryTemp = pd.DataFrame({'database':database['database']},index=[0])
        resultQueryTemp['dbversion'] = dbversion

        if(rule['alert_scope'] == "database"):
            resultQueryTemp['table'] = None
            resultQueryTemp['column'] = None
        elif(rule['alert_scope'] == "table"):
            resultQueryTemp['table'] = rule['table']
            resultQueryTemp['column'] = None
        elif(rule['alert_scope'] == "column"):
            resultQueryTemp['table'] = rule['table']
            resultQueryTemp['column'] = rule['column']

        resultQueryTemp['date'] = strftime("%Y-%m-%d", gmtime())
        resultQueryTemp['alert_level'] = rule['alert_level']
        resultQueryTemp['alert_message'] = rule['alert_message']
        resultQueryTemp['alert_class'] = rule['alert_class']
        resultQueryTemp['alert_scope'] = rule['alert_scope']
        resultQueryTemp['rule_id'] = rule['id_rule']

        resultQuery = resultQuery.append(resultQueryTemp,ignore_index=True)
    remoteDB.close()
    
    return resultQuery


rulesConditionsMap = []
rulesSQLMap = []

alertsSQL = []
alertsConditions = []

# génération des regles avec les identifiants de base de donnée et de version de base :
for database in dbList:
    print("[Info] Generating Alerts on database : "+database['database'])
    try:
        remoteDB = create_engine('mysql://'+database['user']+':'+database['password']+'@'+database['host']+':'+database['port']+'/'+database['database']).connect()
    except:
        print("[Error]["+database['database']+"] Impossible de se connecter à la base : "+database['database'], sys.exc_info())
        remoteDB.close()
        
    dbversion = "0.0"
    try:
        remoteDB.execute("SELECT * FROM version")
        dbversion = cursor.fetchall()
        dbversion = dbversion[0][0]
    except:
        print("[Warning]["+database['database']+"] Erreur : Table Version n'existe pas. defaulting to dbversion 0.0")
    remoteDB.close()

    # Processing des règles : 
    print("[Info] ******* Processing Conditions rules in parrallel on "+str(multiprocessing.cpu_count())+" cores")
    rulesConditionsMap = [(database,dbversion,ruleC,0) for ruleC in ruleConditions.iterrows()]
    with Pool(multiprocessing.cpu_count()) as p:
        try:
            alertsConditions.append(pd.concat(p.map(ruleInterpreterConditionnal,rulesConditionsMap),ignore_index=True))
        except:
            print("[Info] No Alerts Generated by Conditionnal Rules"+str(sys.exc_info()))
    
    print("[Info] ******* Processing SQL rules in parrallel on "+str(multiprocessing.cpu_count())+" cores")
    rulesSQLMap = [(database,dbversion,ruleS,0) for ruleS in ruleSQL.iterrows()]
    with Pool(multiprocessing.cpu_count()) as p:
        try:
            alertsSQL.append(pd.concat(p.map(ruleInterpreterSQL,rulesSQLMap)))
        except:
            print("[Info] No Alerts Generated by SQL Rules"+str(sys.exc_info()))

# # Export des données vers la table d'alertes  :

print("[Info] Put results to database mysql")

try:
    alertsConditions = pd.concat(alertsConditions).reset_index(drop=True).drop_duplicates()    
except:
    print("[Info] pas d'alertes conditionnelles")

try:
    alertsSQL = pd.concat(alertsSQL).reset_index(drop=True).drop_duplicates()
except:
    print("[Info] pas d'alertes SQL")

metricsDB = create_engine('mysql://'+dbUser+':'+dbPassword+'@'+dbHost+':'+dbPort+'/'+dbName).connect()
sql = "DELETE m FROM `hdm_alerts` m where m.date='"+now+"' AND m.alert_class='METRIQUE' OR m.alert_class='METRICCOMPARE' OR m.alert_class='DATA'"
print("[Info] Deleting alerts on this day : "+sql)
try:
    metricsDB.execute(sql)
except:
    print("[Error] ", sys.exc_info())

try:
    alertsConditions.to_sql(con=metricsDB, name='hdm_alerts', if_exists='append',index=False)
except:
    print("[Info] pas d'alertes poussées dans la table d'alertes")

try:
    alertsSQL.to_sql(con=metricsDB, name='hdm_alert', if_exists='append',index=False)
except:
    print("[Info] pas d'alertes poussées dans la table d'alertes")
    
print("[Info] Fin de l'interpretation des regles et générations des alertes, Terminés avec Succès.")

metricsDB.close()

