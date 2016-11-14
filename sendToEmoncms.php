#!/usr/bin/php
<?php

######################################################################
# PvMonit - By David Mercereau : http://david.mercereau.info/contact/
# Licence BEERWARE
# Version 0.2	2016
######################################################################

include_once('/opt/PvMonit/config-default.php');
if (is_file('/opt/PvMonit/config.php')) {
	include_once('/opt/PvMonit/config.php');
}

include('/opt/PvMonit/function.php');

if(($pid = cronHelper::lock()) !== FALSE) {

trucAdir(5, 'Lancement du script');

// Test internet
trucAdir(5, 'Test de la connexion internet');
$connection = @fsockopen($GLOBALS['TEST_INTERNET_HOST'], $GLOBALS['TEST_INTERNET_PORT']);
if (! is_resource($connection)) {
	trucAdir(3, 'Pas internet, on arrête là');
	exit(0);
}
fclose($connection);

// Test répertoires de travail
if (!is_dir($GLOBALS['DATA_COLLECTE'])) {
	trucAdir(3, 'Le répertoire '.$GLOBALS['DATA_COLLECTE'].' n\'existe pas, il ne doit pas y avoir de données collectées, on arrête là');
	exit(0);
}
if (!is_dir($GLOBALS['DATA_COLLECTE_ERROR'])) {
	mkdir($GLOBALS['DATA_COLLECTE_ERROR']);
}

// Expédition des données
$dataOk=0;
$dataNok=0;
$attenteCompteur=10;
foreach (scandir($GLOBALS['DATA_COLLECTE']) as $fichierData) {
	if (is_file($GLOBALS['DATA_COLLECTE'].'/'.$fichierData)) {
		// Compte le nombre de ligne pour savoir quel réponse (nombre de Ok) est attendu
		$nbLigneDansLeFichierData = 0;
		$fp = fopen($GLOBALS['DATA_COLLECTE'].'/'.$fichierData, 'r');
		while( !feof( $fp)) {
			fgets( $fp);
			$nbLigneDansLeFichierData++;
		}
		fclose( $fp);
		$retourAttendu=null;
		for ($i = 1; $i < $nbLigneDansLeFichierData; $i++) {
			$retourAttendu=$retourAttendu.'ok';
		}
		$send_retour=null;
		$send_sortie=null;
		exec('/bin/bash '.$GLOBALS['DATA_COLLECTE'].'/'.$fichierData, $send_sortie, $send_retour);
		if ($send_retour == 0 && $send_sortie[0] == $retourAttendu){
			trucAdir(4, 'Donnée '.$GLOBALS['DATA_COLLECTE'].'/'.$fichierData.' correctement envoyées');
			unlink($GLOBALS['DATA_COLLECTE'].'/'.$fichierData);
			$dataOk++;
			sleep($GLOBALS['SLEEP_OK']);
			if ($dataOk == $attenteCompteur) {
				trucAdir(5, 'Patiente 3 seconde, le serveur HTTP t\'en remercie !');
				sleep(3);
				$attenteCompteur = $attenteCompteur + 10;
			}
		} else {
			trucAdir(1, 'Problème avec le fichier '.$GLOBALS['DATA_COLLECTE'].'/'.$fichierData.' le retour est : '.$send_sortie[0]);
			rename($GLOBALS['DATA_COLLECTE'].'/'.$fichierData, $GLOBALS['DATA_COLLECTE_ERROR'].'/'.$fichierData);
			$dataNok++;
			trucAdir(5, 'Patiente '.$GLOBALS['SLEEP_NOK'].' seconde, le serveur HTTP t\'en remercie !');
			sleep($GLOBALS['SLEEP_NOK']);
		}	
	}
}
trucAdir(1, 'Données correctements envoyées : '.$dataOk.', données en erreurs : '.$dataNok);

cronHelper::unlock();
} else {
	trucAdir(1, 'Le script est déjà en cours d\'exécution, le fichier de lock est présent');
}

?>
