<?php
$exportData='/tmp/peukert-export.json';
$expir_data=60;

if (!is_file($exportData) || filemtime($exportData)+$expir_data < time()){
    trucAdir(1, 'Erreur le fichier '.$exportData.' n\'est pas présent ou est périmé');
} else {
    $nb=0;
    $dataFile=json_decode(file_get_contents($exportData), true);
    foreach ($dataFile[0] as $key=>$data) {
        $array_data[$nb]['id']=$id.$key; 
        $array_data[$nb]['screen']=1;
        $array_data[$nb]['smallScreen']=1;
        $array_data[$nb]['value']=$data;
        if ($key == 'SOC'){
            $array_data[$nb]['units']='%';
            $array_data[$nb]['desc']='['.$id.'] Charge batterie';
        } else {
            $array_data[$nb]['units']='Ah';
            $array_data[$nb]['desc']='['.$id.'] Capacitée restante';
        }
        $nb++;
    }
}

return $array_data;

?>
