<?php

# Téléphone

// Par défaut à off (auto)
$return['log'] = null;
$return['mod'] = 1;

// @todo: faire timeUpMini (éviter bagotage)

// Si la box est allumé
if ($relayEtat[1] == 1) {
    if (MpptAbsOrFlo($data['CS'])) {
        $return['log'] = 'Régulateur en Abs ou Float';
        $return['mod'] = 2;
    }
    if (date('G') > 11 && date('G') < 19 && $data['SOC'] > 95) {
        $return['log'] = 'Il est plus de 11 heure et que les batterie sont suppérieur à 95 c\'est qu\'il fait beau...';
        $return['mod'] = 2;
    } 
}

return $return;
?>
