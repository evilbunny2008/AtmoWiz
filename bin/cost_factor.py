#!/usr/bin/python3

import configparser
import MySQLdb
#import matplotlib.pyplot as plt
import numpy as np
import pandas as pd
import plotly.graph_objs as go
import plotly.io as pio
from sklearn.linear_model import LinearRegression
import statsmodels.formula.api as smf

configParser = configparser.ConfigParser(allow_no_value = True)
configParser.read("/etc/atmowiz.conf")
hostname = configParser.get('mariadb', 'hostname', fallback = 'localhost')
database = configParser.get('mariadb', 'database', fallback = 'atmowiz')
username = configParser.get('mariadb', 'username', fallback = 'atmowiz')
password = configParser.get('mariadb', 'password', fallback = 'password')

mydb = MySQLdb.connect(hostname, username, password, database)

query = "SELECT watts, targetTemperature, (temperature - targetTemperature) as tempDiff FROM `sensibo` WHERE airconon = 1 AND watts != 0 AND mode='cool'"
df = pd.read_sql(query, mydb)

df.reset_index(drop=True, inplace=True)

X = df[["targetTemperature", "tempDiff"]]
Y = df["watts"]

model = LinearRegression()
model.fit(X, Y)
print(model.predict(X))
print('Intercept: ', model.intercept_)
print('Coefficients array: ', model.coef_)

fig = go.Figure()
fig.add_trace(go.Scatter3d(x=df['targetTemperature'], y=df['tempDiff'], z=df['watts'], mode='markers', marker=dict(size=12, color=df['watts'], colorscale='Viridis', opacity=0.8), name='Target Temperature Vs Temperature Difference Vs Watts'))
fig.update_layout(scene=dict(xaxis=dict(title='Target Temperature'), yaxis=dict(title='Temperature Difference'), zaxis=dict(title='Watts')), margin=dict(l=0, r=0, b=0, t=0))
pio.write_html(fig, '/root/AtmoWiz/web/test.html')
