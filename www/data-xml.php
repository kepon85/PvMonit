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
$nb_ppv_total=0;
if ($config['vedirect']['by'] == 'USB') {
        $cache_file=$config['cache']['dir'].'/'.$config['cache']['file_prefix'].'vedirect_scan';
        if(!checkCacheTime($cache_file)) {
                file_put_contents($cache_file, json_encode(vedirect_scan()));
                chmod($cache_file, 0777);
        } 
        $timerefresh=filemtime($cache_file);
        $vedirect_data_ready = json_decode(file_get_contents($cache_file), true);
} elseif ($config['vedirect']['by'] == 'arduino') { 
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
foreach ($vedirect_data_ready as $device) {
        if ($device['serial']  == 'Inconnu' || $device['serial']  == '') {
                $device['serial'] = $device['nom'];
        }
        echo "\n\t".'<device id="'.$device['serial'].'">';
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
        }
        echo "\n\t\t".'</datas>';
        echo "\n\t".'</device>';
}



?>

	<device id="other">
		<nom>Divers</nom>
		<timerefresh><?= time() ?></timerefresh>
		<type></type>
		<modele></modele>
		<serial></serial>
		<datas>
			<?php 
			// Production totale
			if ($ppv_total !== null) {
				echo '<data id="PPVT"'.onScreenPrint('PPVT').'>
				<desc>Production total des panneaux</desc>
				<value>'.$ppv_total.'</value>
				<units>W</units>
				</data>';
			}
			?>
		</datas>		
	</device>
<?php 
// Scan du répertoire bin-enabled
$bin_enabled_data = scandir($config['dir']['bin_enabled']);
foreach ($bin_enabled_data as $bin_script_enabled) { 
        $bin_script_info = pathinfo($config['dir']['bin_enabled'].'/'.$bin_script_enabled);
        if ($bin_script_info['extension'] == 'php') {
                $filenameSplit = explode("-", $bin_script_info['filename']);
                $idParent=$filenameSplit[0];
                $id=$filenameSplit[1];
                $cache_file_script=$config['cache']['dir'].'/'.$config['cache']['file_prefix'].$bin_script_enabled;
                if(!checkCacheTime($cache_file_script)) {
                        $script_return = (include $config['dir']['bin_enabled'].'/'.$bin_script_enabled);
                        file_put_contents($cache_file_script, json_encode($script_return));
                        chmod($cache_file_script, 0777);
                } 
                $timerefresh=filemtime($cache_file_script);
                $script_return_datas = json_decode(file_get_contents($cache_file_script), true) ;
                echo "\n\t<device id=\"".strtolower($idParent)."\">";
                echo "\n\t\t<nom></nom>";
                echo "\n\t\t<timerefresh>".$timerefresh."</timerefresh>";
                echo "\n\t\t<type></type>";
                echo "\n\t\t<modele></modele>";
                echo "\n\t\t<serial></serial>";
                echo "\n\t\t<datas>";
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
?>
</devices>
