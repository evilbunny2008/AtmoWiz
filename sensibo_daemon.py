#!/usr/bin/python3

import argparse
import configparser
import daemon
import json
import MySQLdb
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

    def pod_measurement(self, podUid):
        result = self._get("/pods/%s/measurements" % str(podUid))
        return result['result']

if __name__ == "__main__":
    configParser = configparser.ConfigParser()
    configParser.read('/etc/fujitsu.conf')
    apikey = configParser.get('sensibo', 'apikey')
    hostname = configParser.get('mariadb', 'hostname')
    database = configParser.get('mariadb', 'database')
    username = configParser.get('mariadb', 'username')
    password = configParser.get('mariadb', 'password')

    parser = argparse.ArgumentParser(description='Sensibo client example parser')
    parser.add_argument('--logfile', type = str, default='/var/log/fujitsu.log',help='File to log output to')
    parser.add_argument('--pidfile', type = str, default='/var/run/fujitsu/fujitsu.pid',help='File to set the pid to')
    args = parser.parse_args()

    fmt = '%Y-%m-%d %H:%M:%S'
    from_zone = tz.tzutc()
    to_zone = tz.tzlocal()

    client = SensiboClientAPI(apikey)
    devices = client.devices()

    logfile = open(args.logfile, 'a')
    context = daemon.DaemonContext(stdout = logfile, stderr = logfile, pidfile=pidfile.TimeoutPIDLockFile(args.pidfile))

    mydb = MySQLdb.connect(hostname, username, password, database)

    uidList = devices.values()
    deviceNameByUID = {v:k for k,v in devices.items()}
    with context:
      while True:
        for uid in uidList:
          pod_measurement = client.pod_measurement(uid)
          ac_state = pod_measurement[0]
          sstring = datetime.strptime(ac_state['time']['time'],'%Y-%m-%dT%H:%M:%S.%fZ')
          utc = sstring.replace(tzinfo=from_zone)
          localzone = utc.astimezone(to_zone)
          sdate = localzone.strftime(fmt)
          c = mydb.cursor()
          c.execute("INSERT INTO fujitsu (whentime, uid, temperature, humidity, feelslike, rssi) VALUES (%s, %s, %s, %s, %s, %s)", (sdate, uid, ac_state['temperature'], ac_state['humidity'], ac_state['feelsLike'], ac_state['rssi'], ))
        time.sleep(60)
