# HDM Full Installation

**⚠️	 REQUIREMENTS ⚠️**

**Minimal**🤓
>
	- CPU     :   4 Cores
	- RAM     :  16 Go
	- Storage :  10 Go

**Recommended**  😎
>
	- CPU     :  12 Cores
	- RAM     :  32 Go
	- Storage :  30 Go

* Python 3.9+

In this Tutorial, we are going to install HDM in Full Stack mode. That means that we are going to :

1. **Launch** all the **software stack** :

	- Airflow
	- Nexus
	- Elasticsearch
	- Kibana
	- MySQL
	- HDM frontend

2. Then, we are going to **ingest some dataset** to our **MySQL** database, simulating a dataware that we want to scan.

3. We are going to **register our Metric Packs & Rule Packs** on **Nexus** and configure them into **HDM**.

4. We are going to **add an Airflow DAG** to run them.

5. We are going to **run** our HDM Airflow DAG and **compute metrics/alerts**.

6. Finally we are going to add our **Kibana Dashboards** and use the Explorer and **Alert Dashboard**.

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

* [http://localhost:80](http://localhost:80) Hdm front
* [http://localhost:8081](http://localhost:8081) Nexus
* [http://localhost:5601](http://localhost:5601) Kibana
* [http://localhost:9200](http://localhost:9200) Elasticsearch
* [tcp://127.0.0.1:3306](tcp://127.0.0.1:3306) MySQL Endpoint
  > (host: 127.0.0.1 | Port: 3306 | User: hdm | Password: password | Database: dbhdm)
* [http://localhost:5555](http://localhost:5555) Flower (Celery Scheduler frontend)
* [http://localhost:8080](http://localhost:8080) Airflow (User: airflow | Password: airflow)

When you have all done. Let's go to the next step.

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
