#!/usr/bin/php
<?php

###################################
# Script sous licence BEERWARE
# Version 0.2	2016
###################################

# Joué toutes les 5 minutes par un utilisateur restraint (non root)

include('/opt/PvMonit/config.php');
include('/opt/PvMonit/function.php');

trucAdir(5, 'Lancement du script');
$timestamp=time();

# Test de l'année
if (date('Y') < '2016') {
	trucAdir(1, 'Le système n\'est pas à la bonne heures, on ne collecte rien');
	exit();
}

# Test du répertoire de collecte
if (!is_dir($DATA_COLLECTE)) {
	trucAdir(3, 'Création du répertoire '.$DATA_COLLECTE);
	mkdir($DATA_COLLECTE);
}

function sauvegardeDesDonnes($data) {
	$fichier=$GLOBALS['DATA_COLLECTE'].'/'.$GLOBALS['timestamp'];
	trucAdir(5, 'Les données ##'.$data.'## sont mise à l\'expédition dans '.$fichier);
	file_put_contents($fichier, $data, FILE_APPEND);
}

# Scan des périphérique VE.Direct Victron
foreach (vedirect_scan() as $device) {
	sauvegardeDesDonnes("www-browser --dump '".$EMONCMS_URL_INPUT_JSON_POST."?json={".$device['data']."}&node=".$device['nom']."&time=".time()."&apikey=".$EMONCMS_API_KEY."'\n");
}

$dataNode1=null;
$temperature=temperature();
if ($temperature !== 'NODATA') {
	$dataNode1='temp:'.$temperature;
}
$consommation=consommation();
if ($consommation !== 'NODATA') {
	if (!is_null($dataNode1)) {
		$dataNode1=$dataNode1.',';
	}
	$dataNode1=$dataNode1.'conso:'.$consommation;
}
if (!is_null($dataNode1)) {
	sauvegardeDesDonnes("www-browser --dump '".$EMONCMS_URL_INPUT_JSON_POST."?json={".$dataNode1."}&node=1&time=".time()."&apikey=".$EMONCMS_API_KEY."'\n");
}


?>
