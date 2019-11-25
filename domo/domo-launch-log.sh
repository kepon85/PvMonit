#!/bin/bash
echo > /tmp/domo.log
cd /opt/PvMonit/domo
while (true); do
    python3 domo.py >> /tmp/domo.log
    sleep 0.5
done
