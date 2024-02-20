#!/usr/bin/python3

import argparse
import configparser
import grp
import json
import logging
import math
import MySQLdb
import os
import pwd
import random
import requests
import serial
import shutil
import sys
import threading
import time
import traceback
import xmltodict
from datetime import datetime
from dateutil import tz
from requests.auth import HTTPBasicAuth
from systemd.journal import JournalHandler
from urllib.parse import urlparse

_corf = "C"
_hasPlus = False
_SERVER = 'https://home.sensibo.com/api/v2'

_sqlquery1 = 'INSERT INTO commands (whentime, uid, reason, who, status, airconon, mode, targetTemperature, temperatureUnit, fanLevel, swing, horizontalSwing, changes) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)'
_sqlquery2 = 'INSERT INTO devices (uid, name) VALUES (%s, %s)'
_sqlquery3 = 'INSERT INTO sensibo (whentime, uid, temperature, humidity, feelslike, rssi, airconon, mode, targetTemperature, fanLevel, swing, horizontalSwing, watts) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)'

_sqlselect1 = 'SELECT 1 FROM commands WHERE whentime=%s AND uid=%s'
_sqlselect2 = 'SELECT 1 FROM devices WHERE uid=%s AND name=%s'
_sqlselect3 = 'SELECT 1 FROM sensibo WHERE whentime=%s AND uid=%s'

_INVOCATION_ID = os.environ.get('INVOCATION_ID', False)

_lat = 0
_lon = 0

class SensiboClientAPI(object):
    def __init__(self, api_key):
        self._api_key = api_key

    def _get(self, path, ** params):
        try:
            params['apiKey'] = self._api_key
            response = requests.get(_SERVER + path, params = params)
            response.raise_for_status()
            return response.json()
        except requests.exceptions.RequestException as exc:
            doLog("error", "Request failed, full error messages hidden to protect the API key")
            return None

    def _patch(self, path, data, ** params):
        try:
            params['apiKey'] = self._api_key
            response = requests.patch(_SERVER + path, params = params, data = data)
            response.raise_for_status()
            return response.json()
        except requests.exceptions.RequestException as exc:
            doLog("error", "Request failed, full error messages hidden to protect the API key")
            return None

    def _post(self, path, headers, data, ** params):
        try:
            params['apiKey'] = self._api_key
            response = requests.post(_SERVER + path, headers = headers, params = params, data = data)
            response.raise_for_status()
            return response.json()
        except requests.exceptions.RequestException as exc:
            doLog("error", "Request failed, full error messages hidden to protect the API key")
            return None

    def devices(self):
        result = self._get("/users/me/pods", fields="id,room")
        if(result == None):
            return None

        if(result['status'] == 429):
            doLog("error", "Sensibo said we made too many requests, sleeping for 5s and will then retry")
            time.sleep(5)
            result = devices()

        try:
            return result
        except Exception as e:
            doLog("error", result, True)
            return None

    def pod_all_stats(self, podUid, nb = 1):
        result = self._get("/pods/%s/acStates" % podUid, limit = nb, fields="device,feelsLike")
        if(result == None):
            return None

        if(result['status'] == 429):
            doLog("error", "Sensibo said we made too many requests, sleeping for 5s and will then retry")
            time.sleep(5)
            result = pod_all_stats(podUid, nb)

        try:
            return result
        except Exception as e:
            doLog("error", result, True)
            return None

    def pod_get_remote_capabilities(self, podUid, nb = 1):
        result = self._get("/pods/%s/acStates" % podUid, limit = nb, fields="device,remoteCapabilities,features")
        if(result == None):
            return None

        if(result['status'] == 429):
            doLog("error", "Sensibo said we made too many requests, sleeping for 5s and will then retry")
            time.sleep(5)
            result = pod_get_remote_capabilities(podUid, nb)

        try:
            return result
        except Exception as e:
            doLog("error", result, True)
            return None

    def pod_status(self, podUid, lastlimit = 5):
        result = self._get("/pods/%s/acStates" % podUid, limit = lastlimit, fields="status,reason,time,acState,causedByUser,resultingAcState,changedProperties")
        if(result == None):
            return None

        if(result['status'] == 429):
            doLog("error", "Sensibo said we made too many requests, sleeping for 5s and will then retry")
            time.sleep(5)
            result = pod_status(podUid, lastlimit)

        try:
            return result
        except Exception as e:
            doLog("error", result, True)
            return None

    def pod_get_past(self, podUid, days = 1):
        result = self._get("/pods/%s/historicalMeasurements" % podUid, days = days, fields="status,reason,time,acState,causedByUser")
        if(result == None):
            return None

        if(result['status'] == 429):
            doLog("error", "Sensibo said we made too many requests, sleeping for 5s and will then retry")
            time.sleep(5)
            result = pod_get_past(podUid, days)

        try:
            return result
        except Exception as e:
            doLog("error", result, True)
            return None

    def pod_change_ac_state(self, podUid, on, targetTemperature, mode, fanLevel, swing, hswing):
        headers = {"Accept": "application/json", "Content-Type": "application/json"}
        self._post("/pods/%s/acStates" % podUid, headers,
                json.dumps({"acState": {"on": on, "mode": mode, "targetTemperature": int(targetTemperature), "fanLevel": fanLevel, "swing": swing, "horizontalSwing": hswing}}))

    def pod_location(self, podUid):
        result = self._get("/pods/%s/acStates" % podUid, limit = 1, fields="pod")
        if(result == None):
            return None

        if(result['status'] == 429):
            doLog("error", "Sensibo said we made too many requests, sleeping for 5s and will then retry")
            time.sleep(5)
            result = pod_location(podUid)

        try:
            return result
        except Exception as e:
            doLog("error", result, True)
            return None

def calcAT(temp, humid, country, feelslike):
    if(feelslike != None and country == 'None'):
        return feelslike

    if(country == 'au'):
        doLog("debug", "Using BoM Apparent Temp")
        # BoM's Feels Like formula -- http://www.bom.gov.au/info/thermal_stress/
        # UPDATE sensibo SET feelslike=round(temperature + (0.33 * ((humidity / 100) * 6.105 * exp((17.27 * temperature) / (237.7 + temperature)))) - 4, 1)
        if(_corf == 'F'):
            temp = (temp - 32) * 5 / 9
            doLog("debug", "temp = %f" % temp)

        vp = (humid / 100.0) * 6.105 * math.exp((17.27 * temp) / (237.7 + temp))
        doLog("debug", "vp = %f" % vp)
        at = round(temp + (0.33 * vp) - 4.0, 1)
        doLog("debug", "at = %f" % at)
        return at
    else:
        # North American Heat Index -- https://www.wpc.ncep.noaa.gov/html/heatindex_equation.shtml
        doLog("debug", "Using NA HI")
        if(_corf == 'C'):
            temp =  (temp * 9 / 5) + 32
            doLog("debug", "temp = %f" % temp)


        if(temp <= 80 and temp >= 50):
            HI = 0.5 * (temp + 61.0 + ((temp - 68.0) * 1.2) + (humid * 0.094))
            if(_corf == 'C'):
                HI = (HI - 32) * 5 / 9

            doLog("debug", "HI = %f" % HI)
            return HI
        elif(temp > 50):
            HI = -42.379 + 2.04901523 * temp + 10.14333127 * humid - .22475541 * temp * humid - .00683783 * temp * temp - .05481717 * humid * humid + .00122874 * temp * temp * humid + .00085282 * temp * humid * humid - .00000199 * temp * temp * humid * humid

            if(temp > 80 and humid < 13):
                HI -= ((13 - humid) / 4) * math.sqrt((17 - math.abs(temp - 95)) / 17)

            if(temp >= 80 and temp <= 87 and humid >= 85):
                HI += ((humid - 85) / 10) * ((87 - temp) / 5)

            if(_corf == 'C'):
                HI = (HI - 32) * 5 / 9

            doLog("debug", "HI = %f" % HI)
            return HI
        else:
            if(_corf == 'C'):
                temp = (temp - 32) * 5 / 9

            WC = 35.74 + 0.6215 * temp
            if(_corf == 'C'):
                WC = (WC - 32) * 5 / 9

            doLog("debug", "WC = %f" % WC)
            return WC

