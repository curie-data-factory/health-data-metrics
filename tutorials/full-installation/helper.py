##################################################
# Credits Institut Curie 
# Ce Script est un Helper

# Armand Leopold
# 10/05/2021
##################################################

import json as js
import sqlalchemy as db

# Conf Loading
def loadConf(confPath):
    conf = js.load(open(confPath,'r'))
    return conf

# Return Engine
def dbEngine(dbCreds,confPath):
    conf = loadConf(confPath)
    # Creating MySQL Line
    dbCredsLine = "mysql://"+conf[dbCreds]['user']+":"+conf[dbCreds]['password']+"@"+conf[dbCreds]['host']+":"+conf[dbCreds]['port']

    if('dbname' in conf[dbCreds]):
        dbCredsLine += "/"+conf[dbCreds]['dbname']

    engine = db.create_engine(dbCredsLine)
    return engine

# SQL Query runner
def runSQL(queryFile,dbCreds,confPath):

    engine = dbEngine(dbCreds,confPath)

    # Openning SQL File
    f = open(queryFile, "r")
    queries = f.read().split(';')
    for query in queries:
        print("---- Doing : "+query)
        connection = engine.connect()
        connection.execute(query)
        try:
            result = connection.fetchall()
        except:
            print("---- Query returned no result")
