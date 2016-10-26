<?php
##################
# Configuration
# PvMonit
##################

# Niveau d'affichage des messages
$PRINTMESSAGE=5;	# 0=0	5=debug
# Binaire du logiciel de sonde température
$TEMPERV14_BIN='/opt/temperv14/temperv14';
# Ma sonde n'est pas juste, il faut une correction de quelques degrés (exemple : -3)
$SONDE_TEMPERATURE_CORRECTION='0';	
# Binaire de vedirect.py
$VEDIRECT_BIN = '/usr/bin/python /opt/PvMonit/vedirect.py';
# MPTT data (voir la doc victron sur le protocole VE.Direct)
$VEDIRECT_MPTT_DATA=array ('CS', 'PPV', 'V', 'ERR', 'I', 'VPV', 'H19', 'H20', 'H21', 'H22', 'H23');
# MPTT data (voir la doc victron sur le protocole VE.Direct)
$VEDIRECT_BMV_DATA=array ('P', 'TTG', 'SOC', 'CE');
# Numéro de série (champs SER#) en correspondance avec des nom buvables
$VEDIRECT_DEVICE_CORRESPONDANCE=array ('HQXXXXXXXX' => 'MpttGarage', 'HQYYYYYY' => 'MpttToit'); 
# Répertoire de collecte de données
$DATA_COLLECTE='/tmp/PvMonit.collecteData';
# Binaire ampèrmetre.pl
$AMPEREMETRE_BIN = 'perl /opt/PvMonit/ampermetre.pl';
# le périphérique pour l'ampèremetre
$DEV_AMPEREMETRE='/dev/ttyACM0';
# Plafont de consommation en W impossible à dépasser (techniquement, sinon c'est une erreur de sonde)
$CONSO_PLAFOND = 1500;	
# emoncms URL du post.json & API key
$EMONCMS_URL_INPUT_JSON_POST='http://emoncms.org/input/post.json';
$EMONCMS_API_KEY='XXXXXXXXXXXXXXXXXXXXXXXX';

### Page Web : 
# Chemin du cache
$WWW_CACHE_FILE='/tmp/PvMonit.cache-';
# Âge du cache
$WWW_CACHE_AGE=60;
# Max de la jauge voltage batterie (en V) 
$WWW_VBAT_MAX=30;
# Max de la jauge puissance PV (en W)
$WWW_PPV_MAX=500;	# max Jauge puissance PV (en W)
# Max de la jauge consommation (en W)
$WWW_CONSO_MAX=800;
# Menu 
$WWW_MENU='	<li><a href="http://pvmonit.zici.fr">PvMonit projet</a></li>
			<li><a href="http://emoncms.org/dashboard/view?id=VOTREIDs">EmonCMS (historique)</a></li>
			<li><a href="http://www.windguru.cz">Windguru</a></li>
			';

?>