def validateValues(temp, humid):
    if(humid < 1 or humid > 99):
        return False

    if(_corf == "C" and (temp > 55 or temp < -55)):
        return False
    elif(_corf == "F" and (temp > 131 or temp < -67)):
        return False

    return True

def full_stack():
    exc = sys.exc_info()[0]
    stack = traceback.extract_stack()[:-1]
    if exc is not None:
        del stack[-1]

    trc = 'Traceback (most recent call last):\n'
    stackstr = trc + ''.join(traceback.format_list(stack))
    if exc is not None:
         stackstr += '  ' + traceback.format_exc().lstrip(trc)

    return stackstr

def doLog(logType, line, doStackTrace = False):
    line = str(line)
    if(logType == 'info'):
        if(not _INVOCATION_ID):
            print (line)
        log.info(line)

        if(doStackTrace):
            if(not _INVOCATION_ID):
                print (full_stack())
            log.info(full_stack())
    elif(logType == 'debug'):
        if(not _INVOCATION_ID):
            print ('\33[90m' + line + '\033[0m')

        log.debug(line)
        if(doStackTrace):
            if(not _INVOCATION_ID):
                print ('\33[90m' + full_stack() + '\033[0m')
            log.debug(full_stack())
    elif(logType == 'warning'):
        if(not _INVOCATION_ID):
            print ('\033[93m' + line + '\033[0m')

        log.warning(line)
        if(doStackTrace):
            if(not _INVOCATION_ID):
                print ('\033[93m' + full_stack() + '\033[0m')
            log.warning(full_stack())
    else:
        if(not _INVOCATION_ID):
            print ('\33[91m' + line + '\033[0m')

        log.error(line)
        if(doStackTrace):
            if(not _INVOCATION_ID):
                print ('\33[90m' + full_stack() + '\033[0m')
            log.error(full_stack())

def getWatts():
    if(costCurrentPort == None):
        return 0

    line = ""
    try:
        with serial.Serial() as ser:
            ser.baudrate = 57600
            ser.port = costCurrentPort
            ser.open()
            line = ser.readline()
            line = str(line, 'ascii').strip()
            line = xmltodict.parse(line)
            watts = int(line['msg']['ch1']['watts'])
            ser.close()
            return watts
    except Exception as e:
        doLog("error", line)
        doLog("error", e, True)
        return getWatts()

    return 0

def costFactor(mode, targetTemperature, temperature):
    if(mode == 'heat'):
        if(targetTemperature - temperature <= 0):
            return 0.25
        return (1.035 - (1 / (1 + (targetTemperature - temperature))) ** 2)

    if(mode == 'cool' or mode == 'dry'):
        if(temperature - targetTemperature <= 0):
            return 0.25
        return (1.035 - (1 / (1 + (temperature - targetTemperature))) ** 2)

    return 1

def calcCost(mydb):
    doLog("info", "Running cost calc...")

    try:
        cursor1 = mydb.cursor()
        cursor2 = mydb.cursor()

        query = "SELECT whentime, uid, DAYOFWEEK(whentime) as dow, HOUR(whentime) as hod, mode, targetTemperature, temperature FROM sensibo WHERE airconon=1 AND cost=0.0 AND (mode='cool' OR mode='dry')"
        cursor1.execute(query)
        for (whentime, podUID, dow, hod, mode, targetTemperature, temperature) in cursor1:
            if(dow == 1 or dow == 7):
                cost = cool / EER * offpeak * 90.0 / 3600.0
            else:
                cost = cool / EER * offpeak * 90.0 / 3600.0
                if(hod >= 7 and hod < 9):
                    cost = cool / EER * peak * 90.0 / 3600.0
                if(hod >= 9 and hod < 17):
                    cost = cool / EER * shoulder * 90.0 / 3600.0
                if(hod >= 17 and hod < 20):
                    cost = cool / EER * peak * 90.0 / 3600.0
                if(hod >= 20 and hod < 22):
                    cost = cool / EER * shoulder * 90.0 / 3600.0

            cost *= costFactor(mode, targetTemperature, temperature)
            query = "UPDATE sensibo SET cost=%s WHERE whentime=%s AND uid=%s"
            values = (cost, whentime, podUID)
            doLog("debug", query % values)
            cursor2.execute(query, values)
            mydb.commit()

        query = "SELECT whentime, uid, DAYOFWEEK(whentime) as dow, HOUR(whentime) as hod, mode, targetTemperature, temperature FROM sensibo WHERE airconon=1 AND cost=0.0 AND mode='heat'"
        cursor1.execute(query)
        for (whentime, podUID, dow, hod, mode, targetTemperature, temperature) in cursor1:
            if(dow == 1 or dow == 7):
                cost = heat / COP * offpeak * 90.0 / 3600.0
            else:
                cost = heat / COP * offpeak * 90.0 / 3600.0
                if(hod >= 7 and hod < 9):
                    cost = heat / COP * peak * 90.0 / 3600.0
                if(hod >= 9 and hod < 17):
                    cost = heat / COP * shoulder * 90.0 / 3600.0
                if(hod >= 17 and hod < 20):
                    cost = heat / COP * peak * 90.0 / 3600.0
                if(hod >= 20 and hod < 22):
                    cost = heat / COP * shoulder * 90.0 / 3600.0

            cost *= costFactor(mode, targetTemperature, temperature)
            query = "UPDATE sensibo SET cost=%s WHERE whentime=%s AND uid=%s"
            values = (cost, whentime, podUID)
            doLog("debug", query % values)
            cursor2.execute(query, values)
            mydb.commit()

        query = "SELECT whentime, uid, DAYOFWEEK(whentime) as dow, HOUR(whentime) as hod, mode, targetTemperature, temperature FROM sensibo WHERE airconon=1 AND cost=0.0 AND mode='fan'"
        cursor1.execute(query)
        for (whentime, podUID, dow, hod, mode, targetTemperature, temperature) in cursor1:
            if(dow == 1 or dow == 7):
                cost = fankw * offpeak * 90.0 / 3600.0
            else:
                cost = fankw * offpeak * 90.0 / 3600.0
                if(hod >= 7 and hod < 9):
                    cost = fankw * peak * 90.0 / 3600.0
                if(hod >= 9 and hod < 17):
                    cost = fankw * shoulder * 90.0 / 3600.0
                if(hod >= 17 and hod < 20):
                    cost = fankw * peak * 90.0 / 3600.0
                if(hod >= 20 and hod < 22):
                    cost = fankw * shoulder * 90.0 / 3600.0

            query = "UPDATE sensibo SET cost=%s WHERE whentime=%s AND uid=%s"
            values = (cost, whentime, podUID)
            doLog("debug", query % values)
            cursor2.execute(query, values)
            mydb.commit()

        query = "SELECT whentime, uid, DAYOFWEEK(whentime) as dow, HOUR(whentime) as hod, mode, targetTemperature, temperature FROM sensibo WHERE airconon=0 AND cost=0.0"
        cursor1.execute(query)
        for (whentime, podUID, dow, hod, mode, targetTemperature, temperature) in cursor1:
            if(dow == 1 or dow == 7):
                cost = offkw * offpeak * 90.0 / 3600.0
            else:
                cost = offkw * offpeak * 90.0 / 3600.0
                if(hod >= 7 and hod < 9):
                    cost = offkw * peak * 90.0 / 3600.0
                if(hod >= 9 and hod < 17):
                    cost = offkw * shoulder * 90.0 / 3600.0
                if(hod >= 17 and hod < 20):
                    cost = offkw * peak * 90.0 / 3600.0
                if(hod >= 20 and hod < 22):
                    cost = offkw * shoulder * 90.0 / 3600.0

            query = "UPDATE sensibo SET cost=%s WHERE whentime=%s AND uid=%s"
            values = (cost, whentime, podUID)
            doLog("debug", query % values)
            cursor2.execute(query, values)
            mydb.commit()

    except MySQLdb._exceptions.ProgrammingError as e:
        doLog("error", "There was a problem, error was %s" % e, True)
        pass
    except MySQLdb._exceptions.OperationalError as e:
        doLog("error", "There was a problem, error was %s" % e, True)
        pass
    except MySQLdb._exceptions.IntegrityError as e:
        doLog("error", "There was a problem, error was %s" % e, True)
        pass

