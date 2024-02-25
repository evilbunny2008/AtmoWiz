#!/usr/bin/python3

import configparser

import matplotlib.pyplot as plt
import numpy as np
import pandas as pd

from sklearn.ensemble import RandomForestRegressor
from sklearn.linear_model import LinearRegression
from sklearn.metrics import mean_squared_error

from tensorflow.keras.models import Sequential
from tensorflow.keras.layers import LSTM, Dense

import sys

from sqlalchemy import create_engine

import warnings
warnings.filterwarnings("ignore", message=f"X has feature names, but LinearRegression was fitted without feature names", category=UserWarning)
warnings.filterwarnings("ignore", message=f"X has feature names, but RandomForestRegressor was fitted without feature names", category=UserWarning)
warnings.filterwarnings("ignore", message=f"X does not have valid feature names, but RandomForestRegressor was fitted with feature names", category=UserWarning)

configParser = configparser.ConfigParser(allow_no_value = True)
configParser.read("/etc/atmowiz.conf")
hostname = configParser.get('mariadb', 'hostname', fallback = 'localhost')
database = configParser.get('mariadb', 'database', fallback = 'atmowiz')
username = configParser.get('mariadb', 'username', fallback = 'atmowiz')
password = configParser.get('mariadb', 'password', fallback = 'password')

db_uri = "mysql://%s:%s@%s/%s" % (username, password, hostname, database)
engine = create_engine(db_uri)

query = "SELECT temperature, (temperature - targetTemperature) as tempDiff, humidity, feelsLike, watts FROM `sensibo` WHERE airconon = 1 AND watts > 0 AND mode='cool'"
df = pd.read_sql(query, engine)

X = df[["temperature", "tempDiff", "humidity", "feelsLike"]]
y = df["watts"]

# Reshape X to match the input shape of the LSTM model
X_lstm = np.reshape(X.values, (X.shape[0], X.shape[1], 1))

# Train LSTM model
lstm_model = Sequential()
lstm_model.add(LSTM(50, input_shape=(X_lstm.shape[1], X_lstm.shape[2]), return_sequences=True))
lstm_model.add(LSTM(50))
lstm_model.add(Dense(64, activation='relu'))
lstm_model.add(Dense(1))
lstm_model.compile(optimizer='adam', loss='mse')
lstm_model.fit(X_lstm, y, epochs=250, batch_size=32, verbose=0)

# Train RandomForestRegressor model
rf_model = RandomForestRegressor(n_estimators=250)
rf_model.fit(X, y)

# Make predictions using LSTM model
temperature = float(sys.argv[1])
temp_diff = float(sys.argv[2])
humidity = float(sys.argv[3])
feelsLike = float(sys.argv[4])

X_new = np.array([[temperature, temp_diff, humidity, feelsLike]])
# Reshape X_new to be 2-dimensional
X_new = np.reshape(X_new, (X_new.shape[0], -1))
prediction_lstm = lstm_model.predict(X_new)[0][0]

# Make predictions using RandomForestRegressor model
prediction_rf = rf_model.predict(X_new)[0]

# Average predictions from both models
final_prediction = (prediction_lstm + prediction_rf) / 2

print("Predicted watts (prediction_lstm):", prediction_lstm)
print("Predicted watts (prediction_rf):", prediction_rf)
print("Predicted watts (Ensemble):", final_prediction)


y_pred = lstm_model.predict(X)
residuals = y - y_pred.flatten()

mse = mean_squared_error(y, y_pred)
rmse = np.sqrt(mse)

plt.figure(figsize=(10, 6))
plt.hist(residuals, bins=30, edgecolor='black')
plt.title('Residuals Distribution')
plt.xlabel('Residuals')
plt.ylabel('Frequency')
plt.grid(True)
plt.show()

# Plot actual vs predicted values
plt.figure(figsize=(10, 6))
plt.scatter(y, y_pred, alpha=0.5)
plt.plot([y.min(), y.max()], [y.min(), y.max()], 'k--', lw=2)
plt.title('Actual vs Predicted')
plt.xlabel('Actual Watts')
plt.ylabel('Predicted Watts')
plt.grid(True)
plt.show()

print("Mean Squared Error (MSE):", mse)
print("Root Mean Squared Error (RMSE):", rmse)
