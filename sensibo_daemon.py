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
import time
from datetime import datetime
from dateutil import tz
from systemd.journal import JournalHandler

_hasPlus = False
_SERVER = 'https://home.sensibo.com/api/v2'

_sqlquery1 = 'INSERT INTO commands (whentime, uid, reason, who, status, airconon, mode, targetTemperature, temperatureUnit, fanLevel, swing, horizontalSwing, changes) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)'
_sqlquery2 = 'INSERT INTO devices (uid, name) VALUES (%s, %s)'
_sqlquery3 = 'INSERT INTO sensibo (whentime, uid, temperature, humidity, feelslike, rssi, airconon, mode, targetTemperature, fanLevel, swing, horizontalSwing) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)'

_sqlselect1 = 'SELECT 1 FROM commands WHERE whentime=%s AND uid=%s'
_sqlselect2 = 'SELECT 1 FROM devices WHERE uid=%s AND name=%s'
_sqlselect3 = 'SELECT 1 FROM sensibo WHERE whentime=%s AND uid=%s'

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
            log.error("Request failed, full error messages hidden to protect the API key")
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
            log.error(result)
            log.error(full_stack())
            return None

    def pod_get_past_24hours(self, podUid, lastlimit = 1):
        result = self._get("/pods/%s/historicalMeasurements" % podUid, days = lastlimit, fields="status,reason,time,acState,causedByUser")
        if(result == None):
            return None
        return result['result']

def calcAT(temp, humid, country, feelslike):
    if(feelslike != None and country == 'None'):
        return feelslike

    if(country == 'au' or feelslike == None):
        # BoM's Feels Like formula -- http://www.bom.gov.au/info/thermal_stress/
        # UPDATE sensibo SET feelslike=round(temperature + (0.33 * ((humidity / 100) * 6.105 * exp((17.27 * temperature) / (237.7 + temperature)))) - 4, 1)
        vp = (humid / 100.0) * 6.105 * math.exp((17.27 * temp) / (237.7 + temp))
        log.info("vp = %f" % vp)
        at = round(temp + (0.33 * vp) - 4.0, 1)
        log.info("at = %f" % at)
        return at
    else:
        if(temp <= 80 and temp >= 50):
            HI = 0.5 * (temp + 61.0 + ((temp - 68.0) * 1.2) + (humid * 0.094))
        elif(temp > 50):
            # North American Heat Index -- https://www.wpc.ncep.noaa.gov/html/heatindex_equation.shtml
            HI = -42.379 + 2.04901523 * temp + 10.14333127 * humid - .22475541 * temp * humid - .00683783 * temp * temp - .05481717 * humid * humid + .00122874 * temp * temp * humid + .00085282 * temp * humid * humid - .00000199 * temp * temp * humid * humid

            if(temp > 80 and humid < 13):
                HI -= ((13 - humid) / 4) * math.sqrt((17 - math.abs(temp - 95)) / 17)

            if(temp >= 80 and temp <= 87 and humid >= 85):
                HI += ((humid - 85) / 10) * ((87 - temp) / 5)

            log.info("HI = %d" % HI)
            return HI
        else:
            WC = 35.74 + 0.6215 * temp
            log.info("WC = %d" % WC)
            return WC

def full_stack():
    import traceback, sys
    exc = sys.exc_info()[0]
    stack = traceback.extract_stack()[:-1]
    if exc is not None:
        del stack[-1]

    trc = 'Traceback (most recent call last):\n'
    stackstr = trc + ''.join(traceback.format_list(stack))
    if exc is not None:
         stackstr += '  ' + traceback.format_exc().lstrip(trc)

    return stackstr

