#!/usr/bin/python3

import serial
import xmltodict
import json

with serial.Serial() as ser:
    ser.baudrate = 57600
    ser.port = '/dev/ttyUSB1'
    ser.open()

    line = ser.readline()
    print(line)
#	line = str(line, 'ascii').strip()
#        line = xmltodict.parse(line)
#        amps = (round((int(line['msg']['ch1']['watts']) - 199) / 230, 2))

