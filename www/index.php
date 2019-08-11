<?php
###################################
# Script sous licence BEERWARE
# Version 1.0	2019
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
				<?php echo $WWW_MENU; ?>
				<li><input type="checkbox" id="autoRefresh" title="Actualisation automatique tout les <?= $WWW_REFRESH_TIME/1000 ?> secondes" checked='checked' />
				<input type="hidden" name="refreshBusy" id="refreshBusy" /></li>
				<li><a id="refresh"><img id="refreshImg" src="images/refresh.png" width="20" alt="Refresh" title="Actualiser" /></a></li>
			  </ul>
			</nav>
            <h1>Pv Monit<!-- TRAP TITRE --></h1>
            <p>Monitoring de l'installation solaire électrique</p>
        </div>
        </div>
        <div id="contentwrap">
        <div id="content">
			<div id="waitFirst" class="boxvaleur waitFirst">Patience<img src="images/wait2.gif" width="100%" /></div>
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
			Ajouter notion de "ping" blanc... pour test la réception de l'XML...
			date /heure derier raffraichissemet
			<div style="display: none" id="nodata" class="boxvaleur">Rien à afficher, copier le fichier config-default.php en config.php et modifier le pour vos paramètres. <br /><span id="textStatus"></span> : <span id="errorThrown"></span></div>
	    
			<script>
		
				function trucAdir(niveau, msg) {
					if (<?= $GLOBALS['PRINTMESSAGE'] ?> >= niveau) {
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
      
				function readData(xml) {
					$("#refreshImg").val('0');
					$("#waitFirst").hide();
					trucAdir(5, 'Lecture du XML');
					$( ".box" ).remove(); // supprime les box pour les ré-écrire avec les nouvelles data
					$("#refreshImg").attr('src', "images/refresh.png");
					$(xml).find('device').each(function() {
						var id = $(this).attr('id');
						trucAdir(5, 'Récupération de l\'id ' + id);
						var nom = $(this).find('nom').text();
						var modele = $(this).find('modele').text();
						$('#content').append('<div class="box" id="box_' + id + '"></div>');
						$('#box_' + id + '').prepend('<div class="title">[' + nom + '] ' + modele + '</div>');
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
										var VbatEnPourcentage=Math.round(value*100/<?= $WWW_VBAT_MAX ?>);
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
										var PpvPourcentage=Math.round(value*100/<?= $WWW_PPV_MAX ?>);
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
										var PpvtPourcentage=Math.round(value*100/<?= $WWW_PPVT_MAX ?>);
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
										var ConsoPourcentage=Math.round(value*100/<?= $WWW_CONSO_MAX ?>);
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
							$('#box_' + id + '').append('<div class="boxvaleur plusboutton" onclick="PlusPrint(\''+id+'\')">...</div>'+
														'<div class="boxvaleur moinsboutton" onclick="MoinsPrint(\''+id+'\')">...</div>');
					});
					$('#content').append('<div style="clear:both"></div>');
				  }

				function reloadData() {
					trucAdir(3, 'Reload');
					$("#refreshImg").val('1');
					$("#refreshImg").attr('src', "images/wait.gif");
					$.ajax( {
						type: "GET",
						url: "<?= $URL_DATA_XML ?>",
						dataType: "xml",
						success: readData,
						  error : traiteErreur
					});
				  }

				$( "#refresh" ).click(function() {
					if ($("#refreshImg").val() == 0){
						reloadData();		
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
				var refreshTime = <?= $WWW_REFRESH_TIME ?>;
				function refreshNow() {
					trucAdir(5, 'Fonctio refresh Now go');
					reloadData();
				}
				$(document).ready(function() {  
						reloadData();
						intervalId = setInterval(refreshNow, refreshTime) ;
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
</script>
</body>
</html>