def calcFL(mydb, country):
    try:
        cursor1 = mydb.cursor()

        if(_corf == 'C' and country == 'au'):
            query = "UPDATE sensibo SET feelslike=round(temperature + (0.33 * ((humidity / 100) * 6.105 * exp((17.27 * temperature) / (237.7 + temperature)))) - 4, 1) WHERE feelslike=-1"
            doLog("debug", query)
            cursor1.execute(query)
            mydb.commit()
            return
        else:
            query = "SELECT whentime, uid, temperature, humidity FROM sensibo WHERE feelslike=-1"
            doLog("debug", query)
            cursor1.execute(query)
            cursor2 = mydb.cursor()
            for (whentime, podUID, temp, humid) in cursor1:
                at = calcAT(temp, humid, country, None)
                query = "UPDATE sensibo SET feelslike=%s WHERE whentime=%s AND uid=%s AND feelslike=-1"
                values = (at, whentime, podUID)
                doLog("debug", query % values)
                cursor2.execute(query, values)

            mydb.commit()
            return

    except MySQLdb._exceptions.ProgrammingError as e:
        doLog("error", "There was a problem, error was %s" % e, True)
        pass
    except MySQLdb._exceptions.OperationalError as e:
        doLog("error", "There was a problem, error was %s" % e, True)
        pass
    except MySQLdb._exceptions.IntegrityError as e:
        doLog("error", "There was a problem, error was %s" % e, True)
        pass

def doHistoricalMeasurements(mydb, days = 1):
    doLog("info", "Getting %d days of historical data from Sensibo.com..." % days)
    cursor = mydb.cursor()

    for podUID in uidList:
        historicalMeasurements = client.pod_get_past(podUID, days)
        if(historicalMeasurements == None):
            continue

        historicalMeasurements = historicalMeasurements['result']

        pod_measurement40 = client.pod_all_stats(podUID, 40)
        if(pod_measurement40 == None):
            continue

        pod_measurement40 = pod_measurement40['result']

        rc = -1
        for i in range(len(historicalMeasurements['temperature']) - 1, 0, -1):
            rc += 1
            temp = historicalMeasurements['temperature'][i]['value']
            humid = historicalMeasurements['humidity'][i]['value']

            if(not validateValues(temp, humid)):
                doLog("error", "Temp (%f) or Humidity (%d) out of bounds." % (temp, humid))
                continue

            if(rc < len(pod_measurement40)):
                feelslike = pod_measurement40[rc]['device']['measurements']['feelsLike']
                rssi = pod_measurement40[rc]['device']['measurements']['rssi']
                airconon = pod_measurement40[rc]['device']['acState']['on']
                mode = pod_measurement40[rc]['device']['acState']['mode']
                if(mode != 'fan'):
                    targetTemperature = pod_measurement40[rc]['device']['acState']['targetTemperature']
                    temperatureUnit = pod_measurement40[rc]['device']['acState']['temperatureUnit']
                else:
                    targetTemperature = None
                    temperatureUnit = None

                fanLevel = pod_measurement40[rc]['device']['acState']['fanLevel']
                swing = pod_measurement40[rc]['device']['acState']['swing']
                horizontalSwing = pod_measurement40[rc]['device']['acState']['horizontalSwing']
            else:
                feelslike = None
                rssi = 0
                airconon = 0
                mode = 'cool'
                targetTemperature = 0
                fanLevel = 'medium'
                swing = 'fixedTop'
                horizontalSwing = 'fixedCenter'

            sstring = datetime.strptime(historicalMeasurements['temperature'][i]['time'], fromfmt2)
            utc = sstring.replace(tzinfo=from_zone)
            localzone = utc.astimezone(to_zone)
            sdate = localzone.strftime(fmt)
            values = (sdate, podUID)
            #doLog("debug", _sqlselect3 % values)
            cursor.execute(_sqlselect3, values)
            row = cursor.fetchone()
            if(row):
                continue

            doLog("debug", "rc = %d, i = %d" % (rc, i))
            doLog("debug", historicalMeasurements['temperature'][i])
            doLog("debug", historicalMeasurements['humidity'][i])

            at = calcAT(temp, humid, country, feelslike)
            values = (sdate, podUID, temp, humid, at, rssi, airconon, mode, targetTemperature, fanLevel, swing, horizontalSwing, 0)
            doLog("debug", _sqlquery3 % values)
            cursor.execute(_sqlquery3, values)
            mydb.commit()

def getLastCommands(mydb, nb = 5):
    for podUID in uidList:
        lastCommands = client.pod_status(podUID, nb)['result']
        if(lastCommands == None):
            continue

        for last in lastCommands:
            try:
                cursor = mydb.cursor()
                sstring = datetime.strptime(last['time']['time'], fromfmt2)
                utc = sstring.replace(tzinfo=from_zone)
                localzone = utc.astimezone(to_zone)
                sdate = localzone.strftime(fmt)
                values = (sdate, podUID)
                #doLog("debug", _sqlselect1 % values)
                cursor.execute(_sqlselect1, values)
                row = cursor.fetchone()
                if(row):
                    continue

                if(last['causedByUser'] == None):
                    last['causedByUser'] = {}
                    last['causedByUser']['firstName'] = 'Remote'

                acState = last['resultingAcState']
                if(not acState['swing']):
                    acState = last['acState']

                if(acState['mode'] == 'fan'):
                    acState['targetTemperature'] = None
                    acState['temperatureUnit'] = None

                changes = last['changedProperties']

                values = (sdate, podUID, last['reason'], last['causedByUser']['firstName'],
                          last['status'], acState['on'], acState['mode'], acState['targetTemperature'],
                          acState['temperatureUnit'], acState['fanLevel'], acState['swing'],
                          acState['horizontalSwing'], str(changes))
                doLog("debug", _sqlquery1 % values)
                cursor.execute(_sqlquery1, values)
            except MySQLdb._exceptions.ProgrammingError as e:
                doLog("error", "There was a problem, error was %s" % e, True)
                pass
            except MySQLdb._exceptions.IntegrityError as e:
                doLog("error", "There was a problem, error was %s" % e, True)
                pass
            except MySQLdb._exceptions.OperationalError as e:
                doLog("error", "There was a problem, error was %s" % e, True)
                pass
            except MySQLdb._exceptions.DataError as e:
                if(e.args[0] == 1265):
                    table_name = 'commands'
                    field = e.args[1].split("'")[1]
                    updateEnum(mydb, table_name, field)
                    doLog("debug", _sqlquery1 % values)
                    cursor.execute(_sqlquery1, values)
                    mydb.commit()
                    exit(0)

    mydb.commit()

