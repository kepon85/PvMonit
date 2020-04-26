<?php

include('/opt/PvMonit/function.php');
error_reporting(E_ALL & ~E_NOTICE);

// Chargement de la config
$config = getConfigYaml('/opt/PvMonit');
$config['printMessage']=5;

# WKS
if ($config['wks']['enable'] == true) {
    trucAdir(1, "WKS enable");
    exec($config['wks']['bin'], $wks_sortie, $wks_retour);
    if ($wks_retour != 0){
        trucAdir(1, 'Erreur à l\'exécution du script '.$config['wks']['bin']);
    } else {
        $datas = json_decode($wks_sortie[0]);
        $execTime=time();
        foreach ($datas as $command=>$reponses) {
            if (empty($config['wks']['data'][$command]['hide']) || $config['wks']['data'][$command]['hide'] != true) {
                echo "Command : $command \n";
                if (isset($config['wks']['data'][$command]['name'])) {
                    echo " (name in config.yaml : ".$config['wks']['data'][$command]['name'].")\n";
                } 
                $numReponse=1;
                foreach ($reponses as $reponse) {
                    # Check regex : 
                    if (isset($config['wks']['data'][$command][$numReponse]['regex']) 
                    && $config['wks']['data'][$command][$numReponse]['regex'] != false)  {
                        if (! preg_match($config['wks']['data'][$command][$numReponse]['regex'], $reponse)) {
                            echo "[WKS] Erreur ".$command.$numReponse." regex ".$config['wks']['data'][$command][$numReponse]['regex']." ne correspond pas à l'item ".$reponse."\n";
                            $numReponse++;
                            continue;
                        }
                    }
                    # Si l'ordre est présent
                    if (isset($config['wks']['data'][$command][$numReponse])) {
                        echo "\t".$numReponse." :\n";
                        if (empty($config['wks']['data'][$command][$numReponse]['hide']) || $config['wks']['data'][$command][$numReponse]['hide'] != true) {
                            echo "\t\tid find : ".$config['wks']['data'][$command][$numReponse]['id']."\n";
                            echo "\t\tdesc : ".$config['wks']['data'][$command][$numReponse]['desc']."\n";
                            
                                if (isset($config['wks']['data'][$command][$numReponse]['value2text'])) {
                                    $find = false;
                                    foreach($config['wks']['data'][$command][$numReponse]['value2text'] as $value=>$text) {
                                        if ($reponse == $value) {
                                            echo "\t\tvalue with value2text  : ".$text."\n";
                                            $find = true;
                                        }
                                    }
                                    if ($find == false) {
                                        echo "\t\tvalue with value2text not fond : ".$reponse."\n";
                                    }
                                } 
                                echo "\t\tunits : ".$config['wks']['data'][$command][$numReponse]['units']."\n";
                                echo "\t\tvalue in wks.py : ".$reponse."\n";
                        } else {
                            # Caché
                            echo "\t Hide";
                        }
                    } elseif ($config['wks']['data']['printAll'] == true) {
                        # Sinon c'est par défaut
                        trucAdir(5, "[WKS] pas de config, item par défaut : ".$command.$numReponse);
                        echo "\n\t\t\t".'<data id="'.$command.$numReponse.'">';
                            echo "\n\t\t\t\t".'<desc>'.$command.$numReponse.'</desc>';
                            echo "\n\t\t\t\t".'<value>'.$reponse.'</value>';
                            echo "\n\t\t\t\t".'<units></units>';
                    }
                    $numReponse++;
                }
                echo "\n";
            }
            echo "\n\n";
        }

    }
} else {
    echo "WKS not enable";
}

