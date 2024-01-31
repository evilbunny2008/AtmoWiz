#!/usr/bin/python3

import argparse
import configparser
import json
import os
import requests
from datetime import datetime
from dateutil import tz

_SERVER = 'https://home.sensibo.com/api/v2'

class SensiboClientAPI(object):
    def __init__(self, api_key):
        self._api_key = api_key

    def _get(self, path, ** params):
        params['apiKey'] = self._api_key
        response = requests.get(_SERVER + path, params = params)
        response.raise_for_status()
        return response.json()

    def _patch(self, path, data, ** params):
        params['apiKey'] = self._api_key
        response = requests.patch(_SERVER + path, params = params, data = data)
        response.raise_for_status()
        return response.json()

    def devices(self):
        result = self._get("/users/me/pods", fields="id,room")
        return {x['room']['name']: x['id'] for x in result['result']}

    def pod_measurement(self, podUid):
        result = self._get("/pods/%s/measurements" % podUid)
        return result['result']

    def pod_ac_state(self, podUid):
        result = self._get("/pods/%s/acStates" % podUid, limit = 1, fields="acState")
        return result['result'][0]['acState']

    def pod_last_ac_state(self, podUid, nb = 10):
        result = self._get("/pods/%s/acStates" % podUid, limit = nb, fields="status,reason,time,acState,causedByUser,causedByScheduleID,causedByScheduleType")
        return result['result']

    def pod_change_ac_state(self, podUid, currentAcState, propertyToChange, newValue):
        self._patch("/pods/%s/acStates/%s" % (podUid, propertyToChange),
                json.dumps({'currentAcState': currentAcState, 'newValue': newValue}))

def tempFromMeasurements(measurement):
    unitId=''
    if (args.unitC):
        if(not args.terse):
            unitId='C'
            return (str(measurement["temperature"]) + unitId )
        elif (args.unitF):
           if(not args.terse):
               unitId='F'
               return (str(round(measurement["temperature"]*(9/5)+32,1)) + unitId)
        elif (args.unitDual):
           if(not args.terse):
               unitId='F'
               unitId2='C'
               unitIdSep=' / '
           else:
               unitId2=''
               unitIdSep=' / '

           return (str(round(measurement["temperature"]*(9/5)+32,1)) + unitId + unitIdSep +str(measurement["temperature"]) + unitId2)


