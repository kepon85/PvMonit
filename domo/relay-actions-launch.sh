#!/bin/bash

while (true); do
    su - pvmonit -c '/usr/bin/php /opt/PvMonit/domo/relay-actions.php'
    sleep 10
done