def updateEnum(mydb, table_name, field):
    cursor = mydb.cursor()
    query = "SELECT DISTINCT SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING(COLUMN_TYPE, 7, LENGTH(COLUMN_TYPE) - 8), \"','\", 1 + units.i + tens.i * 10) , \"','\", -1) AS value FROM INFORMATION_SCHEMA.COLUMNS CROSS JOIN " + \
            "(SELECT 0 AS i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) units CROSS JOIN (SELECT 0 AS i UNION SELECT 1 " + \
           f"UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) tens WHERE TABLE_NAME='{table_name}' AND COLUMN_NAME='{field}'"
    doLog("info", query)
    cursor.execute(query)
    result = cursor.fetchall()
    values = []
    default = ""
    for value in result:
        if(default == ""):
            default = value[0]
        values.append(value[0])
    values.append(acState[field])

    enum_values = ', '.join(["'{}'".format(value) for value in values])
    alter_query = f"ALTER TABLE `{table_name}` CHANGE `{field}` `{field}` ENUM({enum_values}) NOT NULL DEFAULT '{default}'"
    doLog("info", alter_query)
    cursor.execute(query)
    mydb.commit()

def checkSettings(mydb):
    doLog("info", "Checking climate settings...")
    for podUID in uidList:
        try:
            cursor = mydb.cursor()
            query = "SELECT onOff, targetType, targetOp, targetValue, turnOnOff, targetTemperature, mode, fanLevel, swing, horizontalSwing FROM settings WHERE uid=%s AND enabled=1 AND " + \
                    "(TIME(NOW()) BETWEEN startTime AND endTime OR (endTime < startTime AND (TIME(NOW()) BETWEEN startTime AND '23:59:59' OR TIME(NOW()) BETWEEN '00:00:00' and endTime)))"
            values = (podUID, )
            #doLog("debug", query % values)
            cursor.execute(query, values)
            result = cursor.fetchall()
            for (onOff, targetType, targetOp, targetValue, turnOnOff, targetTemperature, mode, fanLevel, swing, horizontalSwing) in result:
                #doLog("debug", "%s, %s, %s, %s, %s, %s, %s, %s, %s, %s" % (onOff, targetType, targetOp, targetValue, turnOnOff, targetTemperature, mode, fanLevel, swing, horizontalSwing))
                query = "SELECT airconon,temperature,humidity,feelsLike FROM sensibo WHERE uid=%s ORDER BY whentime DESC LIMIT 1"
                values = (podUID, )
                #doLog("debug", query % values)
                cursor.execute(query, values)
                (airconon, temperature, humidity, feelsLike) = cursor.fetchone()
                doLog("debug", "%d, %s, %s, %s" % (airconon, temperature, humidity, feelsLike))

                query = "SELECT temperature,humidity,feelsLike FROM weather ORDER BY whentime DESC LIMIT 1"
                cursor.execute(query)
                (outdoorTemperature, outdoorHumidity, outdoorFeelsLike) = cursor.fetchone()
                doLog("debug", "%s, %s, %s" % (outdoorTemperature, outdoorHumidity, outdoorFeelsLike))

                dict = {}
                dict['temperature'] = temperature
                dict['humidity'] = humidity
                dict['feelsLike'] = feelsLike
                dict['outdoorTemperature'] = outdoorTemperature
                dict['outdoorHumidity'] = outdoorHumidity
                dict['outdoorFeelsLike'] = outdoorFeelsLike

                #client.pod_change_ac_state(podUID, True, targetTemperature, mode, fanLevel, swing, horizontalSwing)

                for i in dict:
                    if(onOff == 'Off' and airconon == 0 and i == targetType and targetOp == '>=' and dict[i] >= targetValue):
                        if(turnOnOff == 'On'):
                            doLog("info", "Rule 1 hit, %s is %s turning aircon on to %s(%s)..." % (i, dict[i], mode, turnOnOff))
                            return
                        else:
                            doLog("info", "Rule 2 hit, %s is %s keeping aircon off to %s(%s)..." % (i, dict[i], mode, turnOnOff))
                            return
                    elif(onOff == 'Off' and airconon == 0 and i == targetType and targetOp == '<=' and dict[i] <= targetValue):
                        if(turnOnOff == 'Off'):
                            doLog("info", "Rule 3 hit, %s is %s keeping aircon off to %s(%s)..." % (i, dict[i], mode, turnOnOff))
                            return
                        else:
                            doLog("info", "Rule 4 hit, %s is %s turning aircon on to %s(%s)..." % (i, dict[i], mode, turnOnOff))
                            return
                    elif(onOff == 'On' and airconon == 1 and i == targetType and targetOp == '>=' and dict[i] >= targetValue):
                        if(turnOnOff == 'On'):
                            doLog("info", "Rule 5 hit, %s is %s keeping aircon on to %s(%s)..." % (i, dict[i], mode, turnOnOff))
                            return
                        else:
                            doLog("info", "Rule 6 hit, %s is %s turning aircon off to %s(%s)..." % (i, dict[i], mode, turnOnOff))
                            return
                    elif(onOff == 'On' and airconon == 1 and i == targetType and targetOp == '<=' and dict[i] <= targetValue):
                        if(turnOnOff == 'Off'):
                            doLog("info", "Rule 7 hit, %s is %s turning aircon off to %s(%s)..." % (i, dict[i], mode, turnOnOff))
                            return
                        else:
                            doLog("info", "Rule 8 hit, %s is %s keeping aircon on to %s(%s)..." % (i, dict[i], mode, turnOnOff))
                            return

                    #else:
                    #    doLog("debug", "Rule X hit, %s is %s... %s(%s)..." % (i, dict[i], mode, turnOnOff))

            query = "SELECT daysOfWeek, turnOnOff, mode, targetTemperature, fanLevel, swing, horizontalSwing FROM timesettings WHERE uid=%s AND enabled=1 AND " + \
                    "(TIME(NOW()) BETWEEN startTime AND endTime OR (endTime < startTime AND (TIME(NOW()) BETWEEN startTime AND '23:59:59' OR TIME(NOW()) BETWEEN '00:00:00' and endTime)))"
            doLog("debug", query % podUID)
            values = (podUID, )
            #doLog("debug", query % values)
            cursor.execute(query, values)
            result = cursor.fetchall()
            for(daysOfWeek, turnOnOff, mode, targetTemperature, fanLevel, swing, horizontalSwing) in result:
                if(not daysOfWeek & 2 ** datetime.today().weekday()):
                    continue

                query = "SELECT airconon, mode, targetTemperature, fanLevel, swing, horizontalSwing FROM sensibo WHERE uid=%s ORDER BY whentime DESC LIMIT 1"
                values = (podUID, )
                #doLog("debug", query % values)
                cursor.execute(query, values)
                (airconon, current_mode, current_targetTemperature, current_fanLevel, current_swing, current_horizontalSwing) = cursor.fetchone()
                #doLog("debug", "%d, %s, %s, %s" % (airconon, temperature, humidity, feelsLike))

                if((turnOnOff == "On" and airconon == 0) or mode != current_mode or targetTemperature != current_targetTemperature or fanLevel != current_fanLevel or swing != current_swing or horizontalSwing != current_horizontalSwing):
                    doLog("info", "Rule 9 hit, %s is %s turning aircon on to..." % (mode, turnOnOff))
                    return
                elif((turnOnOff == "Off" and airconon == 1) or mode != current_mode or targetTemperature != current_targetTemperature or fanLevel != current_fanLevel or swing != current_swing or horizontalSwing != current_horizontalSwing):
                    doLog("info", "Rule 10 hit, %s is %s turning aircon off to..." % (mode, turnOnOff))
                    return
                elif(not (turnOnOff == "On" and airconon == 1 and mode == current_mode and targetTemperature == current_targetTemperature or fanLevel == current_fanLevel or swing == current_swing or horizontalSwing == current_horizontalSwing)):
                    doLog("info", "Rule 11 hit, keeping aircon on but changing mode, targetTemp, fanLevel swing or hor.swing...")
                    return
                elif(not (turnOnOff == "Off" and airconon == 0 and mode == current_mode and targetTemperature == current_targetTemperature or fanLevel == current_fanLevel or swing == current_swing or horizontalSwing == current_horizontalSwing)):
                    doLog("info", "Rule 12 hit, keeping aircon off but changing mode, targetTemp, fanLevel swing or hor.swing...")
                    return

                #client.pod_change_ac_state(podUID, True, targetTemperature, mode, fanLevel, swing, horizontalSwing)

            query = "SELECT whentime, turnOnOff FROM timers WHERE uid=%s AND UNIX_TIMESTAMP(whentime) + seconds < UNIX_TIMESTAMP(NOW())"
            values = (podUID, )
            doLog("debug", query % values)
            cursor.execute(query, values)
            result = cursor.fetchall()
            for (whentime, turnOnOff) in result:
                query = "SELECT airconon, mode, targetTemperature, fanLevel, swing, horizontalSwing FROM sensibo WHERE uid=%s ORDER BY whentime DESC LIMIT 1"
                values = (podUID, )
                doLog("debug", query % values)
                cursor.execute(query, values)
                (airconon, current_mode, current_targetTemperature, current_fanLevel, current_swing, current_horizontalSwing) = cursor.fetchone()

                if(turnOnOff == "On"):
                    if(airconon == 0):
                        doLog("info", "Rule 13 hit for %s, turning aircon on..." % (podUID, ))
                    query = "DELETE FROM timers WHERE whentime=%s AND uid=%s"
                    values = (whentime, podUID)
                    doLog("debug", query % values)
                    cursor.execute(query, values)
                    mydb.commit()
                    return
                elif(turnOnOff == "Off"):
                    if(airconon == 1):
                        doLog("info", "Rule 14 hit for %s, turning aircon off..." % (podUID, ))
                    query = "DELETE FROM timers WHERE whentime=%s AND uid=%s"
                    values = (whentime, podUID)
                    doLog("debug", query % values)
                    cursor.execute(query, values)
                    mydb.commit()
                    return

                #client.pod_change_ac_state(podUID, True, targetTemperature, mode, fanLevel, swing, horizontalSwing)

        except MySQLdb._exceptions.ProgrammingError as e:
            doLog("error", "There was a problem, error was %s" % e, True)
            pass
        except MySQLdb._exceptions.IntegrityError as e:
            doLog("error", "There was a problem, error was %s" % e, True)
            pass
        except MySQLdb._exceptions.OperationalError as e:
            doLog("error", "There was a problem, error was %s" % e, True)
            pass

