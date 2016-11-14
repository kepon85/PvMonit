#!/usr/bin/php
<?php

######################################################################
# PvMonit - By David Mercereau : http://david.mercereau.info/contact/
# Script sous licence BEERWARE
# Version 0.2	2016
######################################################################

include_once('/opt/PvMonit/config-default.php');
if (is_file('/opt/PvMonit/config.php')) {
	include_once('/opt/PvMonit/config.php');
}
$PRINTMESSAGE=0;
include('/opt/PvMonit/function.php');

# Scan des périphérique VE.Direct Victron
foreach (vedirect_scan() as $device) {
	echo $device['nom']." : \n";
	foreach (explode(',', $device['data']) as $data) {
		$dataSplit = explode(':', $data);
		if (isset($argv[1]) && preg_match_all('/detail/', $argv[1])) {
			$veData=ve_label2($dataSplit[0], $dataSplit[1]);
			echo "\t".$veData['desc']." : ".$veData['value'].$veData['units']."\n";
		} else {
			if (in_array($dataSplit[0], $GLOBALS['WWW_VEDIRECT_DATA_PRIMAIRE'])) {
				$veData=ve_label2($dataSplit[0], $dataSplit[1]);
				echo "\t".$veData['desc']." : ".$veData['value'].$veData['units']."\n";
			}
		}
	}
}
$temperature=temperature();
if ($temperature !== 'NODATA') {
	echo "Température : ".$temperature."°C\n";
}
$consommation=consommation();
if ($consommation !== 'NODATA') {
	echo "Consommation : ".$consommation."W\n";
}



?>
