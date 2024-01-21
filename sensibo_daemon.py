#!/usr/bin/python3

import argparse
import configparser
import daemon
import json
import pymysql.cursors
import requests
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
    configParser = configparser.ConfigParser()
    configParser.read('/etc/fujitsu.conf')
    apikey = configParser.get('sensibo', 'apikey')
    hostname = configParser.get('mariadb', 'hostname', fallback='localhost')
    database = configParser.get('mariadb', 'database', fallback='fujitsu')
    username = configParser.get('mariadb', 'username', fallback='fujitsu')
    password = configParser.get('mariadb', 'password')
    uid = configParser.getint('system', 'uid', fallback=0)
    gid = configParser.getint('system', 'gid', fallback=0)

    parser = argparse.ArgumentParser(description='Sensibo client example parser')
    parser.add_argument('--logfile', type = str, default='/var/log/fujitsu/fujitsu.log',help='File to log output to')
    parser.add_argument('--pidfile', type = str, default='/var/run/fujitsu/fujitsu.pid',help='File to set the pid to')
    args = parser.parse_args()

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
        sql = """INSERT INTO fujitsu (whentime, uid, temperature, humidity, feelslike, rssi, airconon, mode, targettemp, fanlevel, swing, horizontalswing) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)"""

        for uid in uidList:
          pod_measurement = client.pod_measurement(uid)
          ac_state = pod_measurement[0]
          ac_state2 = client.pod_ac_state(uid)
          sstring = datetime.strptime(ac_state['time']['time'],'%Y-%m-%dT%H:%M:%S.%fZ')
          utc = sstring.replace(tzinfo=from_zone)
          localzone = utc.astimezone(to_zone)
          sdate = localzone.strftime(fmt)
          airconon = ac_state2['on']
          mode = ac_state2['mode']
          targettemp = ac_state2['targetTemperature']
          fanlevel = ac_state2['fanLevel']
          swing = ac_state2['swing']
          horizontalswing = ac_state2['horizontalSwing']

          with mydb:
            with mydb.cursor() as cursor:
              try:
                values = (sdate, uid, ac_state['temperature'], ac_state['humidity'], ac_state['feelsLike'], ac_state['rssi'], airconon, mode, targettemp, fanlevel, swing, horizontalswing)
                cursor.execute(sql, values)
                print (sql % values)
              except pymysql.err.IntegrityError as e:
                print ("Skipping insert as the row already exists.")
                pass
        mydb.close()
        time.sleep(60)
