<?php

$SONDE_TEMPERATURE_CORRECTION='-3';	

$array_data[0]['screen']=1; 
$array_data[0]['smallScreen']=0;
$array_data[0]['desc']='Température du local technique';
$array_data[0]['value']=round(Temperature_USB('/usr/bin/sudo /opt/temperv14/temperv14 -c')+$SONDE_TEMPERATURE_CORRECTION, 1);
$array_data[0]['units']='°C';

return $array_data;

?>