def getObservations():
    while True:
        try:
            doLog("info", "getObservations(%s)" % podUID)
            mydb = MySQLdb.connect(hostname, username, password, database)

            if(weatherapikey != ''):
                getWeatherAPI(mydb, podUID)

            if(OWMapikey != ''):
                getOpenWeatherMap(mydb, podUID)

            if(inigoURL != ''):
                getInigoData(mydb)

            if(bomURL != ''):
                getBOM(mydb)

            if(metLocation != ''):
                getMetService(mydb)

            if(doOpenMeteo):
                getOpenMeteo(mydb, podUID)

            mydb.close()

            updateTime = 15
            if(inigoURL != ''):
                updateTime = 5

            ttime = round(datetime.now().timestamp() / (updateTime * 60)) * updateTime * 60 - datetime.now().timestamp() + updateTime * 60 + 45

            doLog("info", "Sleeping Obs thread for %d seconds..." % ttime)
            time.sleep(ttime)

        except Exception as e:
            doLog("error", "There was a problem, error was %s" % e, True)
            pass

def getCurrentWeather(podUID):
    doLog("info", "Getting observation...")

    my_thread = threading.Thread(target=getObservations)
    my_thread.start()

def getOpenWeatherMap(mydb, podUID):
    if(OWMapikey == ''):
        doLog("error", "OpenWeatherMap.org API Key is not set, skipping weather lookup")
        return

    doLog("info", "Getting OpenWeatherMap.org observation...")

    if(_lat == 0 and _lon == 0):
        getLatLon(podUID)

    units = 'metric'
    if(_corf == 'F'):
        units = 'imperial'

    url = "https://api.openweathermap.org/data/2.5/weather?lat=%f&lon=%f&appid=%s&units=%s" % (_lat, _lon, OWMapikey, units)

    try:
        response = requests.get(url, timeout = 10)
        response.raise_for_status()
        result = response.json()

        whentime = datetime.fromtimestamp(result['dt']).strftime(fmt)
        temp = result['main']['temp']
        pressure = result['main']['pressure']
        humid = result['main']['humidity']
        fl = calcAT(temp, humid, country, result['main']['feels_like'])

        cursor = mydb.cursor()
        query = "SELECT 1 FROM weather WHERE whentime=%s"
        values = (whentime, )
        doLog("debug", query % values)
        cursor.execute(query, values)
        row = cursor.fetchone()
        if(row):
            doLog("debug", "Skipping observation as we already have it")
            return

        aqi = getAQI()
        values = (whentime, temp, fl, humid, pressure, aqi)
        query = "INSERT INTO weather (whentime, temperature, feelsLike, humidity, pressure, aqi) VALUES (%s, %s, %s, %s, %s, %s)"
        doLog("debug", query % values)
        cursor.execute(query, values)
        mydb.commit()
    except Exception as e:
        doLog("error", "There was a problem, error was %s" % e, True)
        pass

def getMetService(mydb):
    if(metLocation == ''):
        doLog("error", "MetService.com URL is not set, skipping weather lookup")
        return

    doLog("info", "Getting MetService.com URL observation...")

    try:
        #url = "https://www.metservice.com/publicData/hourlyObsAndForecast_" + metLocation
        #url = "https://www.metservice.com/publicData/oneMinObs_" + metLocation
        url = "https://www.metservice.com/publicData/localObs_" + metLocation
        response = requests.get(url, timeout = 10)
        response.raise_for_status()
        result = response.json()
        #doLog("info", result)

        whentime = datetime.fromtimestamp(round(result['threeHour']['rawTime'] / 1000)).strftime(fmt)
        temp = result['threeHour']['temp']
        humid = result['threeHour']['humidity']
        pressure = result['threeHour']['pressure']
        fl = calcAT(float(temp), float(humid), country, None)

        cursor = mydb.cursor()
        query = "SELECT 1 FROM weather WHERE whentime=%s"
        values = (whentime, )
        doLog("debug", query % values)
        cursor.execute(query, values)
        row = cursor.fetchone()
        if(row):
            doLog("debug", "Skipping observation as we already have it")
            return

        aqi = getAQI()
        values = (whentime, temp, fl, humid, pressure, aqi)
        query = "INSERT INTO weather (whentime, temperature, feelsLike, humidity, pressure, aqi) VALUES (%s, %s, %s, %s, %s, %s)"
        doLog("debug", query % values)
        cursor.execute(query, values)
        mydb.commit()

    except Exception as e:
        doLog("error", "There was a problem, error was %s" % e, True)
        pass

