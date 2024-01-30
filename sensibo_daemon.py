#!/usr/bin/python3

import argparse
import configparser
import daemon
import json
import MySQLdb
import os
import requests
import shutil
import syslog
import time
from daemon import pidfile
from datetime import datetime
from dateutil import tz

_SERVER = 'https://home.sensibo.com/api/v2'
_sql = 'INSERT INTO sensibo (whentime, uid, temperature, humidity, feelslike, rssi, airconon, mode, targettemp, fanlevel, swing, horizontalswing) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)'
_sqlquery = 'INSERT INTO commands (whentime, uid, reason, status, airconon, mode, targetTemperature, temperatureUnit, fanLevel, swing, horizontalSwing) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)'
_sqldevices = 'INSERT INTO devices (uid, name) VALUES (%s, %s)'

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
            syslog.syslog("Request failed, full error messages hidden to protect the API key")
            return None

    def devices(self):
        result = self._get("/users/me/pods", fields="id,room")
        if(result == None):
            return None
        return {x['room']['name']: x['id'] for x in result['result']}

    def pod_all_stats(self, podUid):
        result = self._get("/pods/%s/acStates" % podUid, limit = 1, fields="device")
        if(result == None):
            return None
        return result

    def pod_status(self, podUid, lastlimit = 5):
        result = self._get("/pods/%s/acStates" % podUid, limit = lastlimit, fields="status,reason,time,acState")
        if(result == None):
            return None
        return result['result']

    def pod_get_past_24hours(self, podUid, lastlimit = 1):
        result = self._get("/pods/%s/historicalMeasurements" % podUid, days = lastlimit)
        if(result == None):
            return None
        return result['result']

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description='Daemon to collect data from Sensibo.com and store it locally in a MariaDB database.')
    parser.add_argument('-c', '--config', type = str, default='/etc/sensibo.conf',
                        help='Path to config file, /etc/sensibo.conf is the default')
    parser.add_argument('--pidfile', type = str, default='/var/run/sensibo/sensibo.pid',
                        help='File to set the pid to, /var/run/sensibo/sensibo.pid is the default')
    args = parser.parse_args()

    configParser = configparser.ConfigParser()
    configParser.read(args.config)
    apikey = configParser.get('sensibo', 'apikey', fallback='apikey')
    hostname = configParser.get('mariadb', 'hostname', fallback='localhost')
    database = configParser.get('mariadb', 'database', fallback='sensibo')
    username = configParser.get('mariadb', 'username', fallback='sensibo')
    password = configParser.get('mariadb', 'password', fallback='password')
    uid = configParser.getint('system', 'uid', fallback=0)
    gid = configParser.getint('system', 'gid', fallback=0)

    fileuid = os.stat(args.config).st_uid
    filegid = os.stat(args.config).st_gid

    if(fileuid != 0 or filegid != 0):
        print ("The config file isn't owned by root, can't continue.")
        exit(1)

    if(os.stat(args.config).st_mode != 33152):
        print ("The config file isn't just rw as root, can't continue")
        exit()

    if(apikey == 'apikey' or apikey == '<apikey from sensibo.com>'):
        print ('APIKEY is not set in config file.')
        exit(1)

    if(password == 'password' or password == '<password for local db>'):
        print ("DB Password is not set in the config file, can't continue")
        exit(1)

    if(uid == 0 or gid == 0):
        print ("UID or GID is set to superuser, this is not recommended.")

    updatetime = 90
    fromfmt = '%Y-%m-%dT%H:%M:%S.%fZ'
    fmt = '%Y-%m-%d %H:%M:%S'
    from_zone = tz.tzutc()
    to_zone = tz.tzlocal()

    client = SensiboClientAPI(apikey)
    devices = client.devices()
    if(devices == None):
        print ("Unable to get a list of devices, check your internet connection and apikey and try again.")
        exit(1)

    uidList = devices.values()

    try:
        mydb = MySQLdb.connect(hostname, username, password, database)
        cursor = mydb.cursor()
        for device in devices:
            values = (devices[device], device)
            cursor.execute(_sqlselect2, values)
            row = cursor.fetchone()
            if(row):
                continue

            cursor.execute(_sqldevices, values)
            mydb.commit()

        mydb.close()

        mydb = MySQLdb.connect(hostname, username, password, database)
        cursor = mydb.cursor()
        for podUID in uidList:
            last40 = client.pod_status(podUID, 40)
            if(last40 == None):
                continue

            for last in last40:
                if(last['reason'] == 'UserRequest'):
                    continue

                sstring = datetime.strptime(last['time']['time'], '%Y-%m-%dT%H:%M:%SZ')
                utc = sstring.replace(tzinfo=from_zone)
                localzone = utc.astimezone(to_zone)
                sdate = localzone.strftime(fmt)
                values = (sdate, podUID)
                cursor.execute(_sqlselect1, values)
                row = cursor.fetchone()
                if(row):
                    query = "UPDATE commands SET targetTemperature=%s, temperatureUnit=%s, fanLevel=%s, swing=%s, horizontalSwing=%s WHERE whentime=%s AND uid=%s AND targetTemperature=0"
                    values = (last['acState']['targetTemperature'], last['acState']['temperatureUnit'], last['acState']['fanLevel'], last['acState']['swing'], last['acState']['horizontalSwing'], sdate, podUID)
                    cursor.execute(query, values)
                    mydb.commit()
                    continue

                values = (sdate, podUID, last['reason'], last['status'], last['acState']['on'], last['acState']['mode'], last['acState']['targetTemperature'],
                          last['acState']['temperatureUnit'], last['acState']['fanLevel'], last['acState']['swing'], last['acState']['horizontalSwing'])
                cursor.execute(_sqlquery, values)
                mydb.commit()

        mydb.close()

        mydb = MySQLdb.connect(hostname, username, password, database)
        cursor = mydb.cursor()
        for podUID in uidList:
            past24 = client.pod_get_past_24hours(podUID, 1)
            if(past24 == None):
                continue

            for i in range(len(past24['temperature']) - 1):
                temp = past24['temperature'][i]['value']
                humid = past24['humidity'][i]['value']
                sstring = datetime.strptime(past24['temperature'][i]['time'], '%Y-%m-%dT%H:%M:%SZ')
                utc = sstring.replace(tzinfo=from_zone)
                localzone = utc.astimezone(to_zone)
                sdate = localzone.strftime(fmt)
                values = (sdate, podUID)
                cursor.execute(_sqlselect3, values)
                row = cursor.fetchone()
                if(row):
                    continue

                values = (sdate, podUID, temp, humid, temp, 0, 0, 'cool', 0, 'medium', 'fixedTop', 'fixedCenter')
                cursor.execute(_sql, values)
                mydb.commit()

        mydb.close()
    except MySQLdb._exceptions.ProgrammingError as e:
        print ("here was a problem, error was %s" % e)
        exit(1)
    except MySQLdb._exceptions.OperationalError as e:
        print ("There was a problem, error was %s" % e)
        exit(1)
    except MySQLdb._exceptions.IntegrityError as e:
        print ("There was a problem, error was %s" % e)
        exit(1)

    if(os.path.isfile(args.pidfile)):
        file = open(args.pidfile, 'r')
        file.seek(0)
        old_pid = int(file.readline())
        pid = os.getpid()
        if(pid != old_pid):
            print ("Sensibo daemon is already running with pid %d, and pidfile %s already exists, exiting..." %
                  (old_pid, args.pidfile))
        exit(1)

    mydir = os.path.dirname(os.path.abspath(args.pidfile))
    if(mydir == '/var/run'):
        print ("/var/run isn't a valid directory, can't continue.")
        exit(1)
    if(not os.path.isdir(mydir)):
        print ('Making %s' % mydir)
        os.makedirs(mydir)

    if(os.path.isdir(mydir)):
        diruid = os.stat(mydir).st_uid
        dirgid = os.stat(mydir).st_gid
        if(uid != diruid or gid != dirgid):
            print ('Setting %s uid to %d and gid to %d' % (mydir, uid, gid))
            shutil.chown(mydir, uid, gid)

    logfile = open('/tmp/sensibo.log', 'a')
    context = daemon.DaemonContext(stdout = logfile, stderr = logfile, pidfile=pidfile.TimeoutPIDLockFile(args.pidfile), uid=uid, gid=gid)

    with context:
        syslog.openlog(facility=syslog.LOG_DAEMON)

        while True:
            mydb = MySQLdb.connect(hostname, username, password, database)
            syslog.syslog("Connection to mariadb accepted")
            start = time.time()

            for podUID in uidList:
                pod_measurement = client.pod_all_stats(podUID)
                if(pod_measurement == None):
                    continue

                ac_state = pod_measurement['result'][0]['device']['acState']
                measurements = pod_measurement['result'][0]['device']['measurements']
                sstring = datetime.strptime(measurements['time']['time'], fromfmt)
                utc = sstring.replace(tzinfo=from_zone)
                localzone = utc.astimezone(to_zone)
                sdate = localzone.strftime(fmt)
                values = (sdate, podUID)

                try:
                    cursor = mydb.cursor()
                    cursor.execute(_sqlselect3, values)
                    row = cursor.fetchone()
                    if(row):
                        syslog.syslog("Skipping insert due to row already existing.")
                        continue

                    values = (sdate, podUID, measurements['temperature'], measurements['humidity'],
                              measurements['feelsLike'], measurements['rssi'], ac_state['on'],
                              ac_state['mode'], ac_state['targetTemperature'], ac_state['fanLevel'],
                              ac_state['swing'], ac_state['horizontalSwing'])
                    cursor.execute(_sql, values)
                    syslog.syslog(_sql % values)
                    mydb.commit()

                    last5 = client.pod_status(podUID, 5)
                    if(last5 == None):
                        continue

                    for last in last5:
                        if(last['reason'] == 'UserRequest'):
                            continue

                        sstring = datetime.strptime(last['time']['time'], '%Y-%m-%dT%H:%M:%SZ')
                        utc = sstring.replace(tzinfo=from_zone)
                        localzone = utc.astimezone(to_zone)
                        sdate = localzone.strftime(fmt)
                        values = (sdate, podUID)
                        cursor.execute(_sqlselect1, values)
                        row = cursor.fetchone()
                        if(row):
                            continue

                        values = (sdate, podUID, last['reason'], last['status'], last['acState']['on'], last['acState']['mode'], last['acState']['targetTemperature'],
                                  last['acState']['temperatureUnit'], last['acState']['fanLevel'], last['acState']['swing'], last['acState']['horizontalSwing'])
                        cursor.execute(_sqlquery, values)
                        mydb.commit()
                        syslog.syslog(_sqlquery % values)
                except MySQLdb._exceptions.ProgrammingError as e:
                    syslog.syslog("There was a problem, error was %s" % e)
                    pass
                except MySQLdb._exceptions.IntegrityError as e:
                    syslog.syslog("There was a problem, error was %s" % e)
                    pass
                except MySQLdb._exceptions.OperationalError as e:
                    syslog.syslog("There was a problem, error was %s" % e)
                    pass

            mydb.close()
            end = time.time()
            sleeptime = round(updatetime - (end - start), 1)
            syslog.syslog("Sleeping for %s seconds..." % str(sleeptime))
            time.sleep(sleeptime)
