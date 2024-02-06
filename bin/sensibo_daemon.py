#!/usr/bin/python3

import argparse
import configparser
import json
import logging
import math
import MySQLdb
import os
import random
import requests
import shutil
import sys
import time
import traceback
from datetime import datetime
from dateutil import tz
from systemd.journal import JournalHandler

_corf = "C"
_hasPlus = False
_SERVER = 'https://home.sensibo.com/api/v2'

_sqlquery1 = 'INSERT INTO commands (whentime, uid, reason, who, status, airconon, mode, targetTemperature, temperatureUnit, fanLevel, swing, horizontalSwing, changes) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)'
_sqlquery2 = 'INSERT INTO devices (uid, name) VALUES (%s, %s)'
_sqlquery3 = 'INSERT INTO sensibo (whentime, uid, temperature, humidity, feelslike, rssi, airconon, mode, targetTemperature, fanLevel, swing, horizontalSwing) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)'

_sqlselect1 = 'SELECT 1 FROM commands WHERE whentime=%s AND uid=%s'
_sqlselect2 = 'SELECT 1 FROM devices WHERE uid=%s AND name=%s'
_sqlselect3 = 'SELECT 1 FROM sensibo WHERE whentime=%s AND uid=%s'

_INVOCATION_ID = os.environ.get('INVOCATION_ID', False)

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

    def devices(self):
        result = self._get("/users/me/pods", fields="id,room")
        if(result == None):
            return None
        return {x['room']['name']: x['id'] for x in result['result']}

    def pod_all_stats(self, podUid, nb = 1):
        result = self._get("/pods/%s/acStates" % podUid, limit = nb, fields="device,feelsLike")
        if(result == None):
            return None
        return result['result']

    def pod_get_remote_capabilities(self, podUid, nb = 1):
        result = self._get("/pods/%s/acStates" % podUid, limit = nb, fields="device,remoteCapabilities,features")
        if(result == None):
            return None
        return result['result']

    def pod_status(self, podUid, lastlimit = 5):
        result = self._get("/pods/%s/acStates" % podUid, limit = lastlimit, fields="status,reason,time,acState,causedByUser,resultingAcState,changedProperties")
        if(result == None):
            return None

        try:
            return result['result']
        except Exception as e:
            doLog("error", result, True)
            return None

    def pod_get_past(self, podUid, days = 1):
        result = self._get("/pods/%s/historicalMeasurements" % podUid, days = days, fields="status,reason,time,acState,causedByUser")
        if(result == None):
            return None
        return result['result']

def calcAT(temp, humid, country, feelslike):
    if(feelslike != None and country == 'None'):
        return feelslike

    if(country == 'au'):
        doLog("info", "Using BoM Apparent Temp")
        # BoM's Feels Like formula -- http://www.bom.gov.au/info/thermal_stress/
        # UPDATE sensibo SET feelslike=round(temperature + (0.33 * ((humidity / 100) * 6.105 * exp((17.27 * temperature) / (237.7 + temperature)))) - 4, 1)
        if(_corf == 'F'):
            temp = (temp - 32) * 5 / 9
            doLog("info", "temp = %f" % temp)

        vp = (humid / 100.0) * 6.105 * math.exp((17.27 * temp) / (237.7 + temp))
        doLog("info", "vp = %f" % vp)
        at = round(temp + (0.33 * vp) - 4.0, 1)
        doLog("info", "at = %f" % at)
        return at
    else:
        # North American Heat Index -- https://www.wpc.ncep.noaa.gov/html/heatindex_equation.shtml
        doLog("info", "Using NA HI")
        if(_corf == 'C'):
            temp =  (temp * 9 / 5) + 32
            doLog("info", "temp = %f" % temp)


        if(temp <= 80 and temp >= 50):
            HI = 0.5 * (temp + 61.0 + ((temp - 68.0) * 1.2) + (humid * 0.094))
            if(_corf == 'C'):
                HI = (HI - 32) * 5 / 9

            doLog("info", "HI = %f" % HI)
            return HI
        elif(temp > 50):
            HI = -42.379 + 2.04901523 * temp + 10.14333127 * humid - .22475541 * temp * humid - .00683783 * temp * temp - .05481717 * humid * humid + .00122874 * temp * temp * humid + .00085282 * temp * humid * humid - .00000199 * temp * temp * humid * humid

            if(temp > 80 and humid < 13):
                HI -= ((13 - humid) / 4) * math.sqrt((17 - math.abs(temp - 95)) / 17)

            if(temp >= 80 and temp <= 87 and humid >= 85):
                HI += ((humid - 85) / 10) * ((87 - temp) / 5)

            if(_corf == 'C'):
                HI = (HI - 32) * 5 / 9

            doLog("info", "HI = %f" % HI)
            return HI
        else:
            if(_corf == 'C'):
                temp = (temp - 32) * 5 / 9

            WC = 35.74 + 0.6215 * temp
            if(_corf == 'C'):
                WC = (WC - 32) * 5 / 9

            doLog("info", "WC = %f" % WC)
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
    if(logType == 'info'):
        if(not _INVOCATION_ID):
            print (line)

        log.info(line)
        if(doStackTrace):
            print (full_stack())
            log.info(full_stack())
    else:
        if(not _INVOCATION_ID):
            print (line)

        log.error(line)
        if(doStackTrace):
            print (full_stack())
            log.error(full_stack())