def getBOM(mydb):
    if(bomURL == ''):
        doLog("error", "BoM URL is not set, skipping weather lookup")
        return

    doLog("info", "Getting BoM observation...")

    try:
        skip = 1
        headers = {'User-Agent': 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.1 Safari/605.1.15',
                   'Cache-Control': 'max-age=0', 'Accept-Language': 'en-au', 'Upgrade-Insecure-Requests': '1', 'Accept-Encoding': 'deflate'}
        response = requests.get(bomURL, timeout = 10, headers = headers)
        response.raise_for_status()
        result = response.json()['observations']['data']
        for i in result:
            wt = i['local_date_time_full']
            whentime = wt[0:4] + "-" + wt[4:6] + "-" + wt[6:8] + " " + wt[8:10] + ":" + wt[10:12] + ":" + wt[12:14]
            temp = i['air_temp']
            pressure = i['press']
            humid = i['rel_hum']

            cursor = mydb.cursor()
            query = "SELECT 1 FROM weather WHERE whentime=%s"
            values = (whentime, )
            doLog("debug", query % values)
            cursor.execute(query, values)
            row = cursor.fetchone()
            if(row):
                doLog("debug", "Skipping observation as we already have it")
                continue

            aqi = getAQI()
            fl = calcAT(float(temp), float(humid), country, None)
            values = (whentime, temp, fl, humid, pressure, aqi)
            query = "INSERT INTO weather (whentime, temperature, feelsLike, humidity, pressure, aqi) VALUES (%s, %s, %s, %s, %s, %s)"
            doLog("debug", query % values)
            cursor.execute(query, values)
            mydb.commit()

    except Exception as e:
        doLog("error", "There was a problem, error was %s" % e, True)
        pass

def getAQI():
    if(urad_userid != '' and urad_hash != ''):
        return uradmonitor()
    return openmeteoaqi()

def uradmonitor():
    if(urad_userid != '' and urad_hash != ''):
        headers = {"X-User-id": urad_userid, "X-User-hash": urad_hash}
        response = requests.get("https://data.uradmonitor.com/api/v1/devices", timeout = 10, headers = headers)
        response.raise_for_status()
        result = response.json()
        #doLog("debug", result)
        return result[0]['aqi']
    return -1

def getInigoData(mydb):
    if(inigoURL == ''):
        doLog("error", "Inigo URL is not set, skipping weather lookup")
        return

    doLog("info", "Getting Inigo observation...")

    try:
        skip = 1
        response = requests.get(inigoURL, timeout = 10)
        response.raise_for_status()
        result = response.text.split('|')
        temp = result[skip+0]
        pressure = result[skip+37]
        humid = result[skip+6]
        fl = calcAT(float(temp), float(humid), country, None)

        whentime = datetime.fromtimestamp(int(result[skip+225])).strftime(fmt)

        cursor = mydb.cursor()
        query = "SELECT 1 FROM weather WHERE whentime=%s"
        values = (whentime, )
        doLog("debug", query % values)
        cursor.execute(query, values)
        row = cursor.fetchone()
        if(row):
            doLog("debug", "Skipping observation as we already have it")
            return

        aqi = getAQI()
        values = (whentime, temp, fl, humid, pressure, aqi)
        query = "INSERT INTO weather (whentime, temperature, feelsLike, humidity, pressure, aqi) VALUES (%s, %s, %s, %s, %s, %s)"
        doLog("debug", query % values)
        cursor.execute(query, values)
        mydb.commit()

    except Exception as e:
        doLog("error", "There was a problem, error was %s" % e, True)
        pass

def getWeatherAPI(mydb, podUID):
    if(weatherapikey == ''):
        doLog("error", "WeatherAPIkey not set, skipping weather lookup...")
        return

    doLog("info", "Getting WeatherAPI.com observation...")

    if(_lat == 0 and _lon == 0):
        getLatLon(podUID)

    url = "https://api.weatherapi.com/v1/current.json?key=" + weatherapikey + "&q=" + str(_lat) + "," + str(_lon) + "&aqi=yes"

    try:
        response = requests.get(url, timeout = 10)
        response.raise_for_status()
        result = response.json()
        if(_corf == 'C'):
            temp = str(result['current']['temp_c'])
            fl = str(result['current']['feelslike_c'])
            pressure = str(result['current']['pressure_mb'])
        else:
            temp = str(result['current']['temp_f'])
            fl = str(result['current']['feelslike_f'])
            pressure = str(result['current']['pressure_in'])
        humid = str(result['current']['humidity'])
        if(urad_userid != '' and urad_hash != ''):
            aqi = getAQI()
        else:
            aqi = str(result['current']['air_quality']['us-epa-index'])

        cursor = mydb.cursor()
        query = "SELECT 1 FROM weather WHERE whentime=%s"
        values = (result['current']['last_updated'], )
        cursor.execute(query, values)
        row = cursor.fetchone()
        if(row):
            doLog("debug", "Skipping observation as we already have it")
            return

        values = (result['current']['last_updated'], temp, fl, humid, pressure, aqi)
        query = "INSERT INTO weather (whentime, temperature, feelsLike, humidity, pressure, aqi) VALUES (%s, %s, %s, %s, %s, %s)"
        doLog("debug", query % values)
        cursor.execute(query, values)
        mydb.commit()

    except Exception as e:
        doLog("error", "There was a problem, error was %s" % e, True)
        pass

def getLatLon(podUID):
    global _lat
    global _lon

    latLon = client.pod_location(podUID)
    if(latLon == None):
        return None

    latLon = latLon['result'][0]['pod']['location']['latLon']
    _lat = latLon[0]
    _lon = latLon[1]

def openmeteoaqi():
    doLog("info", "Getting Open-Meteo observation...")

    if(_lat == 0 and _lon == 0):
        getLatLon(podUID)

    url = "https://air-quality-api.open-meteo.com/v1/air-quality?latitude=%f&longitude=%f&current=us_aqi&timeformat=unixtime" % (_lat, _lon)

    try:
        response = requests.get(url, timeout = 10)
        response.raise_for_status()
        result = response.json()
        current = result['current']['us_aqi']
        return current


def getOpenMeteo(mydb, podUID):
    if(not doOpenMeteo):
        doLog("error", "Open-Meteo not set, skipping weather lookup...")
        return

    doLog("info", "Getting Open-Meteo observation...")

    if(_lat == 0 and _lon == 0):
        getLatLon(podUID)

    url = "https://api.open-meteo.com/v1/forecast?latitude=%f&longitude=%f&current=temperature_2m,relative_humidity_2m,apparent_temperature,pressure_msl&timeformat=unixtime" % (_lat, _lon)

    try:
        response = requests.get(url, timeout = 10)
        response.raise_for_status()
        result = response.json()
        #doLog("info", result)
        temp = result['current']['temperature_2m']
        fl = result['current']['apparent_temperature']
        pressure = result['current']['pressure_msl']
        humid = result['current']['relative_humidity_2m']

        whentime = datetime.fromtimestamp(result['current']['time']).strftime(fmt)

        cursor = mydb.cursor()
        query = "SELECT 1 FROM weather WHERE whentime=%s"
        values = (whentime, )
        cursor.execute(query, values)
        row = cursor.fetchone()
        if(row):
            doLog("debug", "Skipping observation as we already have it")
            return

        aqi = getAQI()

        values = (whentime, temp, fl, humid, pressure, aqi)
        query = "INSERT INTO weather (whentime, temperature, feelsLike, humidity, pressure, aqi) VALUES (%s, %s, %s, %s, %s, %s)"
        doLog("debug", query % values)
        cursor.execute(query, values)
        mydb.commit()

    except Exception as e:
        doLog("error", "There was a problem, error was %s" % e, True)
        pass

