<?php
###################################
# Script sous licence BEERWARE
# Version 0.1	2016
###################################
include_once('../config-default.php');
include_once('../config.php');
include_once('./config.php');

include('../function.php');
include('./function.php');


if (!is_file($GLOBALS['DATA_FILE'])) {
    trucAdir(1, "Êtes vous certain d'avoir correctement configuré la tâche planifié qui télécharge les données XML dans $DATA_FILE ?");
} else {
    $xml_data_get=xml_data_get($DATA_FILE);
    if ($xml_data_get == null || $xml_data_get == false) {
        trucAdir(2, 'Données XML invalide');
    } else {
        trucAdir(4, 'Les données sont bonnes !');
        $xml_data_get = xml_data_get($GLOBALS['DATA_FILE']);
    }
}


$relayMod[$argv[1]]=2;
$relayEtat[$argv[1]]=$argv[2];

    
foreach ($relayMod as $relay => $Mod) {
    
    if (is_file($GLOBALS['RELAY_SCRIPT_DIR'].$relay.'.php')) {
        $r['etat']=$relayEtat[$relay];
        $r['id']=$relay;
        $d=$xml_data_get;
        trucAdir(3, "Etat du relay ".$relay." = ".$relayEtat[$relay]);
        $script_return = (include $GLOBALS['RELAY_SCRIPT_DIR'].$relay.'.php');
        if ($relayEtat[$relay] != $script_return) {
            trucAdir(3, "Changement d'état pour le relay ".$relay." (vers ".$script_return.")");
        } else {
            trucAdir(3, "Aucun changement d'état pour le relay ".$relay);
        }
    }

}




?>
