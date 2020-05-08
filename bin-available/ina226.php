<?php
 
$cmd='/usr/bin/sudo /usr/bin/python3 /opt/PvMonit/bin/ina226.py ';

exec($cmd, $ina226_sortie, $ina226_retour);
if ($ina226_retour != 0){
    trucAdir(1, 'Erreur '.$ina226_retour.' à l\'exécussion du programme .'.$cmd);
} else {
    trucAdir(4, 'Le script retourne '.$ina226_sortie[0]);
    $nb=0;
    foreach (json_decode($ina226_sortie[0]) as $key=>$data) {
        $array_data[$nb]['id']=$id.$key; 
        $array_data[$nb]['screen']=1;
        $array_data[$nb]['smallScreen']=0;
        $array_data[$nb]['value']=$data;
        if ($key == 'V'){
            $array_data[$nb]['units']='V';
            $array_data[$nb]['desc']='Tension '.$id;
        }
        if ($key == 'A'){
            $array_data[$nb]['units']='A';
            $array_data[$nb]['desc']='Amperes '.$id;
        }

        $nb++;
    }
}

return $array_data;

?>
