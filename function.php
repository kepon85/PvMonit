<?php

# Fonction de debug
function trucAdir($niveau, $msg) {
	if ($GLOBALS['PRINTMESSAGE'] >= $niveau) {
		echo  date('c') . ' - ' . $msg."\n";
	}
}

# Déterminer quel type de produit victron il s'agit
# Source doc Victron "VE.Direct Protocol"
function ve_type($ve_pid) {
	if (substr($ve_pid, 0, -1) == '0x20') {
		$ve_type_retour='BMV';
	} else if (substr($ve_pid, 0, -2) == '0xA0' || $ve_pid == '0x300') {
		$ve_type_retour='MPTT';
	} else {
		$ve_type_retour='Not supported';
	}
	return $ve_type_retour;
}

function ve_nom($ve_serial) {
	$ve_nom=$ve_serial;
	foreach ($GLOBALS['VEDIRECT_DEVICE_CORRESPONDANCE'] as $serialName => $nom) {
		if ($ve_serial == $serialName) {
			$ve_nom=$nom;
		}
	}
	return $ve_nom;
}

# Fonction vedirect MPTT / BMV
function vedirect_scan() {
	trucAdir(4, 'Recherche de périphérique vedirect');
	$idDevice=0;
	foreach (scandir('/dev') as $unDev) {
		if (substr($unDev, 0, 6) == 'ttyUSB') {
			trucAdir(4, 'Un périphérique TTY à été trouvé : '.$unDev);
			unset($vedirect_sortie);
			unset($vedirect_retour);
			exec($GLOBALS['VEDIRECT_BIN'].' /dev/'.$unDev, $vedirect_sortie, $vedirect_retour);
			if ($vedirect_retour != 0){
				trucAdir(1, 'Erreur à l\'exécution du script '.VEDIRECT_BIN.' sur le '.$unDev);
			} else {
				foreach ($vedirect_sortie as $vedirect_ligne) {
					$vedirect_data = explode(':', $vedirect_ligne);
					switch ($vedirect_data[0]) {
						case 'PID':
							$ve_type=ve_type($vedirect_data[1]);
						break;
						case 'SER#':
							$ve_nom=ve_nom($vedirect_data[1]);
						break;
					}
				}
				trucAdir(3, 'C\'est un '.$ve_type.' du nom de '.$ve_nom);
				$vedirect_data_formate='';
				foreach ($vedirect_sortie as $vedirect_ligne) {
					$vedirect_data = explode(':', $vedirect_ligne);
					switch ($ve_type) {
						case 'MPTT':
							if (in_array($vedirect_data[0], $GLOBALS['VEDIRECT_MPTT_DATA'])) {
								# éviter les doublons
								if (!stristr($vedirect_data_formate, $vedirect_data[0])) {
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
							if (in_array($vedirect_data[0], $GLOBALS['VEDIRECT_BMV_DATA'])) {
								if ($vedirect_data_formate != '') {
									$vedirect_data_formate = $vedirect_data_formate.',';
								}
								$vedirect_data_formate = $vedirect_data_formate.$vedirect_data[0].':'.$vedirect_data[1];
							}
						break;
					}
				}
				trucAdir(3, 'Les données sont formatées comme ceci : '.$vedirect_data_formate );
			}
			$vedirect_scan_return[$idDevice]['nom']=$ve_nom;
			$vedirect_scan_return[$idDevice]['type']=$ve_type;
			$vedirect_scan_return[$idDevice]['data']=$vedirect_data_formate;
			$idDevice++;
		}
	}
	return $vedirect_scan_return;
}

# Récupérer les informations de la sonde de température
function temperature() {
	$temperature_retour='NODATA';
	if (!is_file($GLOBALS['TEMPERV14_BIN'])) {
		trucAdir(3, 'Le programme temperv14 pour la sonde de température n\'existe pas');
	} else {
		# Exécussion du programme pour récupérer les inforamtions de la sonde de température
		exec($GLOBALS['TEMPERV14_BIN'].' -c 2>/dev/null', $temperv14_sortie, $temperv14_retour);
		if ($temperv14_retour != 0){
			trucAdir(3, 'La sonde de température n\'est probablement pas connecté.');
			trucAdir(5, 'Erreur '.$temperv14_retour.' à l\'exécussion du programme .'.$GLOBALS['TEMPERV14_BIN']);
		} else {
			trucAdir(4, 'La sonde de température indique '.$temperv14_sortie[0].'°C');
			$temperature=$temperv14_sortie[0]+$GLOBALS['SONDE_TEMPERATURE_CORRECTION'];
			trucAdir(3, 'Après correction, la température est de '.$temperature.'°C');
			$temperature_retour=$temperature;
		}
	}
	return $temperature_retour;
}

# Récupérer les informations de l'amphèrmètre}
function consommation() {
	$consommation_retour='NODATA';
	if (!is_file($GLOBALS['DEV_AMPEREMETRE'])) {
		for ($i = 1; $i <= 3; $i++) {
			trucAdir(3, 'Tentative '.$i.' de récupération de consommation');
			//trucAdir(5, 'Lancement de la commande : echo "~" | head -n 1 '.$GLOBALS['DEV_AMPEREMETRE'].'  | tail -c6 | sed "s/A//" 2>/dev/null');
			exec($GLOBALS['AMPEREMETRE_BIN'].' | sed "s/A//" 2>/dev/null', $exec_consommation_sortie, $exec_consommation_retour);
			if ($exec_consommation_retour != 0){
				trucAdir(3, 'L\'amphèrmètre n\'est probablement pas connecté.');
				trucAdir(5, 'Erreur '.$exec_consommation_retour.' avec pour sortie .'.$exec_consommation_sortie);
			} else {
				if ($exec_consommation_sortie[0] != '') {
					trucAdir(3, 'Trouvé à la tentative '.$i.' : la La consommation trouvé est '.$exec_consommation_sortie[0].'A');
					$re = '/[0-9][0-9]+.[0-9]$/';
					if (!preg_match_all($re, $exec_consommation_sortie[0])) {
						trucAdir(5, 'La vérification par expression régulière à échoué ('.$re.')');
					} else {				
						$conso_en_w=$exec_consommation_sortie[0]*230;
						trucAdir(1, 'La consommation est de '.$exec_consommation_sortie[0].'A soit '.$conso_en_w.'W');
						if ($conso_en_w > $GLOBALS['CONSO_PLAFOND']) {
							trucAdir(1, 'C`est certainement une erreur, le plafond possible est atteind');
						} else {
							$consommation_retour=$conso_en_w;
						}
					}
					break;
				} else {
					trucAdir(5, 'Echec à la tentative '.$i.' : la La consommation trouvé est null');
					sleep(1);
				}
			}
		}
	} else {
		trucAdir(3, 'Le périphérique '.$GLOBALS['DEV_AMPEREMETRE'].' n\'est pas connecté');
	}
	return $consommation_retour;
}

function temperatureCache() {
	$ficherCache=$GLOBALS['WWW_CACHE_FILE'].'temp';
	if (!file_exists($ficherCache)) {
		$temperature=temperature();
		file_put_contents($ficherCache, $temperature);
		return $temperature;
	} else {
		$dateFichierCache=filemtime($ficherCache);
		$finDuCache=$dateFichierCache+$GLOBALS['WWW_CACHE_AGE'];
		if (time() < $finDuCache) {
			return file_get_contents($ficherCache);
		} else {
			$temperature=temperature();
			file_put_contents($ficherCache, $temperature);
			return $temperature;
		}
	}
}
function consommationCache() {
	$ficherCache=$GLOBALS['WWW_CACHE_FILE'].'conso';
	if (!file_exists($ficherCache)) {
		$consommation=consommation();
		file_put_contents($ficherCache, $consommation);
		return $consommation;
	} else {
		$dateFichierCache=filemtime($ficherCache);
		$finDuCache=$dateFichierCache+$GLOBALS['WWW_CACHE_AGE'];
		if (time() < $finDuCache) {
			return file_get_contents($ficherCache);
		} else {
			$consommation=consommation();
			file_put_contents($ficherCache, $consommation);
			return $consommation;
		}
	}
}

?>
