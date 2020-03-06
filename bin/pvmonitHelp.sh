#!/bin/bash

SSHHOST="pvmonithelp@pvmonithelp.zici.fr"
SSHPORT=22222
SSHPWD="kcbhVGG4!"
SSHIDRSA='/home/pvmonit/.ssh/id_rsa_help'
SSHPORTLOCAL=22
SSHPORTDISTANT=222$(( ( RANDOM % 9 )  + 1 ))$(( ( RANDOM % 9 )  + 1 ))

# Si la clef RSA existe on l'utilise
if [ -f '${SSHIDRSA}' ]; then
	ssh -T -t -t -o "UserKnownHostsFile=/dev/null"  -o "StrictHostKeyChecking=no" -i ${SSHIDRSA} ${SSHHOST} -p ${SSHPORT}  -R ${SSHPORTDISTANT}:localhost:${SSHPORTLOCAL}
elif [ -f '/usr/bin/sshpass' ]; then
	sshpass -p ${SSHPWD} ssh -T  -t -t -o "UserKnownHostsFile=/dev/null"  -o "StrictHostKeyChecking=no" ${SSHHOST} -p ${SSHPORT}  -R ${SSHPORTDISTANT}:localhost:${SSHPORTLOCAL}
else
	echo -n "1"
	exit 1
fi


