#!/usr/bin/python3

import configparser
import MySQLdb
import pandas as pd
import matplotlib.pyplot as plt
import numpy as np
import seaborn as sns

from sklearn import datasets, linear_model

from sklearn.linear_model import LinearRegression
from sklearn.preprocessing import OrdinalEncoder
from sklearn.metrics import mean_squared_error, r2_score

configParser = configparser.ConfigParser(allow_no_value = True)
configParser.read("/etc/atmowiz.conf")
hostname = configParser.get('mariadb', 'hostname', fallback = 'localhost')
database = configParser.get('mariadb', 'database', fallback = 'atmowiz')
username = configParser.get('mariadb', 'username', fallback = 'atmowiz')
password = configParser.get('mariadb', 'password', fallback = 'password')

mydb = MySQLdb.connect(hostname, username, password, database)

query = "SELECT temperature, humidity, mode, targetTemperature, fanLevel, watts, abs(temperature - targetTemperature) as tdiff FROM `sensibo` WHERE airconon = 1 AND watts != 0"
df = pd.read_sql(query, mydb)
print(df)

categorical_cols = ['mode', 'fanLevel']
enc = OrdinalEncoder()
df[categorical_cols] = enc.fit_transform(df[categorical_cols])
print(df.head())
print(df['mode'].unique())
print(df.shape)
X = df[["temperature", "humidity", "mode", "tdiff", "fanLevel"]]
Y = df["watts"]
model = LinearRegression()
model.fit(X, Y)
print(model.predict(X))
print('Intercept: ', model.intercept_)
print('Coefficients array: ', model.coef_)

sns.set_theme(style="ticks")

lmplot = sns.lmplot(data=df, x="tdiff", y="watts")
lmplot.fig.savefig("../web/out.png")
