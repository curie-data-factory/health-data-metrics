##################################################
# Credits Institut Curie 
# Ce Script est un script qui calcul des métriques de qualitées sur des entrepots.

# Armand Leopold
# 10/05/2021
##################################################

#!/usr/bin/env python
# coding: utf-8

# # HDM Metric Pack Basic
# * Author : Armand LEOPOLD
# * Date : 14/01/2021
# 
# > Ce script permet de générer les métriques structurelles de qualitées de données sur les bases de données relationnelles.

import toolshdmscripts as thdms
import multiprocessing
import pandas as pd
import numpy as np
import re
import json
import sys
import time
import hashlib
import base64
import os

from multiprocessing import Pool
from datetime import datetime, timedelta
from time import strftime, gmtime

from pandas.plotting import scatter_matrix
from pandas.api.types import is_string_dtype
from IPython.display import display

from sqlalchemy import create_engine
from sqlalchemy.types import Integer
from sqlalchemy.sql import text

from elasticsearch import Elasticsearch
from elasticsearch import helpers


def checkDataTypes(col):
    data = col.dropna()
    dataType = data.dtype
    try:
        dataType = pd.api.types.infer_dtype(col, skipna=True)
    except:
        a=1
    return dataType

def genDataBulk(dfList,index,typeDoc):
    bulkDataList = []
    for df in dfList:
        df = df.fillna(0)
        cols = list(df.columns)
        df["_index"] = index
        df["_id"] = "dummy"
        df = df.reindex(['_index','_id']+cols, axis=1)
        for row in df.iterrows():
            elem = row[1]
            elem['_id'] = str(row[0])
            bulkDataList.append(elem.to_dict())
            
    return bulkDataList

