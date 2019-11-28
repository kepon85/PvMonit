<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:  application/xml");
?>
<?xml version="1.0" encoding="utf-8"?>
<devices>
<?php
###################################
# Script sous licence BEERWARE
# Version 1.0	2019
###################################

include('/opt/PvMonit/function.php');

// Chargement de la config
$config = getConfigYaml('/opt/PvMonit');
$config['printMessage']=0;

trucAdir(2, "Appel du data-xml.php");

function onScreenPrint($name) {
        global $config;
        if (in_array($name, $config['www']['dataPrimaire'])) {
                $screenVal=' screen="1"';
        } else {
                $screenVal= ' screen="0"';
        }
        if (in_array($name, $config['www']['dataPrimaireSmallScreen'])) {
                $screenVal.= ' smallScreen="1"';
        }  else {
                $screenVal.= ' smallScreen="0"';
        }
        return $screenVal;
}

$ppv_total=null;
$bmv_p=null;
$nb_ppv_total=0;
if ($config['vedirect']['by'] == 'usb') {
        $cache_file=$config['cache']['dir'].'/'.$config['cache']['file_prefix'].'vedirect_scan';
        if(!checkCacheTime($cache_file)) {
                file_put_contents($cache_file, json_encode(vedirect_scan()));
                if (substr(sprintf('%o', fileperms($cache_file)), -3) != '777')  {
                        chmod($cache_file, 0777);
                }
        } 
        $timerefresh=filemtime($cache_file);
        $vedirect_data_ready = json_decode(file_get_contents($cache_file), true);
} elseif ($config['vedirect']['by'] == 'arduino') { 
        if (! is_file($config['vedirect']['arduino']['data_file'])) {
                $vedirect_data_ready[0]['id'] = 'Vedirect serial get by arduino';
                $vedirect_data_ready[0]['nom'] = 'Vedirect erreur';
                $vedirect_data_ready[0]['serial'] = 'vedirect';
                $vedirect_data_ready[0]['data']= 'Fichier de donnée:Introuvable';
        } else if (filemtime($config['vedirect']['arduino']['data_file'])+$config['vedirect']['arduino']['data_file_expir']  < time()) {
                $vedirect_data_ready[0]['id'] = 'Vedirect serial get by arduino';
                $vedirect_data_ready[0]['nom'] = 'Vedirect erreur';
                $vedirect_data_ready[0]['serial'] = 'vedirect';
                $vedirect_data_ready[0]['data']= 'Fichier de donnée :Périmé';
        } else {
                $arduino_data=yaml_parse_file($config['vedirect']['arduino']['data_file']);
                $idDevice=0;
                foreach ($arduino_data as $device_id => $device_data) {
                        if (preg_match_all('/^Serial[0-9]$/m', $device_id)) {
                                $device_vedirect_data[$idDevice]=vedirect_parse_arduino($device_data);
                                $idDevice++;
                        }
                }
                $vedirect_data_ready = $device_vedirect_data;
        } 
}
foreach ($vedirect_data_ready as $device) {
        if ($device['serial']  == 'Inconnu' || $device['serial']  == '') {
                $device['serial'] = $device['nom'];
        }
        echo "\n\t".'<device id="'.str_replace(' ', '', $device['serial']).'">';
        echo "\n\t\t".'<nom>'.$device['nom'].'</nom>';
        echo "\n\t\t".'<timerefresh>'.time().'</timerefresh>';
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
                if ($device['type'] == "BMV" && $dataSplit[0] == 'P'){ 
                        $bmv_p=$dataSplit[1];
                }
        }
        echo "\n\t\t".'</datas>';
        echo "\n\t".'</device>';
}


# Divers
$bin_enabled_data = scandir($config['dir']['bin_enabled']);
if(count($bin_enabled_data) > 2 || $config['data']['ppv_total'] || $config['data']['ppv_total']) {
?>
	<device id="other">
		<nom>Divers</nom>
		<timerefresh></timerefresh>
		<type></type>
		<modele></modele>
		<serial></serial>
		<datas>
			<?php 
			// Production totale
			if ($config['data']['ppv_total'] && $ppv_total !== null) {
				echo '<data id="PPVT"'.onScreenPrint('PPVT').'>
				<desc>Production total des panneaux</desc>
				<value>'.$ppv_total.'</value>
				<units>W</units>
				</data>';
			}
			?>
                        <?php 
			// Calcul consommation foyer
			if ($config['data']['ppv_total'] && $config['data']['conso_calc'] && $ppv_total !== null && $bmv_p != null) {
                                $test=$ppv_total-$bmv_p;
				echo '<data id="CONSO"'.onScreenPrint('CONSO').'>
				<desc>Consommation du foyer</desc>
				<value>'.abs($test).'</value>
				<units>W</units>
				</data>';
			}
			?>
		</datas>		
	</device>
        <?php 
        trucAdir(3, "Scan du répertoire bin-enable");
        // Scan du répertoire bin-enabled
        foreach ($bin_enabled_data as $bin_script_enabled) { 
                $bin_script_info = pathinfo($config['dir']['bin_enabled'].'/'.$bin_script_enabled);
                if ($bin_script_info['extension'] == 'php') {
                        $filenameSplit = explode("-", $bin_script_info['filename']);
                        $idParent=$filenameSplit[0];
                        $id=$filenameSplit[1];
                        $cache_file_script=$config['cache']['dir'].'/'.$config['cache']['file_prefix'].$bin_script_enabled;
                        if(!checkCacheTime($cache_file_script)) {
                                // Ménage
                                foreach ($array_data as $i => $value) {
                                    unset($array_data[$i]);
                                }
                                $script_return = (include $config['dir']['bin_enabled'].'/'.$bin_script_enabled);
                                file_put_contents($cache_file_script, json_encode($script_return));
                                if (substr(sprintf('%o', fileperms($cache_file)), -3) != '777')  {
                                        chmod($cache_file, 0777);
                                }
                        } 
                        $timerefresh=filemtime($cache_file_script);
                        
                        $script_return_datas = json_decode(file_get_contents($cache_file_script), true) ;
                        //trucAdir(4, print_r($script_return_datas));
                        echo "\n\t<device id=\"".strtolower($idParent)."\">";
                        echo "\n\t\t<nom></nom>";
                        echo "\n\t\t<timerefresh>".$timerefresh."</timerefresh>";
                        echo "\n\t\t<type></type>";
                        echo "\n\t\t<modele></modele>";
                        echo "\n\t\t<serial></serial>";
                        echo "\n\t\t<datas>";
                        sort($script_return_datas);
                        
                        foreach ($script_return_datas as $script_return_data) {
                                if (isset($script_return_data['id'])) {
                                        $id_data=$script_return_data['id'];
                                } else {
                                        $id_data=$id;
                                }
                                echo "\n\t\t\t<data id='".$id_data."' screen='".$script_return_data['screen']."' smallScreen='".$script_return_data['smallScreen']."'>";
                                echo "\n\t\t\t\t<desc>".$script_return_data['desc']."</desc>";
                                echo "\n\t\t\t\t<value>".$script_return_data['value']."</value>";
                                echo "\n\t\t\t\t<units>".$script_return_data['units']."</units>";
                                echo "\n\t\t\t</data>";
                        }
                        echo "\n\t\t</datas>";
                        echo "\n\t</device>";
                } 
        }
}
trucAdir(5, "Fin du data-xml.php");
?>
</devices>
