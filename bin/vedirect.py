#!/usr/bin/python
# -*- coding: utf-8 -*-

# Plus que largement inspiré de karioja : https://github.com/karioja/vedirect

import os, serial, sys

if len(sys.argv) < 1:
	print "Préciser le périphérique en argument"
	print "Exemple :"
	print "		python monscipt.py /dev/ttyUSB0)"
	exit()


class vedirect:

    def __init__(self, serialport):
        self.serialport = serialport
        self.ser = serial.Serial(serialport, 19200, timeout=10)
        self.header1 = '\r'
        self.header2 = '\n'
        self.delimiter = '\t'
        self.key = ''
        self.value = ''
        self.bytes_sum = 0;
        self.state = self.WAIT_HEADER
        self.dict = {}


    (WAIT_HEADER, IN_KEY, IN_VALUE, IN_CHECKSUM) = range(4)

    def input(self, byte):
        if self.state == self.WAIT_HEADER:
            self.bytes_sum += ord(byte)
            if byte == self.header1:
                self.state = self.WAIT_HEADER
            elif byte == self.header2:
                self.state = self.IN_KEY

            return None
        elif self.state == self.IN_KEY:
            self.bytes_sum += ord(byte)
            if byte == self.delimiter:
                if (self.key == 'Checksum'):
                    self.state = self.IN_CHECKSUM
                else:
                    self.state = self.IN_VALUE
            else:
                self.key += byte
            return None
        elif self.state == self.IN_VALUE:
            self.bytes_sum += ord(byte)
            if byte == self.header1:
                self.state = self.WAIT_HEADER
                print self.key + ":" + self.value;
                self.key = '';
                self.value = '';
            else:
                self.value += byte
            return None
        elif self.state == self.IN_CHECKSUM:
            self.bytes_sum += ord(byte)
            self.key = ''
            self.value = ''
            self.state = self.WAIT_HEADER
            if (self.bytes_sum % 256 == 0):
                self.bytes_sum = 0
                return self.dict
            else:
                self.bytes_sum = 0

        else:
            raise AssertionError()

    def read_data_single(self):
        while True:
            byte = self.ser.read(1)
            packet = self.input(byte)
            if (packet != None):
                return packet
            
if __name__ == '__main__':
    ve = vedirect(sys.argv[1])
    ve.read_data_single()
    
