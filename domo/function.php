<?php
// XML récupération les données
function xml_data_get($DATA_FILE)  {
    $BMV_data = null;
    $devices = new SimpleXMLElement(file_get_contents($DATA_FILE));
    foreach ($devices as $device) {
        // On trouve un BMV
        if ($device->modele == 'BMV-700' || $device->modele == 'BMV-702' || $device->modele == 'BMV-700H')  {
            // On vérifie que la donnée ne soit pas périmé
            if ($device->timerefresh+$GLOBALS['XML_DATA_TIMEOUT'] > time()) {
                foreach ($device->datas->data as $data) {
                    // On récupère SOC
                    if ($data['id'] == 'SOC') {
                        $BMV_data['SOC'] = $data->value;
                    } else if ($data['id'] == 'P') {
                        $BMV_data['P'] = $data->value;
                    }
                }
            } else {
                $BMV_data = false;
                trucAdir(2, 'Les données sont périmées');
            }
        }
    }
    if (empty($BMV_data['SOC'])) {
        trucAdir(2, 'Le SOC/le BMV n\'est pas présent dans les données...');
        $BMV_data = null;
    }
    return $BMV_data;
}
?>
