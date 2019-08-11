<?php
######################################################################
# PvMonit - By David Mercereau : http://david.mercereau.info/contact/
# Licence BEERWARE
# Version 1.0	2019
######################################################################

# Niveau d'affichage des messages
$PRINTMESSAGE=3;	# 0=0	5=debug

# Binaire de vedirect.py
$VEDIRECT_BIN = '/usr/bin/sudo /usr/bin/python /opt/PvMonit/bin/vedirect.py';
# MPTT donnée récolté (voir la doc victron sur le protocole VE.Direct)
$VEDIRECT_MPTT_DATA=array ('CS', 'PPV', 'V', 'ERR', 'I', 'VPV', 'H19', 'H20', 'H21', 'H22', 'H23');
# BMV donnée récolté (voir la doc victron sur le protocole VE.Direct)
$VEDIRECT_BMV_DATA=array ('V', 'VS', 'VM', 'DM', 'I', 'T', 'P', 'CE', 'SOC', 'TTG', 'AR', 'H1', 'H2', 'H3', 'H4', 'H5', 'H6', 'H7', 'H8', 'H9', 'H10', 'H11', 'H12', 'H13', 'h14', 'H15', 'H16', 'H17', 'H18');
# Phoenix Inverter donnée récolté (voir la doc victron sur le protocole VE.Direct)
$VEDIRECT_PHOENIX_DATA=array ('P', 'CS', 'MODE', 'AC_OUT_V', 'AC_OUT_I', 'WARN');
# Numéro de série (champs SER#) en correspondance avec des nom buvables
$VEDIRECT_DEVICE_CORRESPONDANCE=array ('HQXXXXXXXX' => 'MpttGarage', 'HQYYYYYY' => 'MpttToit'); 

## Sonde de température 
# Binaire du logiciel de sonde température
$TEMPERV14_BIN='';
//$TEMPERV14_BIN='/usr/bin/sudo /opt/temperv14/temperv14';
# Ma sonde n'est pas juste, il faut une correction de quelques degrés (exemple : -3)
$SONDE_TEMPERATURE_CORRECTION='0';	

## Pince amphèrmétrique
# Binaire ampèrmetre.pl
$AMPEREMETRE_BIN = '';
//$AMPEREMETRE_BIN = '/usr/bin/sudo /usr/bin/perl /opt/PvMonit/bin/ampermetre.pl';
# Plafont de consommation en W impossible à dépasser (techniquement, sinon c'est une erreur de sonde)
$CONSO_PLAFOND = 1500;


### Export vers Emoncms
# Test la connexion internet
$TEST_INTERNET_HOST='emoncms.org';
$TEST_INTERNET_PORT=80;
# emoncms URL du post.json & API key
$EMONCMS_URL_INPUT_JSON_POST='https://emoncms.org/input/post.json';
$EMONCMS_API_KEY='XXXXXXXXXXXXXXXXXXXXXXXX';
# Répertoire de collecte de données
$DATA_COLLECTE='/tmp/PvMonit.collecteData';
# Dossier ou ranger les erreurs
$DATA_COLLECTE_ERROR=$DATA_COLLECTE.'_erreur';
# Attente entre deux requête OK
$SLEEP_OK=1;
# Attente entre deux requête échoué
$SLEEP_NOK=3;
# Fichier de lock pour éviter les doublons
$LOCKFILE='/tmp/PvMonit.sendToEmoncms.lock';


### Page Web : 
# URL data
$URL_DATA_XML='http://localhost/data-xml.php';
# Délais de raffraichissement de la page (en seconde) 300000 = 5 minutes
$WWW_REFRESH_TIME=300000;
# Chemin du cache
$WWW_CACHE_FILE='/tmp/PvMonit.cache-';
# Âge du cache
$WWW_CACHE_AGE=10;
# Max de la jauge voltage batterie (en V) 
$WWW_VBAT_MAX=30;
# Max de la jauge puissance PV (en W)
$WWW_PPV_MAX=500;	# max Jauge puissance PV (en W)
# Max de la jauge puissance PV total (si plusieurs régulateur) (en W)
$WWW_PPVT_MAX=500;	# max Jauge puissance PV (en W)
# Max de la jauge consommation (en W)
$WWW_CONSO_MAX=750;
# Menu 
$WWW_MENU='	<li><a href="http://pvmonit.zici.fr">PvMonit projet</a></li>
			<li><a href="http://emoncms.org/dashboard/view?id=VOTREIDs">EmonCMS (historique)</a></li>
			<li><a href="http://www.windguru.cz">Windguru</a></li>
			';

$WWW_DATA_PRIMAIRE=array ('V', 'PPV', 'ERR', 'CS', 'SOC', 'AR', 'P', 'TTG', 'MODE', 'AC_OUT_V', 'AC_OUT_I', 'WARN', 'PPVT', 'CONSO', 'TEMP');
$WWW_DATA_PRIMAIRE_SMALLSCREEN=array ('SOC', 'P', 'PPVT', 'CONSO');

### En Shell
$SHELL_VEDIRECT_DATA_PRIMAIRE=array ('V', 'PPV', 'ERR', 'CS', 'P', 'SOC', 'TTG', 'MODE', 'AC_OUT_V', 'AC_OUT_I', 'WARN');

### En Led
//$LED_BIN='/opt/dcled/dcled';
$LED_BIN='';
$LED_VEDIRECT_DATA_PRIMAIRE=array ('PPV');

?>
