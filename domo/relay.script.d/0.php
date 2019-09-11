<?php

/*
echo $r['id']; // affiche l'id du relay (= au nom du fichier $ID.php)

echo $r['etat']; // affiche l'état du relay $ID 
    // Etat : 
    //  - 1 : off auto
    //  - 2 : on auto

echo $d['SOC']; // affiche l'état de charge (en %) du parc batterie - lu sur me BMV
echo $d['P']; // affiche la puissance instantané (valeur possiblement négative) - lu sur me BMV

// A la fin du script on retour 1 ou 2 pour redéfinir l'état du relais

return 1; // mettra le relais à off si ce n'est pas déjà le cas
*/
/*
$pingHost=array("192.168.1.10","192.168.1.12");

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



// Si c'est éteind, faut-il l'allumer ?
if ($r['etat'] == 1) {
    // Si les batteries sont pleinnes
    if ($d['SOC'] == 100 ||
    // OU Si il est plus de 11 heure et que les batterie sont suppérieur à 95 c'est qu'il fait beau...
    date('G') > 11 && $d['SOC'] > 95) {
        // On enregistre la date du dernier allumage
        if (empty($timeLastUp[$r['id']])) {
            $timeLastUp[$r['id']] = time();
        }
        // On allume !
        return 2;
    } else {
        return 1;
    }
// Si c'est allumé, faut-il l'éteindre ?
} else if ($r['etat'] == 2) {
    $checkIfComputerIsUp = checkIfComputerIsUp($pingHost);
    // S'il est plus de 17h et qu'il n'y a pas d'ordinateur d'allumé
    if ((date('G') >= 17 && $checkIfComputerIsUp == 0) 
    // Si les batteries pass sous les 95% et qu'il n'y a pas d'ordinateur d'allumé
    || ($d['SOC'] <= 95 && $checkIfComputerIsUp == 0)) {

        // On enregistre la date de la dernière extinction
        if (empty($timeLastDown[$r['id']])) {
            $timeLastDown[$r['id']] = time();
        }
        // On éteind !
        return 1;
    } else {
        return 2;
    }
    
} 

*/
return 2;
?>
