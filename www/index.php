
<?php
###################################
# Script sous licence BEERWARE
# Version 1.0	2019
###################################

include('/opt/PvMonit/function.php');

// Chargement de la config
$config = getConfigYaml('/opt/PvMonit');

@include_once('./header.php');  

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">
<head>
<title>Pv Monit</title>
<!--[if IE]><script src="http://html5shiv.googlecode.comdevice_id/svn/trunk/html5.js"></script><![endif]-->
<link rel="stylesheet" type="text/css" href="./css/style.css" />
<meta http-equiv="content-type" content="text/html;charset=utf-8" />
<meta http-equiv="cache-control" content="max-age=0" />
<meta http-equiv="cache-control" content="no-cache" />
<meta http-equiv="expires" content="0" />
<meta http-equiv="expires" content="Tue, 01 Jan 1980 1:00:00 GMT" />
<meta http-equiv="pragma" content="no-cache" />
<meta name="robots" content="non" /> 
	<script
			  src="jquery.min.js" /></script>
</head>
<body>
    <div id="wrapper">
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
				?>
				
				<li><input type="checkbox" id="autoRefresh" title="Actualisation automatique tout les <?= $config['www']['refreshTime']/1000 ?> secondes" checked='checked' />
				<input type="hidden" name="refreshBusy" id="refreshBusy" /></li>
				<li><a id="refresh"><img id="refreshImg" src="images/refresh.png" width="20" alt="Refresh" title="Actualiser" /></a></li>
			  </ul>
			</nav>
            <h1>Pv Monit v<?= VERSION ?> <span id="upgrade"></span><!-- TRAP TITRE --></h1>
            <p>Monitoring de l'installation solaire électrique</p>
        </div>
        </div>
        <div id="contentwrap">

        <div id="content">
			<div id="waitFirst" class="boxvaleur waitFirst">Patience...<img src="images/wait2.gif" width="100%" /></div>
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
			
			<?php 
			if ($config['www']['domo'] == true) { 
				echo '<div style="display: none" class="box" id="box_domo"><div class="title">Domo</div>';
				if (!is_file($config['domo']['jsonFile']['etatPath'])) {
					genDefaultJsonFile('etat');
				}
				if (!is_file($config['domo']['jsonFile']['modPath'])) {
					genDefaultJsonFile('mod');
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
						type : 'GET',
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
						},
						error : traiteErreur,
					});
				}
				function preparDomo(){
					$("#box_domo").show();
					refreshDomo();
				}
				<?php } ?>
				// /domo
				function readData(xml) {
					$("#refreshImg").val('0');
					$("#waitFirst").hide();
					$("#clearBoth").remove();
					$(".boxMonit").remove();
					trucAdir(5, 'Lecture du XML');
					$("#refreshImg").attr('src', "images/refresh.png");
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
					$('#content').append('<div id="clearBoth" style="clear:both"></div>');
				  }
				var uneDate;
				function reloadData(force) {
					trucAdir(3, 'Reload');
					$("#refreshImg").val('1');
					var dt = new Date();
					var time = dt.getHours() + ":" + dt.getMinutes() + ":" + dt.getSeconds();
					$("#refreshImg").attr('title', 'Actualiser (dernier à '+time+')')
					$("#refreshImg").attr('src', "images/wait.gif");
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
						type : 'GET',
						dataType : 'json',
						data : 'action=changeMod&idRelay='+datas[1]+'&changeTo='+datas[2],
						success : function(resultat, statut){
							console.log(resultat);
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
				}); 
			</script>
			
								
			<!-- TRAP BOX -->
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
