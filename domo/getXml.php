<?php

include_once('../config-default.php');
include_once('../config.php');
include_once('./config.php');

include('../function.php');
include('./function.php');

// Toutes les miutes  via pvmonit user !

trucAdir(5, 'Lancement du script de téléchargement');

if(($pid = cronHelper::lock()) !== FALSE) {
    $retry=false;
    $nocacle=false;
    
    for ($i = 1; $i <= $GLOBALS['XML_CHECK_ERROR']; $i++) {
        $ch = curl_init();
        if ($nocacle == true) {
            trucAdir(3, 'Téléchargement sans cache !');
            curl_setopt($ch, CURLOPT_URL, $GLOBALS['URL_DATA_XML'].'?nocache=1');
        } else {
            trucAdir(3, 'Téléchargemet...');
            curl_setopt($ch, CURLOPT_URL, $GLOBALS['URL_DATA_XML']);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSLVERSION,3);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        $data = curl_exec ($ch);
        if(curl_errno($ch)){
            throw new Exception(curl_error($ch));
        }
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);     
        if($statusCode == 200){
            trucAdir(3, 'Téléchargé !');
            file_put_contents($GLOBALS['DATA_FILE'], $data);
            // Contrôle
            $xml_data_get=xml_data_get($DATA_FILE);
            if ($xml_data_get == null || $xml_data_get == false) {
                $retry=true;
                $nocacle=true;
                trucAdir(4, 'On patiente un peu...');
                sleep(5);
            } else {
                trucAdir(4, 'Les données sont bonnes !');
                $retry=false;
                $nocacle=false;
            }
        } else{
            trucAdir(2, 'Erreur de téléchargement : '.$statusCode);
            $retry=true;
            $nocacle=false;
            sleep(5);
        }
        if ($retry == false){
            trucAdir(5, 'Fin de la boucle / du script');
            break;
        }
    }
}
cronHelper::unlock();

?>
