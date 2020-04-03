<?php

# Contrib de akoirium 
 
$cmd='/usr/bin/sudo /usr/bin/python3 /opt/PvMonit/bin/pzem_004t.py ';

exec($cmd, $pzem_sortie, $pzem_retour);
if ($pzem_retour != 0){
    trucAdir(1, 'Erreur '.$conso_retour.' à l\'exécussion du programme .'.$cmd);
} else {
    trucAdir(4, 'Le script retourne '.$pzem_sortie[0]);
    $nb=0;
    foreach (json_decode($pzem_sortie[0]) as $key=>$data) {
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
        if ($key == 'P'){
            $array_data[$nb]['units']='W';
            $array_data[$nb]['desc']='Puissance '.$id;
        }
        if ($key == 'E'){
            $array_data[$nb]['units']='Wh';
            $array_data[$nb]['desc']='Consomation '.$id;
        }
        if ($key == 'F'){
            $array_data[$nb]['units']='Hz';
            $array_data[$nb]['desc']='Frequence '.$id;
        }
        if ($key == 'f'){
            $array_data[$nb]['units']='';
            $array_data[$nb]['desc']='Facteur Puissance '.$id;
        }
        if ($key == 'a'){
            $array_data[$nb]['units']='';
            $array_data[$nb]['desc']='Alarme '.$id;
        }
        $nb++;
    }
}

return $array_data;

?>

