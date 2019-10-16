<?php

$dhtModel=22;    // [11|22|2302] 
$dhtGpio=26;     // GPIO pin number
$cmd='/usr/bin/sudo /usr/bin/python3 '.  $config['dir']['bin'].'DHT.py '.$dhtModel.' '.$dhtGpio;

exec($cmd, $dht_sortie, $dht_retour);
if ($dht_retour != 0){
    trucAdir(1, 'Erreur '.$dht_retour.' à l\'exécussion du programme .'.$cmd);
} else {
    trucAdir(4, 'Le script retourne '.$dht_sortie[0]);
    $nb=0;
    foreach (json_decode($dht_sortie[0]) as $key=>$data) {
        $array_data[$nb]['id']=$id.$key; 
        $array_data[$nb]['screen']=1;
        $array_data[$nb]['smallScreen']=0;
        $array_data[$nb]['value']=$data;
        if ($key == 'H'){
            $array_data[$nb]['units']='%';
            $array_data[$nb]['desc']='Humidité '.$id;
        } else {
            $array_data[$nb]['units']='°C';
            $array_data[$nb]['desc']='Température '.$id;
        }
        $nb++;
    }
}

return $array_data;

?>