def updateDatabase(mydb):
    try:
        # Upgrade the database
        cursor = mydb.cursor()

        query = "SHOW TABLE STATUS WHERE Name = 'timers'"
        cursor.execute(query)
        row = cursor.fetchone()
        if(not row):
            doLog("info", "Creating timers table...")
            query = "CREATE TABLE `timers` (`whentime` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, `uid` VARCHAR(8) NOT NULL, `seconds` MEDIUMINT(5) NOT NULL DEFAULT '1200', `turnOnOff` ENUM('On','Off') NOT NULL DEFAULT 'On') ENGINE = InnoDB"
            cursor.execute(query)
            query = "ALTER TABLE `timers` ADD PRIMARY KEY(`whentime`, `uid`)"
            cursor.execute(query)
            mydb.commit()

        query = "SHOW COLUMNS FROM `sensibo` LIKE 'watts'"
        cursor.execute(query)
        row = cursor.fetchone()
        if(not row):
            doLog("info", "Creating watts column...")
            query = "ALTER TABLE `sensibo` ADD `watts` FLOAT NOT NULL DEFAULT '0' AFTER `cost`"
            cursor.execute(query)
            mydb.commit()

        query = "SHOW COLUMNS FROM `sensibo` LIKE 'amps'"
        cursor.execute(query)
        row = cursor.fetchone()
        if(row):
            doLog("info", "Dropping amps column...")
            query = "ALTER TABLE `sensibo` DROP `amps`"
            cursor.execute(query)
            mydb.commit()

    except Exception as e:
        doLog("error", "There was a problem, error was %s" % e, True)
        pass