def calcCost(mydb):
    try:
        cursor1 = mydb.cursor()
        cursor2 = mydb.cursor()

        query = "SELECT whentime, uid, DAYOFWEEK(whentime) as dow, HOUR(whentime) as hod FROM sensibo WHERE airconon=1 AND cost=0.0 AND mode='cool'"
        cursor1.execute(query)
        for (whentime, uid, dow, hod) in cursor1:
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

            query = "UPDATE sensibo SET cost=%s WHERE whentime=%s AND uid=%s"
            values = (cost, whentime, uid)
            doLog("info", query % values)
            cursor2.execute(query, values)

        query = "SELECT whentime, uid, DAYOFWEEK(whentime) as dow, HOUR(whentime) as hod FROM sensibo WHERE airconon=1 AND cost=0.0 AND mode='heat'"
        cursor1.execute(query)
        for (whentime, uid, dow, hod) in cursor1:
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

            query = "UPDATE sensibo SET cost=%s WHERE whentime=%s AND uid=%s"
            values = (cost, whentime, uid)
            doLog("info", query % values)
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

def calcFL(mydb):
    try:
        cursor1 = mydb.cursor()

        if(_corf == 'C' and country == 'au'):
            query = "UPDATE sensibo SET feelslike=round(temperature + (0.33 * ((humidity / 100) * 6.105 * exp((17.27 * temperature) / (237.7 + temperature)))) - 4, 1) WHERE feelslike=-1"
            cursor1.execute(query)
            mydb.commit()
            return
        else:
            query = "SELECT whentime, uid, temperature, humidity FROM sensibo WHERE feelslike=-1"
            doLog("info", query)
            cursor1.execute(query)
            cursor2 = mydb.cursor()
            for (whentime, uid, temp, humid) in cursor:
                at = calcAT(temp, humid, country, None)
                query = "UPDATE sensibo SET feelslike=%s WHERE whentime=%s AND uid=%s AND feelslike=-1"
                values = (at, whentime, uid)
                doLog("info", query % values)
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
    cursor = mydb.cursor()

    for podUID in uidList:
        past24 = client.pod_get_past(podUID, days)

        if(past24 == None):
            continue

        pod_measurement40 = client.pod_all_stats(podUID, 40)
        if(pod_measurement40 == None):
            continue

        rc = -1
        for i in range(len(past24['temperature']) - 1, 0, -1):
            rc += 1
            temp = past24['temperature'][i]['value']
            humid = past24['humidity'][i]['value']

            if(not validateValues(temp, humid)):
                doLog("error", "Temp (%f) or Humidity (%d) out of bounds." % (temp, humid))
                continue

            if(rc < len(pod_measurement40)):
                feelslike = pod_measurement40[rc]['device']['measurements']['feelsLike']
                rssi = pod_measurement40[rc]['device']['measurements']['rssi']
                airconon = pod_measurement40[rc]['device']['acState']['on']
                mode = pod_measurement40[rc]['device']['acState']['mode']
                targetTemperature = pod_measurement40[rc]['device']['acState']['targetTemperature']
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

            sstring = datetime.strptime(past24['temperature'][i]['time'], fromfmt2)
            utc = sstring.replace(tzinfo=from_zone)
            localzone = utc.astimezone(to_zone)
            sdate = localzone.strftime(fmt)
            values = (sdate, podUID)
            #doLog("info", _sqlselect3 % values)
            cursor.execute(_sqlselect3, values)
            row = cursor.fetchone()
            if(row):
                continue

            doLog("info", "rc = %d, i = %d" % (rc, i))
            doLog("info", past24['temperature'][i])
            doLog("info", past24['humidity'][i])

            at = calcAT(temp, humid, country, feelslike)
            values = (sdate, podUID, temp, humid, at, rssi, airconon, mode, targetTemperature, fanLevel, swing, horizontalSwing)
            doLog("info", _sqlquery3 % values)
            cursor.execute(_sqlquery3, values)
            mydb.commit()

