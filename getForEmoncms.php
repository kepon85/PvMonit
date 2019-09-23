#!/usr/bin/php
<?php

######################################################################
# PvMonit - By David Mercereau : http://david.mercereau.info/contact/
# Script sous licence BEERWARE
# Version 0.2	2016
######################################################################


include_once('/opt/PvMonit/config-default.php');
include_once('/opt/PvMonit/config.php');

include('/opt/PvMonit/function.php');

trucAdir(5, 'Lancement du script');
$timestamp=time();

# Test de l'année
if (date('Y') < '2016') {
	trucAdir(1, 'Le système n\'est pas à la bonne heures, on ne collecte rien');
	exit();
}

# Test du répertoire de collecte
if (!is_dir($GLOBALS['DATA_COLLECTE'])) {
	trucAdir(3, 'Création du répertoire '.$GLOBALS['DATA_COLLECTE']);
	mkdir($GLOBALS['DATA_COLLECTE']);
}

function sauvegardeDesDonnes($data) {
	$fichier=$GLOBALS['DATA_COLLECTE'].'/'.$GLOBALS['timestamp'];
	trucAdir(5, 'Les données ##'.$data.'## sont mise à l\'expédition dans '.$fichier);
	file_put_contents($fichier, $data, FILE_APPEND);
}

# Scan des périphérique VE.Direct Victron
if ($VEDIRECT_BY == 'USB') {
        $cache_file=$CACHE_DIR.'/'.$CACHE_PREFIX.'vedirect_scan';
        if(!checkCacheTime($cache_file)) {
                file_put_contents($cache_file, json_encode(vedirect_scan()));
                chmod($cache_file, 0777);
        } 
        $timerefresh=filemtime($cache_file);
        $vedirect_data_ready=json_decode(file_get_contents($cache_file), true);
} elseif ($VEDIRECT_BY == 'arduino') { 
        $arduino_data=yaml_parse_file($VEDIRECT_DATA_FILE);
        $idDevice=0;
        foreach ($arduino_data as $device_id => $device_data) {
                if (preg_match_all('/^Serial[0-9]$/m', $device_id)) {
                        $device_vedirect_data[$idDevice]=vedirect_parse_arduino($device_data);
                        $idDevice++;
                }
        }
        $vedirect_data_ready = $device_vedirect_data;
}

foreach ($vedirect_data_ready as $device) {
        if ($device['nom'] != '') {
		sauvegardeDesDonnes("www-browser --dump '".$EMONCMS_URL_INPUT_JSON_POST."?json={".$device['data']."}&node=".$device['nom']."&time=".time()."&apikey=".$EMONCMS_API_KEY."'\n");
	}
}


$dataNode1=null;
// Scan du répertoire bin-enabled
$bin_enabled_data = scandir($BIN_ENABLED_DIR);
foreach ($bin_enabled_data as $bin_script_enabled) { 
        $bin_script_info = pathinfo($BIN_ENABLED_DIR.'/'.$bin_script_enabled);
        if ($bin_script_info['extension'] == 'php') {
                $cache_file_script=$CACHE_DIR.'/'.$CACHE_PREFIX.$bin_script_enabled;
                if(!checkCacheTime($cache_file_script)) {
                        $script_return = (include $BIN_ENABLED_DIR.'/'.$bin_script_enabled);
                        file_put_contents($cache_file_script, json_encode($script_return));
                        chmod($cache_file_script, 0777);
                } 
                $timerefresh=filemtime($cache_file_script);
                $script_return_data = json_decode(file_get_contents($cache_file_script), true) ;
                $filenameSplit = explode("-", $bin_script_info['filename']);
                $id=$filenameSplit[1];
                if (!is_null($dataNode1)) {
                        $dataNode1=$dataNode1.',';
                }
                $dataNode1=$dataNode1.strtolower($id).':'.$script_return_data['value'];
        } 
}

if (!is_null($dataNode1)) {
	sauvegardeDesDonnes("www-browser --dump '".$EMONCMS_URL_INPUT_JSON_POST."?json={".$dataNode1."}&node=1&time=".time()."&apikey=".$EMONCMS_API_KEY."'\n");
}


?>
