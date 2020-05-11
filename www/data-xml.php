<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type:  application/xml");

$xmlPrint= '<?xml version="1.0" encoding="utf-8"?>
<devices>';

###################################
# Script sous licence BEERWARE
# Version 1.0	2019
###################################

include('/opt/PvMonit/function.php');

function xmlPrintError($msg) {
    return '<?xml version="1.0" encoding="utf-8"?>
    <devices>
    <device id="other">
        <nom>Erreur</nom>
        <timerefresh></timerefresh>
        <type></type>
        <modele></modele>
        <serial></serial>
        <datas>
            <data id="ERR" screen="1" smallScreen="1">
                <desc>Erreur</desc>
                <value>'.$msg.'</value>
                <units></units>
            </data>
        </datas>
    </device></devices>';
}

// Chargement de la config
$config = getConfigYaml('/opt/PvMonit');
$config['printMessage']=0;

trucAdir(2, "Appel du data-xml.php");

if (checkCacheTime($config['cache']['dir'].'/data.xml')) {
    trucAdir(3, 'XML on sert le fichier de cache car il n\'est pas périmé');
    echo file_get_contents($config['cache']['dir'].'/data.xml');
} else {

    function onScreenPrint($name) {
            global $config;
            if (in_array($name, $config['www']['dataPrimaire'])) {
                    $screenVal=' screen="1"';
            } else {
                    $screenVal= ' screen="0"';
            }
            if (in_array($name, $config['www']['dataPrimaireSmallScreen'])) {
                    $screenVal.= ' smallScreen="1"';
            }  else {
                    $screenVal.= ' smallScreen="0"';
            }
            return $screenVal;
    }

    $ppv_total=null;
    $bmv_p=null;
    $nb_ppv_total=0;
    if ($config['vedirect']['by'] == 'usb') {
            $timerefresh=time();
            $vedirect_data_ready = vedirect_scan();
    } elseif ($config['vedirect']['by'] == 'arduino') { 
            if (! is_file($config['vedirect']['arduino']['data_file'])) {
                    $vedirect_data_ready[0]['id'] = 'Vedirect serial get by arduino';
                    $vedirect_data_ready[0]['nom'] = 'Vedirect erreur';
                    $vedirect_data_ready[0]['serial'] = 'vedirect';
                    $vedirect_data_ready[0]['data']= 'Fichier de donnée:Introuvable';
            } else if (filemtime($config['vedirect']['arduino']['data_file'])+$config['vedirect']['arduino']['data_file_expir']  < time()) {
                    $vedirect_data_ready[0]['id'] = 'Vedirect serial get by arduino';
                    $vedirect_data_ready[0]['nom'] = 'Vedirect erreur';
                    $vedirect_data_ready[0]['serial'] = 'vedirect';
                    $vedirect_data_ready[0]['data']= 'Fichier de donnée :Périmé';
            } else {
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
    }
    if ($config['vedirect']['by'] != false) {
        foreach ($vedirect_data_ready as $device) {
                if ($device['serial']  == 'Inconnu' || $device['serial']  == '') {
                        $device['serial'] = $device['nom'];
                }
                $xmlPrint.= "\n\t".'<device id="'.str_replace(' ', '', $device['serial']).'">';
                $xmlPrint.= "\n\t\t".'<nom>'.$device['nom'].'</nom>';
                $xmlPrint.= "\n\t\t".'<timerefresh>'.time().'</timerefresh>';
                $xmlPrint.= "\n\t\t".'<type>'.$device['type'].'</type>';
                $xmlPrint.= "\n\t\t".'<modele>'.$device['modele'].'</modele>';
                $xmlPrint.= "\n\t\t".'<serial>'.$device['serial'].'</serial>';
                $xmlPrint.= "\n\t\t".'<datas>';
                sort($device['data']);
                foreach (explode(',', $device['data']) as $data) {
                        $dataSplit = explode(':', $data);
                        $veData=ve_label2($dataSplit[0], $dataSplit[1]);
                        $xmlPrint.= "\n\t\t\t".'<data id="'.$veData['label'].'" screen="'.$veData['screen'].'" smallScreen="'.$veData['smallScreen'].'">';
                                $xmlPrint.= "\n\t\t\t\t".'<desc>'.$veData['desc'].'</desc>';
                                $xmlPrint.= "\n\t\t\t\t".'<value>'.$veData['value'].'</value>';
                                $xmlPrint.= "\n\t\t\t\t".'<units>'.$veData['units'].'</units>';
                        $xmlPrint.= "\n\t\t\t".'</data>';
                        if ($dataSplit[0] == 'PPV'){ 
                                $ppv_total=$ppv_total+$dataSplit[1];
                                $nb_ppv_total++;
                        }
                        if ($device['type'] == "BMV" && $dataSplit[0] == 'P'){ 
                                $bmv_p=$dataSplit[1];
                        }
                }
                $xmlPrint.= "\n\t\t".'</datas>';
                $xmlPrint.= "\n\t".'</device>';
        }
    }
    # WKS
    if ($config['wks']['enable'] == true) {
        trucAdir(1, "WKS enable");
        exec($config['wks']['bin'], $wks_sortie, $wks_retour);
        if ($wks_retour != 0){
            trucAdir(1, 'Erreur à l\'exécution du script '.$config['wks']['bin']);
        } else {
            $datas = json_decode($wks_sortie[0]);
            $execTime=time();
            foreach ($datas as $command=>$reponses) {
                if (empty($config['wks']['data'][$command]['hide']) || $config['wks']['data'][$command]['hide'] != true) {
                    $xmlPrint.= "\n\t".'<device id="WKS'.$command.'">';
                    if (isset($config['wks']['data'][$command]['name'])) {
                        $xmlPrint.= "\n\t\t".'<nom>WKS '.$config['wks']['data'][$command]['name'].'</nom>';
                    } else {
                        $xmlPrint.= "\n\t\t".'<nom>WKS '.$command.'</nom>';
                    }
                    $xmlPrint.= "\n\t\t".'<timerefresh>'.$execTime.'</timerefresh>';
                    $xmlPrint.= "\n\t\t".'<type>inverter</type>';
                    $xmlPrint.= "\n\t\t".'<modele></modele>';
                    $xmlPrint.= "\n\t\t".'<serial></serial>';
                    $xmlPrint.= "\n\t\t".'<datas>';
                    $numReponse=1;
                    foreach ($reponses as $reponse) {
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
                                $xmlPrint.= "\n\t\t\t".'<data id="'.$config['wks']['data'][$command][$numReponse]['id'].'"'.onScreenPrint($config['wks']['data'][$command][$numReponse]['id']).'>';
                                    $xmlPrint.= "\n\t\t\t\t".'<desc>'.$config['wks']['data'][$command][$numReponse]['desc'].'</desc>';
                                    if (isset($config['wks']['data'][$command][$numReponse]['value2text'])) {
                                        $find = false;
                                        foreach($config['wks']['data'][$command][$numReponse]['value2text'] as $value=>$text) {
                                            if ($reponse == $value) {
                                                $xmlPrint.= "\n\t\t\t\t".'<value>'.$text.'</value>';
                                                $find = true;
                                            }
                                        }
                                        if ($find == false) {
                                            $xmlPrint.= "\n\t\t\t\t".'<value>'.$reponse.'</value>';
                                        }
                                    } else {
                                        $xmlPrint.= "\n\t\t\t\t".'<value>'.$reponse.'</value>';
                                    }
                                    $xmlPrint.= "\n\t\t\t\t".'<units>'.$config['wks']['data'][$command][$numReponse]['units'].'</units>';
                                $xmlPrint.= "\n\t\t\t".'</data>';
                            } else {
                                # Caché
                                trucAdir(5, "[WKS] idem caché : ".$command.$numReponse);
                            }
                        } elseif ($config['wks']['data']['printAll'] == true) {
                            # Sinon c'est par défaut
                            trucAdir(5, "[WKS] pas de config, item par défaut : ".$command.$numReponse);
                            $xmlPrint.= "\n\t\t\t".'<data id="'.$command.$numReponse.'"'.onScreenPrint($command.$numReponse).'>';
                                $xmlPrint.= "\n\t\t\t\t".'<desc>'.$command.$numReponse.'</desc>';
                                $xmlPrint.= "\n\t\t\t\t".'<value>'.$reponse.'</value>';
                                $xmlPrint.= "\n\t\t\t\t".'<units></units>';
                            $xmlPrint.= "\n\t\t\t".'</data>';
                        }
                        $numReponse++;
                    }
                    $xmlPrint.= "\n\t\t".'</datas>';
                    $xmlPrint.= "\n\t".'</device>';
                }
            }
        }
    }

    # Divers
    $bin_enabled_data = scandir($config['dir']['bin_enabled']);
    $printDivers=false;
    foreach ($bin_enabled_data as $bin_script_enabled) { 
        $bin_script_info = pathinfo($config['dir']['bin_enabled'].'/'.$bin_script_enabled);
        if ($bin_script_info['extension'] == 'php') {
            $printDivers=true;
        } 
    }
    if($printDivers == true || $config['data']['ppv_total'] || $config['data']['ppv_total']) {
        $xmlPrint.= '<device id="other">
            <nom>Divers</nom>
            <timerefresh>'.time().'</timerefresh>
            <type></type>
            <modele></modele>
            <serial></serial>
            <datas>';
                // Production totale
                if ($config['data']['ppv_total'] && $ppv_total !== null) {
                    $xmlPrint.= '<data id="PPVT"'.onScreenPrint('PPVT').'>
                    <desc>Production total des panneaux</desc>
                    <value>'.$ppv_total.'</value>
                    <units>W</units>
                    </data>';
                }
                // Calcul consommation foyer
                if ($config['data']['ppv_total'] && $config['data']['conso_calc'] && $ppv_total !== null && $bmv_p != null) {
                                    $conso=$ppv_total-$bmv_p;
                    $xmlPrint.= '<data id="CONSO"'.onScreenPrint('CONSO').'>
                    <desc>Consommation du foyer</desc>
                    <value>'.abs($conso).'</value>
                    <units>W</units>
                    </data>';
                }
            $xmlPrint.= '</datas>		
        </device>';
            trucAdir(3, "Scan du répertoire bin-enable");
            // Scan du répertoire bin-enabled
            foreach ($bin_enabled_data as $bin_script_enabled) { 
                    $bin_script_info = pathinfo($config['dir']['bin_enabled'].'/'.$bin_script_enabled);
                    if ($bin_script_info['extension'] == 'php') {
                            $filenameSplit = explode("-", $bin_script_info['filename']);
                            $idParent=$filenameSplit[0];
                            $id=$filenameSplit[1];

                            // Ménage
                            foreach ($array_data as $i => $value) {
                                unset($array_data[$i]);
                            }
                            trucAdir(3, "Lecture du script ".$bin_script_enabled);
                            $script_return = (include $config['dir']['bin_enabled'].'/'.$bin_script_enabled);
                            $timerefresh=time();
                            $script_return_datas = $script_return;
                            
                            $xmlPrint.= "\n\t<device id=\"".strtolower($idParent)."\">";
                            $xmlPrint.= "\n\t\t<nom></nom>";
                            $xmlPrint.= "\n\t\t<timerefresh>".$timerefresh."</timerefresh>";
                            $xmlPrint.= "\n\t\t<type></type>";
                            $xmlPrint.= "\n\t\t<modele></modele>";
                            $xmlPrint.= "\n\t\t<serial></serial>";
                            $xmlPrint.= "\n\t\t<datas>";
                            sort($script_return_datas);
                            
                            foreach ($script_return_datas as $script_return_data) {
                                    if (isset($script_return_data['id'])) {
                                            $id_data=$script_return_data['id'];
                                    } else {
                                            $id_data=$id;
                                    }
                                    $xmlPrint.= "\n\t\t\t<data id='".$id_data."' screen='".$script_return_data['screen']."' smallScreen='".$script_return_data['smallScreen']."'>";
                                    $xmlPrint.= "\n\t\t\t\t<desc>".$script_return_data['desc']."</desc>";
                                    $xmlPrint.= "\n\t\t\t\t<value>".$script_return_data['value']."</value>";
                                    $xmlPrint.= "\n\t\t\t\t<units>".$script_return_data['units']."</units>";
                                    $xmlPrint.= "\n\t\t\t</data>";
                            }
                            $xmlPrint.= "\n\t\t</datas>";
                            $xmlPrint.= "\n\t</device>";
                    } 
            }
    }
    $xmlPrint.= "</devices>";
    trucAdir(5, "Fin de la génération du fichier data-xml");

    $xmlValid=true;
    if (isset($config['dataCheck'])) {
        trucAdir(5, "Vérification des donnéesdata-xml");
        // Count data check
        $nbDataCheck=0;
        foreach ($config['dataCheck'] as $dataCheck) {
            if (isset($dataCheck['number'])) {
                $nbDataCheck=$nbDataCheck+$dataCheck['number'];
            } else {
                $nbDataCheck++;
            }
        }
        trucAdir(5, "On recherche $nbDataCheck data pour valider l'XML");
        $devices = simplexml_load_string($xmlPrint);
        //~ $devices = simplexml_load_file('data-xml-test.xml');
        foreach ($devices as $device) {
            foreach ($device->datas->data as $data) {
                $id= (string)  $data['id'];
                $data = (string) $data->value;
                if (isset($config['dataCheck'][$id])) {
                    // Trouvé, on soustrait 1
                    $nbDataCheck=$nbDataCheck-1;
                    trucAdir(5, 'XML parse : '.$id.' à été trouvé');
                    if (isset($config['dataCheck'][$id]['regex']) && !preg_match_all('/'.$config['dataCheck'][$id]['regex'].'/', $data)) {
                        trucAdir(1, 'XML parse : '.$id.' est invalide par rapport au regex recherché');
                        $nbDataCheck=$nbDataCheck-2;
                    }
                }
            }
        }
        if ($nbDataCheck == 0) {
            trucAdir(5, 'XML parse : tout trouvé, tout validé !');
        } else {
            trucAdir(1, 'XML parse : erreur dans le XML');
            $xmlValid=false;
        }
    }

    if ($xmlValid == true) {
        file_put_contents($config['cache']['dir'].'/data.xml', $xmlPrint);
        echo $xmlPrint;
    } else {
        if (is_file($config['cache']['dir'].'/data.xml')) {
            if (filemtime($config['cache']['dir'].'/data.xml')+$config['cache']['expirLimit'] < time()) {
                echo xmlPrintError("Le cache est expiré sans que de bonne données ne soient récupéré...");
                trucAdir(1, 'XML Le cache est expiré sans que de bonne données ne soient récupéré...');
            } else {
                trucAdir(3, 'XML on sert le fichier de cache MEME s\'il est périmé en attendant...');
                echo file_get_contents($config['cache']['dir'].'/data.xml');
            }
        } else {
            echo xmlPrintError("Aucune donnée valide a afficher (voir config.yaml / dataCheck)");
            trucAdir(1, "Aucune donnée valide a afficher (voir config.yaml / dataCheck)");
        }
    }
}
?>


