<?php

###################################
# Script sous licence BEERWARE
###################################

// Si c'est sur le cloud : 
if (isset($cloud) && isset($id)) {
	include_once('./function.php');
	// Chargement / merge de la config
	$config = getConfigYaml($configCloud['users'][$id], $configCloud['configDataDir'].$id.'.yaml');
	$config_dir=$configCloud['configDataDir'];
	$config_file=$id.'.yaml';
	$config['www']['checkUpdate'] = false;
	$config['urlDataXml'] = './data-xml/'.$id.'.xml';
} else {
	include('/opt/PvMonit/function.php');
	// Chargement de la config
	$config_dir='/opt/PvMonit/';
	$config_file='config.yaml';
	$config = getConfigYaml($config_dir);
}

@include_once('./header.php');  

if (isset($_POST['www-password'])) {
	setcookie('www-password', md5($_POST['www-password']), time()+$config['www']['passwordLife']);
	header('Location: '.$_SERVER['REQUEST_URI']);
}
$printWww=false;
if ($config['www']['password'] == false) {
	$printWww=true;
} else {
	if (isset($_COOKIE['www-password']) && $_COOKIE['www-password'] == $config['www']['password']) {
		$printWww=true;
	} 
}
if (isset($_POST['domo-password'])) {
	setcookie('domo-password', md5($_POST['domo-password']), time()+$config['www']['passwordLife']);
	header('Location: '.$_SERVER['REQUEST_URI']);
}
$printDomo=false;
if ($config['www']['domoPassword'] == false) {
	$printDomo=true;
} else {
	if (isset($_COOKIE['domo-password']) && $_COOKIE['domo-password'] == $config['www']['domoPassword']) {
		$printDomo=true;
	} 
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">
<head>
<?php 
if (isset($cloud)) {  
	$title=$configCloud['title'];
	if (isset($configCloud['users'][$id]['title'])) {
		$title=$configCloud['users'][$id]['title'];
	}
	echo '<title>'.$title.'</title>';
} else {  ?>
	<title>Pv Monit  v<?= VERSION ?></title>
<?php }  ?>
<!--[if IE]><script src="http://html5shiv.googlecode.comdevice_id/svn/trunk/html5.js"></script><![endif]-->
<link rel="stylesheet" type="text/css" href="assets/css/style.css" />
<meta http-equiv="content-type" content="text/html;charset=utf-8" />
<meta http-equiv="cache-control" content="max-age=0" />
<meta http-equiv="cache-control" content="no-cache" />
<meta http-equiv="expires" content="0" />
<meta http-equiv="expires" content="Tue, 01 Jan 1980 1:00:00 GMT" />
<meta http-equiv="pragma" content="no-cache" />
<meta name="robots" content="non" /> 
<script src="assets/jquery.min.js" /></script>
</head>
<body>
    <div id="wrapper">
		<?php 
		if ($printWww == true) {
		?>
        <div id="headerwrap">
        <div id="header">
            <nav>
			  <ul>
				  <!-- TRAP MENU -->
				<?php
				foreach ($config['www']['menu'] as $menu) {
					echo $menu;
				} 
				
				if ($config['www']['help'] == true) {
					echo '<li><a href="help.php" onclick="open(\'help.php\', \'Popup\', \'scrollbars=1,resizable=1,height=80,width=350\'); return false;">?</a></li>';
				}
				
				if (isset($config['user'])){
					echo '<li><a href="#" onclick="$(\'#userData\').toggle(); return false;">
					<img src="assets/images/setting.png" width="20" alt="Config" title="Editer la configuration" /></a></li>';
				}
				?>
				
				<li><input type="checkbox" id="autoRefresh" title="Actualisation automatique tout les <?= $config['www']['refreshTime']/1000 ?> secondes" checked='checked' />
				<input type="hidden" name="refreshBusy" id="refreshBusy" /></li>
				<li><a id="refresh"><img id="refreshImg" src="assets/images/refresh.png" width="20" alt="Refresh" title="Actualiser" /></a></li>
			  </ul>
			</nav>
			<?php 
			if (isset($cloud)) {  
				$title=$configCloud['title'];
				if (isset($configCloud['users'][$id]['title'])) {
					$title=$configCloud['users'][$id]['title'];
				}
				echo '<h1>'.$title.'</h1>';
				$subTitle=$configCloud['subTitle'];
				if (isset($configCloud['users'][$id]['subTitle'])) {
					$subTitle=$configCloud['users'][$id]['subTitle'];
				}
				echo '<p>'.$subTitle.'</p>';
            } else {  ?>
				<h1>Pv Monit v<?= VERSION ?> <span id="upgrade"></span><!-- TRAP TITRE --></h1>
				<p>Monitoring de l'installation solaire électrique</p>
            <?php }  ?>
        </div>
        </div>
        <div id="contentwrap">

        <div id="content">
			<?php 
			if (isset($config['user'])){
				if (isset($_POST['submit'])) {
					$config_a_modifier = yaml_parse_file($config_dir.$config_file);
					foreach ($_POST as $name=>$value)  {
						if ($name != 'submit') {
							$config_a_modifier['user'][$name]['value'] = $value;
							// Pour valider le nouvel affichage
							$config['user'][$name]['value'] = $value;
						}
					}
					if (isset($cloud)) {
						touch($config_dir.$config_file.'.change');
					} else {
						@copy($config_dir.$config_file, $config_dir.$config_file.'.'.time());
					}
					if (!yaml_emit_file($config_dir.$config_file, $config_a_modifier)) {
						echo 'Erreur à l\'enregistrement de la configuration';
					}
				}
				echo '<div id="userData" style="display: none;" class="boxvaleur">';
				echo '<p>Configuration modifiable : </p>';
				echo '<form action="#" method="post"><p>';
				foreach ($config['user'] as $name=>$userForm)  {
					echo '<label for="'.$name.'">'.$userForm['label'].'</label><br />';
					echo '<input name="'.$name.'" ';
					foreach ($userForm as $param=>$value)  {
						if ($param != 'label') {
							echo $param.'='.$value.' ';
						}
					}
					echo '/><br />';
				}
				echo '<input type="submit" name="submit" value="Enregistré" /></p>';
				echo '</form>';
				echo '</div>';
			} 
			?> 
			
			<div id="waitFirst" class="boxvaleur waitFirst">Patience...<img src="assets/images/wait2.gif" width="100%" /></div>
			<?php 
			
			// Check heure système
			if (date('Y') < '2019') {
				echo '<div class="box" id="errordate">';
				echo '<div class="title">Erreur</div>';
					echo '<div class="boxvaleur ar"><h3>Heure du système : </h3>';
						echo '<b style="color: red">';
							echo 'incorrect, on ne collecte rien.';
						echo '</b>';
					echo '</div>';
				echo '</div>';	
			}
			?>

			<div style="display: none" id="nodata" class="boxvaleur">Rien à afficher, vérifier le fichier config.yaml. <br /><span style="color: red" id="textStatus"></span> : <span id="errorThrown"></span></div>
				
			<div style="display: none" class="box" id="box_weatherForcast">
				<div class="title">Weather forcast</div>
			</div>			
			<div style="display: none" class="box" id="box_weatherProdForcast">
				<div class="title">Weather production forcast</div>
			</div>			
			
			<?php 
			if ($config['www']['domo'] == true) { 
				echo '<div style="display: none" class="box" id="box_domo"><div class="title">Domo';
				if ($config['www']['domoEdit'] == true) { 
					echo ' - <a href="./domo-edit-script.php"><img src="./assets/images/setting.png" width="13" alt="Edit" /></a>';
				}
				echo '</div>';
				if ($printDomo == true) {
					if (empty($cloud)) {
						if (!is_file($config['domo']['jsonFile']['etatPath'])) {
							genDefaultJsonFile('etat');
						}
						if (!is_file($config['domo']['jsonFile']['modPath'])) {
							genDefaultJsonFile('mod');
						}
						echo '<div class="boxvaleur requestWait">';
							echo '<div>Requête en attente : </div>';
							for ($i = 1; $i <= $config['domo']['relayNb']; $i++) {
								echo '<div class="requestWaitList" id="relay'.$i.'-changeTo0">- '.$config['domo']['relayName'][$i].' : Off</div>';
								echo '<div class="requestWaitList" id="relay'.$i.'-changeTo1">- '.$config['domo']['relayName'][$i].' : Auto</div>';
								echo '<div class="requestWaitList" id="relay'.$i.'-changeTo3">- '.$config['domo']['relayName'][$i].' : On</div>';
							}
						echo '</div>';
					}
					
					for ($i = 1; $i <= $config['domo']['relayNb']; $i++) {
						echo '<div class="boxvaleur">';
						echo '	<div id="relayEtat'.$i.'" class="etatNull relayEtat">&nbsp;</div>
								<div id="relayMod'.$i.'" class="modNull relayMod">
									<span>'.$config['domo']['relayName'][$i].'</span>
									<span style="display: none;" id="relayMod'.$i.'value">Null</span>
									<span class="relayModValueHumain" id="relayMod'.$i.'valueHumain">Null</span>
									<span class="relayModButtons" id="relayMod'.$i.'buttons">
										<span class="relayChange mod0" id="relayModChange-'.$i.'-off">Off</span>
										<span class="relayChange mod1" id="relayModChange-'.$i.'-auto">Auto</span>
										<span class="relayChange mod3" id="relayModChange-'.$i.'-on">On</span>
								</span>
								</div>';
						echo '</div>';
					}
					echo '<div class="boxvaleur"><br /><br /></div>';
				} else {
					echo '<form action="#" method="post" class="formPassword">
					<label for="domo-password">Mot de passe : </label>
					<input type="password" name="domo-password" />
					<input type="submit" />
					</form>';
				}
				
				echo '</div>';
				
					
			}
		?>
			
			<script type="text/javascript">
				
				function trucAdir(niveau, msg) {
					if (<?= $config['printMessage'] ?> >= niveau) {
						console.log(msg)
					}
				}
				function traiteErreur(jqXHR, textStatus, errorThrown) {
					$("#refreshImg").val('0');
					$("#waitFirst").hide();
					$("#nodata").show();
					$("#textStatus").prepend(textStatus);
					$("#errorThrown").prepend(errorThrown);
				}
				<?php  if ($config['www']['domo'] == true) {  ?>
				function refreshDomo() {
					trucAdir(3, 'Refresh Domo');
					$.ajax({
						url : 'domo.php',
						<?php
						if (isset($cloud)) {
							echo "type : 'POST',"; 
						} else {
							echo "type : 'GET',"; 
						}
						?>
						dataType : 'json',
						data : 'action=printRefresh',
						success : function(resultat, statut){
							for (var [cle, valeur] of Object.entries(resultat['etat'])){
								trucAdir(5, 'Etat :' + cle + ' ' + valeur);
								$('#relayEtat'+cle).removeClass('etatNull');
								$('#relayEtat'+cle).removeClass('etat0');
								$('#relayEtat'+cle).removeClass('etat1');
								$('#relayEtat'+cle).addClass('etat'+valeur);
							}
							for (var [cle, valeur] of Object.entries(resultat['mod'])){
								trucAdir(5, 'Mod :' + cle + ' ' + valeur);
								$('#relayMod'+cle).removeClass('modNull');
								$('#relayMod'+cle).removeClass('mod0');
								$('#relayMod'+cle).removeClass('mod1');
								$('#relayMod'+cle).removeClass('mod2');
								$('#relayMod'+cle).removeClass('mod3');
								$('#relayMod'+cle).addClass('mod'+valeur);
								$('#relayMod'+cle+'value').html(valeur);
								switch (valeur) {
									case 0:
										$('#relayMod'+cle+'valueHumain').html('Off');
										$('#relayModChange-'+cle+'-off').hide();
										$('#relayModChange-'+cle+'-auto').show();
										$('#relayModChange-'+cle+'-on').show();
									break;
									case 1:
										$('#relayMod'+cle+'valueHumain').html('Auto (off)');
										$('#relayModChange-'+cle+'-off').show();
										$('#relayModChange-'+cle+'-auto').hide();
										$('#relayModChange-'+cle+'-on').show();
									break;
									case 2:
										$('#relayMod'+cle+'valueHumain').html('Auto (on)');
										$('#relayModChange-'+cle+'-off').show();
										$('#relayModChange-'+cle+'-auto').hide();
										$('#relayModChange-'+cle+'-on').show();
									break;
									case 3:
										$('#relayMod'+cle+'valueHumain').html('On');
										$('#relayModChange-'+cle+'-off').show();
										$('#relayModChange-'+cle+'-auto').show();
										$('#relayModChange-'+cle+'-on').hide();
									break;
									default:
										$('#relayMod'+cle+'valueHumain').html('Null');
										$('#relayModChange-'+cle+'-off').hide();
										$('#relayModChange-'+cle+'-auto').hide();
										$('#relayModChange-'+cle+'-on').hide();
								}
							}
							<?php
							if (isset($cloud)) {
							?>
							$.ajax({
								url : 'domo.php',
								type : 'POST',
								dataType : 'json',
								data : 'action=diffMod',
								success : function(resultat, statut){
									console.log(resultat);
									$('.requestWait').hide();
									$('.requestWaitList').hide();
									for (const property in resultat) {
										$('.requestWait').show();
										$('#relay' + property + '-changeTo' + resultat[property] ).show();
										console.log('relay: ' + property + ' changeTo:' + resultat[property]);
									}
								}
							});
							<?php
							}
							?>
						},
						error : traiteErreur,
					});
				}
				function preparDomo(){
					$("#box_domo").show();
					refreshDomo();
				}
				<?php } ?>
				<?php  if ($config['www']['weatherForcast'] == true) {  ?>
				function refreshWeatherForcast() {
					trucAdir(3, 'Refresh WeatherForcast');
					$.ajax({
						url : 'weatherForcast.php',
						type : 'GET',
						dataType : 'json',
						success : function(resultat, statut){
							$('#box_weatherForcast').show();
							//~ console.log(resultat.error);
							//~ console.log(resultat);
							if (typeof resultat.error !== 'undefined') {
								$('#box_weatherForcast').append('<div class="boxvaleur err">'+resultat.error+'</div>');
							} else {
								var previousDay = 0;
								var plus='';
								for (var [cle, valeur] of Object.entries(resultat['list'])){
									//~ console.log(valeur);
									if (valeur.dt > <?php echo time()+($config['www']['weatherForcastNbDayPrint']*86400) ?>) {
										plus=' plus';
									}
									var date = new Date(valeur.dt * 1000);
									var hours = date.getHours();
									var day = date.getDate();
									var month = date.getMonth();
									var year = date.getFullYear();
									if (previousDay != day) {
										$('#box_weatherForcast').append('<div class="boxvaleur'+plus+'"><h2>Le '+day+' '+month+' '+year+'</h2></div>');
									}
									
									$('#box_weatherForcast').append('<div class="boxvaleur'+plus+'">' +
																	'<img style="float: right" src="https://openweathermap.org/img/wn/'+valeur.weather[0].icon+'.png" width="45" /> ' +
																	hours+'h - '+valeur.main.temp+'°C - H '+valeur.main.humidity+'%' +
																	'<br />Cloud : '+valeur.clouds.all+'% - '+ valeur.weather[0].description +
																	'<br />Wind : '+valeur.wind.speed+'km/h - '+valeur.wind.deg+'° - '+valeur.main.pressure+'Ha' + 
																	'</div>');
									previousDay=day;
								}
								$('#box_weatherForcast').append('<div id="PlusWeatherForcast" class="boxvaleur plusboutton" onclick="PlusPrint(\'weatherForcast\')">...</div>'+
															'<div class="boxvaleur moinsboutton" onclick="MoinsPrint(\'weatherForcast\')">...</div>');
							}
						},
						error : function (xhr, ajaxOptions, thrownError) {
							alert('Erreur à l\'affichage de WeatherForcast');
						}
					});
				}
				<?php } ?>
				<?php  if ($config['www']['weatherProdForcast'] == true) {  ?>
				function refreshWeatherProdForcast() {
					trucAdir(3, 'Refresh WeatherProdForcast');
					$.ajax({
						url : 'weatherProdForcast.php',
						type : 'GET',
						dataType : 'json',
						success : function(resultat, statut){
							$('#box_weatherProdForcast').show();
							if (typeof resultat.error !== 'undefined') {
								$('#box_weatherProdForcast').append('<div class="boxvaleur err">'+resultat.error+'</div>');
							} else {
								var previousDay = 0;
								var plus='';
								for (var [day, valeur] of Object.entries(resultat)){	
									var classProd='';							
									if (day != 0 && valeur.prodCumul > <?= $config['weather']['dalyConsumption'] ?>) {
										classProd='prodOk';
									}else if (day != 0) {
										classProd='proKo';
									}
									$('#box_weatherProdForcast').append('<div class="boxvaleur">Day +'+day+' : <b class="'+classProd+'">'+valeur.prodCumul+'W</b> (cloud '+valeur.cloudAvg+'%)</div>');
									var ByHourContent = '<div class="boxvaleur plus"><ul>';
									for (var [hour, valeurByHour] of Object.entries(valeur.byHour)){
										if (valeurByHour.sun == 1) {
											//~ console.log(valeurByHour);
											ByHourContent = ByHourContent + '<li>'+hour+'H  : <b>'+valeurByHour.prod+'W</b>  (cloud '+valeurByHour.cloud+'%)</li>';
										}
									}
									ByHourContent = ByHourContent + '</ul></div>';
									$('#box_weatherProdForcast').append(ByHourContent);
								}
								$('#box_weatherProdForcast').append('<div id="PlusWeatherProdForcast" class="boxvaleur plusboutton" onclick="PlusPrint(\'weatherProdForcast\')">...</div>'+
															'<div class="boxvaleur moinsboutton" onclick="MoinsPrint(\'weatherProdForcast\')">...</div>');
							}
						},
						error : function (xhr, ajaxOptions, thrownError) {
							alert('Erreur à l\'affichage de WeatherProdForcast');
						}
					});
				}
				<?php } ?>
				// /domo
				function readData(xml) {
					$("#refreshImg").val('0');
					$("#waitFirst").hide();
					$("#clearBoth").remove();
					$(".boxMonit").remove();
					trucAdir(5, 'Lecture du XML');
					$("#refreshImg").attr('src', "assets/images/refresh.png");
					$(xml).find('device').each(function() {
						var id = $(this).attr('id');
						trucAdir(5, 'Récupération de l\'id ' + id);
						var nom = $(this).find('nom').text();
						var modele = $(this).find('modele').text();
						if ($('#box_' + id + '').length == 0) {
								$('#content').append('<div class="box boxMonit" id="box_' + id + '"></div>');
								$('#box_' + id + '').prepend('<div class="title">[' + nom + '] ' + modele + '</div>');
						}
						$(this).find('data').each( function() 	{
								var data_id = $(this).attr('id');
								var screen = '';
								if ($(this).attr('screen') != 1) {
									screen = 'plus';
								}
								var smallScreen = '';
								if ($(this).attr('smallScreen') != 1) {
									smallScreen = 'plusplus';
								}
								var desc = $(this).find('desc').text();
								var value = $(this).find('value').text();
								var units = $(this).find('units').text();
								switch (data_id) {
									case 'V':
										var VbatEnPourcentage=Math.round(value*100/<?= $config['www']['vbatMax'] ?>);
										var jaugeColor='jaugeVerte';
										if (VbatEnPourcentage < 80) {
											jaugeColor='jaugeOrange';
										}
										if (VbatEnPourcentage < 60) {
											jaugeColor='jaugeRouge';
										}
										$('#box_' + id + '').append('<div class="boxvaleur vbat '+screen+' '+smallScreen+'"><h3>'+desc+'</h3>' + 
										'<progress class="' + jaugeColor + '" max="100" value="' + VbatEnPourcentage + '"></progress> ' +
										value+units+'</div>');
									break;
									case 'SOC':
										var jaugeColor='jaugeVerte';
										if (value < 85) {
											jaugeColor='jaugeOrange';
										}
										if (value < 75) {
											jaugeColor='jaugeRouge';
										}
										$('#box_' + id + '').append('<div class="boxvaleur soc '+screen+' '+smallScreen+'"><h3>'+desc+'</h3>' + 
										'<progress class="' + jaugeColor + '" max="100" value="' + value + '"></progress> ' +
										value+units+'</div>');
									break;
									case 'PPV':
										var PpvPourcentage=Math.round(value*100/<?= $config['www']['PpvMax'] ?>);
										var jaugeColor='jaugeVerte';
										if (PpvPourcentage < 25) {
											jaugeColor='jaugeOrange';
										}
										if (PpvPourcentage < 10) {
											jaugeColor='jaugeRouge';
										}
										$('#box_' + id + '').append('<div class="boxvaleur ppv '+screen+' '+smallScreen+'"><h3>'+desc+'</h3>' + 
										'<progress class="' + jaugeColor + '" max="100" value="' + PpvPourcentage + '"></progress> ' +
										value+units+'</div>');
									break;
									case 'ERR':
										souligner='';
										if (value != 'Aucune') {
											souligner='souligner';
										}
										$('#box_' + id + '').append('<div class="boxvaleur err '+screen+' '+smallScreen+'"><h3>'+desc+'</h3><span class="'+souligner+'">' + value +'</span></div>')
									break;
									case 'AR':
										souligner='';
										if (value != 'Aucune') {
											souligner='souligner';
										}
										$('#box_' + id + '').append('<div class="boxvaleur ar '+screen+' '+smallScreen+'"><h3>'+desc+'</h3><span class="'+souligner+'">' + value +'</span></div>')
									break;
									case 'CS':
										$('#box_' + id + '').append('<div class="boxvaleur cs '+screen+' '+smallScreen+'"><h3>'+desc+'</h3> '+value+units+'</div>');
									break;
									case 'PPVT':
										var PpvtPourcentage=Math.round(value*100/<?= $config['www']['PpvtMax'] ?>);
										var jaugeColor='jaugeVerte';
										if (PpvtPourcentage < 25) {
											jaugeColor='jaugeOrange';
										}
										if (PpvtPourcentage < 10) {
											jaugeColor='jaugeRouge';
										}
										$('#box_' + id + '').append('<div class="boxvaleur ppv '+screen+' '+smallScreen+'"><h3>'+desc+'</h3>' + 
										'<progress class="' + jaugeColor + '" max="100" value="' + PpvtPourcentage + '"></progress> ' +
										value+units+'</div>');
									break;
									case 'CONSO':
										var ConsoPourcentage=Math.round(value*100/<?= $config['consoPlafond'] ?>);
										var jaugeColor='jaugeVerte';
										if (ConsoPourcentage > 50) {
											jaugeColor='jaugeOrange';
										}
										if (ConsoPourcentage > 80) {
											jaugeColor='jaugeRouge';
										}
										$('#box_' + id + '').append('<div class="boxvaleur conso '+screen+' '+smallScreen+'"><h3>'+desc+'</h3>' + 
										'<progress class="' + jaugeColor + '" max="100" value="' + ConsoPourcentage + '"></progress> ' +
										value+units+'</div>');
									break;
									case 'TEMP':
										var jaugeColor='jaugeVerte';
										if (value <= 9 || value >= 18) {
											jaugeColor='jaugeOrange';
										}
										if (value <= 1 || value >= 33) {
											jaugeColor='jaugeRouge';
										}
										$('#box_' + id + '').append('<div class="boxvaleur temp '+screen+' '+smallScreen+'"><h3>'+desc+'</h3>' + 
										'<progress class="' + jaugeColor + '" max="45" value="' + value + '"></progress> ' +
										value+units+'</div>');
									break;
									default:
										$('#box_' + id + '').append('<div class="boxvaleur '+screen+' '+smallScreen+'">'+desc+' : '+value+units+'</div>');
								}
								
							});
                                                
					});
					$(xml).find('device').each(function() {
						var id = $(this).attr('id');
						trucAdir(5, 'Pour les plus, on re-récupère les id ' + id);
						if ($('#Plus'+id).length == 0) {
							$('#box_' + id + '').append('<div id="Plus'+id+'" class="boxvaleur plusboutton" onclick="PlusPrint(\''+id+'\')">...</div>'+
														'<div class="boxvaleur moinsboutton" onclick="MoinsPrint(\''+id+'\')">...</div>');
						}
					});
					<?php  if ($config['www']['domo'] == true) {  ?>
						preparDomo();
					<?php } ?>
					<?php  if (empty($cloud)) {  ?>
					$('#content').append('<div id="clearBoth" style="clear:both"></div>');
					<?php } ?>
					<?php 
					if (isset($cloud)) {
						echo '$.ajax( {
							type: "GET",
							url: "data-cloud-xml.php",
							dataType: "xml",
							success: readDataCloud
						});';
					}
					?>
				  }
				function readDataCloud(xml) {
					$(".boxMonitCloud").remove();
					trucAdir(5, 'Lecture du XML Cloud');
					$(xml).find('device').each(function() {
						var id = $(this).attr('id');
						trucAdir(5, 'Récupération de l\'id ' + id);
						var nom = $(this).find('nom').text();
						var modele = $(this).find('modele').text();
						if ($('#box_' + id + '').length == 0) {
								$('#content').append('<div class="box boxMonitCloud" id="box_' + id + '"></div>');
								$('#box_' + id + '').prepend('<div class="title">[' + nom + '] ' + modele + '</div>');
						}
						$(this).find('data').each( function() 	{
								var data_id = $(this).attr('id');
								var screen = '';
								if ($(this).attr('screen') != 1) {
									screen = 'plus';
								}
								var smallScreen = '';
								if ($(this).attr('smallScreen') != 1) {
									smallScreen = 'plusplus';
								}
								var desc = $(this).find('desc').text();
								var value = $(this).find('value').text();
								var units = $(this).find('units').text();
								switch (data_id) {
									case 'ERR':
										souligner='';
										if (value != 'Aucune') {
											souligner='souligner';
										}
										$('#box_' + id + '').append('<div class="boxvaleur err '+screen+' '+smallScreen+'"><h3>'+desc+'</h3><span class="'+souligner+'">' + value +'</span></div>')
									break;
									default:
										$('#box_' + id + '').append('<div class="boxvaleur '+screen+' '+smallScreen+'">'+desc+' : '+value+units+'</div>');
								}
								
							});
					});
					$(xml).find('device').each(function() {
						var id = $(this).attr('id');
						trucAdir(5, 'Pour les plus, on re-récupère les id ' + id);
						if ($('#Plus'+id).length == 0) {
							$('#box_' + id + '').append('<div id="Plus'+id+'" class="boxvaleur plusboutton" onclick="PlusPrint(\''+id+'\')">...</div>'+
														'<div class="boxvaleur moinsboutton" onclick="MoinsPrint(\''+id+'\')">...</div>');
						}
					});
					$('#content').append('<div id="clearBoth" style="clear:both"></div>');
				  }
				
				var uneDate;
				function reloadData(force) {
					trucAdir(3, 'Reload');
					$("#refreshImg").val('1');
					var dt = new Date();
					var time = dt.getHours() + ":" + dt.getMinutes() + ":" + dt.getSeconds();
					$("#refreshImg").attr('title', 'Actualiser (dernier à '+time+')')
					$("#refreshImg").attr('src', "assets/images/wait.gif");
					if (force == 0) {
						$.ajax( {
								type: "GET",
								url: "<?= $config['urlDataXml'] ?>",
								dataType: "xml",
								success: readData,
								  error : traiteErreur
						});
					} else {
						$.ajax( {
								type: "GET",
								url: "<?= $config['urlDataXml'] ?>?nocache=1",
								dataType: "xml",
								success: readData,
								  error : traiteErreur
						});
					}
				  }
				
				// Domo
				$( ".relayChange" ).click(function() {
					datas=this.id.split('-');
					trucAdir(3, datas);
					$.ajax({
						url : 'domo.php',
						<?php
						if (isset($cloud)) {
							echo "type : 'POST',"; 
						} else {
							echo "type : 'GET',"; 
						}
						?>
						dataType : 'json',
						data : 'action=changeMod&idRelay='+datas[1]+'&changeTo='+datas[2],
						success : function(resultat, statut){
							<?php
							if (isset($cloud)) {
							?>
								$('.requestWait').hide();
								$('.requestWaitList').hide();
								for (const property in resultat) {
									$('.requestWait').show();
									$('#relay' + property + '-changeTo' + resultat[property] ).show();
									console.log('relay: ' + property + ' changeTo:' + resultat[property]);
								}
							<?php
							}
							?>
							// Rafraichissement de l'état
							refreshDomo();
						},
						error : traiteErreur,
					});
				});
				
				$( "#refresh" ).click(function() {
					if ($("#refreshImg").val() == 0){
						reloadData(1);		
					} else {
						trucAdir(3, 'Un reload à la fois...');
					}
				});
				$( "#autoRefresh" ).click(function() {
					if ($("#autoRefresh").is(":checked")) {
						intervalId = setInterval(refreshNow, refreshTime) ;
						trucAdir(3, 'Relace de l\'interval de raffraichissement automatique');
					} else {
						clearInterval(intervalId);
						trucAdir(3, 'Arrêt du raffraichissement automatique');
					}
				});
				var intervalId = null;
				var refreshTime = <?= $config['www']['refreshTime']*1000 ?>;
				function refreshNow() {
					trucAdir(5, 'Fonction refresh Now go');
					reloadData(0);
				}
				<?php  if ($config['www']['domo'] == true) {  ?>
				var intervalIdDomo = null;
				var refreshTimeDomo = <?= $config['www']['domoRefreshTime']*1000 ?>;
				<?php  } ?>
				$(document).ready(function() {  
						reloadData(0);
						intervalId = setInterval(refreshNow, refreshTime) ;
						<?php  if ($config['www']['domo'] == true) {  ?>
						intervalIdDomo = setInterval(refreshDomo, refreshTimeDomo) ;
						<?php  } ?>
						<?php  if ($config['www']['weatherForcast'] == true) {  ?>
						refreshWeatherForcast();
						<?php  } ?>
						<?php  if ($config['www']['weatherProdForcast'] == true) {  ?>
						refreshWeatherProdForcast();
						<?php  } ?>
						
				}); 
			</script>
			
								
			<!-- TRAP BOX -->
        </div>
			<?php
			if ($config['printMessageLogfile'] != false && isset($_GET['debug'])) {
				echo '<textarea style="width: 100%; height: 400px">';
				echo "Affichage du log ".$config['printMessageLogfile']."\n";
				echo "===================================================\n";
				$tab = file($config['printMessageLogfile']);
				for ($i=count($tab); $i >= count($tab)-400; $i=$i-1) {
					echo $tab[$i];
				}
				echo '</textarea>';
			}
            ?>
        </div>
        <div id="footerwrap">
        <div id="footer">
            <p class="footer_right">Par <a href="http://david.mercereau.info/">David Mercereau</a> (<a href="https://github.com/kepon85/PvMonit/">Dépôt github</a>)</p>
            <p class="footer_left">Copyleft - <a href="https://fr.wikipedia.org/wiki/Beerware">Licence Beerware</a></p>
        </div>
        </div>
        <?php
		} else {
			echo '<form action="#" method="post" class="formPassword">
			<label for="www-password">Mot de passe : </label>
			<input type="password" name="www-password" />
			<input type="submit" />
			</form>';
		}
		?>
    </div>
