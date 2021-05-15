# HDM Full Installation

This Tutorial guides you on How to install hdm full stack on your local machine.

**⚠️	 REQUIREMENTS ⚠️**

Software :

* Linux/MacOS 64bit or Windows 10 64bit **with WSL2**
* Docker
* Docker-compose
* Python 3.9+

Hardware :
**Minimal**🤓
>
	- CPU     :   4 Cores
	- RAM     :  16 Go
	- Storage :  10 Go

Hardware :
**Recommended**  😎
>
	- CPU     :  12 Cores
	- RAM     :  32 Go
	- Storage :  30 Go

In this Tutorial, we are going to install HDM in Full Stack mode. That means that we are going to :

> ⚠️ Before we start : **all comandlines have to be executed at the root folder of the git source repository**

[1. **Launch** all the **software stack** :](#1-launch-all-the-software-stack)

	- Airflow
	- Nexus
	- Elasticsearch
	- Kibana
	- MySQL
	- HDM frontend

[2. **Ingest some dataset** to our **MySQL** database, simulating a dataware that we want to scan.](#2-ingest-a-dataset)

[3. **Register our Metric Packs & Rule Packs** on **Nexus** and configure them into **HDM**.](#3-metric-pack--rule-pack-registration)

[4. **Add an Airflow DAG** to run them.]()

[5. **Run** our HDM Airflow DAG and **compute metrics/alerts**.]()

[6. Finally, add our **Kibana Dashboards** and use the Explorer and **Alert Dashboard**.]()

___

## 1. Launch all the software stack

To run the stack we need :

* `Docker` See [Get Docker](https://docs.docker.com/get-docker/)
* `Docker Compose` See [Get Docker Compose](https://docs.docker.com/compose/install/)

We are going to run the `docker-compose` files :
- `docker-compose.yml` (HDM primary Stack)
- `docker-compose-airflow.yml` (Airflow Stack) More INFO [Here](https://airflow.apache.org/docs/apache-airflow/stable/start/docker.html#)

```bash
docker-compose -f docker-compose.yml up -d
docker-compose -f docker-compose-airflow.yaml up -d
```

When the installation is complete, you should check the different application endpoints :

* [http://localhost:80](http://localhost:80) HDM
* [http://localhost:8081](http://localhost:8081) Nexus
* [http://localhost:5601](http://localhost:5601) Kibana
* [http://localhost:9200](http://localhost:9200) Elasticsearch
* [http://localhost:8080](http://localhost:8080) Airflow (User: airflow | Password: airflow)
* [tcp://127.0.0.1:3306](tcp://127.0.0.1:3306) MySQL Endpoint
  > **host**: 127.0.0.1 | **Port**: 3306 | **User**: hdm | **Password**: password | **Database**: dbhdm

  or:

  > **host**: 127.0.0.1 | **Port**: 3306 | **User**: root | **Password**: rootpassword

When you have all done. Let's go to the next step.

___

## 2. Ingest a Dataset

We are using the [Kaggle API](https://github.com/Kaggle/kaggle-api) to download our example datasets.

### 2.1 Kaggle cli installation
In a Client with python 3 on it, run :

```bash
pip install kaggle --upgrade
```

### 2.2 Kaggle cli login
Type `kaggle` to check if kaggle is installed.

Run the commandline if needed :

>
	Warning: Your Kaggle API key is readable by other users on this system! To fix this, you can run 'chmod 600 /home/<User>/.kaggle/kaggle.json'

Test with : ` kaggle datasets list`

### 2.3 Download Heart Attack Dataset

```bash
kaggle datasets download rashikrahmanpritom/heart-attack-analysis-prediction-dataset -p ./datasets --unzip
```

### 2.4 Run Python Ingestion Script

We are now going to ingest our Kaggle dataset to our MySQL database.

Requirements installation

* MySQL Client Driver
```bash
sudo apt-get update && sudo apt-get install -y libmysqlclient-dev
```

* Python Package dependencies
```bash
python -m pip install -r tutorials/full-installation/requirements.txt
```
* Ingestion Script :

```bash
python ./tutorials/full-installation/ingest-data.py
```

Data is ingested ! Check it out on **mysql://127.0.0.1:3306/heart-attack**

___

## 3. Metric Pack & Rule Pack Registration

In this step, we are going to register the metric pack and rule pack that are used for HDM.

### 3.1 Setup Nexus

1. In order to setup Nexus we need to get the password :

```bash
docker exec -ti nexus sh -c "cat /nexus-data/admin.password"
```

This will give you the **admin** password for [Nexus](http://localhost:8081/)

2. Go to [http://localhost:8081/](http://localhost:8081/) and login as "admin" + [Password from previous command]

3. Do the setup by changing the default admin password and then checkout the **[x][Enable Anonymous Access]**

### 3.2 Run Nexus Import Script

⚠️ Change the **PASSWORDNEXUS** to your Nexus **admin** password value.

```bash
# Nexus User Credentials
export PASSWORDNEXUS="123qwe"
export USERNEXUS="admin"

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
rm ./packs/hdm-rule-packs/basic/basic_$verScript.zip

# Zip & Upload
verScript=`egrep -o "([0-9]{1,}\.)+[0-9]{1,}" ./packs/hdm-metric-packs/basic/properties.json`
cd ./packs/hdm-metric-packs/basic/ && zip -r ../basic_$verScript.zip . && cd ../../../

# create
export JOBTOUPLOAD="http://localhost:8081/repository/hdm-snapshots/hdm/metricpacks/basic/$verScript/basic_$verScript.zip"
export PATHARTIFACT="./packs/hdm-metric-packs/"

python ./tutorials/full-installation/upload-to-nexus-*.py
rm ./packs/hdm-metric-packs/basic/basic_$verScript.zip
```

This script will create a Maven2 Repository on Nexus named : **hdm-snapshots**

The script then packages into zip files the metric pack & rule pack basic and upload them into the maven repository.

Check if it's ok : [http://localhost:8081/#browse/browse:hdm-snapshots](http://localhost:8081/#browse/browse:hdm-snapshots)

___

### 3.3. Metric Pack & Rule Pack Configuration

#### 3.3.1 Enabling Metric Pack / Rule Pack

We Then have to activate our mp & rp on :

* http://localhost/admin.php?tab=metricpacks
* http://localhost/admin.php?tab=rulepacks

![metric pack configuration page](metrickpacks_conf_1.png)
![Rule pack configuration page](rulepacks_conf_1.png)

#### 3.3.2 Edit Configuration Metric Pack / Rule Pack

![metric pack configuration page](metrickpacks_conf_2.png)

We edit our metric pack configuration to add :

```json
{
  "print_cat_var": false,
  "print_mat_num_plot": false,
  "limit_enabled": true,
  "search_results_limit": 2000000,
  "rootResultFolder": "../results/",
  "esHost": "127.0.0.1",
  "esPort": 9200,
  "esSSL": false
}
```
![metric pack configuration page](metrickpacks_conf_3.png)

And same for our rule pack with :

```text
dev
```

___

## 4. Airflow DAG

Login to Airflow [http://localhost:8080/home](http://localhost:8080/home) with (login : airflow | password: airflow)

### 4.1 Add env variables :

In your previous terminal run these commands :

```bash
# Airflow User Credentials
export PASSWORDAIRFLOW="airflow"
export USERAIRFLOW="airflow"

# Add variables
curl -u $USERAIRFLOW:$PASSWORDAIRFLOW -X POST "http://localhost:8080/api/v1/variables" -H  "accept: application/json" -H  "Content-Type: application/json" -d "{\"key\":\"env\",\"value\":\"dev\"}"
```

They will create all the airflow environment variables in order for our DAG to run.

### 4.2 Enable the dag :

Toggle the dag :

![airflow-dag.png](airflow-dag.png)
___

## 5. Run the dag

Copy python files :

```bash
mkdir dags/packs
cp -r packs/* dags/packs/
```

Trigger the dag :

![dag_trigger.png](dag_trigger.png)

You can check it's execution :

[http://localhost:8080/graph?dag_id=hdm-pipeline](http://localhost:8080/graph?dag_id=hdm-pipeline)

___

## 6. HDM Visualisation

### 6.1 Import kibana dashboard

Run the following comandline to import all the dashboards from the Basic Metric Pack into kibana.

```bash
curl -X POST http://localhost:5601/api/saved_objects/_import?overwrite=true -H "kbn-xsrf: true" --form file=@packs/hdm-metric-packs/basic/kibana-dashboard/export.ndjson
```

### 6.2 Explorer Dashboard

You can explore the different metric pack dashboards from the Explorer.

[http://localhost/explorer/wrapper.php](http://localhost/explorer/wrapper.php)

### 6.3 Alert Dashboard

You can check all the alerts emmitted by the different rule packs from the Alert dashboard :

[http://localhost/alert/alert.php](http://localhost/alert/alert.php)