def procesMetricsTable(elements):
    table = elements[0]
    database = elements[1]
    version = elements[2]

    # connect to DB :
    remoteDB = create_engine('mysql://'+database['user']+':'+database['password']+'@'+database['host']+':'+database['port']+'/'+database['database']).connect()
    metricsDB = create_engine('mysql://'+dbUser+':'+dbPassword+'@'+dbHost+':'+dbPort+'/'+dbName).connect()

    # connect to ES :
    es = Elasticsearch([{'host': esHost, 'port': esPort, 'use_ssl': esSSL, 'verify_certs':False , 'ssl_show_warn':False}])

    ########################################################################################
    ## DATA CRAWLING

    # getting the # of rows :
    query = 'SELECT COUNT(*) FROM `'+database['database']+'`.`'+table+'`';
    nbrRows = pd.read_sql(query,remoteDB).iloc[0][0]
    print("[Info]["+database['database']+"]["+table+"] nbr of rows :"+str(nbrRows))
    
    # Définition de la requete et execution
    if(LIMITENABLED):
        if(nbrRows > LIMIT):
            query = 'SELECT * FROM `'+database['database']+'`.`'+table+'` LIMIT '+str(LIMIT)
        else:
            query = 'SELECT * FROM `'+database['database']+'`.`'+table+'` LIMIT '+str(nbrRows)
    else:
        query = 'SELECT * FROM `'+database['database']+'`.`'+table+'` LIMIT '+str(nbrRows)

    # getting table data

    print("[Info]["+database['database']+"]["+table+"] query : "+query)

    ########################################################################################
    ## DATA ANALYTICS
    # Création du dataframe
    try:
        df = pd.read_sql(query,remoteDB) 
    except:
        print("[Error]["+database['database']+"]["+table+"] Can't fetch table data.", sys.exc_info())
        metricsDB.close()
        remoteDB.close()
        return pd.DataFrame()
        
    # Analytics DF wide
    valueCounts = df.count()
    naValue = nbrRows - valueCounts
    percentNaValues = naValue/nbrRows*100
    
    if(LIMITENABLED):
        if(nbrRows > LIMIT):
            naValue = LIMIT - valueCounts
            percentNaValues = naValue/LIMIT*100

    dfAnalytics = pd.DataFrame(index=df.columns,data=table,columns=['table'])
    dfAnalytics['database'] = database['database']
    dfAnalytics['dbversion'] = version
    dfAnalytics['column'] = df.columns
    dfAnalytics['date'] = strftime("%Y-%m-%d", gmtime())
    dfAnalytics['value_counts'] = valueCounts
    dfAnalytics['na_values'] = naValue
    dfAnalytics['percent_na_values'] = percentNaValues
    dfAnalytics['data_type'] = df.dtypes

    # Analytics Columns wide
    dataTypes = []
    highestFrequency = []
    normHighestFrequency = []
    normSndHighestFrequency = []
    normThrdHighestFrequency = []
    sndHighestFrequency = []
    thrdHighestFrequency = []
    levelsCount = []
    minNumVar = []
    maxNumVar = []
    meanNumVar = []
    medianNumVar = []
    stdNumVar = []

    # Frequency analytics
    for column in df.columns:
        dataType = checkDataTypes(df[column])
        dataTypes.append(dataType)

        if(str(dataType) == "string" or str(dataType) == "integer" or str(dataType) == "floating"):
            valCounts = df[column].value_counts()
            valCountsCount = valCounts.count()
            levelsCount.append(valCountsCount)
            highestFrequency.append(valCounts.iloc[0])
            
            if(valCountsCount > 1):
                try:
                    sndHighestFrequency.append(valCounts.iloc[1])
                except:
                    sndHighestFrequency.append(None)
                    
                if(valCountsCount > 2):
                    try:
                        thrdHighestFrequency.append(valCounts.iloc[2])
                    except:
                        thrdHighestFrequency.append(None)
                else:
                    thrdHighestFrequency.append(None)
            else:
                sndHighestFrequency.append(None)
                thrdHighestFrequency.append(None)
            
            if(str(dataType) == "integer" or str(dataType) == "floating"):
                minNumVar.append(df[column].min())
                maxNumVar.append(df[column].max())
                meanNumVar.append(df[column].mean())
                medianNumVar.append(df[column].median())
                stdNumVar.append(df[column].std())
            else:
                minNumVar.append(None)
                maxNumVar.append(None)
                meanNumVar.append(None)
                medianNumVar.append(None)
                stdNumVar.append(None)
        else:
            levelsCount.append(None)
            highestFrequency.append(None)
            sndHighestFrequency.append(None)
            thrdHighestFrequency.append(None)
            minNumVar.append(None)
            maxNumVar.append(None)
            meanNumVar.append(None)
            medianNumVar.append(None)
            stdNumVar.append(None)
                    
    dfAnalytics['infered_data_type'] = dataTypes
    dfAnalytics['levels_count'] = levelsCount
    dfAnalytics['highest_frequency'] = highestFrequency
    dfAnalytics['sndHighestFrequency'] = sndHighestFrequency
    dfAnalytics['thrdHighestFrequency'] = thrdHighestFrequency
    dfAnalytics['ratio_level_frequency'] = dfAnalytics['highest_frequency']/dfAnalytics['levels_count']
    dfAnalytics['is_key'] = (dfAnalytics['percent_na_values'] == 0)  & (dfAnalytics['highest_frequency'] == 1) & (dfAnalytics['levels_count'] == dfAnalytics['value_counts'])
    dfAnalytics['is_categorical'] = ~dfAnalytics['is_key'] & (((dfAnalytics['levels_count'] < 10) & (dfAnalytics['percent_na_values'] < 50))  | ((dfAnalytics['ratio_level_frequency'] > 100) & (dfAnalytics['levels_count'] < 5000)))
    dfAnalytics['min_num_var'] = minNumVar
    dfAnalytics['max_num_var'] = maxNumVar
    dfAnalytics['mean_num_var'] = meanNumVar
    dfAnalytics['median_num_var'] = medianNumVar
    dfAnalytics['std_num_var'] = stdNumVar
    dfAnalytics['rowKey'] = dfAnalytics['database'] + '-' + dfAnalytics['dbversion'].astype(str) + '-' + dfAnalytics['table'] + '-' + dfAnalytics['column']
    dfAnalytics.index = dfAnalytics['rowKey'].apply(lambda x: hashlib.md5(str.encode(x,'utf-8')).hexdigest())
    dfAnalytics.drop('rowKey',axis=1,inplace=True)
    
    columnListCat = list(dfAnalytics[dfAnalytics['is_categorical'] == True]['column'])
        
    ########################################################################################
    # Adding delta metrics : 
    # Query Metrics J-1 :
    try:
        query = str("SELECT * FROM metric_basic WHERE `date` IN (\'"+yesterday+"\') AND `database` = \'"+database['database']+"\' AND `dbversion` = \'"+version+"\' AND `table` = \'"+table+"\'")

        dfShifted = pd.read_sql(query,metricsDB).drop_duplicates()
        if(len(dfShifted) > 0):
            col_namesMetrics = dfShifted.columns
            dfShifted['rowKey'] = dfShifted['database'] + '-' + dfShifted['dbversion'].astype(str) + '-' + dfShifted['table'] + '-' + dfShifted['column']
            dfShifted.index = dfShifted['rowKey'].apply(lambda x: hashlib.md5(str.encode(x,'utf-8')).hexdigest())
            dfShifted.drop('rowKey',axis=1,inplace=True)
    
            col_namesMetrics = [x for x in col_namesMetrics if x[:6] != 'delta_']
            col_namesMetrics = [x for x in col_namesMetrics if x[:5] != 'evol_']

            for column in col_namesMetrics[5:]:
                try:
                    dfAnalytics['delta_'+column] = dfAnalytics[column] - dfShifted[column]
                except:
                    dfAnalytics['delta_'+column] = None

                try:
                    dfAnalytics['evol_'+column] = dfAnalytics[column]/dfShifted[column]*1.0
                except:
                    dfAnalytics['evol_'+column] = 0
        else:
            print("[Warning]["+database['database']+"]["+table+"] No data available from J-1 metrics. "+database['database']+" AND `dbversion` = "+version+"\' AND `table` = "+table)
    except:
        print("[Warning]["+database['database']+"]["+table+"] Failed to pull J-1 metrics. "+database['database']+" AND `dbversion` = "+version+"\' AND `table` = "+table)

    dfAnalytics['rowKey'] = dfAnalytics['database'] + '-' + dfAnalytics['dbversion'].astype(str) + '-' + dfAnalytics['table'] + '-' + dfAnalytics['column'] + '-' + dfAnalytics['date']
    dfAnalytics.index = dfAnalytics['rowKey'].apply(lambda x: hashlib.md5(str.encode(x,'utf-8')).hexdigest())
    dfAnalytics.drop('rowKey',axis=1,inplace=True)
    
    # Printing categorical variable distribution 
    if(PRINTCATVAR):
        for col in list(dfAnalytics[dfAnalytics['is_categorical'] == True]['column']):
            colName = table + '-' + col +  '-' + strftime("%Y-%m-%d", gmtime())
            if((dfAnalytics['levels_count'][colName] > 1) and (dfAnalytics['levels_count'][colName] < 200)):
                df[col].dropna().value_counts().plot(kind='barh', width=0.9,figsize=(5+dfAnalytics['levels_count'][colName]*0.2,2+dfAnalytics['levels_count'][colName]*0.4))
                plt.title(table+" || "+col)
                plt.savefig(rootResultFolder+"catVarGraphs/"+table+"-"+col+"-"+strftime("%Y-%m-%d", gmtime())+".png")

    ########################################################################################
    ## Running metrics for CATEGORICAL VARIABLES :
    listDataLevels = []
    for col in columnListCat:
        serie = pd.DataFrame(df[col].dropna().value_counts())
        serie['table'] = table
        serie['database'] = database['database']
        serie['dbversion'] = str(version)
        serie['column'] = col
        serie['date'] = strftime("%Y-%m-%d", gmtime())
        serie = serie.reset_index()
        serie = serie.rename(index=str, columns={'index':'value',col:'frequency'})
        serie = serie[['table', 'database', 'dbversion', 'column', 'date', 'value', 'frequency']]
        serie['rowKey'] =  serie['database'] + '-' + serie['dbversion'].astype(str) + '-' + serie['table'] + '-' + serie['column'] +  '-' + yesterday + '-' + serie['value'].astype(str)

        serie.index = serie['rowKey'].apply(lambda x: hashlib.md5(str.encode(x,'utf-8')).hexdigest())
        serie.drop('rowKey',axis=1,inplace=True)
        
        # Calcul des deltas metriques sur les levels
        query = str("SELECT * FROM metric_basic_levels WHERE `date` IN (\'"+yesterday+"\') AND `database` = \'"+database['database']+"\' AND `dbversion` = \'"+version+"\'AND `table` = \'"+table+"\' AND `column` = \'"+col+"\'")    

        # Pulling J-1 metrics
        try:
            dfShifted = pd.read_sql(query,metricsDB).drop_duplicates()
            if(len(dfShifted) > 0):
                col_names_levels = dfShifted.columns
                dfShifted = dfShifted[['table', 'database', 'dbversion', 'column', 'date', 'value', 'frequency']]
                dfShifted['rowKey'] =  dfShifted['database'] + '-' + dfShifted['dbversion'].astype(str) + '-' + dfShifted['table'] + '-' + dfShifted['column']  + '-' + dfShifted['date'] + '-' + dfShifted['value'].astype(str)
                dfShifted.index = dfShifted['rowKey'].apply(lambda x: hashlib.md5(str.encode(x,'utf-8')).hexdigest())
                dfShifted.drop('rowKey',axis=1,inplace=True)
                
                col_names_levels = [x for x in col_names_levels if x[:6] != 'delta_']
                col_names_levels = [x for x in col_names_levels if x[:5] != 'evol_']
                for column in col_names_levels[6:]:
                    try:
                        serie['delta_'+column] = serie[column] - dfShifted[column]
                    except:
                        if(is_string_dtype(serie[column])):
                            serie['delta_'+column] = np.invert(serie[column].equals(dfShifted[column]))
                        else:
                            serie['delta_'+column] = 0

                    try:
                        serie['evol_'+column] = serie[column]/dfShifted[column]*1.0
                    except:
                        serie['evol_'+column] = 0
            else:
                print("[Warning]["+database['database']+"]["+table+"] No data available for J-1 metrics for database "+database['database']+" AND `dbversion` = "+version+"\' AND `table` = "+table+" AND `column` = "+col)
                serie['delta_frequency'] = None
                serie['evol_frequency'] = None
        except:
            print("[Warning]["+database['database']+"]["+table+"] Failed to pull J-1 metrics for database "+database['database']+" AND `dbversion` = "+version+"\' AND `table` = "+table+" AND `column` = "+col)
            serie['delta_frequency'] = None
            serie['evol_frequency'] = None

        serie['rowKey'] =  serie['database'] + '-' + serie['dbversion'].astype(str) + '-' + serie['table'] + '-' + serie['column'] +  '-' + serie['date'] + '-' + serie['value'].astype(str)
        serie.index = serie['rowKey'].apply(lambda x: hashlib.md5(str.encode(x,'utf-8')).hexdigest())
        serie.drop('rowKey',axis=1,inplace=True)
        serie['value'] = serie['value'].astype(str)
        listDataLevels.append(serie)
        
    ########################################################################################
    ## Pushing Categorical variables Data to metrics Databases :
    if(len(listDataLevels) > 0):
        dfDataLevels = pd.concat(listDataLevels)
        dfDataLevels = dfDataLevels[['table', 'database', 'dbversion', 'column', 'date', 'value', 'frequency', 'delta_frequency','evol_frequency']]
        dfDataLevels['value'] = dfDataLevels['value'].astype(str)
        
        # push to MySQL :    
        try:
            if(len(dfDataLevels) > 0):
                dfDataLevels.to_sql(con=metricsDB, name='metric_basic_levels', if_exists='append',index=False)
        except:
            print("[Info]["+database['database']+"]["+table+"] no levels to index (1/2) MySQL")

        # push to ES
        try:
            helpers.bulk(es,genDataBulk(listDataLevels,"metric_basic_levels","levels"))
        except:
            print("[Info]["+database['database']+"]["+table+"] no levels to index (2/2) Elasticsearch")

        if(PRINTMATNUMPLOT):
            dataNumerical = df[list(dfAnalytics[dfAnalytics['infered_data_type'] == ('integer' or 'floating')].index)]
            try:
                scatter_matrix(dataNumerical, alpha=0.2,diagonal='kde',figsize=(8+len(dataNumerical.columns)*2,5+len(dataNumerical.columns)*2))
            except:
                a=1

    ################
    ## Disconnecting
    metricsDB.close()
    remoteDB.close()
    
    return dfAnalytics


