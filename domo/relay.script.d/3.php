<?php

# Pompe de rellevage

// Par défaut à off (auto)
$return['log'] = null;
$return['mod'] = 1;

# Temps d'allumage
$timeUp=300;

if ($data['SOC'] >= 98 && !relayUpToday($thisId)) {     
    $return['log'] = 'UP La batterie est chargé à 98% et pas lancé aujourd hui';
    $return['mod'] = 2;
}
if ($thisEtat == 1 && timeUpMin($thisId, $timeUp)) {
    $return['log'] = 'UP, maintient allume, le temps n est pas passé';
    $return['mod'] = 2; 
} 

return $return;

?>
