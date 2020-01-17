<?php
//~ print_r($thisId);
//~ print_r($thisEtat);
//~ print_r($thisMod);
//~ print_r($data);
//~ print_r($relayMod);
//~ print_r($relaEtat);

// Par défaut à off (auto)
$return['log'] = null;
$return['mod'] = $thisMod;

$timeUp=600;

// @todo: faire timeUpMini (éviter bagotage)

$pingHost=array("192.168.1.10","192.168.1.12");

if (!function_exists('checkIfComputerIsUp')) {
    function checkIfComputerIsUp($pingHost) {
        $onlineHost=0;
        if (is_file('/usr/bin/fping')) {
            $hosts=null;
            foreach ($pingHost as $host) {
                $hosts.=' '.$host;
            }
            @exec("fping -c1 -t500 " . $hosts, $output, $result);
            if (isset($output[0])) {
                $onlineHost++;
            }
        } else {
            foreach ($pingHost as $host) {
                @exec("ping -c 1 -i 0.2 -W 1 " . $host, $output, $result);
                if ($result == 0) {
                    $onlineHost++;
                }
            }
        }
        return $onlineHost;
    }
}

if (is_file('/tmp/domo'.$thisId.'up')) {
    $return['log'] = 'Présence du fichier (/tmp/domo'.$thisId.'up) qui force l\'allumage';
    $return['mod'] = 2;
} else {
    // Si c'est éteind, faut-il l'allumer ?
    if ($thisEtat == 0) {
        // Si les batteries sont pleinnes
        if (date('G') > 11 && date('G') < 17 && $data['SOC'] > 95) {
            $return['log'] = 'Il est plus de 11 heure et que les batterie sont suppérieur à 95 c\'est qu\'il fait beau...';
            $return['mod'] = 2;
        } 
        if (MpptAbsOrFlo($data['CS'])) {
            $return['log'] = 'Régulateur en Abs ou Float';
            $return['mod'] = 2;
        }
    // Si c'est allumé, faut-il l'éteindre ?
    } else if ($thisEtat == 1) {
        $checkIfComputerIsUp = checkIfComputerIsUp($pingHost);
        if (date('G') >= 17 && $checkIfComputerIsUp == 0)  {
            $return['log'] = 'S\'il est plus de 17h et qu\'il n\'y a pas d\'ordinateur d\'allumé';
            $return['mod'] = 1;
        }
        if ($data['SOC'] <= 95 && $checkIfComputerIsUp == 0) {
            $return['log'] = 'Les batteries pass sous les 95% et qu\'il n\'y a pas d\'ordinateur d\'allumé';
            $return['mod'] = 1;
        }         
        // Si un passage à 1 est décidé mais que le temps minimum n'est pas dépassé :
        if ($return['mod'] == 1 && timeUpMin($thisId, $timeUp)) {
            $return['log'] = 'Temps minimum non dépassé, on maintient allumé';
            $return['mod'] = 2;
        }
    } 
}

return $return;
?>
