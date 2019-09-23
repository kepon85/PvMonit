#!/bin/bash
cd /opt/PvMonit/bin
while (true); do
    python3 getSerialArduino.py
    sleep 1 
done