# # CONNEXION DATABASE

print("[INFO] Recovering environnment variables.")
try:
    env = os.environ['ENV']
except:
    print("[ERROR] ERROR GETTING JOBTORUN ENVIRONMENT VARIABLE, please check if it is correctly setup !")
    sys.exit()

conf = thdms.loadConf("./","conf.json")
dbUser = conf[env]['user']
dbPassword = conf[env]['password']
dbHost = conf[env]['host']
dbPort = conf[env]['port']
dbName = conf[env]['database']

print("[Info]>>>>>> Initialisation Metric Pack : Basic")
print("[Info] Loading configuration & database list")
metricsDB = None
try:
    metricsDB = create_engine('mysql://'+dbUser+':'+dbPassword+'@'+dbHost+':'+dbPort+'/'+dbName).connect()
except:
    print("[CriticalError]["+dbName+"] Impossible de se connecter à la base de donnée de métriques.")
    raise
    
dbKeys = pd.read_sql('SELECT * FROM hdm_core_table_corr_db_mp WHERE mp_key = "basic"',metricsDB)
mpConf = pd.read_sql('SELECT * FROM hdm_pack_metric_conf WHERE pack_name = "basic" AND pack_version != "master" ORDER BY `pack_version` DESC ',metricsDB)
mpConf = json.loads(base64.b64decode(mpConf.iloc[0]['pack_config']).decode("utf-8"))