<script> 
<!-- Afficher plus d'information -->
function PlusPrint(idName) {
	$('#box_'+idName+' .plus').show();
	$('#box_'+idName+' .plusboutton').hide();
	$('#box_'+idName+' .moinsboutton').show();
}
function MoinsPrint(idName) {
	$('#box_'+idName+' .plus').hide();
	$('#box_'+idName+' .plusboutton').show();
	$('#box_'+idName+' .moinsboutton').hide();
}

<?php if ($config['www']['checkUpdate'] != false) { ?>
function checkUpdate() {
	var timeStamp = Math.floor(Date.now() / 1000);
	if (! localStorage.getItem('checkUpdate') || Math.floor(parseInt(localStorage.getItem('checkUpdate'))+<?= $config['www']['checkUpdate'] ?>) < timeStamp) {
		localStorage.setItem('checkUpdate', timeStamp);
		$.ajax({
			url: "https://www.zici.fr/pvmonit_checkupdate.php",
			type: "GET",
			crossDomain: true,
			dataType: "html",
			success: function (response) {
				localStorage.setItem('getVersion', response);
			},
			error: function (xhr, status) {
				trucAdir(3, 'Erreur dans le checkupdate' + status);
			}
		});
	}
}
checkUpdate();
if (localStorage.getItem('getVersion')) {
	if (localStorage.getItem('getVersion').replace(/\n|\r/g,'') != '<?= VERSION ?>') {
		$('#upgrade').html('(<a href="http://pvmonit.zici.fr">Upgrade</a>, v' + localStorage.getItem('getVersion').replace(/\n|\r/g,'') + ' is ready)');
	}
}
<?php } ?>

</script>
</body>
</html>
<?php @include_once('./footer.php');  ?>
