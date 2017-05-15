#!/bin/bash

# Bricolage pour relancer des collectes erronées
# crontab : 1 18,19,20,21 * * * /opt/PvMonit/reSend.sh

mv /tmp/PvMonit.collecteData_erreur/1* /tmp/PvMonit.collecteData/
su - pvmonit -c /opt/PvMonit/sendToEmoncms.php
