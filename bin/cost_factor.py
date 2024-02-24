#!/usr/bin/python3

import configparser
import MySQLdb

import matplotlib.pyplot as plt

import numpy as np
import pandas as pd

import plotly.express as px
import plotly.graph_objs as go
import plotly.io as pio

from sklearn.preprocessing import StandardScaler
from sklearn.svm import SVR

#from sklearn.linear_model import LinearRegression
#import statsmodels.formula.api as smf

import sys


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

scaler = StandardScaler()
X_scaled = scaler.fit_transform(X)
model = SVR(kernel='linear')
model.fit(X, Y)


fig = plt.figure()
ax = fig.add_subplot(111, projection='3d')
ax.scatter(X.iloc[:, 0], X.iloc[:, 1], Y)  # Accessing DataFrame columns correctly
ax.set_xlabel('Target Temperature')
ax.set_ylabel('Temperature Difference')
ax.set_zlabel('Watts')

x_min, x_max = X.iloc[:, 0].min(), X.iloc[:, 0].max()
y_min, y_max = X.iloc[:, 1].min(), X.iloc[:, 1].max()
xx, yy = np.meshgrid(np.linspace(x_min, x_max, 10), np.linspace(y_min, y_max, 10))
zz = np.array([model.predict([[a, b]])[0] for a, b in zip(xx.ravel(), yy.ravel())])
zz = zz.reshape(xx.shape)
ax.plot_surface(xx, yy, zz, alpha=0.5)

intercept = model.intercept_[0]
coef_1, coef_2 = model.coef_[0]

def predict_watts(target_temp, temp_diff):
    return intercept + coef_1 * target_temp + coef_2 * temp_diff

print("Intercept:", intercept)
print("Coefficient for Target Temperature:", coef_1)
print("Coefficient for Temperature Difference:", coef_2)

target_temp = float(sys.argv[1])
temp_diff = float(sys.argv[2])
predicted_watts = predict_watts(target_temp, temp_diff)
print("Predicted Watts:", predicted_watts)

#plt.savefig('/root/AtmoWiz/web/out.png')

#print(model.predict(X))

fig = px.scatter_3d(df, x='targetTemperature', y='tempDiff', z='watts', size_max=12, color='watts', opacity=0.8)
fig.update_layout(margin=dict(l=0, r=0, b=0, t=0))
fig.update_layout(scene=dict(xaxis=dict(title='Target Temperature'), yaxis=dict(title='Temperature Difference'), zaxis=dict(title='Watts')))
fig.update_layout(title=dict(text="Target Temperature Vs Temperature Difference Vs Watts", font=dict(size=18), xanchor='left', yanchor='top'))
pio.write_html(fig, '/root/AtmoWiz/web/test.html')
