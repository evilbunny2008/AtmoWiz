#!/usr/bin/python3

import configparser
import MySQLdb
import pandas as pd
import matplotlib.pyplot as plt
import numpy as np
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

print(df.columns)
print(df.head())

model = smf.ols(formula='watts ~ targetTemperature + tempDiff', data=df)
results_formula = model.fit()
print(results_formula.params)
results_formula.params

x_surf, y_surf = np.meshgrid(np.linspace(df.targetTemperature.min(), df.targetTemperature.max(), 50),np.linspace(df.tempDiff.min(), df.tempDiff.max(), 50))
onlyX = pd.DataFrame({'targetTemperature': x_surf.ravel(), 'tempDiff': y_surf.ravel()})
fittedY=results_formula.predict(exog=onlyX)

fittedY=np.array(fittedY)

fig = plt.figure()
ax = fig.add_subplot(111, projection='3d')
ax.scatter(df['targetTemperature'], df['tempDiff'], df['watts'], c='red', marker='o', alpha=0.5)
ax.plot_surface(x_surf,y_surf,fittedY.reshape(x_surf.shape), color='b', alpha=0.3)
ax.set_zlabel('watts')
ax.set_xlabel('targetTemperature')
ax.set_ylabel('tempDiff')

plt.title("Watts Vs tempDiff Vs temperature")
plt.savefig("../web/out.png")
