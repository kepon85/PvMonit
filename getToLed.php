#!/usr/bin/php
<?php

######################################################################
# PvMonit - By David Mercereau : http://david.mercereau.info/contact/
# Script sous licence BEERWARE
# Version 0.2	2016
######################################################################

include_once('/opt/PvMonit/config-default.php');
if (is_file('/opt/PvMonit/config.php')) {
	include_once('/opt/PvMonit/config.php');
}
include('/opt/PvMonit/function.php');

function toLed($msg) {
	trucAdir(5, 'A afficher : '.$msg);
	exec('echo "'.$msg.'" | '.$GLOBALS['LED_BIN'], $led_sortie, $led_retour);
	if ($led_retour != 0){
		trucAdir(3, 'L`écran led n\'est probablement pas connecté.');
		trucAdir(5, 'Erreur '.$led_retour.' à l\'exécussion du programme .'.$GLOBALS['LED_BIN']);
		trucAdir(5, 'Attente 5 secondes');
		sleep(5);
	} 
}

if ($GLOBALS['LED_BIN'] == '') {
	trucAdir(1, 'Aucun binaire n\'est renseigné');
	exit(1);
} else if (!is_file($GLOBALS['LED_BIN'])) {
	trucAdir(1, 'Le binaire : '.$GLOBALS['LED_BIN'].' n\'exsite pas.');
	exit(2);
}

while(true) {

	# Scan des périphérique VE.Direct Victron
	foreach (vedirect_scan() as $device) {
		$aDiffuser=null;
		$aDiffuser="[".$device['nom']."]";
		foreach (explode(',', $device['data']) as $data) {
			$dataSplit = explode(':', $data);
			if (in_array($dataSplit[0], $GLOBALS['LED_VEDIRECT_DATA_PRIMAIRE'])) {
				$veData=ve_label2($dataSplit[0], $dataSplit[1]);
				if (isset($veData['descShort'])) {
					$desc=$veData['descShort']; 
				} else {
					$desc=$veData['desc']; 
				}
				if (!is_null($aDiffuser)) {
					$aDiffuser=$aDiffuser.' - ';
				}
				$aDiffuser=$aDiffuser.$desc.':'.$veData['value'].$veData['units'];
			}
		}
		toLed($aDiffuser);
	}

	// Température & consommation
	$aDiffuser=null;
	$temperature=temperature();
	if ($temperature !== 'NODATA') {
		$aDiffuser=$temperature.'\'C';
	}
	$consommation=consommation();
	if ($consommation !== 'NODATA') {
		if (!is_null($aDiffuser)) {
			$aDiffuser=$aDiffuser.' - ';
		}
		$aDiffuser=$aDiffuser.'Conso:'.$consommation.'W';
	}

	toLed($aDiffuser);

}

?>
