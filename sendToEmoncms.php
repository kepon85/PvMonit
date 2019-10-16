#!/usr/bin/php
<?php

######################################################################
# PvMonit - By David Mercereau : http://david.mercereau.info/contact/
# Licence BEERWARE
# Version 1.0
######################################################################

include('/opt/PvMonit/function.php');

// Chargement de la config
$config = getConfigYaml('/opt/PvMonit');

if(($pid = cronHelper::lock()) !== FALSE) {

trucAdir(5, 'Lancement du script');

// Test internet
trucAdir(5, 'Test de la connexion internet');
$connection = @fsockopen($config['emoncms']['testInternetHost'], $config['emoncms']['testInternetPort']);
if (! is_resource($connection)) {
	trucAdir(3, 'Pas internet, on arrête là');
	exit(0);
}
fclose($connection);

// Test répertoires de travail
if (!is_dir($config['emoncms']['dataCollecte'])) {
	trucAdir(3, 'Le répertoire '.$config['emoncms']['dataCollecte'].' n\'existe pas, il ne doit pas y avoir de données collectées, on arrête là');
	exit(0);
}
if (!is_dir($config['emoncms']['dataCollecteError'])) {
	mkdir($config['emoncms']['dataCollecteError']);
}

// Expédition des données
$dataOk=0;
$dataNok=0;
$attenteCompteur=10;
foreach (scandir($config['emoncms']['dataCollecte']) as $fichierData) {
	if (is_file($config['emoncms']['dataCollecte'].'/'.$fichierData)) {
		// Compte le nombre de ligne pour savoir quel réponse (nombre de Ok) est attendu
		$nbLigneDansLeFichierData = 0;
		$fp = fopen($config['emoncms']['dataCollecte'].'/'.$fichierData, 'r');
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
		exec('/bin/bash '.$config['emoncms']['dataCollecte'].'/'.$fichierData, $send_sortie, $send_retour);
                $sortie_en_ligne='';
                foreach ($send_sortie as $une_sortie) {
                        $sortie_en_ligne=$sortie_en_ligne.$une_sortie;
                }
		if ($send_retour == 0 && $sortie_en_ligne == $retourAttendu){
			trucAdir(4, 'Donnée '.$config['emoncms']['dataCollecte'].'/'.$fichierData.' correctement envoyées');
			unlink($config['emoncms']['dataCollecte'].'/'.$fichierData);
			$dataOk++;
			sleep($config['emoncms']['sleepOk']);
			if ($dataOk == $attenteCompteur) {
				trucAdir(5, 'Patiente 3 seconde, le serveur HTTP t\'en remercie !');
				sleep(3);
				$attenteCompteur = $attenteCompteur + 10;
			}
		} else {
			trucAdir(1, 'Problème avec le fichier '.$config['emoncms']['dataCollecte'].'/'.$fichierData.' le retour est : '.$sortie_en_ligne);
			rename($config['emoncms']['dataCollecte'].'/'.$fichierData, $config['emoncms']['dataCollecteError'].'/'.$fichierData);
			$dataNok++;
			trucAdir(5, 'Patiente '.$config['emoncms']['sleepNok'].' seconde, le serveur HTTP t\'en remercie !');
			sleep($config['emoncms']['sleepNok']);
		}	
	}
}
trucAdir(1, 'Données correctements envoyées : '.$dataOk.', données en erreurs : '.$dataNok);

cronHelper::unlock();
} else {
	trucAdir(1, 'Le script est déjà en cours d\'exécution, le fichier de lock est présent');
}

?>
