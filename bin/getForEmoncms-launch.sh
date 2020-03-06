#!/bin/bash

# Interval d'exécution 
if [ $1 > 0 ]; then
    sleepInterval=$(($1 * 60))
else 
    sleepInterval=$((5 * 60))
fi

while (true); do
    su - pvmonit -c '/usr/bin/php /opt/PvMonit/getForEmoncms.php'
    sleep $sleepInterval
done
