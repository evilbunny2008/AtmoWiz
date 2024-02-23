#!/usr/bin/python3

import configparser
import MySQLdb
import pandas as pd
import matplotlib.pyplot as plt
import numpy as np
import seaborn as sns
import statsmodels.formula.api as smf

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

query = "SELECT watts, targetTemperature, (temperature - targetTemperature) as tempDiff FROM `sensibo` WHERE airconon = 1 AND watts != 0 AND mode='cool'"
df = pd.read_sql(query, mydb)

df.reset_index(drop=True, inplace=True)

print(df.columns)
print(df.head())

model = smf.ols(formula='watts ~ targetTemperature + tempDiff', data=df)
results_formula = model.fit()
print(results_formula.params)
results_formula.params

x_surf, y_surf = np.meshgrid(np.linspace(df.targetTemperature.min(), df.targetTemperature.max(), 50),np.linspace(df.tempDiff.min(), df.tempDiff.max(), 50))
onlyX = pd.DataFrame({'targetTemperature': x_surf.ravel(), 'tempDiff': y_surf.ravel()})
fittedY=results_formula.predict(exog=onlyX)

## convert the predicted result in an array
fittedY=np.array(fittedY)

# Visualize the Data for Multiple Linear Regression
fig = plt.figure()
ax = fig.add_subplot(111, projection='3d')
ax.scatter(df['targetTemperature'], df['tempDiff'], df['watts'], c='red', marker='o', alpha=0.5)
ax.plot_surface(x_surf,y_surf,fittedY.reshape(x_surf.shape), color='b', alpha=0.3)
ax.set_zlabel('watts')
ax.set_xlabel('targetTemperature')
ax.set_ylabel('tempDiff')
plt.show()

plt.title("Watts Vs tempDiff Vs temperature")
plt.savefig("../web/out.png")
















#categorical_cols = ['mode', 'fanLevel']
#enc = OrdinalEncoder()
#df[categorical_cols] = enc.fit_transform(df[categorical_cols])
#print(df.head())
#print(df['mode'].unique())
#print(df.shape)
#model = LinearRegression()
#model.fit(X, Y, Z)
#print(model.predict(X))
#print('Intercept: ', model.intercept_)
#print('Coefficients array: ', model.coef_)

#sns.set_theme(style="ticks")

#f, ax = plt.subplots(figsize=(10, 5))
#sns.despine(f)
#lmplot = sns.relplot(data=df, x="tempDiff", y="watts").fig
#lmplot = sns.regplot(data=df, x="tempDiff", y="watts").figure
#lmplot = sns.kdeplot(data=df, x="tempDiff", y="watts", cmap="Reds", shade=True, bw_method=.30).figure
#lmplot = sns.kdeplot(data=df, x="tempDiff", y="watts").figure
#lmplot = sns.regplot(data=df, x="tempDiff", y="watts").figure
#lmplot = sns.scatterplot(data=df, x="tempDiff", y="watts", marker='+').figure

#lmplot = sns.kdeplot(data=df, x="tempDiff", y="watts", shade=True).figure

