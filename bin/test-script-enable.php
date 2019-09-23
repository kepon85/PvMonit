<?php
###################################
# Script sous licence BEERWARE
# Version 1.0	2019
###################################
include_once('/opt/PvMonit/config-default.php');
include_once('/opt/PvMonit/config.php');

include('/opt/PvMonit/function.php');

echo "Cache disable and Print message in debug mod : ";

$PRINTMESSAGE=5;
$CACHE_TIME=0; // Désactivation du cache

// Scan du répertoire bin-enabled
$bin_enabled_data = scandir($BIN_ENABLED_DIR);
foreach ($bin_enabled_data as $bin_script_enabled) { 
        $bin_script_info = pathinfo($BIN_ENABLED_DIR.'/'.$bin_script_enabled);
        if ($bin_script_info['extension'] == 'php') {
                $filenameSplit = explode("-", $bin_script_info['filename']);
                $idParent=$filenameSplit[0];
                $id=$filenameSplit[1];
                $cache_file_script=$CACHE_DIR.'/'.$CACHE_PREFIX.$bin_script_enabled;
                if(!checkCacheTime($cache_file_script)) {
                        $script_return = (include $BIN_ENABLED_DIR.'/'.$bin_script_enabled);
                        file_put_contents($cache_file_script, json_encode($script_return));
                        chmod($cache_file_script, 0777);
                } 
                $timerefresh=filemtime($cache_file_script);
                $script_return_datas = json_decode(file_get_contents($cache_file_script), true) ;
                echo "\n\t<device id=\"".$idParent."\">";
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

