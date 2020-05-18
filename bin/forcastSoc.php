<?php

include('/opt/PvMonit/function.php');

// Chargement de la config
$config = getConfigYaml('/opt/PvMonit');

function focastSocEndSurise($soc, $weatherProdForcast) {
    global $config;
    $day=0;
    $sunset=$weatherProdForcast[$day]['sunset'];
    trucAdir(5, 'focastSocEndSurise');
    # Si le soleil n'est pas couché
    if (time() < $sunset) {
        # Combien d'heure avant le couché du soleil
        $HourBeforSunset=($sunset-time())/3600;
        trucAdir(4, 'Le soleil se couche dans '.$HourBeforSunset.'h');
        # Quel consommation électrique estimé avant le couché du soleil ?
        $consumptionBeforSunset=$config['weather']['dalyConsumption']/24*$HourBeforSunset;
        trucAdir(4, 'Consommation électrique d\'ici le couché du soleil : '.round($consumptionBeforSunset).'Wh');
        # Energie présente dans les batteries 
        $batEnergieNow=$config['weather']['batCapacity']*$soc/100;
        trucAdir(4, 'Energie restant a actuelle dans les batteries : '.round($batEnergieNow).'Wh / '.$config['weather']['batCapacity'].' Soc='.$soc);
        # Production sans la consommation au fil du soleil
        $prodWithoutConsumption=$weatherProdForcast[$day]['prodCumul']-$consumptionBeforSunset;
        trucAdir(4, 'Production estimé ce jour sans la consommation au fil du soleil : '.round($prodWithoutConsumption).'Wh');
        # Après injection de la production réel dans la batterie
        $forcastSoc=(100*($batEnergieNow+$prodWithoutConsumption))/$config['weather']['batCapacity'];
        trucAdir(2, 'A J+'.$day.', après injection de cette production dans la batterie sera a '.round($forcastSoc).'% au couché du soleil');
        return round($forcastSoc);
    }
}

function focastSocTomorrowEndSurise($soc, $weatherProdForcast) {
    global $config;
    $day=1;
    $focastSocEndSurise=focastSocEndSurise($soc, $weatherProdForcast);
    if ($focastSocEndSurise > 100) {
        $focastSocEndSurise = 100;
    }
    trucAdir(5, 'focastSocTomorrowEndSurise');
    # Energie présente dans les batteries
    $batEnergieNow=$config['weather']['batCapacity']*$focastSocEndSurise/100;
    trucAdir(4, 'Energie restant actuelle dans les batteries : '.round($batEnergieNow).'Wh / '.$config['weather']['batCapacity'].' Soc='.$focastSocEndSurise);
    # Production - consommation
    $prodWithoutConsumption=$weatherProdForcast[$day]['prodCumul']-$config['weather']['dalyConsumption'];
    trucAdir(4, 'Production estimé  ('.$weatherProdForcast[$day]['prodCumul'].') ce jour sans la consommation ('.$config['weather']['dalyConsumption'].') : '.round($prodWithoutConsumption).'Wh');
    # Après injection de la production réel dans la batterie
    $forcastSoc=(100*($batEnergieNow+$prodWithoutConsumption))/$config['weather']['batCapacity'];
    trucAdir(2, 'A J+'.$day.', après injection de cette production dans la batterie sera a '.round($forcastSoc).'% au couché du soleil');
    return round($forcastSoc);
}

function xml_data_get($xmlFulLData)  {
    global $config;
    $xmlData = null;
    try {
        $devices = new SimpleXMLElement($xmlFulLData);
        foreach ($devices as $device) {
            foreach ($device->datas->data as $data) {
                $id= (string)  $data['id'];
                $dataString = (string) $data->value;
                if ($id == 'SOC') {
                    trucAdir(5, 'XML parse : SOC trouvé à '.$dataString);
                    $xmlData[$id] = $dataString;
                }
            }
        }
        // On vérifie si toutes les données sont là
        if (count($xmlData) != 1) {
            trucAdir(2, 'Le SOC n\'a pas été trouvé dans le XML');
            $xmlData = false;
        }
    } catch (Exception $e ) {
        $xmlData = false;
        trucAdir(2, 'Impossible de lire l\'XML : '.$e);
    }
    return $xmlData;
}

trucAdir(4, 'Lancement du script');
while (true) {
    if (isset($config['weather']['dalyConsumption']) && isset($config['weather']['batCapacity'])){
        //~ # Récupération du XML
        $xml_ok=false;
        $sortie='';
        trucAdir(4, 'Acquisition des données (via le data-xml)');
        $cmd=$config['bin']['php-cli'].' '.$config['scriptDataXml'];
        exec($cmd, $sortie, $retour);
        if ($cmd != 0){
            trucAdir(1, 'Erreur '.$retour.' à l\'exécussion du programme .'.$cmd);
        } else {
            $sortie_concat = implode("\n",$sortie);
            //~ var_dump($sortie_concat);
            $xml_data_get=xml_data_get($sortie_concat);
            if ($xml_data_get == null || $xml_data_get == false) {
                trucAdir(2, 'Données XML invalide');
            } else {
                $xml_ok=true;
            }
        }
        # Récupération du des données de prévision de production
        trucAdir(4, 'Aquisition des données de prévision de production (via le weatherProdForcast.php)');
        $weatherProdForcast_ok=false;
        $sortie='';
        $cmd=$config['bin']['php-cli'].' '.$config['dir']['www'].'weatherProdForcast.php';
        exec($cmd, $sortie, $retour);
        if ($cmd != 0){
            trucAdir(1, 'Erreur '.$retour.' à l\'exécussion du programme .'.$cmd);
        } else {
            $weatherProdForcast = json_decode($sortie[0], true);
            if (isset($weatherProdForcast['error']) || $weatherProdForcast == null || $weatherProdForcast == '') {
                trucAdir(2, 'Données JSON de weatherProdForcast invalide');
                var_dump($weatherProdForcast);
            } else {
                $weatherProdForcast_ok=true;
            }
        }
        if ($xml_ok==true && $weatherProdForcast_ok == true) {
            $forcastSocFileData['focastSocEndSurise']=focastSocEndSurise($xml_data_get['SOC'], $weatherProdForcast);
            $forcastSocFileData['focastSocTomorrowEndSurise']=focastSocTomorrowEndSurise($xml_data_get['SOC'], $weatherProdForcast);
            trucAdir(2, 'Enregistrement des informations dans '.$config['weather']['forcastSocFile']);
            file_put_contents($config['weather']['forcastSocFile'], json_encode($forcastSocFileData));
        } else {
            if (is_file($config['weather']['forcastSocFile'])) { unlink($config['weather']['forcastSocFile']); }
        }
    } else {
        trucAdir(1, 'Weather/dalyConsumption et weatherProdForcast ne sont pas renseigné dans le config.yaml');
        if (is_file($config['weather']['forcastSocFile'])) { unlink($config['weather']['forcastSocFile']); }
    }
    // Sleep
    sleep($config['weather']['cache']);
}




?>
