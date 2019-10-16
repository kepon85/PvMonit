<?php

$array_data[0]['screen']=1; 
$array_data[0]['smallScreen']=1;
$array_data[0]['desc']='Consommation du foyer';
$array_data[0]['value']=Amp_USB('/usr/bin/sudo '.$BIN_DIR.'ampermetre.pl')*230;
$array_data[0]['units']='W';

return $array_data;

?>
