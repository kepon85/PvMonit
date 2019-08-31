<?xml version="1.0" encoding="utf-8"?>
<devices>
<?php
###################################
# Script sous licence BEERWARE
# Version 1.0	2019
###################################
include_once('/opt/PvMonit/config-default.php');
include_once('/opt/PvMonit/config.php');

include('/opt/PvMonit/function.php');
$PRINTMESSAGE=0;


$ppv_total=null;
$nb_ppv_total=0;

$cache_file=$CACHE_DIR.'/'.$CACHE_PREFIX.'vedirect_scan';
if(!checkCacheTime($cache_file)) {
        file_put_contents($cache_file, json_encode(vedirect_scan()));
        chmod($cache_file, 0777);
} 
$timerefresh=filemtime($cache_file);
foreach (json_decode(file_get_contents($cache_file), true) as $device) {
        if ($device['serial']  == '') {
                $device['serial'] = $device['nom'];
        }
        echo "\n\t".'<device id="'.$device['serial'].'">';
        echo "\n\t\t".'<nom>'.$device['nom'].'</nom>';
        echo "\n\t\t".'<timerefresh>'.$timerefresh.'</timerefresh>';
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
			
			?>
		
		</datas>		
	</device>
<?php 
// Scan du répertoire bin-enabled
$bin_enabled_data = scandir($BIN_ENABLED_DIR);
foreach ($bin_enabled_data as $bin_script_enabled) { 
        $bin_script_info = pathinfo($BIN_ENABLED_DIR.'/'.$bin_script_enabled);
        if ($bin_script_info['extension'] == 'php') {
                $cache_file_script=$CACHE_DIR.'/'.$CACHE_PREFIX.$bin_script_enabled;
                if(!checkCacheTime($cache_file_script)) {
                        $script_return = (include $BIN_ENABLED_DIR.'/'.$bin_script_enabled);
                        file_put_contents($cache_file_script, json_encode($script_return));
                        chmod($cache_file_script, 0777);
                } 
                $timerefresh=filemtime($cache_file_script);
                $script_return_data = json_decode(file_get_contents($cache_file_script), true) ;
                $filenameSplit = explode("-", $bin_script_info['filename']);
                $idParent=$filenameSplit[0];
                $id=$filenameSplit[1];
                echo "\n\t<device id=\"".$idParent."\">";
                echo "\n\t\t<nom></nom>";
                echo "\n\t\t<timerefresh>".$timerefresh."</timerefresh>";
                echo "\n\t\t<type></type>";
                echo "\n\t\t<modele></modele>";
                echo "\n\t\t<serial></serial>";
                echo "\n\t\t<datas>";
                echo "\n\t\t\t<data id='".$id."' screen='".$script_return_data['screen']."' smallScreen='".$script_return_data['smallScreen']."'>";
                echo "\n\t\t\t\t<desc>".$script_return_data['desc']."</desc>";
                echo "\n\t\t\t\t<value>".$script_return_data['value']."</value>";
                echo "\n\t\t\t\t<units>".$script_return_data['units']."</units>";
                echo "\n\t\t\t</data>";
                echo "\n\t\t</datas>";
                echo "\n\t</device>";
        } 
}
?>
</devices>
