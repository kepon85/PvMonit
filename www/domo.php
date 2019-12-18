<?php

include('/opt/PvMonit/function.php');

// Chargement de la config
$config = getConfigYaml('/opt/PvMonit');

if (isset($_GET['action'])) {
	if ($_GET['action'] == 'printRefresh') {
		echo '{"etat":'.file_get_contents($config['domo']['jsonFile']['etatPath']);
		echo ',"mod":'.file_get_contents($config['domo']['jsonFile']['modPath']).'}';
	} else if ($_GET['action'] == 'changeMod') {
		if (isset($_GET['idRelay']) && isset($_GET['changeTo'])) {
			$json = json_decode(file_get_contents($config['domo']['jsonFile']['modPath']), true);
			switch ($_GET['changeTo']) {
				case 'off':
					$changeTo = 0;
					break;
				case 'auto':
					$changeTo = 1;
					break;
				case 'on':
					$changeTo = 3;
					break;
			}
			
			$json[$_GET['idRelay']] = $changeTo;
			
			file_put_contents($config['domo']['jsonFile']['modPath'], json_encode($json));
			echo '{"result":0}';
		} else {
			exit('No Hack 2');
		}
	}
} else {
	exit('No Hack');
}

?>

