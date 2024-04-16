#!/usr/bin/python3

import configparser

import matplotlib.pyplot as plt

import numpy as np
import pandas as pd

import plotly.express as px
import plotly.graph_objs as go
import plotly.io as pio

import sys

from sklearn.linear_model import LinearRegression, Lasso, Ridge, HuberRegressor

from sqlalchemy import create_engine

import warnings

def predict_watts(target_temp, temp_diff):
    pred_watts = intercept + coef_target_temp * target_temp + coef_temp_diff * temp_diff
    print ("intercept + coef_target_temp * target_temp + coef_temp_diff * temp_diff")
    print (f"{pred_watts} = {intercept} + {coef_target_temp} * {target_temp} + {coef_temp_diff} * {temp_diff}")
    return pred_watts


regressors = {}
regressors['LinearRegression'] = LinearRegression()
regressors['Lasso'] = Lasso()
regressors['Ridge'] = Ridge()
regressors['HuberRegressor'] = HuberRegressor()

for k in regressors:
    warnings.filterwarnings("ignore", message=f"X has feature names, but {k} was fitted without feature names", category=UserWarning)

configParser = configparser.ConfigParser(allow_no_value = True)
configParser.read("/etc/atmowiz.conf")
hostname = configParser.get('mariadb', 'hostname', fallback = 'localhost')
database = configParser.get('mariadb', 'database', fallback = 'atmowiz')
username = configParser.get('mariadb', 'username', fallback = 'atmowiz')
password = configParser.get('mariadb', 'password', fallback = 'password')

db_uri = "mysql://%s:%s@%s/%s" % (username, password, hostname, database)
engine = create_engine(db_uri)

query = "SELECT temperature, (temperature - targetTemperature) as tempDiff, actualwatts FROM `sensibo` WHERE airconon = 1 AND actualwatts != 0 AND (mode='cool' OR mode='dry')"
cool_df = pd.read_sql(query, engine)
#cool_df.to_csv('/root/AtmoWiz/web/data.csv')

X = cool_df[["temperature", "tempDiff"]]
y = cool_df["actualwatts"]

for k in regressors:
    print (f"Trying {k}...")

    model = regressors[k]
    model.fit(X, y)
    model.feature_names_in_ = None

    y_pred = model.predict(X)

    new_data_point = [[float(sys.argv[1]), float(sys.argv[2])]]
    predicted_watts = model.predict(new_data_point)
    print("Predicted Watts: %f" % (predicted_watts, ))

    intercept = model.intercept_
    coef_target_temp = model.coef_[0]
    coef_temp_diff = model.coef_[1]

    predicted_watts = predict_watts(float(sys.argv[1]), float(sys.argv[2]))
    print("Cool Predicted Watts:", predicted_watts)

    break

query = "SELECT temperature, (temperature - targetTemperature) as tempDiff, actualwatts FROM `sensibo` WHERE airconon = 1 AND actualwatts != 0 AND mode='heat'"
heat_df = pd.read_sql(query, engine)

X = heat_df[["temperature", "tempDiff"]]
y = heat_df["actualwatts"]

for k in regressors:
    print (f"Trying {k}...")

    model = regressors[k]
    model.fit(X, y)
    model.feature_names_in_ = None

    y_pred = model.predict(X)

    new_data_point = [[float(sys.argv[1]), float(sys.argv[2])]]
    predicted_watts = model.predict(new_data_point)
    print("Predicted Watts: %f" % (predicted_watts, ))

    intercept = model.intercept_
    coef_target_temp = model.coef_[0]
    coef_temp_diff = model.coef_[1]

    predicted_watts = predict_watts(float(sys.argv[1]), float(sys.argv[2]))
    print("heat Predicted Watts:", predicted_watts)

    break

exit(0)



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

fig = px.scatter_3d(df, x='temperature', y='tempDiff', z='actualwatts', size_max=12, color='actualwatts', opacity=0.8)
fig.update_layout(margin=dict(l=0, r=0, b=0, t=0))
fig.update_layout(scene=dict(xaxis=dict(title='Target Temperature'), yaxis=dict(title='Temperature Difference'), zaxis=dict(title='Watts')))
fig.update_layout(title=dict(text="Target Temperature Vs Temperature Difference Vs Watts", font=dict(size=18), xanchor='left', yanchor='top'))
pio.write_html(fig, '/root/AtmoWiz/web/out.html')
