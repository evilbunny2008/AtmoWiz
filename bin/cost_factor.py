#!/usr/bin/python3

import configparser

import matplotlib.pyplot as plt

import numpy as np
import pandas as pd

import plotly.express as px
import plotly.graph_objs as go
import plotly.io as pio

import sys

from sklearn.model_selection import train_test_split
from sklearn.linear_model import LinearRegression
from sklearn.metrics import mean_squared_error

from sqlalchemy import create_engine

import warnings

warnings.filterwarnings("ignore", message="X has feature names, but LinearRegression was fitted without feature names", category=UserWarning)

configParser = configparser.ConfigParser(allow_no_value = True)
configParser.read("/etc/atmowiz.conf")
hostname = configParser.get('mariadb', 'hostname', fallback = 'localhost')
database = configParser.get('mariadb', 'database', fallback = 'atmowiz')
username = configParser.get('mariadb', 'username', fallback = 'atmowiz')
password = configParser.get('mariadb', 'password', fallback = 'password')

db_uri = "mysql://%s:%s@%s/%s" % (username, password, hostname, database)
engine = create_engine(db_uri)

query = "SELECT targetTemperature, (temperature - targetTemperature) as tempDiff, watts FROM `sensibo` WHERE airconon = 1 AND watts != 0 AND mode='cool'"
df = pd.read_sql(query, engine)

X = df[["targetTemperature", "tempDiff"]]
y = df["watts"]

#X.columns = ['targetTemperature', 'tempDiff']

X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.2, random_state=42)
model = LinearRegression()
#model.feature_names_in_ = None
model.fit(X_train, y_train)
model.feature_names_in_ = None

y_pred = model.predict(X_test)

mse = mean_squared_error(y_test, y_pred)
print("Mean Squared Error:", mse)

new_data_point = [[float(sys.argv[1]), float(sys.argv[2])]]
predicted_watts = model.predict(new_data_point)
print("Predicted Watts: %f" % (predicted_watts, ))


fig = plt.figure()
ax = fig.add_subplot(111, projection='3d')
ax.scatter(X.iloc[:, 0], X.iloc[:, 1], y)
ax.set_xlabel('Target Temperature')
ax.set_ylabel('Temperature Difference')
ax.set_zlabel('Watts')

x_min, x_max = X.iloc[:, 0].min(), X.iloc[:, 0].max()
y_min, y_max = X.iloc[:, 1].min(), X.iloc[:, 1].max()
xx, yy = np.meshgrid(np.linspace(x_min, x_max, 10), np.linspace(y_min, y_max, 10))
zz = np.array([model.predict([[a, b]])[0] for a, b in zip(xx.ravel(), yy.ravel())])
zz = zz.reshape(xx.shape)
ax.plot_surface(xx, yy, zz, alpha=0.5)

fig.savefig('/root/AtmoWiz/web/out.png')

fig = px.scatter_3d(df, x='targetTemperature', y='tempDiff', z='watts', size_max=12, color='watts', opacity=0.8)
fig.update_layout(margin=dict(l=0, r=0, b=0, t=0))
fig.update_layout(scene=dict(xaxis=dict(title='Target Temperature'), yaxis=dict(title='Temperature Difference'), zaxis=dict(title='Watts')))
fig.update_layout(title=dict(text="Target Temperature Vs Temperature Difference Vs Watts", font=dict(size=18), xanchor='left', yanchor='top'))
pio.write_html(fig, '/root/AtmoWiz/web/test.html')
