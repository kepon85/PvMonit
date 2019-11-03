<?php

function getConfigYaml($config_dir){
        $config=yaml_parse_file($config_dir.'/config-default.yaml');
        $config_perso=yaml_parse_file($config_dir.'/config.yaml');
        
        foreach($config_perso as $key1=>$perso1) {
                if ($key1 == 'deviceCorrespondance') {
                        $config[$key1]=$perso1;
                } elseif (is_array($perso1)) {
                        foreach($perso1 as $key2=>$perso2) {
                                if (is_array($perso2)) {
                                        foreach($perso2 as $key3=>$perso3) {
                                                if (isset($config[$key1][$key2][$key3]))  {
                                                        $config[$key1][$key2][$key3]=$perso3;
                                                }
                                        }
                                }elseif (isset($config[$key1][$key2]))  {
                                        $config[$key1][$key2]=$perso2;
                                }
                        }
                } elseif (isset($config[$key1]))  {
                        $config[$key1]=$perso1;
                }
        }
        return $config;
}

# Victron : détermine le type d'appareil
# Source doc Victron "VE.Direct Protocol"
function ve_type($ve_pid) {
	if (substr($ve_pid, 0, -1) == '0x20') {
		$ve_type_retour='BMV';
	} else if (substr($ve_pid, 0, -2) == '0xA0' || $ve_pid == '0x300') {
		$ve_type_retour='MPTT';
	} else if (substr($ve_pid, 0, -2) == '0xA2') {
		$ve_type_retour='PhoenixInverter';
	} else {
		$ve_type_retour='Inconnu';
	}
	return $ve_type_retour;
}

# Victron : détermine le modèle de l'appareil
# Source doc Victron "VE.Direct Protocol"
function ve_modele($ve_pid) {
	switch ($ve_pid) {
		case '0x203': $ve_modele_retour='BMV-700'; break;
		case '0x204': $ve_modele_retour='BMV-702'; break;
		case '0x205': $ve_modele_retour='BMV-700H'; break;
		case '0xA04C': $ve_modele_retour='BlueSolar MPPT 75/10'; break;
		case '0x300': $ve_modele_retour='BlueSolar MPPT 70/15'; break;
		case '0xA042': $ve_modele_retour='BlueSolar MPPT 75/15'; break;
		case '0xA043': $ve_modele_retour='BlueSolar MPPT 100/15'; break;
		case '0xA044': $ve_modele_retour='BlueSolar MPPT 100/30 rev1'; break;
		case '0xA04A': $ve_modele_retour='BlueSolar MPPT 100/30 rev2'; break;
		case '0xA041': $ve_modele_retour='BlueSolar MPPT 150/35 rev1'; break;
		case '0xA04B': $ve_modele_retour='BlueSolar MPPT 150/35 rev2'; break;
		case '0xA04D': $ve_modele_retour='BlueSolar MPPT 150/45'; break;
		case '0xA040': $ve_modele_retour='BlueSolar MPPT 75/50'; break;
		case '0xA045': $ve_modele_retour='BlueSolar MPPT 100/50 rev1'; break;
		case '0xA049': $ve_modele_retour='BlueSolar MPPT 100/50 rev2'; break;
		case '0xA04E': $ve_modele_retour='BlueSolar MPPT 150/60'; break;
		case '0xA046': $ve_modele_retour='BlueSolar MPPT 150/70'; break;
		case '0xA04F': $ve_modele_retour='BlueSolar MPPT 150/85'; break;
		case '0xA047': $ve_modele_retour='BlueSolar MPPT 150/100'; break;
		case '0xA051': $ve_modele_retour='SmartSolar MPPT 150/100'; break;
		case '0xA050': $ve_modele_retour='SmartSolar MPPT 250/100'; break;
		case '0xA201': $ve_modele_retour='Phoenix Inverter 12V 250VA 230V'; break;
		case '0xA202': $ve_modele_retour='Phoenix Inverter 24V 250VA 230V'; break;
		case '0xA204': $ve_modele_retour='Phoenix Inverter 48V 250VA 230V'; break;
		case '0xA211': $ve_modele_retour='Phoenix Inverter 12V 375VA 230V'; break;
		case '0xA212': $ve_modele_retour='Phoenix Inverter 24V 375VA 230V'; break;
		case '0xA214': $ve_modele_retour='Phoenix Inverter 48V 375VA 230V'; break;
		case '0xA221': $ve_modele_retour='Phoenix Inverter 12V 500VA 230V'; break;
		case '0xA222': $ve_modele_retour='Phoenix Inverter 24V 500VA 230V'; break;
		case '0xA224': $ve_modele_retour='Phoenix Inverter 48V 500VA 230V'; break;
		default; $ve_modele_retour = 'Inconnu'; break;
	}
	return $ve_modele_retour;
}

