<?php
###################################
# Script sous licence BEERWARE
# Version 0.1	2016
###################################
include_once('/opt/PvMonit/config-default.php');
include_once('/opt/PvMonit/config.php');

include('/opt/PvMonit/function.php');

if ($_GET['cache'] == 'no') {
	$WWW_CACHE_AGE=1;
}
$aucunAffichage=true;
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
            <h1>Pv Monit<!-- TRAP TITRE --></h1>
            <p>Monitoring de l'installation solaire électrique</p>
        </div>
        </div>
        <div id="contentwrap">
        <div id="content">
						
			<?php 
			// VE.DIRECT SCAN 
			
			$ppv_total=null;
			$nb_ppv_total=0;
			foreach (vedirect_scan() as $device) {
				$aucunAffichage=false;
				echo '<div class="box" id="'.$device['nom'].'">';
				echo '<div class="title">['.$device['nom'].'] '.$device['modele'].'</div>';
				sort($device['data']);
				foreach (explode(',', $device['data']) as $data) {
					$dataSplit = explode(':', $data);
					$veData=ve_label2($dataSplit[0], $dataSplit[1]);
					switch ($dataSplit[0]) {
						// MPTT & BMV & Phoenix Inverter
						case 'V':
							echo '<div class="boxvaleur vbat"><h3>'.$veData['desc'].' : </h3>';
							$VbatenPourcentage=round($veData['value']*100/$WWW_VBAT_MAX);
							$jaugeColor='jaugeVerte';
							if ($VbatenPourcentage < 80) 
								$jaugeColor='jaugeOrange';
							if ($VbatenPourcentage < 60) 
								$jaugeColor='jaugeRouge';
							echo '<progress class="'.$jaugeColor.'" style="width: 200px" max="100" value="'.$VbatenPourcentage.'"></progress> ';
							echo $veData['value'].$veData['units'];
							echo '</div>';
						break;
						case 'PPV':
							echo '<div class="boxvaleur pvw"><h3>'.$veData['desc'].'</h3>';
							$PpvPourcentage=$veData['value']*100/$WWW_PPV_MAX;
							$jaugeColor='jaugeVerte';
							if ($PpvPourcentage < 25) 
								$jaugeColor='jaugeOrange';
							if ($PpvPourcentage < 10) 
								$jaugeColor='jaugeRouge';
							echo '<progress class="'.$jaugeColor.'" style="width: 200px" max="100" value="'.$PpvPourcentage.'"></progress> ';
							echo $veData['value'].$veData['units'];
							$ppv_total=$ppv_total+$dataSplit[1];
							$nb_ppv_total++;
							echo '</div>';
						break;
						case 'ERR':
							echo '<div class="boxvaleur err"><h3>'.$veData['desc'].'</h3>';
							if ($veData['value'] == 'Aucune') {
								echo $veData['value'];
							} else {
								echo '<b style="color: red">';
								echo $veData['value'];
								echo '</b>';
							}
							echo '</div>';
						break;
						case 'CS':
							echo '<div class="boxvaleur cs"><h3>'.$veData['desc'].'</h3>';
							echo $veData['value'];
							echo '</div>';
						break;
						case 'SOC':
							echo '<div class="boxvaleur soc"><h3>'.$veData['desc'].'</h3>';
							$jaugeColor='jaugeVerte';
							if ($veData['value'] < 80) 
								$jaugeColor='jaugeOrange';
							if ($veData['value'] < 60) 
								$jaugeColor='jaugeRouge';
							echo '<progress class="'.$jaugeColor.'" style="width: 200px" max="100" value="'.$veData['value'].'"></progress> ';
							echo $veData['value'].$veData['units'];
							echo '</div>';
						break;
						case 'AR':
							echo '<div class="boxvaleur ar"><h3>'.$veData['desc'].'</h3>';
							if ($veData['value'] == 'Aucune') {
								echo $veData['value'];
							} else {
								echo '<b style="color: red">';
								echo $veData['value'];
								echo '</b>';
							}
							echo '</div>';
						break;
						default:
							echo '<div class="boxvaleur '.$veData['plus'].'">'.$veData['desc'].' : '.$veData['value'].$veData['units'].'</div>';
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
					if ($PpvPourcentage < 25) {
						$jaugeColor='jaugeOrange';
					}
					if ($PpvPourcentage < 10) {
						$jaugeColor='jaugeRouge';
					}
					echo '<div class="boxvaleur pvw"><h3>Production total des panneaux</h3>';
					echo '<progress class="'.$jaugeColor.'" style="width: 80%" max="100" value="'.$PpvPourcentage.'"></progress> '.$ppv_total.'W</div>';
				}
				?>
				
				<?php
				$consommation=consommationCache(); 
				if ($consommation !== null) {
					$aucunAffichage=false;
					echo '<div class="boxvaleur conso"><h3>Consommation de l\'habitat</h3>';
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
					echo '</div>';
				}
				?>
				
				<?php
				$temperature=temperatureCache(); 
				if ($temperature !== null) {
					$aucunAffichage=false;
					echo '<div class="boxvaleur temp"><h3>Température du local</h3>';
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
					echo '</div>';
				}
				?>
				
				<?php
				if ($aucunAffichage === true) {
					echo '<div class="boxvaleur">Rien à afficher, copier le fichier config-default.php en config.php et modifier le pour vos paramètres</div>';
				}
				?>
				
			</div>
			
                        <?php if (is_file('./windguru.php')) { ?>
                        <div style="width: 500px" class="box">
                        <div class="title">Météo Windguru</div>
                                <?php include('./windguru.php'); ?>
                        </div>
                        <?php } ?>

			<!-- TRAP BOX -->
		
			<div style="clear:both"></div>
        </div>
        </div>
        <div id="footerwrap">
        <div id="footer">
            <p class="footer_right">Par <a href="http://david.mercereau.info/">David Mercereau</a> (<a href="https://github.com/kepon85/PvMonit/">Dépôt github</a>)</p>
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
