#!/usr/bin/env python
# -*- coding: utf-8 -*-
import usb.core, usb.util, usb.control
import crc16
#import pprint
import json
import re
import argparse

# Question testeur : 
# - le script met du temps à s'exécuter ?
# - 

##### Config ###
vendorId = 0x0665
productId = 0x5161
interface = 0
################

jsonOut = {}


def serial2json(serial):
	data = []
	serial=serial[1:]
	serialSplit=serial.split()
	for val in serialSplit:
		if re.match(r"^([0-9]+)\.?([0-9]+)?$", val):
			data.append(val)
	return data

def serial2jsonConcat(serial):
	data = []
	serial=serial[1:]
	for i in range(0, len(serial)):
		nextI=i+1
		# ~ print(serial[i:nextI])
		if re.match(r"^([0-9]+)\.?([0-9]+)?$", serial[i:nextI]):
			data.append(serial[i:nextI])
	return data

def getCommand(cmd):
    cmd = cmd.encode('utf-8')
    crc = crc16.crc16xmodem(cmd).to_bytes(2,'big')
    cmd = cmd+crc
    cmd = cmd+b'\r'
    while len(cmd)<8:
        cmd = cmd+b'\0'
    return cmd

def sendCommand(cmd):
    dev.ctrl_transfer(0x21, 0x9, 0x200, 0, cmd)

def getResult(timeout=100):
    res=""
    i=0
    while '\r' not in res and i<20:
        try:
            res+="".join([chr(i) for i in dev.read(0x81, 8, timeout) if i!=0x00])
        except usb.core.USBError as e:
            if e.errno == 110:
                pass
            else:
                raise
        i+=1
    return res



parser = argparse.ArgumentParser()
parser.add_argument("-d", "--debug", help="Print debug mod", action="store_true")
args = parser.parse_args()

dev = usb.core.find(idVendor=vendorId, idProduct=productId)
if dev.is_kernel_driver_active(interface):
    dev.detach_kernel_driver(interface)
dev.set_interface_altsetting(0,0)

sendCommand(getCommand('QPIRI'))
res = getResult()
if args.debug:
    print('QPIRI : ')
    print(res)
else:
    jsonOut["QPIRI"] = serial2json(res)

sendCommand(getCommand('QPIGS'))
res = getResult()
if args.debug:
    print('QPIGS : ')
    print(res)
else:
    jsonOut["QPIGS"] = serial2json(res)

sendCommand(getCommand('QPIWS'))
res = getResult()
if args.debug:
    print('QPIWS : ')
    print(res)
else:
    jsonOut["QPIWS"] = serial2jsonConcat(res)

if args.debug:
    print('End script')
else:
    print(json.dumps(jsonOut))
