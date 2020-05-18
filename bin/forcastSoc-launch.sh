#!/bin/bash
while (true); do
    su - pvmonit -c '/usr/bin/php /opt/PvMonit/bin/forcastSoc.php'
    sleep 10
done
