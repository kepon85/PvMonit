<?php
include('/opt/PvMonit/function.php');

// Chargement de la config
$config_dir='/opt/PvMonit/';
$config_file='config.yaml';
$config = getConfigYaml($config_dir);

trucAdir(4, 'Lancement du script');

if ($config['domo']['daemon'] == true) {
    if (!is_file($config['domo']['jsonFile']['etatPath'])) {
        genDefaultJsonFile('etat');
    }
    if (!is_file($config['domo']['jsonFile']['modPath'])) {
        genDefaultJsonFile('mod');
    }
}

function dataXmlDownload(){
    trucAdir(2, "Téléchargement des données");
    global $config;
    $opts = array('http' =>
        array(
            'method'  => 'GET',
            'timeout' => 60
        )
    );
    $context  = stream_context_create($opts);
    $result = file_get_contents($config['urlDataXml'], false, $context);
    $docValid = simplexml_load_string($result);
    if (!$docValid) {
        return false;
    } else {
        file_put_contents($config['tmpFileDataXml'], $result);
        return true;
    }
}

function dataXmlPut($nbPut){
    trucAdir(3, "Envoi des données sur le service Cloud");
    global $config;
    
    if ($config['domo']['daemon'] == true) {
        $domoData='{"etat":'.file_get_contents($config['domo']['jsonFile']['etatPath']).',"mod":'.file_get_contents($config['domo']['jsonFile']['modPath']).'}';
    } else {
        $domoData=null;
    }
                
    // Contenu du post
    if ($nbPut == 0 || $config['md5sum'] != md5_file('/opt/PvMonit/config.yaml')) {
        trucAdir(2, "On transfère aussi la config : ".$nbPut);
        $config = getConfigYaml('/opt/PvMonit');
        $array_http = array(
            'data-xml' => file_get_contents($config['tmpFileDataXml']),
            'config' => yaml_emit($config),
            'domo' => $domoData,
        );
    } else {
        $array_http = array(
            'data-xml' => file_get_contents($config['tmpFileDataXml']),
            'domo' => $domoData,
        );
    }
    $postdata = http_build_query(
        $array_http
    );
    // Préparation de la requête
    $opts = array('ssl' => [
                    'verify_peer'=>false,
                    'verify_peer_name'=>false,
                ],
                'http' =>
                    array(
                        'method'  => 'POST',
                        'header'  => 'Content-Type: application/x-www-form-urlencoded',
                        'content' => $postdata
                    )
    );
    trucAdir(5, "Envoi sur l'adresse ".$config['cloud']['url'].'api_'.$config['cloud']['api']);
    // Construction de la requête
    $context  = stream_context_create($opts);
    $result = file_get_contents($config['cloud']['url'].'api_'.$config['cloud']['api'], false, $context);
    return $result;
}

$nbPut=0;
while(true) {
    $dataDownloadTentative=0;
    $dataOk=false;
    while($dataDownloadTentative < 2) {
        if (dataXmlDownload() == true) {
            $dataOk=true;
            break;
        } else {
            $dataDownloadTentative++;
            trucAdir(2, "Erreur sur les données, patience 10s avant nouvelle tentative");
            sleep(10);
        }
    }
    if ($dataOk == true) {
        $ret=json_decode(dataXmlPut($nbPut), true);
        // Pour le débug : 
        //~ echo dataXmlPut($nbPut);
        //~ exit();
        if ($ret['result'] != "ok") {
            trucAdir(1, "Erreur, le service cloud renvoi une erreur : ".$ret['result']);
        } else {
            trucAdir(1, "Données correctements transmises : ".$nbPut);
            $nbPut++;
            if (isset($ret['domoRequest'])) {
                trucAdir(1, "Des action domotiques sont requises");
                file_put_contents($config['domo']['jsonFile']['modPath'], json_encode($ret['domoRequest']));
            }
            if (isset($ret['help'])) {
                $fileCheck='/tmp/PvMonit_HELP';
                if ($ret['help'] == 1) {
                    touch($fileCheck);
                    trucAdir(3, "[help] début de la demande de support");
                } elseif ($ret['help'] == 0) {
                    unlink($fileCheck);
                    trucAdir(3, "[help] arrêt de la demande de support");
                } else {
                    trucAdir(3, "[help] Demande de support incomprise");
                }
            }
            if (isset($ret['config']) && $ret['config'] != null) {
                // Sauvegarde
                @copy($config_dir.$config_file, $config_dir.$config_file.'.'.time());
                // Récupération
                $config_local = yaml_parse_file($config_dir.$config_file);
                // Modification
                var_dump($ret['config']);
                $config_local['user'] = json_decode($ret['config'], true);
                var_dump($config_local);
                // Enregistrement
                trucAdir(3, "Enregistrement de la config user qui a été modifié à distance");
                if (!yaml_emit_file($config_dir.$config_file, $config_local)) {
                    trucAdir(1, "Erreur à l\'enregistrement de la configuration");
                }
            }
        }
    } else {
        trucAdir(1, "Les données ne sont pas valides, on passe se tour..");
        sleep(10);
    }
    
    trucAdir(5, "Patience ".$config['cloud']['sendDelay']." avant nouvel envoi de données");
    sleep($config['cloud']['sendDelay']);

}

?>
