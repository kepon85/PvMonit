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


include('./PhpSerial.php');


$serial = new PhpSerial();


while (true) {
    if ($serial->deviceSet("/dev/ttyUSB0")) {
        $serial->confBaudRate(9600);
        $serial->confParity("none");
        $serial->confCharacterLength(8);
        $serial->confStopBits(1);
        $serial->confFlowControl("none");

        trucAdir(4, 'Lancement du script');

        $serial->deviceOpen();

        trucAdir(4, 'Le device est prêt !');

        $heartBeatCount=0;
        $xml_last_check=0;
        $xml_check_error=$GLOBALS['XML_CHECK_ERROR'];
        $relay_script_last_exec=0;
        while(true) {
            
            if ($xml_last_check+$GLOBALS['XML_CHECK_TIME'] < time()) {
                trucAdir(3,  "XML data recup");
                if (!is_file($GLOBALS['DATA_FILE'])) {
                    trucAdir(1, "Êtes vous certain d'avoir correctement configuré la tâche planifié qui télécharge les données XML dans $DATA_FILE ?");
                    $xml_check_error++;
                } else if (filemtime($GLOBALS['DATA_FILE'])+$GLOBALS['DATA_FILE_TIMEOUT'] < time()) {
                    trucAdir(1, "Le fichier data est périmé !");
                    $xml_check_error++;
                } else {
                    $xml_data_get=xml_data_get($DATA_FILE);
                    if ($xml_data_get == null || $xml_data_get == false) {
                        trucAdir(2, 'Données XML invalide');
                        $xml_check_error++;
                    } else {
                        trucAdir(4, 'Les données sont bonnes !');
                        $xml_data_get = xml_data_get($GLOBALS['DATA_FILE']);
                        $xml_check_error=0;
                    }
                }
                $xml_last_check=time();
            }
            // Si pas trop d'erreur
            if ($xml_check_error <= $GLOBALS['XML_CHECK_ERROR']) {
                // 
                // Expédition du heartBeat
                //
                if ($heartBeatCount >= $HEARTBEAT_FREQ) {
                    $serial->sendMessage("H \n");
                    trucAdir(5, 'Heardbeat !');
                    $heartBeatCount=0;
                } else {
                    $heartBeatCount++;
                }
          
                //
                // Lecture des données
                //
                $read = $serial->readPort();
                if ($read) {

                    $serialDatas=explode("\n",$read);
           
                    foreach ($serialDatas as $serialData) {
                        if (preg_match('/^ARDOMO DEBUG/', $serialData)) {
                            trucAdir(5, $serialData);
                        } else if (preg_match('/^ARDOMO/', $serialData)) {
                            trucAdir(2, $serialData);
                        } else if (preg_match('/^E\|/', $serialData)) {
                            trucAdir(5, "Seri2 : $serialData");
                            $serialDataExplode = explode("|", $serialData);
                            // Vérification du nombre d'information (sinon c'est une erreur)
                            if (count($serialDataExplode)-1 == $NBRELAY) {
                                trucAdir(3, "Etat des relays : $serialData");
                                $i=0;
                                foreach($serialDataExplode as $relayEtatValue) {
                                    if ($relayEtatValue != "E") {
                                        $relayEtatJustValue = explode(":", $relayEtatValue);
                                        $relayEtat[$i]=$relayEtatJustValue[1];
                                        $i++;
                                    }
                                }
                            }
                        } else if (preg_match('/^M\|/', $serialData)) {
                            trucAdir(5, "Seri3 : $serialData");
                            $serialDataExplode = explode("|", $serialData);
                            // Vérification du nombre d'information (sinon c'est une erreur)
                            if (count($serialDataExplode)-1 == $NBRELAY) {
                                trucAdir(3, "Mode des relays : $serialData");
                                $i=0;
                                foreach($serialDataExplode as $relayModValue) {
                                    if ($relayModValue != "M") {
                                        $relayModJustValue = explode(":", $relayModValue);
                                        $relayMod[$i]=$relayModJustValue[1];
                                        $i++;
                                    }
                                }
                            }
                        /*} else {
                            trucAdir(5, "Serial ? : $serialData");*/
                        }
                    }
                    
                }
                //
                // Traitement, ordre...
                //
                if ($relay_script_last_exec+$GLOBALS['RELAY_SCRIPT_EXEC_INTERVAL'] < time()) {
                    trucAdir(3, "Traitement des ordres");
                    if (isset($relayMod) && isset($relayEtat)) {
                        foreach ($relayMod as $relay => $Mod) {
                            // On s'occupe uniquement de ceux qui sont en mode auto
                            if ($Mod == 2) {
                                if (is_file($GLOBALS['RELAY_SCRIPT_DIR'].$relay.'.php')) {
                                    $r['etat']=$relayEtat[$relay];
                                    $r['id']=$relay;
                                    $d=$xml_data_get;
                                    $script_return = (include $GLOBALS['RELAY_SCRIPT_DIR'].$relay.'.php');
                                    if ($relayEtat[$relay] != $script_return) {
                                        trucAdir(3, "Changement d'état pour le relay ".$relay." (vers ".$script_return.")");
                                        $serial->sendMessage('RO:'.$relay.'='.$script_return."\n");
                                    } else {
                                        trucAdir(3, "Aucun changement d'état pour le relay ".$relay);
                                    }
                                }
                            }
                        }
                    } else {
                        trucAdir(3, "Aucune donnée sur les relay exploitable pour pouvoir lancer des actions");
                    }
                    $relay_script_last_exec=time();
                }
                sleep(0.5);
            } else {
                trucAdir(1, 'Trop d\'erreur ('.$xml_check_error.') sur le xml, on stop le heartbeat.');
                sleep(10);
            }
        }
        trucAdir(4, 'Fin du script !');
        
    } else {
        trucAdir(4, 'Le device n\'est pas prêt, on patiente 30s !');
        sleep(3); 
    }
}
?>
