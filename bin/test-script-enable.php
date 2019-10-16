<?php
###################################
# Script sous licence BEERWARE
# Version 1.0	2019
###################################

include('/opt/PvMonit/function.php');

// Chargement de la config
$config = getConfigYaml('/opt/PvMonit');

echo "Cache disable and Print message in debug mod : ";

$config['printMessage']=5;
$config['cache']['time']=0;

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
                        // Ménage
                        foreach ($array_data as $i => $value) {
                            unset($array_data[$i]);
                        }
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
?>

