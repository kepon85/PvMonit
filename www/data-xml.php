<?xml version="1.0" encoding="utf-8"?>
<devices>
<?php
###################################
# Script sous licence BEERWARE
# Version 1.0	2019
###################################
include_once('/opt/PvMonit/config-default.php');
include_once('/opt/PvMonit/config.php');
$PRINTMESSAGE=0;

include('/opt/PvMonit/function-v2.php');


			$ppv_total=null;
			$nb_ppv_total=0;
			foreach (vedirect_scan() as $device) {
				if ($device['serial']  == '') {
					$device['serial'] = $device['nom'];
				}
				echo "\n\t".'<device id="'.$device['serial'].'">';
				echo "\n\t\t".'<nom>'.$device['nom'].'</nom>';
				echo "\n\t\t".'<type>'.$device['type'].'</type>';
				echo "\n\t\t".'<modele>'.$device['modele'].'</modele>';
				echo "\n\t\t".'<serial>'.$device['serial'].'</serial>';
				echo "\n\t\t".'<datas>';
				sort($device['data']);
				foreach (explode(',', $device['data']) as $data) {
					$dataSplit = explode(':', $data);
					$veData=ve_label2($dataSplit[0], $dataSplit[1]);
					echo "\n\t\t\t".'<data id="'.$veData['label'].'" screen="'.$veData['screen'].'" smallScreen="'.$veData['smallScreen'].'">';
						echo "\n\t\t\t\t".'<desc>'.$veData['desc'].'</desc>';
						echo "\n\t\t\t\t".'<value>'.$veData['value'].'</value>';
						echo "\n\t\t\t\t".'<units>'.$veData['units'].'</units>';
					echo "\n\t\t\t".'</data>';
					if ($dataSplit[0] == 'PPV'){ 
						$ppv_total=$ppv_total+$dataSplit[1];
						$nb_ppv_total++;
					}
				}
				echo "\n\t\t".'</datas>';
				echo "\n\t".'</device>';
			}

			?>

	<device id="other">
		<nom>Divers</nom>
		<type></type>
		<modele></modele>
		<serial></serial>
		<datas>
			<?php 
			// Production totale
			if ($ppv_total !== null) {
				echo "".'<data id="PPVT" ';
				if (in_array('PPVT', $GLOBALS['WWW_DATA_PRIMAIRE'])) {
					echo ' screen="1"';
				} else {
					echo ' screen="0"';
				}
				if (in_array('PPVT', $GLOBALS['WWW_DATA_PRIMAIRE_SMALLSCREEN'])) {
					echo ' smallScreen="1"';
				}  else {
					echo ' smallScreen="0"';
				}
				echo '>
				<desc>Production total des panneaux</desc>
				<value>'.$ppv_total.'</value>
				<units>W</units>
				</data>';
			}
			
			// Conso ampèrmètre
			$consommation=consommationCache(); 
			if ($consommation !== null) {
				echo "\n\t\t\t".'<data id="CONSO"';
				if (in_array('CONSO', $GLOBALS['WWW_DATA_PRIMAIRE'])) {
					echo ' screen="1"';
				} else {
					echo ' screen="0"';
				}
				if (in_array('CONSO', $GLOBALS['WWW_DATA_PRIMAIRE_SMALLSCREEN'])) {
					echo ' smallScreen="1"';
				}  else {
					echo ' smallScreen="0"';
				}
				echo '>
				<desc>Consommation du foyer</desc>
				<value>'.$consommation.'</value>
				<units>W</units>
				</data>';
			}
			
			$temperature=temperatureCache(); 
			if ($temperature !== null) {
				echo "\n\t\t\t".'<data id="TEMP"';
				if (in_array('TEMP', $GLOBALS['WWW_DATA_PRIMAIRE'])) {
					echo ' screen="1"';
				} else {
					echo ' screen="0"';
				}
				if (in_array('TEMP', $GLOBALS['WWW_DATA_PRIMAIRE_SMALLSCREEN'])) {
					echo ' smallScreen="1"';
				}  else {
					echo ' smallScreen="0"';
				}
				echo '>
				<desc>Température du local</desc>
				<value>'.round($temperature,1).'</value>
				<units>°C</units>
				</data>';
			}
			?>
		
		</datas>		
	</device>
</devices>
