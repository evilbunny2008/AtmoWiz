#!/usr/bin/python3

import argparse
import configparser
import daemon
import json
import os
import pymysql.cursors
import requests
import shutil
import syslog
import time
from daemon import pidfile
from datetime import datetime
from dateutil import tz

_SERVER = 'https://home.sensibo.com/api/v2'

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
            syslog.syslog("Request1 failed with message %s" % exc)
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

    if(apikey == 'apikey'):
        print ('APIKEY is not set in config file.')
        exit(1)

    if(password == 'password'):
        print ("DB Password is not set in the config file, can't continue")
        exit(1)

    if(uid == 0 or gid == 0):
        print ("UID or GID is set to superuser, this is not recommended.")

    if(os.path.isfile(args.pidfile)):
        file = open(args.pidfile, 'r')
        file.seek(0)
        old_pid = int(file.readline())
        pid = os.getpid()
        if(pid != old_pid):
            print ("Sensibo daemon is already running with pid %d, and pidfile %s already exists, exiting..." %
                  (old_pid, args.pidfile))
        exit(1)
    else:
        mydir = os.path.dirname(os.path.abspath(args.pidfile))
        if(mydir == '/var/run'):
            print ("/var/run isn't a valid directory, can't continue.")
            exit(1)
        if(not os.path.isdir(mydir)):
            print ('Making %s and setting uid to %d and gid to %d' % (mydir, uid, gid))
            os.makedirs(mydir)
            shutil.chown(mydir, uid, gid)

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

    syslog.openlog(ident='Sensibo Daemon', logoption=syslog.LOG_PID, facility=syslog.LOG_DAEMON)
    context = daemon.DaemonContext(pidfile=pidfile.TimeoutPIDLockFile(args.pidfile), uid=uid, gid=gid)

    with context:
        while True:
            mydb = pymysql.connect(hostname, username, password, database, cursorclass=pymysql.cursors.DictCursor)
            syslog.syslog(syslog.LOG_INFO, "Connection to mariadb accepted")
            sql = """INSERT INTO sensibo (whentime, uid, temperature, humidity, feelslike, rssi, """ + \
                  """airconon, mode, targettemp, fanlevel, swing, horizontalswing) """ + \
                  """VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)"""
            start = time.time()

            for uid in uidList:
                pod_measurement = client.pod_all_stats(uid)
                if(pod_measurement == None):
                    continue

                ac_state = pod_measurement['result'][0]['device']['acState']
                measurements = pod_measurement['result'][0]['device']['measurements']
                sstring = datetime.strptime(measurements['time']['time'], fromfmt)
                utc = sstring.replace(tzinfo=from_zone)
                localzone = utc.astimezone(to_zone)
                sdate = localzone.strftime(fmt)
                query = """SELECT 1 FROM sensibo WHERE whentime=%s AND uid=%s"""
                values = (sdate, uid)
                syslog.syslog(syslog.LOG_INFO, query % values)

                with mydb:
                    with mydb.cursor() as cursor:
                        try:
                            cursor.execute(query, values)
                            row = cursor.fetchone()
                            if(row):
                                continue

                            values = (sdate, uid, measurements['temperature'], measurements['humidity'],
                                      measurements['feelsLike'], measurements['rssi'], ac_state['on'],
                                      ac_state['mode'], ac_state['targetTemperature'], ac_state['fanLevel'],
                                      ac_state['swing'], ac_state['horizontalSwing'])
                            cursor.execute(sql, values)
                            syslog.syslog(syslog.LOG_INFO, sql % values)
                        except pymysql.err.IntegrityError as e:
                            syslog.syslog(syslog.LOG_ERR, "Skipping insert as the row already exists.")
                            pass

            mydb.close()
            end = time.time()
            sleeptime = round(updatetime - (end - start), 1)
            syslog.syslog(syslog.LOG_INFO, "Sleeping for %s seconds..." % str(sleeptime))
            time.sleep(sleeptime)
