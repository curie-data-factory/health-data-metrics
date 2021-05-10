##################################################
# Credits Institut Curie 
# Ce Script est un helper

# Armand Leopold
# 10/05/2021
##################################################

import json

from datetime import datetime
from time import strftime

def logMsg(message):
    dt = datetime.now()
    return {"date":strftime("%d/%m/%Y %H:%M:%S"),"message":message}

def loadConf(rootConfFolder,file):
    with open(rootConfFolder+file, 'r') as conf:  
        conf = json.load(conf)
    return conf