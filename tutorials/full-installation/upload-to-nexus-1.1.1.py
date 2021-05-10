##################################################
# Credits Institut Curie 
# Ce Script upload les ressources  vers Nexus

# Armand Leopold
# 10/05/2021
##################################################
scriptVersion = "1.1.1bis"

import os
import re
import sys
import datetime
import requests
import tempfile
import urllib3
from datetime import datetime
from time import strftime
urllib3.disable_warnings()

def logMsg(message):
    dt = datetime.now()
    return {"date":strftime("%d/%m/%Y %H:%M:%S"),"message":message}


if __name__ == "__main__":

    print("*********************************************************")
    print("***                  Upload To Nexus                  ***")
    print("*********************************************************")
    logs = []   
    print("[INFO] Start Script at  "+datetime.now().strftime("%d-%m-%Y %H:%M:%S")+" : ")
    logs.append(logMsg("[INFO] Start Script at  "+datetime.now().strftime("%d-%m-%Y %H:%M:%S")))
    print("[INFO] Version of script :"+scriptVersion)
    logs.append(logMsg("[INFO] Version of script :"+scriptVersion))
    
    userNexus = ""
    passwordNexus = ""
    jobtoupload = ""
    rootNexusUrl=""
    path = ""

    print("[INFO] Recovering environnment variables.")
    try:
        jobtoupload = os.environ['JOBTOUPLOAD']
        userNexus = os.environ['USERNEXUS']
        passwordNexus = os.environ['PASSWORDNEXUS']
        path = os.environ['PATHARTIFACT']
        rootNexusUrl = os.environ['ROOTNEXUSURL']
    except:
        print("[ERROR] ERROR GETTING JOBTORUN ENVIRONMENT VARIABLE, please check if it is correctly setup !")
        sys.exit()

    fileName = jobtoupload.split('/')[-1]
    artifactId = re.split('([_-]|v)([\dmaster.*]+)([.])', fileName)[0]
    version = re.split('([_-]|v)([\dmaster.*]+)([.])', fileName)[-3]
    folder = "/".join(jobtoupload.split('/')[5:-3])
    extension = jobtoupload.split(".")[-1]
    repo = jobtoupload.split("/")[4]

    if((folder == repo) | (folder == "")):
        folder = artifactId

    print("folder : ",folder,"\nfileName : ",fileName,"\nrepo :",repo,"\nartifactId :",artifactId,"\nversion : ",version,"\nextension : ",extension)

    file = {"file":fileName,"artifactId":artifactId,"version":version,"path":path,"repo":repo,"folder":folder,"extension":extension}

    file_obj = open(file['path']+file['file'], 'r') 
    nexus_url = rootNexusUrl+file['repo']
    print("[INFO] "+str(file))
    try:
        requests.post(
        nexus_url,
        auth = (userNexus,passwordNexus),
        data = {"maven2.groupId":file['folder'],"maven2.artifactId":file['artifactId'],"maven2.version":file['version'],"maven2.asset1.extension":file['extension']},
        files = {'maven2.asset1': open(file_obj.name,'rb')} ,
        headers = {"accept":"application/json"},
        verify=False)
    except Exception as e:
        print("[ERROR] cannot upload File"+str(e))

    file_obj.close()
    
    print("*********************************************************")
    print("***                        FIN                        ***")
    print("*********************************************************")

