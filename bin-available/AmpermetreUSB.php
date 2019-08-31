<?php

$array_data['screen']=1; 
$array_data['smallScreen']=1;
$array_data['desc']='Consommation du foyer';
$array_data['value']=Amp_USB('/usr/bin/sudo '.$BIN_DIR.'ampermetre.pl')*230;
$array_data['units']='W';

return $array_data;

?>
