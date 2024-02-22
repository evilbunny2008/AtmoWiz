#!/usr/bin/python3

import configparser
import MySQLdb
import pandas as pd

from sklearn.preprocessing import LabelEncoder
from sklearn.preprocessing import OneHotEncoder

configParser = configparser.ConfigParser(allow_no_value = True)
configParser.read("/etc/atmowiz.conf")
hostname = configParser.get('mariadb', 'hostname', fallback = 'localhost')
database = configParser.get('mariadb', 'database', fallback = 'atmowiz')
username = configParser.get('mariadb', 'username', fallback = 'atmowiz')
password = configParser.get('mariadb', 'password', fallback = 'password')

mydb = MySQLdb.connect(hostname, username, password, database)

query = "SELECT temperature, humidity, mode, targetTemperature, fanLevel, watts FROM `sensibo` WHERE airconon = 1 AND watts != 0"
result_df = pd.read_sql(query, mydb)
print(result_df)

categorical_cols = ['mode', 'fanLevel']
