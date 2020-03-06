<?php

// Par défaut à off (auto)
$return['log'] = null;
$return['mod'] = 1;

if (is_file('/tmp/domo'.$thisId.'up')) {
    $return['log'] = 'Présence du fichier (/tmp/domo'.$thisId.'up) qui force l\'allumage';
    $return['mod'] = 2;
}

return $return;
?>