# Victron : détermine plein de trucs en fonction du label
# Source doc Victron "VE.Direct Protocol"
function ve_label2($label, $valeur) {
        global $config;
	$veData['label']=$label;
	$veData['desc']=$label;
	$veData['value']=$valeur;
	$veData['units']='';
	$veData['screen']=0;
	$veData['smallScreen']=0;
        
	if (in_array($label, $config['www']['dataPrimaire'])) {
		$veData['screen']=1;
	} 
	if (in_array($label, $config['www']['dataPrimaireSmallScreen'])) {
		$veData['smallScreen']=1;
	} 
        
	switch ($label) {
		case 'V':
			$veData['value']=round($valeur*0.001, 2);
			$veData['desc']='Tension de la batterie';
			$veData['units']='V';
		break;
		case 'I':
			$veData['value']=$valeur*0.001;
			$veData['desc']='Courant de la batterie';
			$veData['units']='A';
		break;
		case 'PPV':
			$veData['desc']='Production des panneaux';
			$veData['descShort']='PV';
			$veData['units']='W';
		break;
		case 'ERR':
			$veData['desc']='Présence d\'erreur';
			if ($valeur == 0) {
				$veData['value']='Aucune';
			} else {
				switch ($veData['value']) {
					case 2: $veData['value'] = 'Battery voltage too high'; break;
					case 17: $veData['value'] = 'Charger temperature too high'; break;
					case 18: $veData['value'] = 'Charger over current'; break;
					case 19: $veData['value'] = 'Charger current reversed'; break;
					case 20: $veData['value'] = 'Bulk time limit exceeded'; break;
					case 21: $veData['value'] = 'Current sensor issue (sensor bias/sensor broken)'; break;
					case 26: $veData['value'] = 'Terminals overheated'; break;
					case 33: $veData['value'] = 'Input voltage too high (solar panel)'; break;
					case 34: $veData['value'] = 'Input current too high (solar panel)'; break;
					case 38: $veData['value'] = 'Input shutdown (due to excessive battery voltage)'; break;
					case 116: $veData['value'] = 'Factory calibration data lost'; break;
					case 117: $veData['value'] = 'Invalid/incompatible firmware'; break;
					case 119: $veData['value'] = 'User settings invalid'; break;
					default: $veData['value'] = $dataSplit[1]; break;
				}
			}
		break;
		case 'VPV':
			$veData['desc']='Voltage des panneaux';
			$veData['units']='mV';
		break;
		case 'H19':
			$veData['value']=$valeur*0.01;
			$veData['desc']='Le rendement total';
			$veData['units']='kWh';
		break;
		case 'H20':
			$veData['value']=$valeur*0.01;
			$veData['desc']='Rendement aujourd\'hui';
			$veData['units']='kWh';
		break;
		case 'H21':
			$veData['desc']='Puissance maximum ce jour';
			$veData['units']='W';
		break;
		case 'H22':
			$veData['value']=$valeur*0.01;
			$veData['desc']='Rendemain hier';
			$veData['units']='kWh';
		break;
		case 'H23':
			$veData['desc']='Puissance maximum hier';
			$veData['units']='W';
		break;
		case 'AR':
			$veData['desc']='Raison de l\'alarme';
			switch ($veData['value']) {
				case 0: $veData['value']= 'Aucune'; break;
				case 1: $veData['value']= 'Low Voltage'; break;
				case 2: $veData['value']= 'High Voltage'; break;
				case 4: $veData['value']= 'Low SOC'; break;
				case 8: $veData['value']= 'Low Starter Voltage'; break;
				case 16: $veData['value']= 'High Starter Voltage'; break;
				case 32: $veData['value']= 'Low Temperature'; break;
				case 64: $veData['value']= 'High Temperature'; break;
				case 128: $veData['value']= 'Mid Voltage'; break;
				case 256: $veData['value']= 'Overload'; break;
				case 512: $veData['value']= 'DC-ripple'; break;
				case 1024: $veData['value']= 'Low V AC out'; break;
				case 2048: $veData['value']= 'High V AC out'; break;
			}
		break;
		case 'CS':
			$veData['desc']='Status de charge';
			switch ($veData['value']) {
				case 0: $veData['value']= 'Off'; break;
				case 1: $veData['value']= 'Faible puissance'; break;
				case 2:	$veData['value']= 'Fault'; break;
				case 3:	$veData['value']= 'Bulk (en charge)'; break;
				case 4:	$veData['value']= 'Absorption';	break;
				case 5:	$veData['value']= 'Float (maintient la charge pleine)';	break;
				case 9:	$veData['value']= 'On';	break;
			}
		break;
		case 'P':
			$veData['desc']='Puissance instantané';
			$veData['units']='W';
		break;
		case 'T':
			$veData['desc']='Température de la batterie';
			$veData['units']='°C';
		break;
		case 'VM':
			$veData['desc']='Mid-point voltage of the battery bank';
			$veData['value']=$valeur*0.001;
			$veData['units']='V';
		break;
		case 'DM':
			$veData['desc']='Mid-point deviation of the battery bank';
			$veData['units']='%';
		break;
		case 'H17':
			$veData['desc']='Quantité d\'énergie déchargée';
			$veData['value']=$valeur*0.01;
			$veData['units']='kWh';
		break;
		case 'H18':
			$veData['desc']='Quantité d\'énergie chargée';
			$veData['value']=$valeur*0.01;
			$veData['units']='kWh';
		break;
		case 'H13':
			$veData['desc']='Number of low auxiliary voltage alarms';
		break;
		case 'H14':
			$veData['desc']='Number of high auxiliary voltage alarms';
		break;
		case 'VS':
			$veData['desc']='Auxiliary (starter) voltage';
			$veData['value']=$valeur*0.001;
			$veData['units']='V';
		break;
		case 'CE':
			$veData['desc']='Ampères heures consommées';
			$veData['value']=$valeur*0.001;
			$veData['units']='Ah';
		break;
		case 'SOC':
			$veData['desc']='État de charge';
			$veData['value']=$valeur/10;
			$veData['units']='%';
		break;
		case 'TTG':
			if ($veData['value'] == '-1') {				
				$veData['value'] = '&#8734;';
			} else {
			$total=$veData['value']*60;
			$jours=floor($total/86400);
			$reste=$total%86400;
			$heures=floor($reste/3600);
			$reste=$reste%3600;
			$minutes=floor($reste/60);
			$secondes=$reste%60;
			if ($veData['value'] > 1440) {				
				$veData['value'] = $jours . 'j '. $heures. 'h ' . $minutes .'m';
			} else {
				$veData['value'] = '.<b>'.$heures. 'h ' . $minutes .'m</b>';
			}
			}
			$veData['desc']='Temps restant';
		break;
		case 'Alarm':
			$veData['desc']='Condition d\'alarme active';
		break;
		case 'H1':
			$veData['desc']='Profondeur de la décharge la plus profonde';
			$veData['value']=$valeur*0.001;
			$veData['units']='Ah';
		break;
		case 'H2':
			$veData['desc']='Profondeur de la dernière décharge';
			$veData['value']=$valeur*0.001;
			$veData['units']='Ah';
		break;
		case 'H3':
			$veData['desc']='Profondeur de la décharge moyenne';
			$veData['value']=$valeur*0.001;
			$veData['units']='Ah';
		break;
		case 'H4':
			$veData['desc']='Nombre de cycles de charge';
		break;
		case 'H5':
			$veData['desc']='Nombre de cycles de décharge';
		break;
		case 'H6':
			$veData['desc']='Cumulative Amp Hours drawn';
			$veData['value']=$valeur*0.001;
			$veData['units']='Ah';
		break;
		case 'H7':
			$veData['desc']='Tension minimale batterie';
			$veData['value']=$valeur*0.001;
			$veData['units']='V';
		break;
		case 'H8':
			$veData['desc']='Tension maximale de la batterie';
			$veData['value']=$valeur*0.001;
			$veData['units']='V';
		break;
		case 'H9':
			$veData['desc']='Nombre de secondes depuis la dernière charge complète';
			$veData['units']='s';
		break;
		case 'H10':
			$veData['desc']='Nombre de synchronisations automatiques';
		break;
		case 'H11':
			$veData['desc']='Nombre d\'alarmes de tension faible';
		break;
		case 'H12':
			$veData['desc']='Nombre d\'alarmes de tension élevée';
		break;
		case 'H13':
			$veData['desc']='Minimum auxiliary (battery) voltage';
			$veData['value']=$valeur*0.001;
			$veData['units']='V';
		break;
		case 'H13':
			$veData['desc']='Maximum auxiliary (battery) voltage';
			$veData['value']=$valeur*0.001;
			$veData['units']='V';
		break;
		case 'MODE':
			$veData['desc']='Device mode';
			switch ($veData['value']) {
				case 2: $veData['value']= 'Inverter'; break;
				case 4: $veData['value']= 'Off'; break;
				case 5: $veData['value']= 'Eco'; break;
			}
		break;
		case 'AC_OUT_V':
			$veData['value']=$valeur*0.01;
			$veData['desc']='AC output voltage';
			$veData['units']='V';
		break;
		case 'AC_OUT_I':
			$veData['desc']='AC output current';
			$veData['value']=$valeur*0.1;
			$veData['units']='A';
		break;
		case 'WARN':
			$veData['desc']='Warning reason';
		break;
	}
	return $veData;
}


