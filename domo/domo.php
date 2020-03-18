<?php
include('/opt/PvMonit/function.php');

// Chargement de la config
$config = getConfigYaml('/opt/PvMonit');


function xml_data_get($DATA_FILE)  {
    global $config;
    $xmlData = null;
    try {
       
        $devices = new SimpleXMLElement(file_get_contents($DATA_FILE));
        foreach ($devices as $device) {
            //~ // On vérifie que la donnée ne soit pas périmé
            if ($device->timerefresh+$config['domo']['xmlDataExpir'] > time()) {
                foreach ($device->datas->data as $data) {
                    foreach ($config['domo']['valueUse'] as $id => $regexCheck) {
                        if ($data['id'] == $id) {
                            $xmlDataTemp = json_decode(json_encode($data->value), true);
                            trucAdir(5, 'XML parse : la valeur pour '.$data['id'].' à été trouvé à '.$xmlDataTemp[0]);
                            if (preg_match_all('/'.$regexCheck.'/', $xmlDataTemp[0])) {
                                $xmlData[$id] = $xmlDataTemp[0];
                            } else {
                                trucAdir(5, 'XML parse ERROR : La vérification regex pour '.$data['id'].' n\'est pas correct pour la valeur '.$xmlDataTemp[0].' ('.$regexCheck.')');
                                $xmlData[$id] = false;
                            }
                        }
                    }
                }
            } else {
                $xmlData = false;
                trucAdir(2, 'Les données sont périmées');
            }
        }
        
    } catch (Exception $e ) {
        $xmlData = false;
        trucAdir(2, 'Impossible de lire l\'XML : '.$e);
    }
    
    return $xmlData;
}

function MpptAbsOrFlo($cs) {
    if (preg_match_all('/^Absorption|^Float/', $cs)) {
        return true;
    } else {
        return false;
    }
}

function MpptFlo($cs) {
    if (preg_match_all('/^Float/', $cs)) {
        return true;
    } else {
        return false;
    }
}



trucAdir(4, 'Lancement du script');

if (!is_file($config['domo']['jsonFile']['etatPath'])) {
	genDefaultJsonFile('etat');
}
if (!is_file($config['domo']['jsonFile']['modPath'])) {
	genDefaultJsonFile('mod');
}


