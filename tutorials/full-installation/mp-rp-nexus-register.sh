#!/bin/bash
# Prerequis :
# ZIP
sudo apt update && sudo apt install -y zip

# Create repo
curl -u $USERNEXUS:$PASSWORDNEXUS -X POST "http://localhost:8081/service/rest/v1/repositories/maven/hosted" -H "accept: application/json" -H "Content-Type: application/json" -d "{ \"name\": \"hdm-snapshots\", \"online\": true, \"storage\": { \"blobStoreName\": \"default\", \"strictContentTypeValidation\": false, \"writePolicy\": \"allow\" }, \"cleanup\": { \"policyNames\": [ \"string\" ] }, \"component\": { \"proprietaryComponents\": true }, \"maven\": { \"versionPolicy\": \"MIXED\", \"layoutPolicy\": \"PERMISSIVE\" }}"

# Zip & Upload
verScript=`egrep -o "([0-9]{1,}\.)+[0-9]{1,}" ./packs/hdm-rule-packs/basic/properties.json`
cd ./packs/hdm-rule-packs/basic/ && zip -r ../basic_$verScript.zip . && cd ../../../

# create
export JOBTOUPLOAD="http://localhost:8081/repository/hdm-snapshots/hdm/rulepacks/basic/$verScript/basic_$verScript.zip"
export PATHARTIFACT="./packs/hdm-rule-packs/"
export ROOTNEXUSURL="http://localhost:8081/service/rest/v1/components?repository="

python ./tutorials/full-installation/upload-to-nexus-*.py
rm ./packs/hdm-rule-packs/basic_$verScript.zip

# Zip & Upload
verScript=`egrep -o "([0-9]{1,}\.)+[0-9]{1,}" ./packs/hdm-metric-packs/basic/properties.json`
cd ./packs/hdm-metric-packs/basic/ && zip -r ../basic_$verScript.zip . && cd ../../../

# create
export JOBTOUPLOAD="http://localhost:8081/repository/hdm-snapshots/hdm/metricpacks/basic/$verScript/basic_$verScript.zip"
export PATHARTIFACT="./packs/hdm-metric-packs/"

python ./tutorials/full-installation/upload-to-nexus-*.py
rm ./packs/hdm-metric-packs/basic_$verScript.zip
