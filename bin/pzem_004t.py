#!/usr/bin/python3

#  Contrib de akoirium 

import pymodbus
import serial
import math

from pymodbus.pdu import ModbusRequest
from pymodbus.client.sync import ModbusSerialClient as ModbusClient
from pymodbus.transaction import ModbusRtuFramer

def calc (registers, factor):
    format = '%%0.%df' % int (math.ceil (math.log10 (factor)))
    if len(registers) == 1:
        return format % ((1.0 * registers[0]) / factor)
    elif len(registers) == 2:
        return format % (((1.0 * registers[1] * 65535) + (1.0 * registers[0])) / factor)
    #endif
#end calc

client = ModbusClient (method = "rtu", port="/dev/ttyUSB1", stopbits = 1, bytesize = 8, parity = 'N', baudrate = 9600)

#Connect to the serial modbus server
connection = client.connect()
if client.connect ():
    try:
        result = client.read_input_registers (0x0000, 10, unit = 0x01)
        print ('{', end='')
        print ('"V": ' + calc (result.registers[0:1], 10) + ', ', end='')
        print ('"A": ' + calc (result.registers[1:3], 1000) + ', ', end='')
        print ('"P": ' + calc (result.registers[3:5], 10) + ', ', end='')
        print ('"E": ' + calc (result.registers[5:7], 1) + ', ', end='')
        print ('"F": ' + calc (result.registers[7:8], 10) + ', ', end='')
        print ('"f": ' + calc (result.registers[8:9], 100) + ', ', end='')
        print ('"a": ' + calc (result.registers[9:10], 1), end='')
        print ('}')
    finally:
        client.close()
    #end try
#end if