$initDb=false;
if (!is_file($config['domo']['dbFile'])) {
    $initDb=true;
}
// Connect DB
try {
    $dbco = new PDO('sqlite:'.$config['domo']['dbFile']);
	$dbco->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch ( PDOException $e ) {
	die('Connexion à la base : '.$e->getMessage());
}
// Create DB if not exists
if ($initDb==true) {
    try {
        $create = $dbco->query("CREATE TABLE relay (id INTEGER PRIMARY KEY, 
                                                      relay_number INTEGER, 
                                                      info VARCHAR(2), 
                                                      valeur INTEFER, 
                                                      date INTEGER, 
                                                      event TEXT)");
    } catch ( PDOException $e ) {
        echo 'Error initializing tables. Please contact the admin';
        die();
    }
}

function logInDb($relay, $info, $valeur, $event) {
    global $dbco;
    try {
		$insertcmd = $dbco->prepare("INSERT INTO relay (relay_number, info, valeur, date, event) 
										VALUES (:relay, :info, :valeur, ".time().", :event)");
		$insertcmd->bindParam('relay', $relay, PDO::PARAM_INT);
        $insertcmd->bindParam('info', $info, PDO::PARAM_STR);
		$insertcmd->bindParam('valeur', $valeur, PDO::PARAM_INT);
		$insertcmd->bindParam('event', $event, PDO::PARAM_STR);
		$insertcmd->execute();
	} catch ( PDOException $e ) {
		echo "DB error :  ", $e->getMessage();
		die();
	}
}


function relayLastUp($relay) {
    global $dbco;
    return  $dbco->query("SELECT date FROM relay WHERE relay_number = ".$relay." AND info = 'E' AND valeur = 1 ORDER BY date DESC LIMIT 1")->fetchColumn();        
}

function relayLastUpAuto($relay) {
    global $dbco;
    return  $dbco->query("SELECT date FROM relay WHERE relay_number = ".$relay."  AND info = 'M' AND valeur = 2 ORDER BY date DESC LIMIT 1")->fetchColumn();  
}

function relayLastDown($relay) {
    global $dbco;
    return  $dbco->query("SELECT date FROM relay WHERE relay_number = ".$relay." AND info = 'E' AND valeur = 0 ORDER BY date DESC LIMIT 1")->fetchColumn();  
}

# Est-ce que le relay c'est allumé puis est maintenant éteind aujourd'hui ? (dans les 12 heures)
function relayUpDownToday($relay) {
    global $dbco;
    $result = $dbco->query("SELECT count(date) FROM relay WHERE relay_number = ".$relay." AND info = 'M' AND (valeur = 1 OR valeur = 2) AND date > ".(time()-43200)." ORDER BY date DESC LIMIT 2")->fetchColumn();  
    if ($result == 2) {
        return true;
    } else {
        return false;
    }
}
# Est-ce que le relay c'est allumé aujourd'hui ? (dans les 12 heures)
function relayUpToday($relay) {
    global $dbco;
    $result = $dbco->query("SELECT count(date) FROM relay WHERE relay_number = ".$relay." AND info = 'E' AND valeur = 1 AND date > ".(time()-43200)." ORDER BY date DESC LIMIT 1")->fetchColumn();  
    if ($result >= 1) {
        return true;
    } else {
        return false;
    }
}
function timeUpMax($relay, $timeUp) {
    if (relayLastUpAuto($relay)+$timeUp < time()) {
        return true;
    } else {
        return false;
    }
}
function timeUpMin($relay, $timeUp) {
    if (relayLastUpAuto($relay)+$timeUp > time()) {
        return true;
    } else {
        return false;
    }
}



$dataCheckTime=0;
$xml_check_error=0;
$downloadNow=false;
$dataRefresh=false;
$relay_script_last_exec = 0;
$lastRefreshMod = 0;
$lastRefreshEtat = 0;
$dernierScriptJoue = 0;
while(true) {
    // Gestion du XML
    if (!is_file($config['tmpFileDataXml'])) {
        trucAdir(2, "Fichier inexistant");
        $downloadNow=true;    
    } else if (filemtime($config['tmpFileDataXml'])+$config['domo']['fileExpir'] < time()) {
        trucAdir(2, "Le fichier data est périmé !");
        $downloadNow=true;
    } elseif ($dataCheckTime+$config['domo']['dataCheckTime'] < time()) {
        trucAdir(4, "Préparation du rafraichissement des données");
        if (filemtime($config['tmpFileDataXml'])+$config['domo']['dataCheckTime'] > time()) {
            trucAdir(5, "Pas de téléchargement, le fichier semble très résent (peut être téléchargé par un autre script... lcd.py ?)"); 
            $dataRefresh=true;
        } else {
            $downloadNow=true;
        }
    } else {
        if ($xml_check_error == 0) {
            $downloadNow=false;
        }
    }

    // Téléchargement
    if ($downloadNow == true) {
        trucAdir(2, "Téléchargement du fichier xml");
        if ($xml_check_error != 0) {
            trucAdir(2, "C'est suite à une erreur, on patiente un peu...");
            sleep(10);
        }
        $opts = array('http' =>
            array(
                'method'  => 'GET',
                'timeout' => 60
            )
        );
        $context  = stream_context_create($opts);
        $result = file_get_contents($config['urlDataXml'], false, $context);
        file_put_contents($config['tmpFileDataXml'], $result);
        $dataRefresh=true;
        $downloadNow=false;
    }
    
    if ($dataRefresh==true) {
        trucAdir(4, "Rafraichissement des données");

        $xml_data_get=xml_data_get($config['tmpFileDataXml']);
        if ($xml_data_get == null || $xml_data_get == false) {
            trucAdir(2, 'Données XML invalide');
            $xml_check_error++;
            $downloadNow=true;
        } else {
            $xml_data_error=0;
            foreach ($xml_data_get as $id => $xml_data) {
                if ($xml_data === false) {
                    trucAdir(5, 'ERROR donnée '.$id.' est à false');
                    $xml_data_error++;
                }
            }
            if ($xml_data_error != 0) {
                trucAdir(4, 'ERROR Certaine données (dans config.yaml : valueUse) ne sont pas conforme ou pas présente.');
                $xml_check_error++;
                $downloadNow=true;
            } else {
                trucAdir(4, 'Les données sont bonnes !');
                $xml_check_error=0;
                $dataCheckTime=time();
            }
        }
        $dataRefresh=false;
    }
    
    // Sécurité, si pas de data pendant trop de temps, on éteind les relay en mod haut
    if ($dataCheckTime+$config['domo']['relay']['secuDownNoData'] < time()) {
        trucAdir(5, "SECURITE, trop de temps c'est écoulé sans donnée, on passe les relays en mode 2 (auto on) à 1 (auto off)");
        $relayModSecu = json_decode(file_get_contents($config['domo']['jsonFile']['modPath']), true);
        foreach ($relayModSecu as $relay => $Mod) {
            if ($Mod == 2) {
                $relayModSecu[$relay] = 1;
            }
        }
        file_put_contents($config['domo']['jsonFile']['modPath'], json_encode($relayModSecu));
    }
    
    // Récupération et enregistrement des données de mod
    if (filemtime($config['domo']['jsonFile']['modPath']) > $lastRefreshMod) {
        trucAdir(4, "Récupération des mods des relay");
        $relayMod_New = json_decode(file_get_contents($config['domo']['jsonFile']['modPath']), true);
        if (isset($relayMod)) {
            foreach (array_diff_assoc($relayMod_New,$relayMod) as $relayIdDiff => $relayModDiff) {
                trucAdir(4, "Enregistrement du changement sur le relay ".$relayIdDiff." vers le mod ".$relayModDiff);
                if (isset($saveInDb[$relayIdDiff]) && !is_null($saveInDb[$relayIdDiff])) {
                    trucAdir(4, "Avec le log ".$saveInDb[$relayIdDiff]['log']);
                    logInDb($relayIdDiff, 'M', $relayModDiff, $saveInDb[$relayIdDiff]['log']);
                    $saveInDb[$relayIdDiff]=null;
                } else {
                    logInDb($relayIdDiff, 'M', $relayModDiff, '');
                }
            }
        }
        $relayMod = $relayMod_New;
        $lastRefreshMod=time();
    }
    // Récupération et enregistrement des données d'état
    if (filemtime($config['domo']['jsonFile']['etatPath']) > $lastRefreshEtat) {
        trucAdir(4, "Récupération de l'état des relay");
        $relayEtat_New = json_decode(file_get_contents($config['domo']['jsonFile']['etatPath']), true);
        if (isset($relayEtat)) {
            foreach (array_diff_assoc($relayEtat_New,$relayEtat) as $relayIdDiff => $relayEtatDiff) {
                trucAdir(4, "Enregistrement du changement sur le relay ".$relayIdDiff." vers l'état ".$relayEtatDiff);
                logInDb($relayIdDiff, 'E', $relayEtatDiff, '');
            }
        }
        $relayEtat = $relayEtat_New;
        $lastRefreshEtat=time();
    }
    
    //
    // Traitement, ordre...
    //
    if ($xml_check_error == 0) {
        if ($relay_script_last_exec+$config['domo']['relay']['scriptExecInterval'] < time()) {
            trucAdir(3, "Traitement des ordres");
            if (is_array($relayMod) && is_array($relayEtat)) {
                foreach ($relayMod as $relay => $Mod) {
                    //~ if ($relay > $dernierScriptJoue) {
                        // On s'occupe uniquement de ceux qui sont en mode auto
                        if ($Mod == 1 || $Mod == 2) {
                            if (is_file($config['domo']['relay']['scriptDir'].'/'.$relay.'.php')) {
                                $thisId=$relay;
                                $thisEtat=$relayEtat[$relay];
                                $thisMod=$relayMod[$relay];
                                $data=$xml_data_get;
                                $script_return = (include $config['domo']['relay']['scriptDir'].'/'.$relay.'.php');
                                if ($relayMod[$relay] != $script_return['mod'] && $script_return != null) {
                                    trucAdir(1, "Changement de mod pour le relay ".$relay." (".$thisMod." vers ".$script_return['mod'].")");
                                    trucAdir(2, "Pourquoi ? : ".$script_return['log']);
                                    $relayModPut=$relayMod;
                                    $relayModPut[$relay] = $script_return['mod'];
                                    file_put_contents($config['domo']['jsonFile']['modPath'], json_encode($relayModPut));
                                    $saveInDb[$relay]['mod'] = $script_return['mod'];
                                    $saveInDb[$relay]['log'] = $script_return['log'];
                                    $dernierScriptJoue = $relay;
                                    break;
                                } else {
                                    trucAdir(3, "Aucun changement d'état pour le relay ".$relay);
                                    if ($script_return['log'] != null) {
                                        trucAdir(3, '['.$thisId.']'.$script_return['log']);
                                    }
                                }
                            } else {
                                trucAdir(5, "Pas de script pour le relay ".$relay);
                            }
                        } else {
                            trucAdir(5, "Le relay ".$relay." n'est pas en mod automatique");
                        }
                        
                    //~ }
                    //~ $dernierScriptJoue = $relay;
                }
                //~ if ($dernierScriptJoue >= $config['domo']['relayNb']) {
                    //~ $dernierScriptJoue = 0;
                //~ }
            } else {
                trucAdir(3, "Aucune donnée sur les relay exploitable pour pouvoir lancer des actions");
            }
            $relay_script_last_exec=time();
        }
    }
    
    
    
    sleep(1);

}

?>
