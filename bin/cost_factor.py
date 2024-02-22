#!/usr/bin/python3

import configparser
import MySQLdb
import pandas as pd
import seaborn as sns
import matplotlib.pyplot as plt
from mpl_toolkits.mplot3d import Axes3D

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
Y = df["watts"]
model = LinearRegression()
model.fit(X, Y)
print(model.predict(X))
print('Intercept: ', model.intercept_)
print('Coefficients array: ', model.coef_)

print(df.describe())
print(df.dtypes)

plt.style.use('default')
plt.style.use('ggplot')

fig, ax = plt.subplots(figsize=(7, 3.5))
ax.plot(X, Y, color='k', label='Regression model')
ax.scatter(X, Y, edgecolor='k', facecolor='grey', alpha=0.7, label='Sample data')
fig.savefig("../web/out.png")

#sns.pairplot(data = df, height = 2)
#fig = sns.lmplot(data=df).get_figure()
#fig.savefig("../web/out.png")
