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
if (empty($config['weather']['prod'])) {
	exit('{"error":"weather / prod (total PV installed in Wc) is not config in config.yaml"}');
}


function timeHms2s($timestamp) {
	return date('s', $timestamp) +
			date('m', $timestamp) * 60 +
			date('H', $timestamp) * 60 * 60;
}

$cacheFile=$config['cache']['dir'].'/openweathermap-forecast.json';
$cacheFileProd=$config['cache']['dir'].'/openweathermap-prod-forecast.json';

if (!is_file($cacheFileProd) || filemtime($cacheFile)+$config['weather']['cache'] < time()) {

	$result = getDataOpenWeathert();
	if ($result == '') {
		exit('{"error":"openweathermap not download, openweathermapAppId and openweathermapAppId is true ? (in config.yaml)"}');
	}

	$resultArray = json_decode($result, true);
	
	//~ if (!$include) {
		//~ echo "surise : " ;
		//~ echo date('H:m:s', $resultArray['city']['sunrise']);
		//~ $sunriseSecond = timeHms2s($resultArray['city']['sunrise']);
		//~ echo "<br />\n";
		//~ echo "sunset : " ;
		//~ echo date('H:m:s', $resultArray['city']['sunset']);
		//~ $sunsetSecond = timeHms2s($resultArray['city']['sunset']);
	//~ }
	$forecast['sunset']=$resultArray['city']['sunset'];
	$forecast['surise']=$resultArray['city']['sunrise'];
	//~ echo "<br />\n";
	//~ echo "H : comprendre H-3h";
	//~ echo "<br />\n";
	foreach($resultArray as $key=>$weather) {
		global $config;
		if ($key == 'list') {
			foreach($weather as $no=>$weatherDay) {
				// On cherche l'interval de date
				$dateNow = new DateTime(date('Y-m-d', time()));
				$datePrevision = new DateTime(date('Y-m-d', $weatherDay['dt']));
				$interval = date_diff($dateNow, $datePrevision);
				$diffIntervalOperation = $interval->format('%R%a');
				$diffDay = $interval->format('%a');
				// On s'arrête à J+2 en prévision
				if ($diffDay > $config['weather']['forecastDay']){
					continue;
				}
				// Moyenne des nuages duprant la journée
				//~ average cloud during the day
				//~ if (date('G', $weatherDay['dt']) >= date('G', $forecast['surise']) && date('G', $weatherDay['dt']) <= date('G', $forecast['sunset'])) {
					//~ if (isset($avenage[$diffDay])) 
						//~ echo date('G', $weatherDay['dt']);
				//~ }
				$forecast[$diffDay][date('G', $weatherDay['dt']-3600-3600)]=$weatherDay['clouds']['all'];
				$forecast[$diffDay][date('G', $weatherDay['dt']-3600)]=$weatherDay['clouds']['all'];
				$forecast[$diffDay][date('G', $weatherDay['dt'])]=$weatherDay['clouds']['all'];			
			}
		}
	}
	//~ echo json_encode($forecast);
	//~ exit();
	function prodForecast($forecast) {
		global $config;
		global $include;
		# On cherche la différence entre l'heure configuré (time zone) et l'UTC
		$dateTimeZoneUTC = new DateTimeZone("UTC");
		$dateTimeZoneGet = new DateTimeZone(date_default_timezone_get());
		$dateTimeUTC = new DateTime("now", $dateTimeZoneUTC);
		//~ $dateTimeGet = new DateTime("now", $dateTimeZoneGet);
		$diffUTC = round($dateTimeZoneGet->getOffset($dateTimeUTC)/3600);
		trucAdir(5, "La timezone ".date_default_timezone_get()." est à $diffUTC h d'UTC");
		for ($day = 0; $day <= $config['weather']['forecastDay']; $day++) {
			if (empty($forecast[$day])) {
				continue;
			}
			if ($day != 0 && count($forecast[$day]) != 24) {
				continue;
			}
			foreach ($forecast[$day] as $hour=>$cloud) {
				trucAdir(5, "Day $day, $hour H - Cloud : $cloud % ");
				$abatementCloud=(100-$cloud)/100+$config['weather']['prod_mini'];
				if ($hour >= date('G', $forecast['surise']) && $hour <= date('G', $forecast['sunset'])) {
					$diffSunrise=$hour-date('G', $forecast['surise']);
					$diffSunset=date('G', $forecast['sunset'])-$hour;
					if (isset($config['weather']['abatementSurise'][$diffSunrise])) {
						$abatement=$config['weather']['abatementSurise'][$diffSunrise];
					} elseif (isset($config['weather']['abatementSunset'][$diffSunset])) {
						$abatement=$config['weather']['abatementSunset'][$diffSunset];
					} else {
						$abatement=1;
					}
					//~ exit();
					$hourUtc=$hour-$diffUTC;
					$abatementHour=1;
					if (isset($config['weather']['abatementHour'][$hourUtc])) {
						$abatementHour=$config['weather']['abatementHour'][$hourUtc];
						trucAdir(5, "Un abattement horraire de $abatementHour s'applique à $hour H ($hourUtc H UTC))");
					}
					$prodForcast[$day]['byHour'][$hour]['prod']=round($config['weather']['prod']*$abatementCloud*$config['weather']['prod_yield_global']*$abatement*$abatementHour, 0);
					$prodForcast[$day]['byHour'][$hour]['cloud']=$cloud;
					$prodForcast[$day]['byHour'][$hour]['sun']=1;
					trucAdir(5, "Prod estimé : ".$prodForcast[$day]['byHour'][$hour]['prod']."W - Abatement config $abatement - ");
				} else {
					$prodForcast[$day]['byHour'][$hour]['prod']=0;
					$prodForcast[$day]['byHour'][$hour]['cloud']=$cloud;
					$prodForcast[$day]['byHour'][$hour]['sun']=0;
					trucAdir(5, "Prod estimé : ".$prodForcast[$day]['byHour'][$hour]['prod']);
				}
			}
			$prodCumul=0;
			$cloudAvg=0;
			$cloudAvgNb=0;
			foreach ($prodForcast[$day]['byHour'] as $byHour) {
				$prodCumul=$prodCumul+$byHour['prod'];
				if ($byHour['sun'] == 1) {
					$cloudAvg=$cloudAvg+$byHour['cloud'];
					$cloudAvgNb++;
				}
			}
			$prodForcast[$day]['prodCumul']=round($prodCumul, 0);
			$prodForcast[$day]['cloudAvg']=round($cloudAvg/$cloudAvgNb, 0);
			if($day == 0) {
				$prodForcast[$day]['sunset']=$forecast['sunset'];
				$prodForcast[$day]['surise']=$forecast['surise'];
			}
			ksort($prodForcast[$day]['byHour']);
		}
		return $prodForcast;
	}
	file_put_contents($cacheFileProd, json_encode(prodForecast($forecast)));
} else {
	trucAdir(5, "Le cache est service pour la prod forcast");
}
echo file_get_contents($cacheFileProd);

?>

