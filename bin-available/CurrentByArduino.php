<?php

$arduino_data=yaml_parse_file($config['vedirect']['arduino']['data_file']);
foreach ($arduino_data as $device_id => $device_data) {
    if ($device_id == 'CONSO') {
        if (key($device_data) == 'I') {
                $value = $device_data[key($device_data)]*$config['tensionNorme'];
                $units = 'W';
        } else {
                $value = $device_data[key($device_data)];
                $units = key($device_data);
        }
    }
}

$array_data[0]['screen']=1; 
$array_data[0]['smallScreen']=1;
$array_data[0]['desc']='Conso2';
$array_data[0]['value']=$value;
//$array_data[0]['value']="?";
$array_data[0]['units']=$units;

return $array_data;



?>