dbList = []
for db in dbKeys['db_key'].apply(lambda x: x.split(':')).to_list():
    dbList.append({'database':db[0],'host':db[1],'port':db[2],'user':db[3],'ssl':db[4]})
dbList = pd.DataFrame(dbList).merge(pd.DataFrame(conf['databases']),on=['database','host','port','user','ssl']).to_json(orient="records")
dbList = json.loads(dbList)

LIMIT = mpConf['search_results_limit']
PRINTCATVAR = mpConf['print_cat_var']
PRINTMATNUMPLOT = mpConf['print_mat_num_plot']
LIMITENABLED = mpConf['limit_enabled']
rootResultFolder = mpConf['rootResultFolder']
esHost = mpConf['esHost']
esPort = mpConf['esPort']
esSSL = mpConf['esSSL']

yesterday = datetime.now() - timedelta(days=1)
yesterday = yesterday.strftime("%Y-%m-%d")

# creating metric tables :
file = open('../create-table/create.sql')
for query in file.read().split(";"):
    metricsDB.execute(text(query))
    
time.sleep(1)

for database in dbList:
    try:
        print("[Info]["+database['database']+"] Calculating metrics on database : "+database['database'])
        time.sleep(1)
        remoteDB = create_engine('mysql://'+database['user']+':'+database['password']+'@'+database['host']+':'+database['port']+'/'+database['database']).connect()

        # Récupération des noms des tables
        tables = pd.read_sql('SHOW TABLES',remoteDB)

        version = "0.0"
        try:
            df = pd.read_sql('SELECT * FROM version',remoteDB)
            version = str(df.iloc[0][0])
        except:
            print("[Warning]["+database['database']+"] Can't fetch database version, going default value = 0.0")

        remoteDB.close()

        tables = list(tables.iloc[:,0])
        if('dbversion' in tables):
            tables.remove('dbversion')

        dfGlobal = []
        nbrRows = LIMIT

        print("[Info]["+database['database']+"] Table LIST : "+str(tables))
        print("[Info]["+database['database']+"] Running metrics processing on : "+str(multiprocessing.cpu_count())+" cores.")
        print("[Info]["+database['database']+"] Version database : "+str(version))

        elements = [(table,database,version) for table in tables]
        with Pool(multiprocessing.cpu_count()) as p:
            resMap = p.map(procesMetricsTable,elements)
            result = pd.concat(resMap)
        
        print("[Info]["+database['database']+"] Generating files")
        # export en fichiers plats : 
        try:
            result.to_csv(rootResultFolder+"results-hdm-"+strftime("%Y-%m-%d", gmtime())+".csv",index=False)
        except:
            print("[Warning]["+database['database']+"] Impossible d'exporter les données en fichiers CSV")

        try:
            result.to_excel(rootResultFolder+'results-hdm-'+strftime("%Y-%m-%d", gmtime())+'.xlsx', engine='xlsxwriter',index=False)
        except:
            print("[Warning]["+database['database']+"] Impossible d'exporter les données en fichiers CSV")

        # Publishing metrics to Elasticsearch :
        # ES connexion link : 
        try:
            print("[Info]["+database['database']+"] Put results to database Elasticsearch")
            es = Elasticsearch([{'host': esHost, 'port': esPort, 'use_ssl': esSSL, 'verify_certs':False , 'ssl_show_warn':False}])
            for row in result.iterrows():
                body = row[1].to_json()
                es.index(index="metric_basic",id=row[0],body=body)
        except:
            print("[Error]["+database['database']+"] Impossible d'exporter les données dans la base Elasticsearch", sys.exc_info())

        # Publishing metrics to MySQL : 
        print("[Info]["+database['database']+"] Put results to database MySQL")
        try:
            metricsDB = create_engine('mysql://'+dbUser+':'+dbPassword+'@'+dbHost+':'+dbPort+'/'+dbName).connect()
            result.to_sql(con=metricsDB, name='metric_basic', if_exists='append',index=False)
            metricsDB.close()
        except:
            print("[Error]["+database['database']+"] Impossible d'exporter les données dans la base de donnée de métriques.", sys.exc_info())
            metricsDB.close()
        
    except:
        print("[Error]["+database['database']+"] Impossible de se connecter à la base de donnée.", sys.exc_info())
        metricsDB.close()
        
print("[Info] Fin du calcul des métriques. Terminés avec Succès.")
print("[Info] Dé-duplication des métriques.")

# Metriques
metricsDB = create_engine('mysql://'+dbUser+':'+dbPassword+'@'+dbHost+':'+dbPort+'/'+dbName).connect()
df = pd.read_sql('SELECT * FROM metric_basic',metricsDB)
df = df.drop_duplicates()
metricsDB.execute(text("TRUNCATE `metric_basic`;"))
df.to_sql(con=metricsDB, name='metric_basic', if_exists='append',index=False)
# levels
df = pd.read_sql('SELECT * FROM metric_basic_levels',metricsDB)
df = df.drop_duplicates()
metricsDB.execute(text("TRUNCATE `metric_basic_levels`;"))
df.to_sql(con=metricsDB, name='metric_basic_levels', if_exists='append',index=False)
metricsDB.close()
print("[Info] Fin process Metric Pack Basic.")

