#!/bin/bash
cd /opt/PvMonit/domo
while (true); do
    python3 domo.py 
    sleep 0.5
done
