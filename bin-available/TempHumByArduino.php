<?php
$value="Inconnu";
$units="Inconnu";

$arduino_data=yaml_parse_file($config['vedirect']['arduino']['data_file']);
foreach ($arduino_data as $device_id => $device_data) {
    if ($device_id == $id) {
        $nb=0;
        foreach ($device_data as $key=>$data) {
            $array_data[$nb]['id']=$id.$key; 
            if ($key == 'TR'){
                $array_data[$nb]['screen']=0; 
            } else {
                $array_data[$nb]['screen']=1; 
            }
            $array_data[$nb]['smallScreen']=0;
            //$array_data['value']=$value;
            $array_data[$nb]['value']=$data;
            if ($key == 'H'){
                $array_data[$nb]['units']='%';
                $array_data[$nb]['desc']='Humidité '.$id;
            } elseif ($key == 'TR'){
                $array_data[$nb]['units']='°C';
                $array_data[$nb]['desc']='Température ressentie '.$id;
            } else {
                $array_data[$nb]['units']='°C';
                $array_data[$nb]['desc']='Température '.$id;
            }
            $nb++;
        }
    }
}


return $array_data;



?>
