<?php


$cmd='/usr/bin/sudo /opt/PvMonit/bin/ht2000 /dev/hidraw0';

exec($cmd, $sortie, $retour);
if ($retour != 0 || $sortie[0] == null){
    trucAdir(1, 'Erreur '.$retour.' à l\'exécussion du programme .'.$cmd);
} else {
    trucAdir(4, 'Le script retourne '.$sortie[0]);
    $array_data[0]['id']='Co2Home';
    $array_data[0]['screen']=1; 
    $array_data[0]['smallScreen']=0;
    $array_data[0]['desc']='Co2 dans la maison';
    $array_data[0]['value']=$sortie[0];
    $array_data[0]['units']='ppm';
}



return $array_data;


?>
