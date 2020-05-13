<?php

include('/opt/PvMonit/function.php');

// Chargement de la config
$config = getConfigYaml('/opt/PvMonit');
$config['printMessage']=0;

// Si on est pas en HTTP
if (php_sapi_name() != "cli") {
    header('Content-Type: application/json');
    header("Access-Control-Allow-Origin: *");
}

if ($config['weather']['enable'] != true) {
	exit('{"error":"Weather not enable in config.yaml"}');
}
if (empty($config['weather']['openweathermapCityId']) || $config['weather']['openweathermapCityId']  == "XXXXXXX") {
	exit('{"error":"openweathermapCityId is not config in config.yaml"}');
}
if (empty($config['weather']['openweathermapAppId']) || $config['weather']['openweathermapAppId']  == "XXXXXXXXXXXXXXXXXXXXXXXX") {
	exit('{"error":"openweathermapAppId is not config in config.yaml"}');
}

$result = getDataOpenWeathert();

if ($result != '') {
    echo $result;
} else {
    exit('{"error":"openweathermap not download, openweathermapAppId and openweathermapAppId is true ? (in config.yaml)"}');
}

?>

