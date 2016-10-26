#!/bin/bash

###################################
# Script sous licence BEERWARE
# Version 0.1	2016
###################################

# A lancer toutes les heures par root et dans le rc.local
VEDIRECT_TTY_FIND="/dev/ttyUSB*"

USBBUS=`lsusb | grep Microdia |  awk '{ print $2 }'`
USBDEVICE=`lsusb | grep Microdia |  awk '{ print $4 }' | cut -d: -f1`
if [ -e "/dev/bus/usb/${USBBUS}/${USBDEVICE}" ]; then
	if [ "`stat -c %A /dev/bus/usb/${USBBUS}/${USBDEVICE}`" != "crw-rw-rw-" ] ; then
		chmod a+rw /dev/bus/usb/${USBBUS}/${USBDEVICE}
	fi
fi

if [ -e /dev/ttyACM0 ] ; then
	if [ "`stat -c %A /dev/ttyACM0`" != "crw-rw-rw-" ] ; then
		chmod a+rw /dev/ttyACM0
	fi
fi

for ttyTrouve in `ls $VEDIRECT_TTY_FIND`
do
	if [ "`stat -c %A ${ttyTrouve}`" != "crw-rw-rw-" ] ; then
		chmod a+rw ${ttyTrouve}
	fi
done
