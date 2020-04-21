#!/usr/bin/env python3
# -*- coding: utf-8 -*-
#import pprint
# ~ import sys
import argparse
parser = argparse.ArgumentParser()
parser.add_argument("-d", "--debug", help="Print debug mod", action="store_true")
parser.add_argument("--vendor", help="WKS vendor id", default='0x0665')
parser.add_argument("--product", help="WKS product id", default='0x5161')
parser.add_argument("--interface", help="WKS interface", type=int, default=0)
args = parser.parse_args()
# ~ print(args.vendor)
# ~ print(args.product)
# ~ print(args.interface)
print('{"QPIRI": ["230.0", "13.0", "230.0", "50.0", "13.0", "3000", "3000", "48.0", "46.0", "42.0", "56.4", "53.0", "2", "15", "20", "1", "2", "2", "01", "1", "0", "54.0", "0"], "QPIGS": ["228.0", "50.0", "227.0", "50.0", "0917", "0897", "031", "476", "53.72", "001", "095", "0390", "0001", "059.6", "53.82", "00000", "10010111", "12", "04", "00052"], "QPIWS": ["0", "0", "0", "0", "0", "0", "0", "0", "0", "0", "0", "0", "0", "0", "0", "0", "0", "0", "0", "0", "0", "0", "0", "0", "0", "0", "0", "0", "0", "0", "0", "0"]}');
vendorId = 0x0665
# ~ print(type(vendorId))
# ~ print(vendorId)
