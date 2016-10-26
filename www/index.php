<?php
###################################
# Script sous licence BEERWARE
# Version 0.1	2016
###################################
include_once('/opt/PvMonit/config-dist.php');
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
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate"/>
<meta http-equiv="Pragma" content="no-cache"/>
<meta http-equiv="Expires" content="0"/>
</head>
<body>
    <div id="wrapper">
        <div id="headerwrap">
        <div id="header">
            <nav>
			  <ul>
				<?php echo $WWW_MENU; ?>
				<!-- TRAP MENU-->
			  </ul>
			</nav>
            <h1>Pv Monit</h1>
            <p>Monitoring de l'installation solaire électrique</p>
        </div>
        </div>
        <div id="contentwrap">
        <div id="content">
			
           <!--<div class="box">
				<div class="title">MPTT blue solare</div>
				<div class="boxvaleur vbat"><h3>Tension de la batterie</h3>
				<progress class="jaugeVerte" style="width: 80%" max="30" value="2"></progress>
				12V
				</div>
				<div class="boxvaleur pvw"><h3>Production des panneaux</h3>
				<progress class="jaugeRouge" style="width: 80%" max="30" value="39"></progress>
				122W</div>
				<div class="boxvaleur cs"><h3>Status de charge</h3>
				<progress class="jaugeBleu" style="width: 80%" max="30" value="39"></progress>Blunk</div>
				<div class="boxvaleur err"><h3>Présence d'erreur</h3> Aucune</div>
			</div>-->
			
			<?php 
			$ppv_total=null;
			$nb_ppv_total=0;
			foreach (vedirect_scan() as $device) {
				echo '<div class="box" id="'.$device['nom'].'">';
				echo '<div class="title">['.$device['type'].'] '.$device['nom'].'</div>';
				sort($device['data']);
				foreach (explode(',', $device['data']) as $data) {
					$dataSplit = explode(':', $data);
					switch ($dataSplit[0]) {
						case 'V':
							echo '<div class="boxvaleur vbat"><h3>Tension de la batterie';
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
						case 'CS':
							echo '<div class="boxvaleur cs"><h3>Status de charge</h3>';
							switch ($dataSplit[1]) {
								case 0:
									echo 'Off (pas de production)';
								break;
								case 2:
									echo 'Fault';
								break;
								case 3:
									echo 'Blunk (Pas plein, on charge)';
								break;
								case 4:
									echo 'Absorption';
								break;
								case 5:
									echo 'Float (Pleinne charge on maintient)';
								break;
							}
							echo '</div>';
						break;
						case 'ERR':
							echo '<div class="boxvaleur err"><h3>Présence d\'erreur</h3>';
							if ($dataSplit[1] == 0) {
								echo 'aucune';
							} else {
								echo '<b style="color: red">code '.$dataSplit[1].'</b>';
							}
							echo '</div>';
						break;
						case 'I':
							echo '<div class="boxvaleur plus">Courant de la batterie : '.$dataSplit[1].'mA</div>';
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
			
			<script src="http://widget.windguru.cz/js/wg_widget.php" type="text/javascript"></script>
			<script language="JavaScript" type="text/javascript">
			//<![CDATA[
			WgWidget({
			s: 496406, odh:8, doh:22, wj:'kmh', tj:'c', waj:'m', fhours:72, lng:'fr',
			params: ['WINDSPD','GUST','SMER','TMPE','APCPs','TCDC','CDC','RH'],
			first_row:true,
			spotname:true,
			first_row_minfo:true,
			last_row:false,
			lat_lon:true,
			tz:true,
			sun:true,
			link_archive:false,
			link_new_window:false
			},
			'wg_target_div_496406_33925293'
			);
			//]]>
			</script>
			<div style="width: 500px" class="box">
			<div class="title">Météo Windguru</div>
			<div id="wg_target_div_496406_33925293"></div>
			</div>
			
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
