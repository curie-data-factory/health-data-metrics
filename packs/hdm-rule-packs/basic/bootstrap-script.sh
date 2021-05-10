#!/bin/bash
# This script will be executed first at the installation of the pack
# Put Here everything that needs to be installed in order for the metric script to run properly

pip install --upgrade pip 
pip install -r requirements.txt
cd process
python rule_generator.py
python rule_basic.py