#!/usr/bin/python3

import configparser
import MySQLdb
import pandas as pd

configParser = configparser.ConfigParser(allow_no_value = True)
configParser.read("/etc/sensibo.conf")
hostname = configParser.get('mariadb', 'hostname', fallback = 'localhost')
database = configParser.get('mariadb', 'database', fallback = 'sensibo')
username = configParser.get('mariadb', 'username', fallback = 'sensibo')
password = configParser.get('mariadb', 'password', fallback = 'password')

mydb = MySQLdb.connect(hostname, username, password, database)

query = "SELECT temperature, humidity, feelslike, mode, targetTemperature, fanLevel, amps FROM `sensibo` WHERE airconon = 1 AND amps != 0"
result_df = pd.read_sql(query, mydb)
print(result_df)
