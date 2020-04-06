#!/bin/bash

while (true); do
    su - pvmonit -c '/usr/bin/php /opt/PvMonit/cloudService.php'
    # Si plantage, on relance dans 10 secondes
    sleep 10
done