if __name__ == "__main__":
    apikey = ''

    if(os.path.isfile('/etc/sensibo.conf') and os.access('/etc/sensibo.conf', os.R_OK)):
        configParser = configparser.ConfigParser()
        configParser.read("/etc/sensibo.conf")
        apikey = configParser.get('sensibo', 'apikey', fallback='')

    parser = argparse.ArgumentParser(description='Sensibo client example parser')
    parser.add_argument('--apikey', type = str, help='Sensibo API Key', default='')
    parser.add_argument('--deviceName', type = str, help='Device name')
    parser.add_argument('--allDevices', action='store_true', help='Query all devices')
    parser.add_argument('--showState', action='store_true',help='Display the AC state')
    parser.add_argument('--togglePower', action='store_true',help='Toggle the AC power')
    parser.add_argument('--showMeasurements', action='store_true',help='Display the sensor measurements')
    parser.add_argument('--showTempMeasurement', action='store_true',help='Display the sensor temperature')
    parser.add_argument('--last', type=int, help='Display the last X of the AC, From 1 to 40')
    parser.add_argument('--unitF', action='store_true',help='Use Fahrenheit')
    parser.add_argument('--unitC', action='store_true',help='Use Celsius')
    parser.add_argument('--unitDual', action='store_true',help='Use F/C')
    parser.add_argument('--terse', action='store_true',help='Keep the response short')

    # AC settings
    parser.add_argument('--mode', type=str, help='Heat,Cool,Dry')
    parser.add_argument('--fanLevel', type=str,help='Fan Level low,medium,etc ')
    parser.add_argument('--targetTemp', type=int, help='set AC temp')
    parser.add_argument('--tempUnit', type=str, help='C or F')
    parser.add_argument('--verticalSwing', type=str, help='Vertical Swing Y')
    parser.add_argument('--horizontalSwing', type=str, help='Horizontal Swing Z')

    args = parser.parse_args()

    if(args.last and args.last < 1):
        args.last = 1
    if(args.last and args.last > 40):
        args.last = 40


    if(args.apikey == ''):
        if(apikey == ''):
            print ("You need to supply an API key, either in config file or on the command line.")
            parser.print_help()
            exit(1)
    else:
        apikey = args.apikey


    fmt = '%d/%m/%Y %H:%M:%S'
    from_zone = tz.tzutc()
    to_zone = tz.tzlocal()

    if(not args.unitF and not args.unitC and not args.unitDual):
        args.unitC=True

    client = SensiboClientAPI(apikey)
    try:
        devices = client.devices()
        print ("-" * 10, "devices", "-" * 10)
        print (devices)
    except requests.exceptions.RequestException as exc:
        print ("Request failed with message %s" % exc)
        exit(1)

    if(args.deviceName and not args.allDevices):
        uidList = [devices[args.deviceName]]
        deviceNameByUID = {devices[args.deviceName]:args.deviceName}
    elif(args.allDevices):
        uidList = devices.values()
        deviceNameByUID = {v:k for k,v in devices.items()}
    else:
        uidList = False

    #A specific device or all devices were requested
    if(uidList):
        #Default to showing AC state since no particular information request was given
        if(not args.showState and not args.showMeasurements and not args.showTempMeasurement and not args.last):
            args.showState=True

        try:
            for uid in uidList:
                if(args.terse and args.allDevices):
                    print (deviceNameByUID[uid])
                if(args.showState or args.togglePower):
                    ac_state = client.pod_ac_state(uid)
                if(args.showState):
                    not args.terse and print ("-" * 10, "AC State of %s" % deviceNameByUID[uid], "-" * 10)
                    print (ac_state)
                if(args.togglePower):
                    client.pod_change_ac_state(uid, ac_state, "on", not ac_state['on'])
                if(args.showMeasurements or args.showTempMeasurement):
                    pod_measurement = client.pod_measurement(uid)
                if(args.showMeasurements):
                    not args.terse and print ("-" * 10, "Measurement of %s" % deviceNameByUID[uid], "-" * 10)
                    ac_state = pod_measurement[0]
                    sstring = datetime.strptime(ac_state['time']['time'],'%Y-%m-%dT%H:%M:%S.%fZ')
                    utc = sstring.replace(tzinfo=from_zone)
                    localzone = utc.astimezone(to_zone)
                    sdate = localzone.strftime(fmt)
                    print ("Command executed at %(date)s : %(state)s" % { 'date' : sdate, 'state': ac_state})
                if(args.showTempMeasurement):
                    not args.terse and print ("-" * 10, "Temperature in %s" % deviceNameByUID[uid], "-" * 10)
                    ac_state = pod_measurement[0]
                    sstring = datetime.strptime(ac_state['time']['time'],'%Y-%m-%dT%H:%M:%S.%fZ')
                    utc = sstring.replace(tzinfo=from_zone)
                    localzone = utc.astimezone(to_zone)
                    sdate = localzone.strftime(fmt)
                    print ("Command executed at %(date)s : %(state)s" % { 'date' : sdate, 'state': tempFromMeasurements(pod_measurement[0])})
                if(args.last):
                    last_ac_state = client.pod_last_ac_state(uid, args.last)
                    for ac_state in last_ac_state:
                        sstring = datetime.strptime(ac_state['time']['time'],'%Y-%m-%dT%H:%M:%SZ')
                        utc = sstring.replace(tzinfo=from_zone)
                        localzone = utc.astimezone(to_zone)
                        sdate = localzone.strftime(fmt)
                        print ("Command executed at %(date)s : %(state)s -- Reason: %(reason)s, Status: %(status)s" % { 'date' : sdate, 'state': str(ac_state['acState']), 'reason': ac_state['reason'], 'status': ac_state['status']})
                        print (ac_state)

                if(args.fanLevel):
                    client.pod_change_ac_state(uid, ac_state, "fanLevel", args.fanLevel)
                    print("Fan",args.fanLevel)

                if(args.verticalSwing):
                    client.pod_change_ac_state(uid, ac_state, "swing", args.verticalSwing)
                    print("Vertical Swing",args.verticalSwing)  

                if(args.horizontalSwing):
                    client.pod_change_ac_state(uid, ac_state, "horizontalSwing", args.horizontalSwing)
                    print("Horizontal Swing",args.horizontalSwing) 

                if(args.mode):
                    client.pod_change_ac_state(uid, ac_state, "mode", args.mode)
                    print("Mode",args.mode)

                if(args.targetTemp):
                    client.pod_change_ac_state(uid, ac_state, "targetTemperature", args.targetTemp)
                    print("Target Temp",args.targetTemp)

                if(args.tempUnit):
                    client.pod_change_ac_state(uid, ac_state, "temperatureUnit", args.tempUnit)
                    print("Temp Unit",args.tempUnit)

        except requests.exceptions.RequestException as exc:
            print ("Request failed with message %s" % exc)
            exit(1)
