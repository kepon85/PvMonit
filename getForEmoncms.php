#!/usr/bin/php
<?php

include('/opt/PvMonit/function.php');
// Chargement de la config
$config = getConfigYaml('/opt/PvMonit');

trucAdir(5, 'Lancement du script');
$timestamp=time();

# Test de l'année
if (date('Y') < '2016') {
	trucAdir(1, 'Le système n\'est pas à la bonne heures, on ne collecte rien');
	exit();
}

# Test du répertoire de collecte
if (!is_dir($config['emoncms']['dataCollecte'])) {
    trucAdir(3, 'Création du répertoire '.$config['emoncms']['dataCollecte']);
    mkdir($config['emoncms']['dataCollecte']);
}

function sauvegardeDesDonnes($data) {
	global $config;
	$fichier=$config['emoncms']['dataCollecte'].'/'.$GLOBALS['timestamp'];
	trucAdir(5, 'Les données ##'.$data.'## sont mise à l\'expédition dans '.$fichier);
	file_put_contents($fichier, $data, FILE_APPEND);
}

exec($config['bin']['php-cli'].' '.$config['scriptDataXml'], $data_xml, $data_xml_retour);
if ($data_xml_retour == 0){
    $data_xml_string = implode("\n",$data_xml);
    $devices = simplexml_load_string($data_xml_string);
    foreach ($devices as $device) {
	//~ $id= (string)  $device['id'];
	$nom = (string) $device->nom;
	foreach ($device->datas->data as $data) {
	    $id= (string)  $data['id'];
	    $data = (float) $data->valueBeast;
	    $dataPreparPerDevice[$nom][$id]=$data;
	}
    }
}

foreach ($dataPreparPerDevice as $device=>$dataPrepar) {
    sauvegardeDesDonnes("www-browser --dump '".$config['emoncms']['urlInputJsonPost']."?json=".json_encode($dataPrepar)."&node=".$device."&time=".time()."&apikey=".$config['emoncms']['apiKey']."'\n");
}

// Domo
if ($config['www']['domo'] == true) { 
    if (is_file($config['domo']['jsonFile']['etatPath'])) {
	sauvegardeDesDonnes("www-browser --dump '".$config['emoncms']['urlInputJsonPost']."?json=".file_get_contents($config['domo']['jsonFile']['etatPath'])."&node=domoEtat&time=".time()."&apikey=".$config['emoncms']['apiKey']."'\n");
    }
    if (is_file($config['domo']['jsonFile']['modPath'])) {
	sauvegardeDesDonnes("www-browser --dump '".$config['emoncms']['urlInputJsonPost']."?json=".file_get_contents($config['domo']['jsonFile']['modPath'])."&node=domoMod&time=".time()."&apikey=".$config['emoncms']['apiKey']."'\n");
    }
}

if (is_file('/opt/PvMonit/getForEmoncms-inc.php')) {
    include('/opt/PvMonit/getForEmoncms-inc.php');
}

?>
