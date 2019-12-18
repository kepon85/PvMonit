<?php
###################################
# Script sous licence BEERWARE
###################################

include('/opt/PvMonit/function.php');

// Chargement de la config
$config = getConfigYaml('/opt/PvMonit');

if (!is_file($config['domo']['jsonFile']['etatPath'])) {
	genDefaultJsonFile('etat');
}
if (!is_file($config['domo']['jsonFile']['modPath'])) {
	genDefaultJsonFile('mod');
}

$premierLancement=true;
			
function etatRelayRead($relayNumber) {
	global $config;
	$cmd = $config['domo']['binGpio'].' read '.$config['domo']['relayWiringPi'][$relayNumber];
	trucAdir(5, 'cmd relay '.$relayNumber.' : '.$cmd);
	exec($cmd, $output, $return_var);
	if ($return_var != 0) {
		trucAdir(1, 'La commande '.$config['domo']['binGpio'].' a échoué, est-ce que wiringpi est bien installé ? Est-ce que sudo est bien configuré ?');
		$return = false;
	} else { 
		$return = $output[0];
	}
	trucAdir(1, 'Etat du relay '.$relayNumber.' = '.$output[0]);
	return $return;
}

function etatRelayWrite($relayNumber, $ordre) {
	global $config;
	global $etatUp;
	global $etatDown;
	$cmd = $config['domo']['binGpio'].' write '.$config['domo']['relayWiringPi'][$relayNumber].' '.$ordre;
	trucAdir(5, 'cmd relay '.$relayNumber.' : '.$cmd);
	exec($cmd, $output, $return_var);
	if ($return_var != 0) {
		trucAdir(1, 'La commande '.$config['domo']['binGpio'].' a échoué, est-ce que wiringpi est bien installé ? Est-ce que sudo est bien configuré ?');
	}
	// Enregistrement de l'état
	$etats = json_decode(file_get_contents($config['domo']['jsonFile']['etatPath']), true);
	if ($ordre == $etatUp) {
		trucAdir(1, 'On enregistre l\'état du relay '.$relayNumber.' à 1');
		$etats[$relayNumber] = 1;
	} elseif ($ordre == $etatDown) {
		trucAdir(1, 'On enregistre l\'état du relay '.$relayNumber.' à 0');
		$etats[$relayNumber] = 0;
	}
	file_put_contents($config['domo']['jsonFile']['etatPath'], json_encode($etats));
}

$etatUp=0;
$etatDown=1;
$lastRefreshMod=0;
while (true) {
	clearstatcache();
	// Si le fichier à été modifié c'est qu'il y a quelque chose à changer...
	if (filemtime($config['domo']['jsonFile']['modPath']) > $lastRefreshMod) {
		trucAdir(4, 'Changement détecté');

		$relay_mods = json_decode(file_get_contents($config['domo']['jsonFile']['modPath']), true);
		$lastRefreshMod=time();

		foreach ($relay_mods as $id_relay => $relay_mod) {
			if ($relay_mod == 2 || $relay_mod == 3) {
				if (etatRelayRead($id_relay) == $etatDown) {
					trucAdir(2, 'On allume le relay '.$id_relay);
					etatRelayWrite($id_relay, $etatUp);
				} 
			} else {
				if (etatRelayRead($id_relay) == $etatUp) {
					trucAdir(2, 'On éteind le relay '.$id_relay);
					etatRelayWrite($id_relay, $etatDown);
				} 
			}
		}
		foreach ($relay_mods as $id_relay => $relay_mod) {
			if ($premierLancement==true){
				trucAdir(1, 'Premier lancement :  '.$id_relay);
				$cmd = $config['domo']['binGpio'].' mode '.$config['domo']['relayWiringPi'][$id_relay].' out';
				trucAdir(5, 'cmd : '.$cmd);
				exec($cmd, $output, $return_var);
			}
		}
		$premierLancement=false;
		
		
	}
	
	sleep($config['domo']['relayActionRefresh']);
}

?>
