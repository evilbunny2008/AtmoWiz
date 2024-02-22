#!/usr/bin/python3

import configparser
import MySQLdb
import pandas as pd

from sklearn.preprocessing import LabelEncoder
from sklearn.preprocessing import OneHotEncoder
from sklearn.preprocessing import OrdinalEncoder
from sklearn.linear_model import LinearRegression

configParser = configparser.ConfigParser(allow_no_value = True)
configParser.read("/etc/atmowiz.conf")
hostname = configParser.get('mariadb', 'hostname', fallback = 'localhost')
database = configParser.get('mariadb', 'database', fallback = 'atmowiz')
username = configParser.get('mariadb', 'username', fallback = 'atmowiz')
password = configParser.get('mariadb', 'password', fallback = 'password')

mydb = MySQLdb.connect(hostname, username, password, database)

query = "SELECT temperature, humidity, mode, targetTemperature, fanLevel, watts FROM `sensibo` WHERE airconon = 1 AND watts != 0"
df = pd.read_sql(query, mydb)
print(df)

categorical_cols = ['mode', 'fanLevel']
enc = OrdinalEncoder()
df[categorical_cols] = enc.fit_transform(df[categorical_cols])
print(df.head())
print(df['mode'].unique())
print(df.shape)
X = df[["temperature", "humidity", "mode", "targetTemperature", "fanLevel"]]
y = df["watts"]
model = LinearRegression()
model.fit(X, y)
print(model.predict(X))
