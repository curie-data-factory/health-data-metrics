#!/bin/bash
# pull & run hdm docker container stack
docker-compose -f docker-compose.yml up -d

# get current user to have same user in airflow container workers.
echo -e "AIRFLOW_UID=$(id -u)\nAIRFLOW_GID=0" > .env
cp .env ~/.env

# pull & run airflow docker container stack
docker-compose -f docker-compose-airflow.yaml up -d

# fetch hdm application composer dependencies
docker exec -ti hdm sh -c "composer install --no-dev --optimize-autoloader --ignore-platform-req=ext-apache"
