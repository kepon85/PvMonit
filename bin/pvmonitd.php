#!/usr/bin/php
<?php

include('/opt/PvMonit/function.php');
// Chargement de la config
$config = getConfigYaml('/opt/PvMonit');

if (! posix_getuid() == 0){
    exit("Lancer ce script avec l'utilisateur root ou avec sudo\n");
}

//~ error_reporting(0);
$sortie=0;
$printHelp=false;
$screenRunDir = '/run/screen/S-root/';
$screenList = scandir($screenRunDir);

//~ ---------- Pour fixer 
//~ /opt/PvMonit/bin/lighttpd-fix-user.sh

$listCheckDaemon= array('arduino',
                        'lcd',
                        'relay-actions',
                        'tm1638',
                        'domo',
                        'help',
                        'sendToEmoncms',
                        'getForEmoncms',
                        'cloudService',
                        'forcastSoc');

function startCmd($daemonSelect) {
    global $config;
    switch ($daemonSelect) {
        case 'arduino':
            $return = 'screen -A -m -d -S arduino /opt/PvMonit/bin/getSerialArduino-launch.sh';
        break;
        case 'lcd':
            $return = 'screen -A -m -d -S lcd /opt/PvMonit/lcd/lcd-launch.sh';
        break;
        case 'relay-actions':
            $return = 'screen -A -m -d -S relay-actions /opt/PvMonit/domo/relay-actions-launch.sh';
        break;
        case 'tm1638':
            $return = 'screen -A -m -d -S tm1638 /opt/PvMonit/domo/tm1638-launch.sh';
        break;
        case 'domo':
            $return = 'screen -A -m -d -S domo /opt/PvMonit/domo/domo-launch.sh';
        break;
        case 'help':
            $return = 'screen -A -m -d -S help /opt/PvMonit/bin/pvmonitHelp-launch.sh';
        break;
        case 'sendToEmoncms':
            $return = 'screen -A -m -d -S sendToEmoncms /opt/PvMonit/bin/sendToEmoncms-launch.sh '.$config['emoncms']['sendInterval'].'';
        break;
        case 'getForEmoncms':
            $return = 'screen -A -m -d -S getForEmoncms /opt/PvMonit/bin/getForEmoncms-launch.sh '.$config['emoncms']['getInterval'].'';
        break;
        case 'cloudService':
            $return = 'screen -A -m -d -S cloudService /opt/PvMonit/bin/cloudService.sh';
        break;
        case 'forcastSoc':
            $return = 'screen -A -m -d -S forcastSoc /opt/PvMonit/bin/forcastSoc-launch.sh';
        break;
        //~ default:
            //~ return '/dev/null';
        //~ break;
    }
    return $return;
}

function startEnable($daemonSelect) {
    global $config;
    $return = false;
    switch ($daemonSelect) {
        case 'arduino':
            if ($config['vedirect']['by'] == 'arduino') {
                $return = true;
            }
        break;
        case 'lcd':
            if ($config['lcd']['daemon'] == true) {
                $return = true;
            }
        break;
        case 'relay-actions':
        case 'domo':
            if ($config['domo']['daemon'] == true) {
                $return = true;
            }
        break;
        case 'tm1638':
            if ($config['domo']['tm1638']['daemon'] == true) {
                $return = true;
            }
        break;
        case 'help':
            if ($config['www']['help'] == true) {
                $return = true;
            }
        break;
        case 'sendToEmoncms':
        case 'getForEmoncms':
            if ($config['emoncms']['daemon'] == true) {
                $return = true;
            }
        break;
        case 'cloudService':
            if ($config['cloud']['daemon'] == true) {
                $return = true;
            }
        break;
        case 'forcastSoc':
            if ($config['weather']['enable'] == true && $config['weather']['forcastSoc'] == true) {
                $return = true;
            }
        break;
    }
    return $return;
}

function daemonPidOf($nom) {
    global $screenList;
    $return = false;
    foreach ($screenList as $screenFullName) { 
        $screenSplit=explode(".", $screenFullName);
        if ($screenSplit[1] == $nom) {
            $return = $screenSplit[0];
        }
    }
    return $return;
}

function stop(){
    global $listCheckDaemon;
    global $daemonSelect;
    if ($daemonSelect=='all') {
        foreach ($listCheckDaemon as $daemon) { 
            if (daemonPidOf($daemon) != false) {
                exec('kill '.daemonPidOf($daemon), $sortie, $retour);
                if ($retour != 0){
                    echo "Error to stop ".$daemon."\n";
                    $sortie=245;
                }
            }
        }
    } else {
        if (daemonPidOf($daemonSelect) != false) {
            exec('kill '.daemonPidOf($daemonSelect), $sortie, $retour);
            if ($retour != 0){
                echo "Error to stop ".$daemonSelect."\n";
                $sortie=245;
            }
        } else {
            echo "$daemonSelect alredy stop\n";
        }
    }
}

function start(){
    global $listCheckDaemon;
    global $daemonSelect;
    if ($daemonSelect=='all') {
        foreach ($listCheckDaemon as $daemon) { 
            if (startEnable($daemon) == true) {
                if (daemonPidOf($daemon) == false)  {
                    exec(startCmd($daemon), $sortie, $retour);
                    if ($retour != 0){
                        echo "Error to start ".$daemon."\n";
                        $sortie=245;
                    }
                } else {
                    echo "$daemon alredy start\n";
                }
            }
        }
    } else {
        if (daemonPidOf($daemonSelect) == false)  {
            exec(startCmd($daemonSelect), $sortie, $retour);
            if ($retour != 0){
                echo "Error to start ".$daemonSelect."\n";
                $sortie=245;
            }
        } else {
            echo "$daemonSelect alredy start\n";
        }
    }
}

// Argument d'action
if (isset($argv[1])) {
    // Séleciton daemon
    $daemonSelect='all';
    if (isset($argv[2]) && in_array($argv[2], $listCheckDaemon)) {
        $daemonSelect=$argv[2];
    } else if (isset($argv[2])) {
        exit("Le daemon '".$argv[2]."' n'existe pas\n");
        exit(2);
    }
    switch ($argv[1]) {
        case 'start':
        case 'star':
        case 'tart';
            start();
        break;
        case 'stop':
        case 'top':
        case 'sto':
            stop();
        break;
        case 'reload':
        case 'restart':
        case 'restar':
        case 'estart':
            stop();
            sleep(1);
            $screenList = scandir($screenRunDir);
            start();
        break;
        case 'status':
        case 'statu':
        case 'tatus':
            foreach ($listCheckDaemon as $daemon) { 
                if (startEnable($daemon)) {
                    echo "Enable\t\t";
                }else {
                    echo "Disable\t\t";
                }
                if (daemonPidOf($daemon) == false) {
                    echo "Not Run \t\t $daemon\n";
                } else {
                    echo "Run (".daemonPidOf($daemon).") \t\t $daemon\n";
                }
            }
        break;
        default:
            $printHelp=true;
        break;
    }
}

if ($printHelp == true) {
    echo $argv[0]." start|stop|status [arduino|lcd|relay-action|tm1638|domo|help|sendToEmoncms|getForEmoncms|cloudService]\n";
}

chown('/opt/PvMonit', 'pvmonit');
chown('/opt/PvMonit/config.yaml', 'pvmonit');

exit($sortie);
