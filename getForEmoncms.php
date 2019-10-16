#!/usr/bin/php
<?php

######################################################################
# PvMonit - By David Mercereau : http://david.mercereau.info/contact/
# Script sous licence BEERWARE
# Version 1.0	2016
######################################################################

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

# Scan des périphérique VE.Direct Victron
if ($config['vedirect']['by'] == 'usb') {
        $cache_file=$config['cache']['dir'].'/'.$config['cache']['file_prefix'].'vedirect_scan';
        if(!checkCacheTime($cache_file)) {
                file_put_contents($cache_file, json_encode(vedirect_scan()));
                chmod($cache_file, 0777);
        } 
        $timerefresh=filemtime($cache_file);
        $vedirect_data_ready=json_decode(file_get_contents($cache_file), true);
} elseif ($config['vedirect']['by'] == 'arduino') { 
        $arduino_data=yaml_parse_file($config['vedirect']['arduino']['data_file']);
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
		sauvegardeDesDonnes("www-browser --dump '".$config['emoncms']['urlInputJsonPost']."?json={".$device['data']."}&node=".$device['nom']."&time=".time()."&apikey=".$config['emoncms']['apiKey']."'\n");
	}
}


$dataNode1=null;
// Scan du répertoire bin-enabled
$bin_enabled_data = scandir($config['dir']['bin_enabled']);
foreach ($bin_enabled_data as $bin_script_enabled) { 
        $bin_script_info = pathinfo($config['dir']['bin_enabled'].'/'.$bin_script_enabled);
        if ($bin_script_info['extension'] == 'php') {
                trucAdir(3, "Le script ".$config['dir']['bin_enabled'].'/'.$bin_script_enabled." est appelé");
                $filenameSplit = explode("-", $bin_script_info['filename']);
                $idParent=$filenameSplit[0];
                $id=$filenameSplit[1];
                $cache_file_script=$config['cache']['dir'].'/'.$config['cache']['file_prefix'].$bin_script_enabled;
                if(!checkCacheTime($cache_file_script)) {
                        // Ménage
                        foreach ($array_data as $i => $value) {
                            unset($array_data[$i]);
                        }
                        $script_return = (include $config['dir']['bin_enabled'].'/'.$bin_script_enabled);
                        file_put_contents($cache_file_script, json_encode($script_return));
                        chmod($cache_file_script, 0777);
                } 
                $timerefresh=filemtime($cache_file_script);
                $script_return_datas = json_decode(file_get_contents($cache_file_script), true) ;
                #print_r($script_return_datas);
                foreach ($script_return_datas as $script_return_data) {
                        if (isset($script_return_data['id'])) {
                                $id_data=$script_return_data['id'];
                        } else {
                                $id_data=$id;
                        }
                        if (!is_null($dataNode1)) {
                                $dataNode1=$dataNode1.',';
                        }
                        $dataNode1=$dataNode1.strtolower($id_data).':'.$script_return_data['value'];
                }
                
        } 
}

if (!is_null($dataNode1)) {
	sauvegardeDesDonnes("www-browser --dump '".$config['emoncms']['urlInputJsonPost']."?json={".$dataNode1."}&node=1&time=".time()."&apikey=".$config['emoncms']['apiKey']."'\n");
}


?>
