#!/usr/bin/python3

import MySQLdb
import pandas as pd

mydb = MySQLdb.connect('localhost', 'sensibo', 'uMrSzMH5MRHLF4iz', 'sensibo')

query = "SELECT temperature, humidity, feelslike, mode, targetTemperature, fanLevel, amps FROM `sensibo` WHERE amps != 0"
result_df = pd.read_sql(query, mydb)
print(result_df)
