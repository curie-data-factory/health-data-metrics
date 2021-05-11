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
    'retries': 1,
    'retry_delay': timedelta(minutes=1)
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
                               bash_command='python /home/airflow/airflow/dags/packs/hdm-metric-packs/basic/process/metric_basic.py',
                               dag=dag)

        rpbasicrg = BashOperator(task_id="rp_basic_rule_generator",
                               bash_command='python /home/airflow/airflow/dags/packs/hdm-rule-packs/basic/process/rule_generator.py',
                               dag=dag)

        rpbasicri = BashOperator(task_id="rp_basic_rule_interpreter",
                               bash_command='python /home/airflow/airflow/dags/packs/hdm-rule-packs/basic/process/rule_basic.py',
                               dag=dag)

        ## Enchainement des tâches dans le groupe
        mpbasic >> rpbasicrg >> rpbasicri
    ## Enchainement des tâches du DAG
    start >> hdm >> end
