#!/bin/bash
cd /opt/PvMonit/lcd
while (true); do
    python3 ada-lcd.py
    sleep 1 
done
