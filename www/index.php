<?php
###################################
# Script sous licence BEERWARE
# Version 0.1	2016
###################################
include_once('/opt/PvMonit/config-default.php');
include_once('/opt/PvMonit/config.php');

include('/opt/PvMonit/function.php');
$PRINTMESSAGE=0;
if ($_GET['cache'] == 'no') {
	$WWW_CACHE_AGE=1;
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">
<head>
<title>Pv Monit</title>
<!--[if IE]><script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script><![endif]-->
<link rel="stylesheet" type="text/css" href="./css/style.css" />
<meta http-equiv="content-type" content="text/html;charset=utf-8" />
<meta http-equiv="cache-control" content="max-age=0" />
<meta http-equiv="cache-control" content="no-cache" />
<meta http-equiv="expires" content="0" />
<meta http-equiv="expires" content="Tue, 01 Jan 1980 1:00:00 GMT" />
<meta http-equiv="pragma" content="no-cache" />
<meta name="robots" content="non" /> 
</head>
<body>
    <div id="wrapper">
        <div id="headerwrap">
        <div id="header">
            <nav>
			  <ul>
				  <!-- TRAP MENU -->
				<?php echo $WWW_MENU; ?>
			  </ul>
			</nav>
            <h1>Pv Monit</h1>
            <p>Monitoring de l'installation solaire électrique</p>
        </div>
        </div>
        <div id="contentwrap">
        <div id="content">
	
			<!-- TRAP BOX -->
						
			<?php 
			// VE.DIRECT SCAN 
			
			$ppv_total=null;
			$nb_ppv_total=0;
			foreach (vedirect_scan() as $device) {
				echo '<div class="box" id="'.$device['nom'].'">';
				echo '<div class="title">['.$device['nom'].'] '.$device['modele'].'</div>';
				sort($device['data']);
				foreach (explode(',', $device['data']) as $data) {
					$dataSplit = explode(':', $data);
					switch ($dataSplit[0]) {
						// MPTT & BMV & Phoenix Inverter
						case 'V':
							echo '<div class="boxvaleur vbat"><h3>Tension de la batterie :</h3>';
							$Vbat=$dataSplit[1]*0.001;
							$VbatenPourcentage=round($Vbat*100/$WWW_VBAT_MAX);
							echo $VbatenPourcentage;
							$jaugeColor='jaugeVerte';
							if ($VbatenPourcentage < 80) 
								$jaugeColor='jaugeOrange';
							if ($VbatenPourcentage < 60) 
								$jaugeColor='jaugeRouge';
							echo '<progress class="'.$jaugeColor.'" style="width: 200px" max="100" value="'.$VbatenPourcentage.'"></progress> ';
							echo $Vbat.'V';
							echo '</div>';
						break;
						// BMV & MPTT
						case 'I':
							echo '<div class="boxvaleur plus">Courant de la batterie : '.$dataSplit[1].'mA</div>';
						break;
						// MPTT
						case 'PPV':
							echo '<div class="boxvaleur pvw"><h3>Production des panneaux</h3>';
							$PpvPourcentage=$dataSplit[1]*100/$WWW_PPV_MAX;
							$jaugeColor='jaugeVerte';
							if ($PpvPourcentage < 30) 
								$jaugeColor='jaugeOrange';
							if ($PpvPourcentage < 10) 
								$jaugeColor='jaugeRouge';
							echo '<progress class="'.$jaugeColor.'" style="width: 200px" max="100" value="'.$PpvPourcentage.'"></progress> ';
							echo $dataSplit[1].'W';
							$ppv_total=$ppv_total+$dataSplit[1];
							$nb_ppv_total++;
							echo '</div>';
						break;
						case 'ERR':
							echo '<div class="boxvaleur err"><h3>Présence d\'erreur</h3>';
							if ($dataSplit[1] == 0) {
								echo 'aucune';
							} else {
								echo '<b style="color: red">';
								switch ($dataSplit[1]) {
									case 2: echo 'Battery voltage too high'; break;
									case 17: echo 'Charger temperature too high'; break;
									case 18: echo 'Charger over current'; break;
									case 19: echo 'Charger current reversed'; break;
									case 20: echo 'Bulk time limit exceeded'; break;
									case 21: echo 'Current sensor issue (sensor bias/sensor broken)'; break;
									case 26: echo 'Terminals overheated'; break;
									case 33: echo 'Input voltage too high (solar panel)'; break;
									case 34: echo 'Input current too high (solar panel)'; break;
									case 38: echo 'Input shutdown (due to excessive battery voltage)'; break;
									case 116: echo 'Factory calibration data lost'; break;
									case 117: echo 'Invalid/incompatible firmware'; break;
									case 119: echo 'User settings invalid'; break;
									default: echo $dataSplit[1]; break;
								}
								echo '</b>';
							}
							echo '</div>';
						break;
						
						case 'VPV':
							echo '<div class="boxvaleur plus">Voltage des panneaux : '.$dataSplit[1].'mV</div>';
						break;
						case 'H19':
							$enKWh=$dataSplit[1]/100;
							echo '<div class="boxvaleur plus">Le rendement total: '.$enKWh.' kWh </div>';
						break;
						case 'H20':
							$enKWh=$dataSplit[1]/100;
							echo '<div class="boxvaleur plus">Rendement aujourd\'hui : '.$enKWh.' kWh </div>';
						break;
						case 'H21':
							echo '<div class="boxvaleur plus">Puissance maximum ce jour : '.$dataSplit[1].' W</div>';
						break;
						case 'H22':
							$enKWh=$dataSplit[1]/100;
							echo '<div class="boxvaleur plus">Rendemain hier : '.$enKWh.' kWh </div>';
						break;
						case 'H23':
							echo '<div class="boxvaleur plus">Puissance maximum hier : '.$dataSplit[1].' W</div>';
						break;
						// BMV & Phoenix Inverter
						case 'AR':
							echo '<div class="boxvaleur">Alarm reason '.$dataSplit[1];
							switch ($dataSplit[1]) {
								case 0: echo 'Aucune'; break;
								case 1: echo 'Low Voltage'; break;
								case 2: echo 'High Voltage'; break;
								case 4: echo 'Low SOC'; break;
								case 8: echo 'Low Starter Voltage'; break;
								case 16: echo 'High Starter Voltage'; break;
								case 32: echo 'Low Temperature'; break;
								case 64: echo 'High Temperature'; break;
								case 128: echo 'Mid Voltage'; break;
								case 256: echo 'Overload'; break;
								case 512: echo 'DC-ripple'; break;
								case 1024: echo 'Low V AC out'; break;
								case 2048: echo 'High V AC out'; break;
								default: echo $dataSplit[1]; break;
							}
							echo '</div>';
						break;
						// MPTT & Phoenix Inverter
						case 'CS':
							echo '<div class="boxvaleur cs"><h3>Status de charge</h3>';
							switch ($dataSplit[1]) {
								case 0: echo 'Off'; break;
								case 1: echo 'Faible puissance'; break;
								case 2:	echo 'Fault'; break;
								case 3:	echo 'Blunk (en charge)'; break;
								case 4:	echo 'Absorption';	break;
								case 5:	echo 'Float (maintient la charge pleine)';	break;
								case 9:	echo 'On';	break;
								default: echo $dataSplit[1]; break;
							}
							echo '</div>';
						break;
						// BMV 700 Only
						case 'P':
							echo '<div class="boxvaleur">Puissance instantané : '.$dataSplit[1].' W</div>';
						break;
						case 'T':
							echo '<div class="boxvaleur plus">Battery temperature : '.$dataSplit[1].' °C</div>';
						break;
						case 'VM':
							echo '<div class="boxvaleur plus">Mid-point voltage of the battery bank : '.$dataSplit[1].' mV</div>';
						break;
						case 'DM':
							echo '<div class="boxvaleur plus">Mid-point deviation of the battery bank : '.$dataSplit[1].' %</div>';
						break;
						case 'H17':
							$enKWh=$dataSplit[1]/100;
							echo '<div class="boxvaleur plus">Amount of discharged energy '.$dataSplit[1].'</div>';
						break;
						case 'H18':
							$enKWh=$dataSplit[1]/100;
							echo '<div class="boxvaleur plus">Amount of charged energy '.$dataSplit[1].'</div>';
						break;
						// BMV 600 only
						case 'H13':
							echo '<div class="boxvaleur plus">Number of low auxiliary voltage alarms '.$dataSplit[1].'</div>';
						break;
						case 'H14':
							echo '<div class="boxvaleur plus">Number of high auxiliary voltage alarms '.$dataSplit[1].'</div>';
						break;
						// BMV 
						case 'VS':
							echo '<div class="boxvaleur plus">Auxiliary (starter) voltage '.$dataSplit[1].' mV</div>';
						break;
						case 'CE':
							echo '<div class="boxvaleur">Consumed Amp Hours '.$dataSplit[1].' mAh</div>';
						break;
						case 'SOC':
							echo '<div class="boxvaleur">State-of-charge '.$dataSplit[1].'%</div>';
						break;
						case 'TTG':
							echo '<div class="boxvaleur">Time-to-go '.$dataSplit[1].' Minutes</div>';
						break;
						case 'Alarm':
							echo '<div class="boxvaleur plus">Alarm condition active '.$dataSplit[1].'</div>';
						break;
						case 'H1':
							echo '<div class="boxvaleur plus">Depth of the deepest discharge '.$dataSplit[1].' mAh</div>';
						break;
						case 'H2':
							echo '<div class="boxvaleur plus">Depth of the last discharge '.$dataSplit[1].' mAh</div>';
						break;
						case 'H3':
							echo '<div class="boxvaleur plus">Depth of the average discharge '.$dataSplit[1].' mAh</div>';
						break;
						case 'H4':
							echo '<div class="boxvaleur plus">Number of charge cycles '.$dataSplit[1].'</div>';
						break;
						case 'H5':
							echo '<div class="boxvaleur plus">Number of full discharges '.$dataSplit[1].'</div>';
						break;
						case 'H6':
							echo '<div class="boxvaleur plus">Cumulative Amp Hours drawn '.$dataSplit[1].' mAh</div>';
						break;
						case 'H7':
							echo '<div class="boxvaleur plus">Minimum main (battery) voltage '.$dataSplit[1].' mV</div>';
						break;
						case 'H8':
							echo '<div class="boxvaleur plus">Maximum main (battery) voltage '.$dataSplit[1].' mV</div>';
						break;
						case 'H9':
							echo '<div class="boxvaleur plus">Number of seconds since last full charge '.$dataSplit[1].' Seconds</div>';
						break;
						case 'H10':
							echo '<div class="boxvaleur plus">Number of automatic synchronizations '.$dataSplit[1].'</div>';
						break;
						case 'H11':
							echo '<div class="boxvaleur plus">Number of low main voltage alarms '.$dataSplit[1].'</div>';
						break;
						case 'H12':
							echo '<div class="boxvaleur plus">Number of high main voltage alarms '.$dataSplit[1].'</div>';
						break;
						case 'H15':
							echo '<div class="boxvaleur plus">Minimum auxiliary (battery) voltage '.$dataSplit[1].' mV</div>';
						break;
						case 'H16':
							echo '<div class="boxvaleur plus">Maximum auxiliary (battery) voltage '.$dataSplit[1].' mV</div>';
						break;
						// Phoenix Inverter
						case 'MODE':
							echo '<div class="boxvaleur">Device mode :';
							switch ($dataSplit[1]) {
								case 2: echo 'Inverter'; break;
								case 4: echo 'Off'; break;
								case 5: echo 'Eco'; break;
								default: echo $dataSplit[1]; break;
							}
							echo '</div>';
						break;
						case 'AC_OUT_V':
							echo '<div class="boxvaleur">AC output voltage (0,01V) '.$dataSplit[1].'</div>';
						break;
						case 'AC_OUT_I':
							echo '<div class="boxvaleur">AC output current (0.1 A) '.$dataSplit[1].'</div>';
						break;
						case 'WARN':
							echo '<div class="boxvaleur">Warning reason '.$dataSplit[1].'</div>';
						break;
						default:
							echo '<div class="boxvaleur plus">'.$dataSplit[0].' : '.$dataSplit[1].'</div>';
						break;
					}
				}
				echo '<div class="boxvaleur plusboutton" onclick="PlusPrint(\''.$device['nom'].'\')">...<span style="display: none" id="'.$device['nom'].'Status">hide</span></div>';
				echo '</div>';
			}

			?>
			<div class="box">
				<div class="title">Divers</div>
				<?php 
				if ($ppv_total !== null) {
					$ppv_max_total=$WWW_PPV_MAX*$nb_ppv_total;
					$PpvPourcentage=$ppv_total*100/$ppv_max_total;
					$jaugeColor='jaugeVerte';
					if ($PpvPourcentage < 30) {
						$jaugeColor='jaugeOrange';
					}
					if ($PpvPourcentage < 10) {
						$jaugeColor='jaugeRouge';
					}
					echo '<div class="boxvaleur pvw"><h3>Production total des panneaux</h3>';
					echo '<progress class="jaugeRouge" style="width: 80%" max="100" value="'.$PpvPourcentage.'"></progress> '.$ppv_total.'W</div>';
				}
				?>
				<div class="boxvaleur conso"><h3>Consommation de l'habitat</h3>
				<?php
				$consommation=consommationCache(); 
				if ($consommation === 'NODATA') {
					echo ' -- Indisponible --';
				} else {
					$ConsoPourcentage=round($consommation)*100/$WWW_CONSO_MAX;
					$jaugeColor='jaugeVerte';
					if ($ConsoPourcentage > 50) 
						$jaugeColor='jaugeOrange';
					if ($ConsoPourcentage > 80) 
						$jaugeColor='jaugeRouge';
					echo '<progress class="'.$jaugeColor.'" style="width: 200px" max="100" value="'.$ConsoPourcentage.'"></progress>';
					echo $consommation.'W';
				}
				?>
				</div>
				<div class="boxvaleur temp"><h3>Température du local</h3>
				<?php
				$temperature=temperatureCache(); 
				if ($temperature === 'NODATA') {
					echo ' -- Indisponible --';
				} else {
					$jaugeColor='jaugeVerte';
					if ($temperature <= 10 || $temperature >= 26) 
						$jaugeColor='jaugeOrange';
					if ($temperature <= 5 || $temperature >= 30) 
						$jaugeColor='jaugeRouge';
					echo '<progress class="'.$jaugeColor.'" style="width: 200px" max="45" value="'.$temperature.'"></progress>';
					echo round($temperature,1).'°';
				} 
				?>
				</div>
			</div>
			
			<?php if (is_file('./windguru.php')) { ?>
			<div style="width: 500px" class="box">
			<div class="title">Météo Windguru</div>
				<?php include('./windguru.php'); ?>
			</div>
			<?php } ?>
		
			<div style="clear:both"></div>
        </div>
        </div>
        <div id="footerwrap">
        <div id="footer">
            <p class="footer_right">By <a href="http://david.mercereau.info/">David Mercereau</a></p>
            <p class="footer_left">Copyleft - <a href="https://fr.wikipedia.org/wiki/Beerware">Licence Beerware</a></p>
        </div>
        </div>
    </div>
<script> 
<!-- Afficher plus d'information -->
function PlusPrint(idName) {
	var x = document.getElementById(idName).getElementsByClassName("plus");
	var i;
	for (i = 0; i < x.length; i++) {
		if (document.getElementById(idName + 'Status').innerHTML == 'hide') {
			x[i].style.display = "block";
		} else {
			x[i].style.display = "none";
		}
	}
	if (document.getElementById(idName + 'Status').innerHTML == 'hide') {
		document.getElementById(idName + 'Status').innerHTML = 'print';
	} else {
		document.getElementById(idName + 'Status').innerHTML = 'hide';
	}
}
</script>
</body>
</html>