function ve_nom($ve_serial) {
        global $config;
	$ve_nom=$ve_serial;
	foreach ($config['deviceCorrespondance'] as $serialName => $nom) {
		if ($ve_serial == $serialName) {
			$ve_nom=$nom;
		}
	}
	return $ve_nom;
}

# Fonction vedirect MPTT / BMV
function vedirect_scan() {
        global $config;
	trucAdir(4, 'Recherche de périphérique vedirect');
	$idDevice=0;
	foreach (scandir('/dev') as $unDev) {
		if (substr($unDev, 0, 6) == 'ttyUSB') {
			trucAdir(4, 'Un périphérique TTY à été trouvé : '.$unDev);
			unset($vedirect_sortie);
			unset($vedirect_retour);
			exec($config['vedirect']['usb']['bin'].' /dev/'.$unDev, $vedirect_sortie, $vedirect_retour);
			if ($vedirect_retour != 0){
				trucAdir(1, 'Erreur à l\'exécution du script '.VEDIRECT_BIN.' sur le '.$unDev);
			} else {
				// Pour gérer le BMV-600
				$BMV600=false;
				$ve_nom=null;
                                $ve_type='Inconnu';
                                $ve_modele='Inconnu';
                                $ve_type='Inconnu';
				foreach ($vedirect_sortie as $vedirect_ligne) {
					$vedirect_data = explode(':', $vedirect_ligne);
					switch ($vedirect_data[0]) {
						case 'PID':
							$ve_type=ve_type($vedirect_data[1]);
							$ve_modele=ve_modele($vedirect_data[1]);
						break;
						case 'SER#':
							$ve_serial=$vedirect_data[1];
							$ve_nom=ve_nom($vedirect_data[1]);
						break;
						case 'BMV':
							$ve_type='BMV';
							$ve_nom=$vedirect_data[1];
						break;
					}
				}
				trucAdir(3, 'C\'est un '.$ve_type.', modèle "'.$ve_modele.'" du nom de '.$ve_nom);
				$vedirect_data_formate='';
				foreach ($vedirect_sortie as $vedirect_ligne) {
					$vedirect_data = explode(':', $vedirect_ligne);
					switch ($ve_type) {
						case 'MPTT':
							if (in_array($vedirect_data[0], $config['vedirect']['data_ok']['mppt'])) {
								# éviter les doublons
								if (!stristr($vedirect_data_formate, "$key:$value")) {
									trucAdir(5, 'Valeur trouvé : '.$vedirect_data[0].':'.$vedirect_data[1]);
									if ($vedirect_data_formate != '') {	
										$vedirect_data_formate = $vedirect_data_formate.',';
									}
									$vedirect_data_formate = $vedirect_data_formate.$vedirect_data[0].':'.$vedirect_data[1];
								} else {
									trucAdir(5, 'Doublon, on passe');
								}
							}
						break;
						case 'BMV':
							if (in_array($vedirect_data[0], $config['vedirect']['data_ok']['bmv'])) {
								if ($vedirect_data_formate != '') {
									$vedirect_data_formate = $vedirect_data_formate.',';
								}
								$vedirect_data_formate = $vedirect_data_formate.$vedirect_data[0].':'.$vedirect_data[1];
							}
						break;
                                                case 'PhoenixInverter':
                                                        if (in_array($key, $config['vedirect']['data_ok']['phoenix'])) {
                                                                if ($vedirect_data_formate != '') {
                                                                        $vedirect_data_formate = $vedirect_data_formate.',';
                                                                }
                                                                $vedirect_data_formate = $vedirect_data_formate.$key.':'.$value;
                                                        }
                                                break;
                                                default:
                                                        if ($vedirect_data_formate != '') {
                                                                $vedirect_data_formate = $vedirect_data_formate.',';
                                                        }
                                                        $vedirect_data_formate = $vedirect_data_formate.$key.':'.$value;
					}
				}
				trucAdir(3, 'Les données sont formatées comme ceci : '.$vedirect_data_formate );
				$vedirect_scan_return[$idDevice]['nom']=$ve_nom;
				$vedirect_scan_return[$idDevice]['type']=$ve_type;
				$vedirect_scan_return[$idDevice]['serial']=$ve_serial;
				$vedirect_scan_return[$idDevice]['modele']=$ve_modele;
				$vedirect_scan_return[$idDevice]['data']=$vedirect_data_formate;
				$idDevice++;
			}	
		}
	}
	return $vedirect_scan_return;
}

