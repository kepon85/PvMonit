<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head>
	<title>Assistance PvMonit</title>
	<meta http-equiv="content-type" content="text/html;charset=utf-8" />
	<meta http-equiv="refresh" content="2;help.php"> 
</head>

<body>
<?php

include('/opt/PvMonit/function.php');

// Chargement de la config
$config = getConfigYaml('/opt/PvMonit');

if ($config['www']['help'] != true) {
	exit('L\'assistance n\'est pas activé dans votre fichier config.yaml (www/help)');
}

$fileCheck='/tmp/PvMonit_HELP';

	if ($_GET['action'] == 'stop') {
		unlink('/tmp/PvMonit_HELP');
		echo "<script language='javscript' type='text/javascript'>parent.window.close();</script>";
		echo "Arrêt demandé";
	}

if (is_file($fileCheck)) {
	$contenu=file_get_contents($fileCheck);
	if ($contenu == '1') {
		echo "Connexion assistance établie !<br />";
	} else {
		echo "Connexion assistance demandé... <br />";
	}
	echo '<input type="button" onclick="location.href=\'help.php?action=stop\';" value="Stopper la connexion à l\'assistance" />';
} else {
	if ($_GET['action'] == 'start') {
		touch('/tmp/PvMonit_HELP');
		echo "Démarage demandé<br />";
	} else {
		echo '<input type="button" onclick="location.href=\'help.php?action=start\';" value="Démarrer la connexion à l\'assistance" />';
		echo '<br />';
		echo '<a href="http://pvmonit.zici.fr" target="_blank">Le support de PvMonit</a> doit être au courant de votre requête';
	}
}

?>
</body>

</html>
