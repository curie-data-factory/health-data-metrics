##=========================================================================================##
##                                      CONFIGURATION

from airflow.operators.dummy_operator import DummyOperator
from airflow.operators.bash_operator import BashOperator
from airflow.utils.task_group import TaskGroup
from airflow.models import Variable
from airflow import DAG

from datetime import datetime, timedelta

env = Variable.get("env")

default_args = {
    'owner': 'airflow',
    'email_on_failure': False,
    'depends_on_past': False,
    'email_on_retry': False,
    'retries': 0,
}

##==============================##

## Définition du DAG
with DAG('hdm-pipeline',
    tags=['hdm'],
    schedule_interval="0 2 * * *",
    start_date=datetime(2021, 1, 1),
    default_args=default_args,
    catchup=False,
    max_active_runs=1,
    description='Pipeline d\'execution des packs HDM'
) as dag:
##=========================================================================================##

## Définition des tâches
    start = DummyOperator(task_id='start', dag=dag)
    end   = DummyOperator(task_id='end', dag=dag)

    with TaskGroup("hdm-"+env) as hdm:
        mpbasic = BashOperator(task_id="mp_basic",
                               bash_command='cd /opt/airflow/dags/packs/hdm-metric-packs/basic && bash bootstrap-script.sh ',
                               dag=dag)

        rpbasic = BashOperator(task_id="rp_basic",
                               bash_command='cd /opt/airflow/dags/packs/hdm-rule-packs/basic && bash bootstrap-script.sh ',
                               dag=dag)

        ## Enchainement des tâches dans le groupe
        mpbasic >> rpbasic
    ## Enchainement des tâches du DAG
    start >> hdm >> end