function vedirect_parse_arduino($data) {
        global $config;
        // Pour gérer le BMV-600
        $BMV600=false;
        $ve_nom=null;
        $ve_type='Inconnu';
        $ve_modele='Inconnu';
        $ve_serial='Inconnu';
        foreach ($data as $key => $value) {
                switch ($key) {
                        case 'PID':
                                $ve_type=ve_type($value);
                                $ve_modele=ve_modele($value);
                        break;
                        case 'SER#':
                                $ve_serial=$value;
                                $ve_nom=ve_nom($value);
                        break;
                        case 'BMV':
                                $ve_type='BMV';
                                $ve_nom=$value;
                        break;    
                } 
        }
        trucAdir(3, 'C\'est un '.$ve_type.', modèle "'.$ve_modele.'" du nom de '.$ve_nom);
        $vedirect_data_formate='';
        krsort($data);
        foreach ($data as $key => $value) {
                switch ($ve_type) {
                        case 'MPTT':
                                if (in_array($key, $config['vedirect']['data_ok']['mppt'])) {
                                        # éviter les doublons
                                        if (!stristr($vedirect_data_formate, "$key:$value")) {
                                                trucAdir(5, 'Valeur trouvé : '.$key.':'.$value);
                                                if ($vedirect_data_formate != '') {	
                                                        $vedirect_data_formate = $vedirect_data_formate.',';
                                                }
                                                $vedirect_data_formate = $vedirect_data_formate.$key.':'.$value;
                                        } else {
                                                trucAdir(5, 'Doublon, on passe');
                                        }
                                }
                        break;
                        case 'BMV':
                                if (in_array($key, $config['vedirect']['data_ok']['bmv'])) {
                                        if ($vedirect_data_formate != '') {
                                                $vedirect_data_formate = $vedirect_data_formate.',';
                                        }
                                        $vedirect_data_formate = $vedirect_data_formate.$key.':'.$value;
                                }
                        break;
                        case 'PhoenixInverter':
                                if (in_array($key, $config['vedirect']['data_ok']['phoenix'])) {
                                        if ($vedirect_data_formate != '') {
                                                $vedirect_data_formate = $vedirect_data_formate.',';
                                        }
                                        $vedirect_data_formate = $vedirect_data_formate.$key.':'.$value;
                                }
                        break;
                        default:
                                if ($vedirect_data_formate != '') {
                                        $vedirect_data_formate = $vedirect_data_formate.',';
                                }
                                $vedirect_data_formate = $vedirect_data_formate.$key.':'.$value;
                }
        }
        trucAdir(3, 'Les données sont formatées comme ceci : '.$vedirect_data_formate );
        $vedirect_scan_return['nom']=$ve_nom;
        $vedirect_scan_return['type']=$ve_type;
        $vedirect_scan_return['serial']=$ve_serial;
        $vedirect_scan_return['modele']=$ve_modele;
        $vedirect_scan_return['data']=$vedirect_data_formate;
        return $vedirect_scan_return;
}