if __name__ == "__main__":
    log = logging.getLogger('Sensibo Daemon')
    log.addHandler(JournalHandler(SYSLOG_IDENTIFIER='Sensibo Daemon'))
    log.setLevel(logging.INFO)
    log.info("Daemon started....")

    if(os.getuid() != 0 or os.getgid() != 0):
        log.error("This program is designed to be started as root.")
        exit(1)

    parser = argparse.ArgumentParser(description='Daemon to collect data from Sensibo.com and store it locally in a MariaDB database.')
    parser.add_argument('-c', '--config', type = str, default='/etc/sensibo.conf',
                        help='Path to config file, /etc/sensibo.conf is the default')
    args = parser.parse_args()

    if(not os.path.exists(args.config) or not os.path.isfile(args.config)):
        log.error("Config file %s doesn't exist." % args.config)
        exit(1)

    if(not os.access(args.config, os.R_OK)):
        log.error("Config file %s isn't readable." % args.config)
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
    country = configParser.get('system', 'country', fallback = 'au')

    if(days <= 0):
        days = 1

    fileuid = os.stat(args.config).st_uid
    filegid = os.stat(args.config).st_gid

    if(fileuid != 0 or filegid != 0):
        log.error("The config file isn't owned by root, can't continue.")
        exit(1)

    if(os.stat(args.config).st_mode != 33152):
        log.error("The config file isn't just rw as root, can't continue")
        exit(1)

    if(apikey == 'apikey' or apikey == '<apikey from sensibo.com>'):
        log.error('APIKEY is not set in config file.')
        exit(1)

    if(password == 'password' or password == '<password for local db>'):
        log.error("DB Password is not set in the config file, can't continue")
        exit(1)

    if(uid == 0 or gid == 0):
        log.info("UID or GID is set to superuser, this is not recommended.")
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
        log.error("Unable to get a list of devices, check your internet connection and apikey and try again.")
        exit(1)

    uidList = devices.values()

    try:
        mydb = MySQLdb.connect(hostname, username, password, database)
        cursor = mydb.cursor()
        for device in devices:
            values = (devices[device], device)
            # log.info(_sqlselect2 % values)
            cursor.execute(_sqlselect2, values)
            row = cursor.fetchone()
            if(row):
                continue

            log.info(_sqlquery2 % values)
            cursor.execute(_sqlquery2, values)
            mydb.commit()

        mydb.close()

        mydb = MySQLdb.connect(hostname, username, password, database)
        cursor = mydb.cursor()
        cursor.execute("TRUNCATE meta")
        mydb.commit()

        for podUID in uidList:
            remoteCapabilities = client.pod_get_remote_capabilities(podUID)
            if(remoteCapabilities == None):
                continue

            device = remoteCapabilities[0]['device']['remoteCapabilities']
            features = remoteCapabilities[0]['device']['features']
            if('plus' in features and 'showPlus' in features):
                _hasPlus = True

            corf = "C"
            if(device['modes']['cool']['temperatures']['F']['isNative'] == True):
                corf = "F"

            query = "INSERT INTO meta (uid, mode, keyval, value) VALUES (%s, %s, %s, %s)"

            for mode in ['cool', 'heat', 'dry', 'auto', 'fan']:
                if(mode != "fan"):
                    for temp in device['modes'][mode]['temperatures'][corf]['values']:
                        # log.info(query % (podUID, mode, 'temperatures', temp))
                        cursor.execute(query, (podUID, mode, 'temperatures', temp))

                for keyval in ['fanLevels', 'swing', 'horizontalSwing']:
                    for modes in device['modes'][mode][keyval]:
                        # log.info(query % (podUID, mode, keyval, modes))
                        cursor.execute(query, (podUID, mode, keyval, modes))

        mydb.commit()
        mydb.close()

        mydb = MySQLdb.connect(hostname, username, password, database)
        cursor = mydb.cursor()
        for podUID in uidList:
            last40 = client.pod_status(podUID, 40)

            if(last40 == None):
                continue

            for last in last40:
                sstring = datetime.strptime(last['time']['time'], fromfmt2)
                utc = sstring.replace(tzinfo=from_zone)
                localzone = utc.astimezone(to_zone)
                sdate = localzone.strftime(fmt)
                values = (sdate, podUID)
                #log.info(_sqlselect1 % values)
                cursor.execute(_sqlselect1, values)
                row = cursor.fetchone()
                if(row):
                    changes = last['changedProperties']
                    #log.info(changes)
                    query = "UPDATE commands SET changes=%s WHERE whentime=%s AND uid=%s AND changes=''"
                    values = (str(changes), sdate, podUID)
                    #log.info(query % values)
                    cursor.execute(query, values)
                    mydb.commit()
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
                log.info(_sqlquery1 % values)
                cursor.execute(_sqlquery1, values)
                mydb.commit()

        mydb.close()

        if(not _hasPlus and days > 1):
            days = 1

        mydb = MySQLdb.connect(hostname, username, password, database)
        cursor = mydb.cursor()
        for podUID in uidList:
            past24 = client.pod_get_past_24hours(podUID, days)

            if(past24 == None):
                continue

            pod_measurement40 = client.pod_all_stats(podUID, 40)
            if(pod_measurement40 == None):
                continue

            rc = 0
            for i in range(len(past24['temperature']) - 1, 0, -1):
                # print("rc = %d, i = %d" % (rc, i))
                temp = past24['temperature'][i]['value']
                humid = past24['humidity'][i]['value']

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

                sstring = datetime.strptime(past24['temperature'][i]['time'], '%Y-%m-%dT%H:%M:%SZ')
                utc = sstring.replace(tzinfo=from_zone)
                localzone = utc.astimezone(to_zone)
                sdate = localzone.strftime(fmt)
                values = (sdate, podUID)
                #log.info(_sqlselect3 % values)
                cursor.execute(_sqlselect3, values)
                row = cursor.fetchone()
                if(row):
                    continue

                at = calcAT(temp, humid, country, feelslike)
                values = (sdate, podUID, temp, humid, at, rssi, airconon, mode, targetTemperature, fanLevel, swing, horizontalSwing)
                log.info(_sqlquery3 % values)
                cursor.execute(_sqlquery3, values)
                mydb.commit()
                rc += 1

        mydb.close()
    except MySQLdb._exceptions.ProgrammingError as e:
        log.error("here was a problem, error was %s" % e)
        log.error(full_stack())
        exit(1)
    except MySQLdb._exceptions.OperationalError as e:
        log.error("There was a problem, error was %s" % e)
        log.error(full_stack())
        exit(1)
    except MySQLdb._exceptions.IntegrityError as e:
        log.error("There was a problem, error was %s" % e)
        log.error(full_stack())
        exit(1)

    while True:
        try:
            mydb = MySQLdb.connect(hostname, username, password, database)
            log.info("Connection to mariadb accepted")
            secondsAgo = -1;

            for podUID in uidList:
                pod_measurement = client.pod_all_stats(podUID, 1)
                if(pod_measurement == None):
                    continue

                pod_measurement = pod_measurement[0]
                ac_state = pod_measurement['device']['acState']
                measurements = pod_measurement['device']['measurements']
                sstring = datetime.strptime(measurements['time']['time'], fromfmt1)

                if(secondsAgo == -1):
                    #log.info("secondsAgo = %d" % measurements['time']['secondsAgo'])
                    secondsAgo = 90 - measurements['time']['secondsAgo']
                utc = sstring.replace(tzinfo=from_zone)
                localzone = utc.astimezone(to_zone)
                sdate = localzone.strftime(fmt)
                values = (sdate, podUID)

                try:
                    cursor = mydb.cursor()
                    #log.info(_sqlselect3 % values)
                    cursor.execute(_sqlselect3, values)
                    row = cursor.fetchone()
                    if(row):
                        #log.info("Skipping insert due to row already existing.")
                        continue

                    at = calcAT(measurements['temperature'], measurements['humidity'], country, measurements['feelsLike'])

                    values = (sdate, podUID, measurements['temperature'], measurements['humidity'],
                              at, measurements['rssi'], ac_state['on'],
                              ac_state['mode'], ac_state['targetTemperature'], ac_state['fanLevel'],
                              ac_state['swing'], ac_state['horizontalSwing'])
                    log.info(_sqlquery3 % values)
                    cursor.execute(_sqlquery3, values)
                    mydb.commit()
                except MySQLdb._exceptions.ProgrammingError as e:
                    log.error("There was a problem, error was %s" % e)
                    log.error(full_stack())
                    pass
                except MySQLdb._exceptions.IntegrityError as e:
                    log.error("There was a problem, error was %s" % e)
                    log.error(full_stack())
                    pass
                except MySQLdb._exceptions.OperationalError as e:
                    log.error("There was a problem, error was %s" % e)
                    log.error(full_stack())
                    pass

                last5 = client.pod_status(podUID, 5)
                if(last5 == None):
                    continue

                for last in last5:
                    if(last['reason'] == 'UserRequest'):
                        continue

                    try:

                        sstring = datetime.strptime(last['time']['time'], '%Y-%m-%dT%H:%M:%SZ')
                        utc = sstring.replace(tzinfo=from_zone)
                        localzone = utc.astimezone(to_zone)
                        sdate = localzone.strftime(fmt)
                        values = (sdate, podUID)
                        #log.info(_sqlselect1 % values)
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
                        log.info(_sqlquery1 % values)
                        cursor.execute(_sqlquery1, values)
                        mydb.commit()
                    except MySQLdb._exceptions.ProgrammingError as e:
                        log.error("There was a problem, error was %s" % e)
                        log.error(full_stack())
                        pass
                    except MySQLdb._exceptions.IntegrityError as e:
                        log.error("There was a problem, error was %s" % e)
                        log.error(full_stack())
                        pass
                    except MySQLdb._exceptions.OperationalError as e:
                        log.error("There was a problem, error was %s" % e)
                        log.error(full_stack())
                        pass

            mydb.close()

            if(secondsAgo <= 0):
                secondsAgo = 90
            if(secondsAgo > 90):
                secondsAgo = 90

            timeToWait = secondsAgo + random.randint(10, 20)

            log.info("Sleeping for %d seconds..." % timeToWait)
            time.sleep(timeToWait)
        except Exception as e:
            log.error("There was a problem, error was %s" % e)
            log.error(full_stack())
            exit(1)
