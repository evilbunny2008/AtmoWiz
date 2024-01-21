#!/usr/bin/python3

import argparse
import configparser
import daemon
import json
import os
import pymysql.cursors
import requests
import shutil
import sys
import time
from daemon import pidfile
from datetime import datetime
from dateutil import tz
from pathlib import Path

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
          print ("Request1 failed with message %s" % exc)
          exit(1)

    def devices(self):
        result = self._get("/users/me/pods", fields="id,room")
        return {x['room']['name']: x['id'] for x in result['result']}

    def pod_ac_state(self, podUid):
        result = self._get("/pods/%s/acStates" % podUid, limit = 1, fields="acState")
        return result['result'][0]['acState']

    def pod_measurement(self, podUid):
        result = self._get("/pods/%s/measurements" % str(podUid))
        return result['result']

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description='Sensibo client example parser')
    parser.add_argument('-c', '--config', type = str, default='/etc/fujitsu.conf',help='Path to config file, /etc/fujitsu.conf is the default')
    parser.add_argument('--logfile', type = str, default='/var/log/fujitsu/fujitsu.log',help='File to log output to, /var/log/fujitsu/fujitsu.log is the default')
    parser.add_argument('--pidfile', type = str, default='/var/run/fujitsu/fujitsu.pid',help='File to set the pid to, /var/run/fujitsu/fujitsu.pid is the default')
    args = parser.parse_args()

    configParser = configparser.ConfigParser()
    configParser.read(args.config)
    apikey = configParser.get('sensibo', 'apikey', fallback='apikey')
    hostname = configParser.get('mariadb', 'hostname', fallback='localhost')
    database = configParser.get('mariadb', 'database', fallback='fujitsu')
    username = configParser.get('mariadb', 'username', fallback='fujitsu')
    password = configParser.get('mariadb', 'password', fallback='password')
    uid = configParser.getint('system', 'uid', fallback=0)
    gid = configParser.getint('system', 'gid', fallback=0)

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
        print ("%d is running and %s already exists, exiting" % (pid, args.pidfile))
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

    if(not os.path.isfile(args.logfile)):
      mydir = os.path.dirname(os.path.abspath(args.logfile))
      if(mydir == '/var/log'):
        print ("/var/log isn't a valid directory, can't continue.")
        exit(1)
      if(not os.path.isdir(mydir)):
        print ('Making %s and setting uid to %d and gid to %d' % (mydir, uid, gid))
        os.makedirs(mydir)
        shutil.chown(mydir, uid, gid)

    updatetime = 89
    fromfmt = '%Y-%m-%dT%H:%M:%S.%fZ'
    fmt = '%Y-%m-%d %H:%M:%S'
    from_zone = tz.tzutc()
    to_zone = tz.tzlocal()

    client = SensiboClientAPI(apikey)
    devices = client.devices()

    uidList = devices.values()
    deviceNameByUID = {v:k for k,v in devices.items()}

    logfile = open(args.logfile, 'a')
    context = daemon.DaemonContext(stdout = logfile, stderr = logfile, pidfile=pidfile.TimeoutPIDLockFile(args.pidfile), uid=uid, gid=gid)

    with context:
      while True:
        mydb = pymysql.connect(hostname, username, password, database, cursorclass=pymysql.cursors.DictCursor)
        sql = """INSERT INTO fujitsu (whentime, uid, temperature, humidity, feelslike, rssi, airconon, mode, targettemp, fanlevel, swing, horizontalswing) 
                        VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)"""
        start = time.time()

        for uid in uidList:
          pod_measurement = client.pod_measurement(uid)
          ac_state = pod_measurement[0]
          ac_state2 = client.pod_ac_state(uid)
          sstring = datetime.strptime(ac_state['time']['time'], fromfmt)
          utc = sstring.replace(tzinfo=from_zone)
          localzone = utc.astimezone(to_zone)
          sdate = localzone.strftime(fmt)

          with mydb:
            with mydb.cursor() as cursor:
              try:
                values = (sdate, uid, ac_state['temperature'], ac_state['humidity'], ac_state['feelsLike'], ac_state['rssi'], ac_state2['on'], ac_state2['mode'],
                                      ac_state2['targetTemperature'], ac_state2['fanLevel'], ac_state2['swing'], ac_state2['horizontalSwing'])
                cursor.execute(sql, values)
                print (sql % values)
              except pymysql.err.IntegrityError as e:
                print ("Skipping insert as the row already exists.")
                pass
        mydb.close()
        end = time.time()
        sleeptime = round(updatetime - (end - start), 1)
        print ("Sleeping for %s seconds..." % str(sleeptime))
        time.sleep(sleeptime)