# Fonction de debug
function trucAdir($niveau, $msg) {
        global $config;
	if ($config['printMessage'] >= $niveau) {
		if (isset($_SERVER['SERVER_NAME'])) {
			echo  '<script type="text/javascript">console.log(\''.date('c') . ' - ' . strtr($msg, '\'', '\\\'').'\'); </script>';
		} else {
			echo  date('c') . ' - ' . $msg."\n";
		}
	}
        if ($config['printMessageLogfile'] != false) {
                if (! is_file($config['printMessageLogfile'])) {
                        touch($config['printMessageLogfile']);
                        if (substr(sprintf('%o', fileperms($config['printMessageLogfile'])), -3) != '777')  {
                                chmod($config['printMessageLogfile'], 0777);
                        }
                }
                file_put_contents($config['printMessageLogfile'], date('c') . ' - ' . $_SERVER['SCRIPT_NAME']. ' - ' . $msg . "\n", FILE_APPEND);
        }
}

# Récupérer les informations de la sonde de température
function Temperature_USB($TEMPERV14_BIN) {
        global $config;
        # Exécussion du programme pour récupérer les inforamtions de la sonde de température
        exec($TEMPERV14_BIN, $temperv14_sortie, $temperv14_retour);
        if ($temperv14_retour != 0){
                trucAdir(3, 'La sonde de température n\'est probablement pas connecté.');
                trucAdir(5, 'Erreur '.$temperv14_retour.' à l\'exécussion du programme .'.$TEMPERV14_BIN);
                $temperature_retour='NODATA';
        } else {
                trucAdir(4, 'La sonde de température indique '.$temperv14_sortie[0].'°C, il y aura peut être correction.');
                $temperature_retour=$temperv14_sortie[0];
        }
	return $temperature_retour;
}

