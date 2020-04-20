#!/bin/bash

# Si l'utilisateur à déposé un fichier wpa_supplicant.conf dans boot
if [ -f /boot/wpa_supplicant.conf ]; then
	fs_mode=$(mount | sed -n -e "s/^.* on \/ .*(\(r[w|o]\).*/\1/p")
	if [ "$fs_mode" == "ro" ]; then
		/bin/mount -o remount,rw /
	fi
	cat /boot/wpa_supplicant.conf > /etc/wpa_supplicant/wpa_supplicant.conf
	rm /boot/wpa_supplicant.conf 
	if ! (($?)); then
		/sbin/reboot
	fi
fi