def getLastCommands(mydb, nb = 5):
    for podUID in uidList:
        lastCommands = client.pod_status(podUID, nb)
        if(lastCommands == None):
            continue

        for last in lastCommands:
            try:
                sstring = datetime.strptime(last['time']['time'], fromfmt2)
                utc = sstring.replace(tzinfo=from_zone)
                localzone = utc.astimezone(to_zone)
                sdate = localzone.strftime(fmt)
                values = (sdate, podUID)
                #doLog("info", _sqlselect1 % values)
                cursor.execute(_sqlselect1, values)
                row = cursor.fetchone()
                if(row):
                    continue

                if(last['causedByUser'] == None):
                    last['causedByUser'] = {}
                    last['causedByUser']['firstName'] = 'Remote'

                acState = last['resultingAcState']
                changes = last['changedProperties']

                values = (sdate, podUID, last['reason'], last['causedByUser']['firstName'],
                          last['status'], acState['on'], acState['mode'], acState['targetTemperature'],
                          acState['temperatureUnit'], acState['fanLevel'], acState['swing'],
                          acState['horizontalSwing'], str(changes))
                doLog("info", _sqlquery1 % values)
                cursor.execute(_sqlquery1, values)
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


if __name__ == "__main__":
    log = logging.getLogger('Sensibo Daemon')
    log.addHandler(JournalHandler(SYSLOG_IDENTIFIER='Sensibo Daemon'))
    log.setLevel(logging.INFO)
    doLog("info", "Daemon started....")
    if(not _INVOCATION_ID):
        doLog("info", "Not started by SystemD")
    else:
        doLog("info", "Started by SystemD")

    if(os.getuid() != 0 or os.getgid() != 0):
        doLog("error", "This program is designed to be started as root.", True)
        exit(1)

    parser = argparse.ArgumentParser(description='Daemon to collect data from Sensibo.com and store it locally in a MariaDB database.')
    parser.add_argument('-c', '--config', type = str, default='/etc/sensibo.conf',
                        help='Path to config file, /etc/sensibo.conf is the default')
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
    hostname = configParser.get('mariadb', 'hostname', fallback = 'localhost')
    database = configParser.get('mariadb', 'database', fallback = 'sensibo')
    username = configParser.get('mariadb', 'username', fallback = 'sensibo')
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
        doLog("info", "UID or GID is set to superuser, this is not recommended.")
    else:
        os.setgid(gid)
        os.setuid(uid)

    fromfmt1 = '%Y-%m-%dT%H:%M:%S.%fZ'
    fromfmt2 = '%Y-%m-%dT%H:%M:%SZ'
    fmt = '%Y-%m-%d %H:%M:%S'
    from_zone = tz.tzutc()
    to_zone = tz.tzlocal()

    client = SensiboClientAPI(apikey)
    devices = client.devices()
    if(devices == None):
        doLog("error", "Unable to get a list of devices, check your internet connection and apikey and try again.")
        exit(1)

    try:
        mydb = MySQLdb.connect(hostname, username, password, database)
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
            doLog("info", query)
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
            doLog("info", query)
            cursor.execute(query)
            mydb.commit()
            mydb.close()
            calcFL()
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
            # doLog("info", _sqlselect2 % values)
            cursor.execute(_sqlselect2, values)
            row = cursor.fetchone()
            if(row):
                continue

            doLog("info", _sqlquery2 % values)
            cursor.execute(_sqlquery2, values)

        mydb.commit()

        cursor.execute("TRUNCATE meta")
        mydb.commit()

        for podUID in uidList:
            remoteCapabilities = client.pod_get_remote_capabilities(podUID)
            if(remoteCapabilities == None):
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
                        # doLog("info", query % (podUID, mode, 'temperatures', temp))
                        cursor.execute(query, (podUID, mode, 'temperatures', temp))

                for keyval in ['fanLevels', 'swing', 'horizontalSwing']:
                    for modes in device['modes'][mode][keyval]:
                        # doLog("info", query % (podUID, mode, keyval, modes))
                        cursor.execute(query, (podUID, mode, keyval, modes))

        mydb.commit()

        getLastCommands(mydb, 40)

        if(not _hasPlus and days > 1):
            days = 1

        doHistoricalMeasurements(mydb, days)
    except MySQLdb._exceptions.ProgrammingError as e:
        doLog("error", "There was a problem, error was %s" % e, True)
        exit(1)
    except MySQLdb._exceptions.OperationalError as e:
        doLog("error", "There was a problem, error was %s" % e, True)
        exit(1)
    except MySQLdb._exceptions.IntegrityError as e:
        doLog("error", "There was a problem, error was %s" % e, True)
        exit(1)

    calcCost(mydb)

    while True:
        try:
            doLog("info", "Connection to mariadb accepted")
            secondsAgo = -1
            loops = 0

            for podUID in uidList:
                pod_measurement = client.pod_all_stats(podUID, 1)
                if(pod_measurement == None):
                    continue

                pod_measurement = pod_measurement[0]
                ac_state = pod_measurement['device']['acState']
                measurements = pod_measurement['device']['measurements']

                if(not validateValues(measurements['temperature'], measurements['humidity'])):
                    doLog("error", "Temp (%f) or Humidity (%d) out of bounds." % (measurements['temperature'], measurements['humidity']))
                    continue

                sstring = datetime.strptime(measurements['time']['time'], fromfmt1)

                if(secondsAgo == -1):
                    #doLog("info", "secondsAgo = %d" % measurements['time']['secondsAgo'])
                    secondsAgo = 90 - measurements['time']['secondsAgo']
                utc = sstring.replace(tzinfo=from_zone)
                localzone = utc.astimezone(to_zone)
                sdate = localzone.strftime(fmt)
                values = (sdate, podUID)

                try:
                    cursor = mydb.cursor()
                    #doLog("info", _sqlselect3 % values)
                    cursor.execute(_sqlselect3, values)
                    row = cursor.fetchone()
                    if(row):
                        #doLog("info", "Skipping insert due to row already existing.")
                        continue

                    at = calcAT(measurements['temperature'], measurements['humidity'], country, measurements['feelsLike'])

                    values = (sdate, podUID, measurements['temperature'], measurements['humidity'],
                              at, measurements['rssi'], ac_state['on'],
                              ac_state['mode'], ac_state['targetTemperature'], ac_state['fanLevel'],
                              ac_state['swing'], ac_state['horizontalSwing'])
                    doLog("info", _sqlquery3 % values)
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

            calcCost(mydb)

            getLastCommands(mydb, 5)

            loops += 1

            if(loops >= 15):
                loops = 0
                doHistoricalMeasurements(mydb, 1)

            if(secondsAgo <= 0):
                secondsAgo = 90
            if(secondsAgo > 90):
                secondsAgo = 90

            timeToWait = secondsAgo + random.randint(10, 20)

            doLog("info", "Sleeping for %d seconds..." % timeToWait)
            time.sleep(timeToWait)
        except Exception as e:
            doLog("error", "There was a problem, error was %s" % e, True)
            exit(1)