function Amp_USB($bin) {
        global $config;
        $consommation_retour='NODATA';
        for ($i = 1; $i <= 3; $i++) {
                trucAdir(3, 'Tentative '.$i.' de récupération de la sonde ');
                exec($bin.' | sed "s/A//" 2>/dev/null', $exec_consommation_sortie, $exec_consommation_retour);
                if ($exec_consommation_retour != 0){
                        trucAdir(3, 'L\'amphèrmètre n\'est probablement pas connecté.');
                        trucAdir(5, 'Erreur '.$exec_consommation_retour.' avec pour sortie .'.$exec_consommation_sortie);
                } else {
                        if ($exec_consommation_sortie[0] != '') {
                                trucAdir(3, 'Trouvé à la tentative '.$i.' : la La consommation trouvé est '.$exec_consommation_sortie[0].'A');
                                $re = '/^[0-9][0-9]+.[0-9]$/';
                                if (!preg_match_all($re, $exec_consommation_sortie[0])) {
                                        trucAdir(5, 'La vérification par expression régulière à échoué ('.$re.')');
                                } else {				
                                        $conso_en_w=$exec_consommation_sortie[0]*230;
                                        trucAdir(1, 'La consommation est de '.$exec_consommation_sortie[0].'A soit '.$conso_en_w.'W');
                                        if ($conso_en_w > $config['consoPlafond']) {
                                                trucAdir(1, 'C`est certainement une erreur, le plafond possible est atteind');
                                        } else {
                                                $consommation_retour=$exec_consommation_sortie[0];
                                        }
                                }
                                break;
                        } else {
                                trucAdir(5, 'Echec à la tentative '.$i.' : la La consommation trouvé est null');
                                sleep(1);
                        }
                }
        }
        return $consommation_retour;
}