if __name__ == "__main__":
    os.system("")
    log = logging.getLogger('AtmoWiz Daemon')
    log.addHandler(JournalHandler(SYSLOG_IDENTIFIER='AtmoWiz Daemon'))
    log.setLevel(logging.DEBUG)
    doLog("info", "Daemon started....")
    if(_INVOCATION_ID):
        doLog("info", "Started by SystemD: Yes")
    else:
        doLog("info", "Started by SystemD: No")

    if(os.getuid() != 0 or os.getgid() != 0):
        doLog("error", "This program is designed to be started as root.", True)
        exit(1)

    parser = argparse.ArgumentParser(description='Daemon to collect data from Sensibo.com and store it locally in a MariaDB database.')
    parser.add_argument('-c', '--config', type = str, default='/etc/atmowiz.conf',
                        help='Path to config file, /etc/atmowiz.conf is the default')
    parser.add_argument('--reCalcCost', action='store_true', help='Recalc the cost of running the aircon after updating power prices')
    parser.add_argument('--reCalcFL', action='store_true', help='Recalc the feels like temperature')
    args = parser.parse_args()

    if(not os.path.exists(args.config) or not os.path.isfile(args.config)):
        doLog("error", "Config file %s doesn't exist." % args.config)
        exit(1)

    if(not os.access(args.config, os.R_OK)):
        doLog("error", "Config file %s isn't readable." % args.config)
        exit(1)

    configParser = configparser.ConfigParser(allow_no_value = True)
    configParser.read(args.config)
    apikey = configParser.get('sensibo', 'apikey', fallback = 'apikey')
    days = configParser.getint('sensibo', 'days', fallback = 1)

    weatherapikey = configParser.get('observations', 'weatherapikey', fallback = '')
    inigoURL = configParser.get('observations', 'inigoURL', fallback = '')
    urad_URL = configParser.get('observations', 'urad_URL', fallback = '')
    urad_userid = configParser.get('observations', 'urad_userid', fallback = '')
    urad_hash = configParser.get('observations', 'urad_hash', fallback = '')
    bomURL = configParser.get('observations', 'bomURL', fallback = '')
    metLocation = configParser.get('observations', 'metLocation', fallback = '')
    OWMapikey = configParser.get('observations', 'OWMapikey', fallback = '')
    doOpenMeteo = configParser.getboolean('observations', 'doOpenMeteo', fallback = True)

    costCurrentPort = configParser.get('power', 'costCurrentPort', fallback = None)

    hostname = configParser.get('mariadb', 'hostname', fallback = 'localhost')
    database = configParser.get('mariadb', 'database', fallback = 'atmowiz')
    username = configParser.get('mariadb', 'username', fallback = 'atmowiz')
    password = configParser.get('mariadb', 'password', fallback = 'password')

    uid = configParser.getint('system', 'uid', fallback = 0)
    gid = configParser.getint('system', 'gid', fallback = 0)
    country = configParser.get('system', 'country', fallback = 'None')

    peak = configParser.getfloat('cost', 'peak', fallback = 0.50)
    shoulder = configParser.getfloat('cost', 'shoulder', fallback = 0.50)
    offpeak = configParser.getfloat('cost', 'offpeak', fallback = 0.50)
    EER = configParser.getfloat('cost', 'EER', fallback = 3.0)
    COP = configParser.getfloat('cost', 'COP', fallback = 3.0)
    cool = configParser.getfloat('cost', 'cool', fallback = 5.0)
    heat = configParser.getfloat('cost', 'heat', fallback = 5.0)
    fankw = configParser.getfloat('cost', 'fankw', fallback = 0.050)
    offkw = configParser.getfloat('cost', 'offkw', fallback = 0.012)

    if(weatherapikey != ''):
        doOpenMeteo = False

    if(OWMapikey != ''):
        doOpenMeteo = False
        weatherapikey = ''

    if(metLocation != ''):
        doOpenMeteo = False
        OWMapikey = ''
        weatherapikey = ''

    if(bomURL != ''):
        doOpenMeteo = False
        metLocation = ''
        weatherapikey = ''

    if(inigoURL != ''):
        doOpenMeteo = False
        weatherapikey = ''
        metLocation = ''
        bomURL = ''

    if(days <= 0):
        days = 1

    fileuid = os.stat(args.config).st_uid
    filegid = os.stat(args.config).st_gid

    if(fileuid != 0 or filegid != 0):
        doLog("error", "The config file isn't owned by root, can't continue.")
        exit(1)

    if(os.stat(args.config).st_mode != 33152):
        doLog("error", "The config file isn't just rw as root, can't continue")
        exit(1)

    if(apikey == 'apikey' or apikey == '<apikey from sensibo.com>'):
        doLog("error", 'APIKEY is not set in config file.')
        exit(1)

    if(password == 'password' or password == '<password for local db>'):
        doLog("error", "DB Password is not set in the config file, can't continue")
        exit(1)

    if(uid == 0 or gid == 0):
        doLog("warning", "UID or GID is set to superuser, this is not recommended.")
    else:
        os.setgid(gid)
        os.setuid(uid)

    try:
        doLog("info", "User: %s" % pwd.getpwuid(uid)[0])
    except Exception as e:
        doLog("error", "User unavailable: %s" % e)

    try:
        doLog("info", "Group: %s" % grp.getgrgid(gid)[0])
    except Exception as e:
        doLog("error", "Group unavailable: %s" % e)

    try:
        groupList = os.getgroups()
        mygroups = []
        for group in groupList:
             mygroups.append(grp.getgrgid(group)[0])
        mygrouplist = ' '.join(mygroups)
        doLog("info", "Groups: %s" % mygrouplist)
    except Exception as e:
        doLog("error", "Group membership unavailable: %s" % e)

    fromfmt1 = '%Y-%m-%dT%H:%M:%S.%fZ'
    fromfmt2 = '%Y-%m-%dT%H:%M:%SZ'
    fmt = '%Y-%m-%d %H:%M:%S'
    from_zone = tz.tzutc()
    to_zone = tz.tzlocal()

    client = SensiboClientAPI(apikey)
    result = client.devices()
    devices = {x['room']['name']: x['id'] for x in result['result']}

    if(devices == None):
        doLog("error", "Unable to get a list of devices, check your internet connection and apikey and try again.")
        exit(1)

    try:
        mydb = MySQLdb.connect(hostname, username, password, database)
        updateDatabase(mydb)
    except MySQLdb._exceptions.ProgrammingError as e:
        doLog("error", "There was a problem, error was %s" % e, True)
        exit(1)
    except MySQLdb._exceptions.OperationalError as e:
        doLog("error", "There was a problem, error was %s" % e, True)
        exit(1)
    except MySQLdb._exceptions.IntegrityError as e:
        doLog("error", "There was a problem, error was %s" % e, True)
        exit(1)

    if(args.reCalcCost):
        try:
            cursor = mydb.cursor()
            query = "UPDATE sensibo SET cost=0.0"
            doLog("debug", query)
            cursor.execute(query)
            mydb.commit()
            calcCost(mydb)
            mydb.close()
            doLog("info", "Cost has been recalculated.")
            exit(0)
        except MySQLdb._exceptions.ProgrammingError as e:
            doLog("error", "There was a problem, error was %s" % e, True)
            exit(1)
        except MySQLdb._exceptions.OperationalError as e:
            doLog("error", "There was a problem, error was %s" % e, True)
            exit(1)
        except MySQLdb._exceptions.IntegrityError as e:
            doLog("error", "There was a problem, error was %s" % e, True)
            exit(1)

    if(args.reCalcFL):
        try:
            cursor = mydb.cursor()
            query = "UPDATE sensibo SET feelslike=-1"
            doLog("debug", query)
            cursor.execute(query)
            mydb.commit()
            calcFL(mydb, country)
            mydb.close()
            doLog("info", "Feels like has been recalculated.")
            exit(0)
        except MySQLdb._exceptions.ProgrammingError as e:
            doLog("error", "There was a problem, error was %s" % e, True)
            exit(1)
        except MySQLdb._exceptions.OperationalError as e:
            doLog("error", "There was a problem, error was %s" % e, True)
            exit(1)
        except MySQLdb._exceptions.IntegrityError as e:
            doLog("error", "There was a problem, error was %s" % e, True)
            exit(1)

    uidList = devices.values()

    try:
        cursor = mydb.cursor()
        for device in devices:
            values = (devices[device], device)
            doLog("debug", _sqlselect2 % values)
            cursor.execute(_sqlselect2, values)
            row = cursor.fetchone()
            if(row):
                continue

            doLog("debug", _sqlquery2 % values)
            cursor.execute(_sqlquery2, values)

        mydb.commit()

        cursor.execute("TRUNCATE meta")
        mydb.commit()

        for podUID in uidList:
            remoteCapabilities = client.pod_get_remote_capabilities(podUID)
            if(remoteCapabilities == None):
                continue

            remoteCapabilities = remoteCapabilities['result']
            if(remoteCapabilities == None or remoteCapabilities == []):
                continue

            _corf = remoteCapabilities[0]['device']['temperatureUnit']

            device = remoteCapabilities[0]['device']['remoteCapabilities']
            features = remoteCapabilities[0]['device']['features']
            if('plus' in features and 'showPlus' in features):
                _hasPlus = True

            query = "INSERT INTO meta (uid, mode, keyval, value) VALUES (%s, %s, %s, %s)"

            for mode in device['modes']:
                if(mode != "fan"):
                    for temp in device['modes'][mode]['temperatures'][_corf]['values']:
                        doLog("debug", query % (podUID, mode, 'temperatures', temp))
                        cursor.execute(query, (podUID, mode, 'temperatures', temp))

                for keyval in ['fanLevels', 'swing', 'horizontalSwing']:
                    for modes in device['modes'][mode][keyval]:
                        doLog("debug", query % (podUID, mode, keyval, modes))
                        cursor.execute(query, (podUID, mode, keyval, modes))

        mydb.commit()

        getLastCommands(mydb, 40)

        if(not _hasPlus and days > 1):
            days = 1

        doHistoricalMeasurements(mydb, days)
        calcCost(mydb)
        checkSettings(mydb)

    except MySQLdb._exceptions.ProgrammingError as e:
        doLog("error", "There was a problem, error was %s" % e, True)
        exit(1)
    except MySQLdb._exceptions.OperationalError as e:
        doLog("error", "There was a problem, error was %s" % e, True)
        exit(1)
    except MySQLdb._exceptions.IntegrityError as e:
        doLog("error", "There was a problem, error was %s" % e, True)
        exit(1)

    mydb.close()

    loops = 0

    getCurrentWeather(podUID)

    while True:
        try:
            mydb = MySQLdb.connect(hostname, username, password, database)
            doLog("debug", "Connection to mariadb accepted")
            secondsAgo = -1

            for podUID in uidList:
                pod_measurement = client.pod_all_stats(podUID, 1)
                if(pod_measurement == None):
                    continue

                pod_measurement = pod_measurement['result'][0]
                ac_state = pod_measurement['device']['acState']
                measurements = pod_measurement['device']['measurements']

                if(not validateValues(measurements['temperature'], measurements['humidity'])):
                    doLog("error", "Temp (%f) or Humidity (%d) out of bounds." % (measurements['temperature'], measurements['humidity']))
                    continue

                if(secondsAgo == -1):
                    #doLog("debug", "secondsAgo = %d" % measurements['time']['secondsAgo'])
                    secondsAgo = 90 - measurements['time']['secondsAgo']

                sstring = datetime.strptime(measurements['time']['time'], fromfmt1)
                utc = sstring.replace(tzinfo=from_zone)
                localzone = utc.astimezone(to_zone)
                sdate = localzone.strftime(fmt)
                values = (sdate, podUID)

                try:
                    cursor = mydb.cursor()
                    #doLog("debug", _sqlselect3 % values)
                    cursor.execute(_sqlselect3, values)
                    row = cursor.fetchone()
                    if(row):
                        #doLog("debug", "Skipping insert due to row already existing.")
                        continue

                    if(ac_state['mode'] == 'fan'):
                        ac_state['targetTemperature'] = None
                        ac_state['temperatureUnit'] = None

                    at = calcAT(measurements['temperature'], measurements['humidity'], country, measurements['feelsLike'])

                    values = (sdate, podUID, measurements['temperature'], measurements['humidity'],
                              at, measurements['rssi'], ac_state['on'],
                              ac_state['mode'], ac_state['targetTemperature'], ac_state['fanLevel'],
                              ac_state['swing'], ac_state['horizontalSwing'], getWatts())
                    doLog("debug", _sqlquery3 % values)
                    cursor.execute(_sqlquery3, values)
                    mydb.commit()
                except MySQLdb._exceptions.ProgrammingError as e:
                    doLog("error", "There was a problem, error was %s" % e, True)
                    pass
                except MySQLdb._exceptions.IntegrityError as e:
                    doLog("error", "There was a problem, error was %s" % e, True)
                    pass
                except MySQLdb._exceptions.OperationalError as e:
                    doLog("error", "There was a problem, error was %s" % e, True)
                    pass
                except MySQLdb._exceptions.DataError as e:
                    if(e.args[0] == 1265):
                        table_name = 'sensibo'
                        field = e.args[1].split("'")[1]
                        updateEnum(mydb, table_name, field)
                        doLog("debug", _sqlquery3 % values)
                        cursor.execute(_sqlquery3, values)
                        mydb.commit()
                        pass

            calcCost(mydb)

            getLastCommands(mydb, 5)
            checkSettings(mydb)

            loops += 1
            if(loops >= 40):
                loops = 0
                doHistoricalMeasurements(mydb, 1)

            if(secondsAgo <= 0):
                secondsAgo = 90
            if(secondsAgo > 90):
                secondsAgo = 90

            timeToWait = secondsAgo + random.randint(10, 20)

            doLog("debug", "Closing connection to MariaDB")
            mydb.close()
            doLog("debug", "Sleeping for %d seconds..." % timeToWait)
            time.sleep(timeToWait)
        except Exception as e:
            doLog("error", "There was a problem, error was %s" % e, True)
            exit(1)
