#!/usr/bin/python3

import configparser

import matplotlib.pyplot as plt

import numpy as np
import pandas as pd

import tensorflow as tf
from tensorflow.keras.models import Sequential
from tensorflow.keras.layers import LSTM, Dense

import sys

from sqlalchemy import create_engine

configParser = configparser.ConfigParser(allow_no_value = True)
configParser.read("/etc/atmowiz.conf")
hostname = configParser.get('mariadb', 'hostname', fallback = 'localhost')
database = configParser.get('mariadb', 'database', fallback = 'atmowiz')
username = configParser.get('mariadb', 'username', fallback = 'atmowiz')
password = configParser.get('mariadb', 'password', fallback = 'password')

db_uri = "mysql://%s:%s@%s/%s" % (username, password, hostname, database)
engine = create_engine(db_uri)

query = "SELECT temperature, (temperature - targetTemperature) as tempDiff, watts FROM `sensibo` WHERE airconon = 1 AND watts != 0 AND mode='cool'"
df = pd.read_sql(query, engine)

X = df[["temperature", "tempDiff"]]
y = df["watts"]

X_new = np.reshape(X.values, (X.shape[0], X.shape[1], 1))

model = Sequential()
model.add(LSTM(50, input_shape=(X.shape[1], X.shape[2])))
model.add(Dense(1))
model.compile(optimizer='adam', loss='mse')
model.fit(X, y, epochs=100, batch_size=32, verbose=1)

temperature = float(sys.argv[1])
temp_diff = float(sys.argv[2])

X_new = np.array([[temperature, temp_diff]])
X_new = np.reshape(X_new, (X_new.shape[0], X_new.shape[1], 1))

prediction = model.predict(X_new)
print("Predicted watts:", prediction[0][0])

