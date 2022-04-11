#!/bin/sh

sudo apt-get update && sudo apt-get install -y libmysqlclient-dev python3-dev default-libmysqlclient-dev build-essential 
python -m pip install -r tutorials/full-installation/requirements.txt
python tutorials/full-installation/ingest-data.py