// Class source : http://abhinavsingh.com/how-to-use-locks-in-php-cron-jobs-to-avoid-cron-overlaps/
class cronHelper {
	private static $pid;

	function __construct() {}

	function __clone() {}

	private static function isrunning() {
		$pids = explode(PHP_EOL, `ps -e | awk '{print $1}'`);
		if(in_array(self::$pid, $pids))
			return TRUE;
		return FALSE;
	}

	public static function lock() {
                global $config;
		global $argv;

		$lock_file = $config['emoncms']['lockFile'];

		if(file_exists($lock_file)) {
			//return FALSE;

			// Is running?
			self::$pid = file_get_contents($lock_file);
			if(self::isrunning()) {
				error_log("==".self::$pid."== Already in progress...");
				return FALSE;
			}
			else {
				error_log("==".self::$pid."== Previous job died abruptly...");
			}
		}

		self::$pid = getmypid();
		file_put_contents($lock_file, self::$pid);
		//error_log("==".self::$pid."== Lock acquired, processing the job...");
		return self::$pid;
	}

	public static function unlock() {
		global $argv;
                global $config;
		$lock_file = $config['emoncms']['lockFile'];

		if(file_exists($lock_file))
			unlink($lock_file);

		//error_log("==".self::$pid."== Releasing lock...");
		return TRUE;
	}

}

// Check cache expire
function checkCacheTime($file) {
        global $config;
        if (!is_dir($config['cache']['dir'])) {
                mkdir($config['cache']['dir'], 0777);
                chmod($config['cache']['dir'], 0777);
        }
        if (!is_file($file)) {
                return false;
        } else if (filemtime($file)+$config['cache']['time'] < time()) {
                return false;
        } else if (isset($_GET['nocache'])) {
                return false;
        } else {
                return true;
        }
}


?>
