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

# Scan des périphérique VE.Direct Victron
if ($config['vedirect']['by'] == 'usb') {
        $cache_file=$config['cache']['dir'].'/'.$config['cache']['file_prefix'].'vedirect_scan';
        if(!checkCacheTime($cache_file)) {
                file_put_contents($cache_file, json_encode(vedirect_scan()));
                if (substr(sprintf('%o', fileperms($cache_file)), -3) != '777')  {
                        chmod($cache_file, 0777);
                }
        } 
        $timerefresh=filemtime($cache_file);
        $data_ready=json_decode(file_get_contents($cache_file), true);
} elseif ($config['vedirect']['by'] == 'arduino') { 
        $arduino_data=yaml_parse_file($config['vedirect']['arduino']['data_file']);
        $idDevice=0;
        foreach ($arduino_data as $device_id => $device_data) {
                if (preg_match_all('/^Serial[0-9]$/m', $device_id)) {
                        $device_vedirect_data[$idDevice]=vedirect_parse_arduino($device_data);
                        $idDevice++;
                }
        }
        $data_ready = $device_vedirect_data;
}
// pour les WKS
if ($config['wks']['enable'] == true) {
    trucAdir(1, "WKS enable");
    exec($config['wks']['bin'], $wks_sortie, $wks_retour);
    if ($wks_retour != 0){
        trucAdir(1, 'Erreur à l\'exécution du script '.$config['wks']['bin']);
    } else {
        $datas = json_decode($wks_sortie[0]);
	foreach ($datas as $command=>$data) {
	    $nbData_ready=count($data_ready)+1;
	    $data_ready[$nbData_ready]['nom']=$command;
	    $data_ready[$nbData_ready]['type']='WKS';
	    $numReponse=1;
	    $dataConcat='';
	    foreach ($data as $reponse) {
		# Check regex : 
		if (isset($config['wks']['data'][$command][$numReponse]['regex']) 
		&& $config['wks']['data'][$command][$numReponse]['regex'] != false)  {
		    if (! preg_match($config['wks']['data'][$command][$numReponse]['regex'], $reponse)) {
			trucAdir(3, "[WKS] Erreur ".$command.$numReponse." regex ".$config['wks']['data'][$command][$numReponse]['regex']." ne correspond pas à l'item ".$reponse);
			$numReponse++;
			continue;
		    }
		}
		# Si l'ordre est présent
		if (isset($config['wks']['data'][$command][$numReponse])) {
		    if (empty($config['wks']['data'][$command][$numReponse]['hide']) || $config['wks']['data'][$command][$numReponse]['hide'] != true) {
			trucAdir(5, "[WKS] Config trouvé, on affiche ".$command.$numReponse);
			if ($dataConcat!='') { $dataConcat.=','; }
			$dataConcat.=$config['wks']['data'][$command][$numReponse]['id'].':'.$reponse;
		    } else {
			# Caché
			trucAdir(5, "[WKS] idem caché : ".$command.$numReponse);
		    }
		} elseif ($config['wks']['data']['printAll'] == true) {
		    # Sinon c'est par défaut
		    if ($dataConcat!='') { $dataConcat.=','; }
		    $dataConcat.=$numReponse.':'.$reponse;
		}
		$numReponse++;
	    }
	    $data_ready[$nbData_ready]['data']=$dataConcat;
	}
    }
}

//~ print_r($data_ready);

$ppv_total=null;
$bmv_p=null;
$nb_ppv_total=0;
foreach ($data_ready as $device) {
	if ($device['nom'] != '') {
		sauvegardeDesDonnes("www-browser --dump '".$config['emoncms']['urlInputJsonPost']."?json={".$device['data']."}&node=".$device['nom']."&time=".time()."&apikey=".$config['emoncms']['apiKey']."'\n");
	}
	foreach (explode(',', $device['data']) as $data) {
			$dataSplit = explode(':', $data);
			if ($dataSplit[0] == 'PPV'){ 
					$ppv_total=$ppv_total+$dataSplit[1];
					$nb_ppv_total++;
			}
			if ($device['type'] == "BMV" && $dataSplit[0] == 'P'){ 
					$bmv_p=$dataSplit[1];
			}
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

if ($config['data']['ppv_total'] && $config['data']['conso_calc'] && $ppv_total !== null && $bmv_p != null) {
	$conso=$ppv_total-$bmv_p;
	$dataNode1=$dataNode1.','.strtolower('CONSO').':'.abs($conso);
}


if (!is_null($dataNode1)) {
    sauvegardeDesDonnes("www-browser --dump '".$config['emoncms']['urlInputJsonPost']."?json={".$dataNode1."}&node=1&time=".time()."&apikey=".$config['emoncms']['apiKey']."'\n");
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

?>
