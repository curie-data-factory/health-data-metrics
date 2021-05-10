##################################################
# Credits Institut Curie 
# Ce Script upload les données Kaggle vers une base MySQL locale

# Armand Leopold
# 10/05/2021
##################################################

import pandas as pd
import sqlalchemy as db
import helper

path = "tutorials/full-installation/"

# Creating Database if not exists
helper.runSQL(path+"create-db.sql",'db-root',path+"conf-db.json")

# Loading Data
heartDf = pd.read_csv('./datasets/heart.csv')
o2SaturationDf = pd.read_csv('./datasets/o2Saturation.csv')

# Ingest Data
engine = helper.dbEngine('hdm-data',path+"conf-db.json")
heartDf.to_sql('heart', con=engine, if_exists='append', index=False)
o2SaturationDf.to_sql('o2_saturation', con=engine, if_exists='append', index=False)
