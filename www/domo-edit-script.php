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

if ($config['www']['domoEdit'] == false || $config['www']['domo'] == false) {
    exit('domoEdit ou domo sont à false dans le fichier de configuration, vous n\'avez donc pas accès à cette page');
}

@include_once('./header.php');  

if (isset($_POST['domoEdit-password'])) {
	setcookie('domoEdit-password', md5($_POST['domoEdit-password']), time()+$config['www']['passwordLife']);
	header('Location: '.$_SERVER['REQUEST_URI']);
}
$printDomoEdit=false;
if ($config['www']['domoEditPassword'] == false) {
	$printDomoEdit=true;
} else {
	if (isset($_COOKIE['domoEdit-password']) && $_COOKIE['domoEdit-password'] == $config['www']['domoEditPassword']) {
		$printDomoEdit=true;
	} 
}

$idScript=false;
if (isset($_GET['idScript'])) {
	$idScript=$_GET['idScript'];
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
<?php if ($idScript != false && $_GET['edit'] == 'blockly') { ?>
<link rel="stylesheet" href="./assets/blockly-child/style.css">
<script src="./assets/blockly/blockly_compressed.js"></script>
<script src="./assets/blockly/blocks_compressed.js"></script>
<script src="./assets/blockly/javascript_compressed.js"></script>
<script src="./assets/blockly/php_compressed.js"></script>
<script>

<?php    
// Enregistrement des scripts posté
if (isset($_POST['script_php']) && isset($_POST['script_xml'])) {
    // Enregistrement du XML
    if (file_put_contents($config['domo']['relay']['scriptDir'].'/'.$idScript.'.blockly.xml', $_POST['script_xml'])) {
        // Enregistrement du PHP
        $script_php_content=$_POST['script_php'];
        $script_php_content = str_replace("\$retour_mod", "\$retour['mod']", $script_php_content);
        $script_php_content = str_replace("\$retour_log", "\$retour['log']", $script_php_content);
        $script_php_content = preg_replace("/^print\(/m", "#print(", $script_php_content);
        $script_php_content = preg_replace("/^[\s]+print\(/m", "#print(", $script_php_content);
        
        $script_php_content="<?php\n".$script_php_content."\nreturn \$retour;\n?>";
        if (file_put_contents($config['domo']['relay']['scriptDir'].'/'.$idScript.'.php', $script_php_content)) {
            // Check syntax
            $error="aucune";
            if (is_file('/usr/bin/php')) {
                exec('/usr/bin/php -l '.$config['domo']['relay']['scriptDir'].'/'.escapeshellcmd($idScript).'.php', $output, $return_var);
                if ($return_var != 0) {
                    $error='Erreur, le script PHP généré comporte des erreurs de syntax';
                }
            }
            if ($error == "aucune"){
                // Génère le MD5
                file_put_contents($config['domo']['relay']['scriptDir'].'/'.$idScript.'.blockly.md5', md5_file($config['domo']['relay']['scriptDir'].'/'.$idScript.'.php'));
                echo 'alert("Enregistrement réussi le '.date("c").'");';
            } else {
                echo 'alert("'.$error.'");';
            }
        } else {
            echo 'alert("Erreur, impossible d\'enregistrer le fichier script PHP '.$config['domo']['relay']['scriptDir'].'/'.$idScript.'.php");';
        }
    } else {
        echo 'alert("Erreur, impossible d\'enregistrer le fichier XML blockly '.$config['domo']['relay']['scriptDir'].'/'.$idScript.'.blockly.xml");';
    }
}
?>
    
var Code = {};

<?php if ($config['printMessage'] == 5) { ?>
Code.TABS_ = ['blocks', 'javascript', 'php', 'xml'];
Code.TABS_DISPLAY_ = [
  'Blocks', 'JavaScript', 'PHP', 'XML',
];
<?php } else { ?>
Code.TABS_ = ['blocks', 'xml'];
Code.TABS_DISPLAY_ = [
  'Blocks', 'XML',
];
<?php } ?>

/**
 * Initialize Blockly.  Called on page load.
 */
Code.init = function() {
  Code.initLanguage();

  var rtl = Code.isRtl();
  var container = document.getElementById('content_area');
  var onresize = function(e) {
    var bBox = Code.getBBox_(container);
    for (var i = 0; i < Code.TABS_.length; i++) {
      var el = document.getElementById('content_' + Code.TABS_[i]);
      el.style.top = bBox.y + 'px';
      el.style.left = bBox.x + 'px';
      // Height and width need to be set, read back, then set again to
      // compensate for scrollbars.
      el.style.height = bBox.height + 'px';
      el.style.height = (2 * bBox.height - el.offsetHeight) + 'px';
      el.style.width = bBox.width + 'px';
      el.style.width = (2 * bBox.width - el.offsetWidth) + 'px';
    }
    // Make the 'Blocks' tab line up with the toolbox.
    if (Code.workspace && Code.workspace.getToolbox().width) {
      document.getElementById('tab_blocks').style.minWidth =
          (Code.workspace.getToolbox().width - 38) + 'px';
          // Account for the 19 pixel margin and on each side.
    }
  };
  window.addEventListener('resize', onresize, false);

  for (var messageKey in MSG) {
    if (messageKey.indexOf('cat') == 0) {
      Blockly.Msg[messageKey.toUpperCase()] = MSG[messageKey];
    }
  }

  // Construct the toolbox XML, replacing translated variable names.
  var toolboxText = document.getElementById('toolbox').outerHTML;
  toolboxText = toolboxText.replace(/(^|[^%]){(\w+)}/g,
      function(m, p1, p2) {return p1 + MSG[p2];});
  var toolboxXml = Blockly.Xml.textToDom(toolboxText);

  Code.workspace = Blockly.inject('content_blocks',
      {grid:
          {spacing: 25,
           length: 3,
           colour: '#ccc',
           snap: true},
       media: './assets/blockly/media/',
       rtl: rtl,
       toolbox: toolboxXml,
       zoom:
           {controls: true,
            wheel: true}
      });

  // Add to reserved word list: Local variables in execution environment (runJS)
  // and the infinite loop detection function.
  Blockly.JavaScript.addReservedWords('code,timeouts,checkTimeout');

	<?php
	if (isset($_GET['example']) && $_GET['example'] != '') {
		if (is_file($config['www']['domoEditExampleDir'].'/'.$_GET['example'].'.xml')) {
			echo 'Code.loadBlocks(\''.addslashes(preg_replace("#\n|\t|\r#", "", file_get_contents($config['www']['domoEditExampleDir'].'/'.$_GET['example'].'.xml'))).'\');';
		} else {
			echo 'alert("Impossible de trouver l\'exemple '.$_GET['example'].'");';
		}
	} else if ($idScript) {
		if (is_file($config['domo']['relay']['scriptDir'].'/'.$idScript.'.blockly.xml')) {
			echo 'Code.loadBlocks(\''.addslashes(preg_replace("#\n|\t|\r#", "", file_get_contents($config['domo']['relay']['scriptDir'].'/'.$idScript.'.blockly.xml'))).'\');';
		} else {
			echo 'Code.loadBlocks(\''.addslashes(preg_replace("#\n|\t|\r#", "", file_get_contents($config['www']['domoEditExampleDir'].'/default.xml'))).'\');';
		}
	}
	?>
  

  if ('BlocklyStorage' in window) {
    // Hook a save function onto unload.
    BlocklyStorage.backupOnUnload(Code.workspace);
  }

  Code.tabClick(Code.selected);

  Code.bindClick('trashButton',
      function() {Code.discard(); Code.renderContent();});
  Code.bindClick('runButton', Code.runJS);
  // Disable the link button if page isn't backed by App Engine storage.
  var linkButton = document.getElementById('linkButton');
  if ('BlocklyStorage' in window) {
    BlocklyStorage['HTTPREQUEST_ERROR'] = MSG['httpRequestError'];
    BlocklyStorage['LINK_ALERT'] = MSG['linkAlert'];
    BlocklyStorage['HASH_ERROR'] = MSG['hashError'];
    BlocklyStorage['XML_ERROR'] = MSG['xmlError'];
    Code.bindClick(linkButton,
        function() {BlocklyStorage.link(Code.workspace);});
  } else if (linkButton) {
    linkButton.className = 'disabled';
  }

  for (var i = 0; i < Code.TABS_.length; i++) {
    var name = Code.TABS_[i];
    Code.bindClick('tab_' + name,
        function(name_) {return function() {Code.tabClick(name_);};}(name));
  }
  Code.bindClick('tab_code', function(e) {
    if (e.target !== document.getElementById('tab_code')) {
      // Prevent clicks on child codeMenu from triggering a tab click.
      return;
    }
    Code.changeCodingLanguage();
  });

  onresize();
  Blockly.svgResize(Code.workspace);

  // Lazy-load the syntax-highlighting.
  window.setTimeout(Code.importPrettify, 1);
};
</script>
<script src="./assets/blockly-child/codePvMonit.js"></script>

<?php } ?>
</head>
<body>
    <div id="wrapper">
		<?php 
		if ($printDomoEdit == true) {
		?>
        <div id="headerwrap">
        <div id="header">
            <nav>
			  <ul>
                <?php if (isset($_GET['idScript'])) { ?>
				<li><a id="saveScript">Enregistrer</a></li>
                <?php } ?> 
				<li><a href="domo-edit-script.php">Retour aux scripts</a></li>
				<li><a href="/">PvMonit</a></li>
			  </ul>
			</nav>
				<h1>Pv Monit v<?= VERSION ?> <span id="upgrade"></span><!-- TRAP TITRE --></h1>
				<p>Monitoring de l'installation solaire électrique</p>
        </div>
        </div>
        <div id="contentwrap" style="width: 100%">
        <?php if ($_GET['edit'] == 'blockly') {  ?>
        <div id="content" style="height: 600px">
		<?php } else { ?>
        <div id="content" >
		<?php } ?>
			
		<?php 
		if ($idScript == false) {
			// Exemple à lister
			$bloklyExample = scandir($config['www']['domoEditExampleDir']);
			foreach ($bloklyExample as $bloklyExampleMeta) {
				if (preg_match_all('/\.meta\.json$/m', $bloklyExampleMeta)) {
					// Analyse des méta pour savoir si les valeurs requises sont ok (dans config domo valueUse)
					$exampleEnable=true;
					$bloklyExampleMetaData= json_decode(file_get_contents($config['www']['domoEditExampleDir'].'/'.$bloklyExampleMeta), true);
					foreach ($bloklyExampleMetaData['data'] as $dateRequir) {
						if (!array_key_exists($dateRequir, $config['domo']['valueUse'])) {
							$exampleEnable=false;
						} 
					}
					$bloklyExampleExplode=explode('.', $bloklyExampleMeta);
					$exampleScript[$bloklyExampleExplode[0]]['title'] = $bloklyExampleMetaData['title'];
					$exampleScript[$bloklyExampleExplode[0]]['desc'] = $bloklyExampleMetaData['desc'];
					$exampleScript[$bloklyExampleExplode[0]]['enable'] = $exampleEnable;
				}
			}
            echo '<div id="scriptEditList">';
			echo '<h1>Choisissez le script à éditer :</h1>';
			
			foreach ($exampleScript as $exampleName=>$exampleMeta) {
				echo '<div style="display: none" class="exampleScriptsDesc" id="'.$exampleName.'">Le script exemple ayant pour titre <i>'.$exampleMeta['title'].'</i> est décris comme tel : ';
				echo '<p>'.$exampleMeta['desc'].'</p></div>';
			}
			
			for ($i = 1; $i <= $config['domo']['relayNb']; $i++) {
				$etat='';
				$erase=null;
				$new=false;
				echo '<div><h2>#'.$i.' : '.$config['domo']['relayName'][$i].'</h2>';
				echo '<form method="get" action="#" title="'.$i.'"><ul>';
				echo '<input type="hidden" name="idScript" value="'.$i.'" />';
				echo '<input type="hidden" name="edit" value="blockly" />';
				// Pas de script php, on propose une édition
				if (!is_file($config['domo']['relay']['scriptDir'].'/'.$i.'.php')) {
					$etat='Aucun script actif, vous pouvez chargez un exemple';
					$erase=false;
					$new=true;
				} else if (is_file($config['domo']['relay']['scriptDir'].'/'.$i.'.blockly.xml')) {
					// Si le fichier PHP est différent de celui généré par le XML
					if (md5_file($config['domo']['relay']['scriptDir'].'/'.$i.'.php') 
					!= file_get_contents($config['domo']['relay']['scriptDir'].'/'.$i.'.blockly.md5')) {
						$etat='Il y a eu des changements réalisé directement dans le scripts';
						$erase=true;
					// Le fichier php correspond à l'XML généré
					} else {
						$etat='Script généré avec blockly';
						$erase=false;
					}
				} else {
					$etat ='Un script est actif';
					$erase=true;
				}
				echo '<li>'.$etat.'</li>';
				if ($new == true && $erase == false) {
					echo '<li><input class="submit" type="button" name="edit" value="Commencer avec blockly" /></li>';
                } elseif ($new == false && $erase == true) {
                    echo '<li><input class="submit" type="button" name="edit" value="Ecraser reprendre avec blockly" /></li>';
				} elseif ($erase) {
					echo '<li><input class="submit" type="button" name="edit" value="Ecraser et créer avec blockly" /></li>';
				} else {
					echo '<li><input class="submit" type="button" name="edit" value="Ouvrir avec blockly" /></li>';
				}
				echo '<li><select name="example" class="exampleScripts" id="exampleScript'.$i.'">
					<option value=""> - </option>';
					foreach ($exampleScript as $exampleName=>$exampleMeta) {
						if ($exampleMeta['enable']) {
							echo '<option value="'.$exampleName.'">'.$exampleMeta['title'].'</option>';
						} else {
							echo '<option value="'.$exampleName.'" disabled>'.$exampleMeta['title'].'</option>';
						}
					}
				echo '</select>';
				if ($new == true && $erase == false) {
					echo '<input class="submit" type="button" name="example" value="Commencer avec un exemple" /></li>';
				} else {
					echo '<input class="submit" type="button" name="example" value="Ecraser avec cet exemple" /></li>';
				} 
				echo '</ul>';
				echo '</form>';
				echo '</div>';
			}            
            echo '</div>';
		} elseif ($_GET['edit'] == 'blockly') {             
            
            // Check data for blockly_data
            foreach ($config['domo']['valueUse'] as $value=>$regex)  {
                $bloklyData[$value]['type']='input';
                $bloklyData[$value]['value']=0;
                $bloklyData[$value]['output']='null';
                $bloklyData[$value]['tooltip']='"Add domo/valueSimu in your config.yaml - check config-default.yaml"';
                if (isset($config['domo']['valueSimu'][$value])) {
                    foreach ($config['domo']['valueSimu'][$value] as $valueSimu) {
                        $valueSimuSplit=explode(':', $valueSimu);
                        $bloklyData[$value][$valueSimuSplit[0]]=$valueSimuSplit[1];
                    }
                } 
            }
            
		?>
<!--
Pour l'enregistrement des données
-->
<form id="script_save" action="<?= $_SERVER['SCRIPT_NAME'] ?>?idScript=<?= $idScript ?>&edit=blockly" method="post">
    <textarea id="script_xml" name="script_xml"></textarea>
    <textarea id="script_php" name="script_php"></textarea>
</form>
<!--
########### Blockly
-->
  <table width="100%" height="100%">
    <tr>
      <td style="text-align: center">
        Edition du script pour le relai #<?= $idScript ?>
      </td>
      <td class="farSide">
        <select id="languageMenu"></select>
      </td>
    </tr>
    <tr>
      <td colspan=2>
        <table width="100%">
          <tr id="tabRow" height="1em">
            <td id="tab_blocks" class="tabon">...</td>
            <td class="tabmin tab_collapse">&nbsp;</td>
            <?php if ($config['printMessage'] == 5) { ?>
            <td id="tab_javascript" class="taboff tab_collapse">JavaScript</td>
            <td class="tabmin tab_collapse">&nbsp;</td>
            <td id="tab_php" class="taboff tab_collapse">PHP</td>
            <td class="tabmin tab_collapse">&nbsp;</td>
            <?php } ?>
            <td id="tab_xml" class="taboff tab_collapse">XML</td>
            <td class="tabmin">&nbsp;</td>
            <td id="tab_code" class="taboff">
              <select id="code_menu"></select>
            </td>
            <td class="tabmax">
              <button id="trashButton" class="notext" title="...">
                <img src='./assets/blockly/media/1x1.gif' class="trash icon21">
              </button>
              <button id="linkButton" class="notext" title="...">
                <img src='./assets/blockly/media/1x1.gif' class="link icon21">
              </button>
              <button id="runButton" class="notext primary" title="...">
                <img src='./assets/blockly/media/1x1.gif' class="run icon21">
              </button>
            </td>
          </tr>
        </table>
      </td>
    </tr>
    <tr>
      <td height="99%" colspan=2 id="content_area">
      </td>
    </tr>
  </table>
  <div id="content_blocks" class="content"></div>
  <pre id="content_javascript" class="content prettyprint lang-js"></pre>
  <pre id="content_php" class="content prettyprint lang-php"></pre>
  <textarea id="content_xml" class="content" wrap="off"></textarea>

	<?php 
	// Include toolbar xml
	include('./assets/blockly-child/toolbox.php'); 
	?>

<script type="text/javascript">

/*
#####################################
# Déclaration des blocs personnalisés
#####################################
*/

//~ Block thisIS

Blockly.Blocks['thisid'] = {
  init: function() {
    this.appendDummyInput()
        .appendField("thisId");
    this.setOutput(true, "Number");
    this.setColour(60);
 this.setTooltip("Renvoi l'ID/le numéro du relai");
 this.setHelpUrl("https://framagit.org/kepon/PvMonit/-/blob/master/domo/relay.script.d/ID.php.exemple");
  }
};
Blockly.JavaScript['thisid'] = function(block) {
  var code = <?= $idScript ?>;
  return [code, Blockly.JavaScript.ORDER_NONE];
};
Blockly.PHP['thisid'] = function(block) {
  var code = '$thisId';
  return [code, Blockly.PHP.ORDER_NONE];
};

//~ Block thisEtat

Blockly.Blocks['thisetat'] = {
  init: function() {
    this.appendDummyInput()
        .appendField("thisEtat");
    this.appendDummyInput()
        .appendField("Pour la simulation")
        .appendField(new Blockly.FieldDropdown([["Ce relai est éteint","0"], ["Ce relai est allumé","1"]]), "simu_return");
    this.setOutput(true, "Number");
    this.setColour(60);
 this.setTooltip("Renvoi l'état de ce relai");
 this.setHelpUrl("https://framagit.org/kepon/PvMonit/-/blob/master/domo/relay.script.d/ID.php.exemple");
  }
};
Blockly.JavaScript['thisetat'] = function(block) {
  var dropdown_simu_return = block.getFieldValue('simu_return');
  var code = dropdown_simu_return;
  return [code, Blockly.JavaScript.ORDER_NONE];
};
Blockly.PHP['thisetat'] = function(block) {
  var code = '$thisEtat';
  return [code, Blockly.PHP.ORDER_NONE];
};

//~ Block thisMod

Blockly.Blocks['thismod'] = {
  init: function() {
    this.appendDummyInput()
        .appendField("thisMod");
    this.appendDummyInput()
        .appendField("Pour la simulation")
        .appendField(new Blockly.FieldDropdown([["Off forcé (action de l'utilisateur)","0"], ["Off automatique (par défaut)","1"], ["On automatique (allumé par les scripts)","2"], ["On forcé (action de l'utilisateur)","3"]]), "simu_return");
    this.setOutput(true, "Number");
    this.setColour(60);
 this.setTooltip("Renvoi le mod de ce relai");
 this.setHelpUrl("https://framagit.org/kepon/PvMonit/-/blob/master/domo/relay.script.d/ID.php.exemple");
  }
};
Blockly.JavaScript['thismod'] = function(block) {
  var dropdown_simu_return = block.getFieldValue('simu_return');
  var code = dropdown_simu_return;
  return [code, Blockly.JavaScript.ORDER_NONE];
};
Blockly.PHP['thismod'] = function(block) {
  var code = '$thisMod';
  return [code, Blockly.PHP.ORDER_NONE];
};

//~ Block relayMod

Blockly.Blocks['relaymod'] = {
  init: function() {
    this.appendValueInput("id")
        .setCheck("Number")
        .appendField("relayMod")
        .appendField("Id ");
    this.appendDummyInput()
        .appendField("Pour la simulation")
        .appendField(new Blockly.FieldDropdown([["Off forcé (action de l'utilisateur)","0"], ["Off automatique (par défaut)","1"], ["On automatique (allumé par les scripts)","2"], ["On forcé (action de l'utilisateur)","3"]]), "simu_return");
    this.setInputsInline(false);
    this.setOutput(true, "Number");
    this.setColour(60);
 this.setTooltip("Renvoi le mod du relai demandé");
 this.setHelpUrl("");
  }
};
Blockly.PHP['relaymod'] = function(block) {
  var value_id = Blockly.PHP.valueToCode(block, 'id', Blockly.PHP.ORDER_ATOMIC);
  var code = '$relayMod['+value_id+']';
  return [code, Blockly.PHP.ORDER_NONE];
};
Blockly.JavaScript['relaymod'] = function(block) {
  var dropdown_simu_return = block.getFieldValue('simu_return');
  var code = dropdown_simu_return;
  return [code, Blockly.JavaScript.ORDER_NONE];
};

//~ Block relayEtat

Blockly.Blocks['relayetat'] = {
  init: function() {
    this.appendValueInput("id")
        .setCheck("Number")
        .appendField("relayEtat")
        .appendField("id : ");
    this.appendDummyInput()
        .appendField("Pour la simulation")
        .appendField(new Blockly.FieldDropdown([["Ce relai est éteint","0"], ["Ce relai est allumé","1"]]), "simu_return");
    this.setInputsInline(false);
    this.setOutput(true, "Number");
    this.setColour(60);
 this.setTooltip("Renvoi l'état du relai demandé (0 ou 1)");
 this.setHelpUrl("");
  }
};
Blockly.PHP['relayetat'] = function(block) {
  var value_id = Blockly.PHP.valueToCode(block, 'id', Blockly.PHP.ORDER_ATOMIC);
  var code = '$relayEtat['+value_id+']';
  return [code, Blockly.PHP.ORDER_NONE];
};
Blockly.JavaScript['relayetat'] = function(block) {
  var dropdown_simu_return = block.getFieldValue('simu_return');
  var code = dropdown_simu_return;
  return [code, Blockly.JavaScript.ORDER_NONE];
};


//~ Block relayLastUp

Blockly.Blocks['relaylastup'] = {
  init: function() {
    this.appendValueInput("id")
        .setCheck("Number")
        .appendField("relayLastUp")
        .appendField("id:");
    this.appendDummyInput()
        .appendField("Pour la simulation ")
        .appendField(new Blockly.FieldNumber(<?= time() ?>, 0, 9999999999, 1), "simu_return");
    this.setInputsInline(false);
    this.setOutput(true, "Number");
    this.setColour(60);
 this.setTooltip("retourne la date (en seconde/timestamp) du dernier allumage de l'ID passé en paramètre");
 this.setHelpUrl("https://www.epochconverter.com/");
  }
};
Blockly.PHP['relaylastup'] = function(block) {
  var value_id = Blockly.PHP.valueToCode(block, 'id', Blockly.PHP.ORDER_ATOMIC);
  var code = 'relayLastUp('+value_id+')';
  return [code, Blockly.PHP.ORDER_NONE];
};
Blockly.JavaScript['relaylastup'] = function(block) {
  var number_simu_return = block.getFieldValue('simu_return');
  var code = number_simu_return;
  return [code, Blockly.JavaScript.ORDER_NONE];
};


//~ Block relayLastUpAuto

Blockly.Blocks['relaylastupauto'] = {
  init: function() {
    this.appendValueInput("id")
        .setCheck("Number")
        .appendField("relayLastUpAuto")
        .appendField("id:");
    this.appendDummyInput()
        .appendField("Pour la simulation ")
        .appendField(new Blockly.FieldNumber(<?= time() ?>, 0, 9999999999, 1), "simu_return");
    this.setInputsInline(false);
    this.setOutput(true, "Number");
    this.setColour(60);
 this.setTooltip("retourne la date (en seconde/timestamp) du dernier allumage automatique de l'ID passé en paramètre");
 this.setHelpUrl("https://www.epochconverter.com/");
  }
};
Blockly.PHP['relaylastupauto'] = function(block) {
  var value_id = Blockly.PHP.valueToCode(block, 'id', Blockly.PHP.ORDER_ATOMIC);
  var code = 'relayLastUpAuto('+value_id+')';
  return [code, Blockly.PHP.ORDER_NONE];
};

Blockly.JavaScript['relaylastupauto'] = function(block) {
  var number_simu_return = block.getFieldValue('simu_return');
  var code = number_simu_return;
  return [code, Blockly.JavaScript.ORDER_NONE];
};

//~ Block relayLastDown

Blockly.Blocks['relaylastdown'] = {
  init: function() {
    this.appendValueInput("id")
        .setCheck("Number")
        .appendField("relayLastDown")
        .appendField("id:");
    this.appendDummyInput()
        .appendField("Pour la simulation ")
        .appendField(new Blockly.FieldNumber(<?= time() ?>, 0, 9999999999, 1), "simu_return");
    this.setInputsInline(false);
    this.setOutput(true, "Number");
    this.setColour(60);
 this.setTooltip("retourne la date de la dernière extinction de l'ID passé en paramètre");
 this.setHelpUrl("https://www.epochconverter.com/");
  }
};
Blockly.PHP['relaylastdown'] = function(block) {
  var value_id = Blockly.PHP.valueToCode(block, 'id', Blockly.PHP.ORDER_ATOMIC);
  var code = 'relayLastDown('+value_id+')';
  return [code, Blockly.PHP.ORDER_NONE];
};
Blockly.JavaScript['relaylastdown'] = function(block) {
  var number_simu_return = block.getFieldValue('simu_return');
  var code = number_simu_return;
  return [code, Blockly.JavaScript.ORDER_NONE];
};

//~ Block relayUpToday

Blockly.Blocks['relayuptoday'] = {
  init: function() {
    this.appendValueInput("id")
        .setCheck("Number")
        .appendField("relayUpToday")
        .appendField("id:");
    this.appendDummyInput()
        .appendField("Pour la simulation ")
        .appendField(new Blockly.FieldDropdown([["Oui ce relai a déjà été allumé ce jour","true"], ["Non ce relai n'a pas déjà été allumé ce jour","false"]]), "simu_return");
    this.setInputsInline(false);
    this.setOutput(true, "Boolean");
    this.setColour(60);
 this.setTooltip("retourne true si le relai à déjà été allumé ce jour");
 this.setHelpUrl("https://framagit.org/kepon/PvMonit/-/blob/master/domo/relay.script.d/ID.php.exemple#L32");
  }
};
Blockly.PHP['relayuptoday'] = function(block) {
  var value_id = Blockly.PHP.valueToCode(block, 'id', Blockly.PHP.ORDER_ATOMIC);
  var code = 'relayUpToday('+value_id+')';
  return [code, Blockly.PHP.ORDER_NONE];
};
Blockly.JavaScript['relayuptoday'] = function(block) {
  var dropdown_simu_return = block.getFieldValue('simu_return');
  var code = dropdown_simu_return;
  return [code, Blockly.JavaScript.ORDER_NONE];
};


//~ Block relayUpDownToday

Blockly.Blocks['relayupdowntoday'] = {
  init: function() {
    this.appendValueInput("id")
        .setCheck("Number")
        .appendField("relayUpDownToday")
        .appendField("id:");
    this.appendDummyInput()
        .appendField("Pour la simulation ")
        .appendField(new Blockly.FieldDropdown([["Oui il a été allumé puis éteint ce jour","true"], ["Non il n'a pas été allumé puis éteint ce jour","false"]]), "simu_return");
    this.setInputsInline(false);
    this.setOutput(true, "Boolean");
    this.setColour(60);
 this.setTooltip(" retourne true si le relai à déjà été allumé puis éteind ce jour");
 this.setHelpUrl("https://framagit.org/kepon/PvMonit/-/blob/master/domo/relay.script.d/ID.php.exemple#L32");
  }
};
Blockly.PHP['relayupdowntoday'] = function(block) {
  var value_id = Blockly.PHP.valueToCode(block, 'id', Blockly.PHP.ORDER_ATOMIC);
  var code = 'relayUpDownToday('+value_id+')';
  return [code, Blockly.PHP.ORDER_NONE];
};
Blockly.JavaScript['relayupdowntoday'] = function(block) {
  var dropdown_simu_return = block.getFieldValue('simu_return');
  var code = dropdown_simu_return;
  return [code, Blockly.JavaScript.ORDER_NONE];
};

//~ Block timeUpMin

Blockly.Blocks['timeupmin'] = {
  init: function() {
    this.appendValueInput("id")
        .setCheck("Number")
        .appendField("timeUpMin")
        .appendField(new Blockly.FieldNumber(200, 0, 86400, 1), "delai")
        .appendField("s")
        .appendField(", id:");
    this.appendDummyInput()
        .appendField("Pour la simulation ")
        .appendField(new Blockly.FieldDropdown([["Le temps d'allumage minimum n'est pas dépassé","true"], ["Le temps d'allumage minimum est pas dépassé","false"]]), "simu_return");
    this.setInputsInline(false);
    this.setOutput(true, "Boolean");
    this.setColour(60);
 this.setTooltip("retourne true si le temps minimum (en seconde) n'est pas encore écoulé depuis l'allumage");
 this.setHelpUrl("https://framagit.org/kepon/PvMonit/-/blob/master/domo/relay.script.d/ID.php.exemple#L32");
  }
};
Blockly.PHP['timeupmin'] = function(block) {
  var number_delai = block.getFieldValue('delai');
  var value_id = Blockly.PHP.valueToCode(block, 'id', Blockly.PHP.ORDER_ATOMIC);
  var code = 'timeUpMin('+value_id+', '+number_delai+')';
  return [code, Blockly.PHP.ORDER_NONE];
};
Blockly.JavaScript['timeupmin'] = function(block) {
  var dropdown_simu_return = block.getFieldValue('simu_return');
  var code = dropdown_simu_return;
  return [code, Blockly.JavaScript.ORDER_NONE];
};

//~ Block timeUpMax
Blockly.Blocks['timeupmax'] = {
  init: function() {
    this.appendValueInput("id")
        .setCheck("Number")
        .appendField("timeUpMax")
        .appendField(new Blockly.FieldNumber(200, 0, 86400, 1), "delai")
        .appendField("s")
        .appendField(", id:");
    this.appendDummyInput()
        .appendField("Pour la simulation ")
        .appendField(new Blockly.FieldDropdown([["Le temps d'allumage maximum est pas dépassé","true"], ["Le temps d'allumage maximum n'est pas dépassé","false"]]), "simu_return");
    this.setInputsInline(false);
    this.setOutput(true, "Boolean");
    this.setColour(60);
 this.setTooltip("retourne true si le temps maximum (en seconde) n'est pas encore écoulé depuis l'allumage");
 this.setHelpUrl("https://framagit.org/kepon/PvMonit/-/blob/master/domo/relay.script.d/ID.php.exemple#L32");
  }
};
Blockly.PHP['timeupmax'] = function(block) {
  var number_delai = block.getFieldValue('delai');
  var value_id = Blockly.PHP.valueToCode(block, 'id', Blockly.PHP.ORDER_ATOMIC);
var code = 'timeUpMax('+value_id+', '+number_delai+')';
  return [code, Blockly.PHP.ORDER_NONE];
};
Blockly.JavaScript['timeupmax'] = function(block) {
  var dropdown_simu_return = block.getFieldValue('simu_return');
  var code = dropdown_simu_return;
  return [code, Blockly.JavaScript.ORDER_NONE];
};

<?php if (isset($config['domo']['valueUse']['CS'])) { ?>

//~ Block MpptFlo
Blockly.Blocks['mpptflo'] = {
  init: function() {
    this.appendDummyInput()
        .appendField("MpptFlo")
        .appendField("data CS");
    this.appendDummyInput()
        .appendField("Pour la simulation ")
        .appendField(new Blockly.FieldDropdown([["Le régulateur est en Float","true"], ["Le régulateur n'est pas en Float","false"]]), "simu_return");
    this.setInputsInline(false);
    this.setOutput(true, "Boolean");
    this.setColour(60);
 this.setTooltip("retourne true si le régulateur est en Float");
 this.setHelpUrl("https://framagit.org/kepon/PvMonit/-/blob/master/domo/relay.script.d/ID.php.exemple#L32");
  }
};
Blockly.PHP['mpptflo'] = function(block) {
  var code = 'MpptFlo($data[\'CS\'])';
  return [code, Blockly.PHP.ORDER_NONE];
};
Blockly.JavaScript['mpptflo'] = function(block) {
  var dropdown_simu_return = block.getFieldValue('simu_return');
  var code = dropdown_simu_return;
  return [code, Blockly.JavaScript.ORDER_NONE];
};
//~ Block MpptAbsOrFlo
Blockly.Blocks['mpptabsorflo'] = {
  init: function() {
    this.appendDummyInput()
        .appendField("MpptAbsOrFlo")
        .appendField("data CS");
    this.appendDummyInput()
        .appendField("Pour la simulation ")
        .appendField(new Blockly.FieldDropdown([["Le régulateur est en Absorption ou Float","true"], ["Le régulateur n'est pas en Absorption ou Float","false"]]), "simu_return");
    this.setInputsInline(false);
    this.setOutput(true, "Boolean");
    this.setColour(60);
 this.setTooltip("retourne true si le régulateur est en Float ou en Absorption");
 this.setHelpUrl("https://framagit.org/kepon/PvMonit/-/blob/master/domo/relay.script.d/ID.php.exemple#L32");
  }
};
Blockly.PHP['mpptabsorflo'] = function(block) {
  var code = 'MpptAbsOrFlo($data[\'CS\'])';
  return [code, Blockly.PHP.ORDER_NONE];
};
Blockly.JavaScript['mpptabsorflo'] = function(block) {
  var dropdown_simu_return = block.getFieldValue('simu_return');
  var code = dropdown_simu_return;
  return [code, Blockly.JavaScript.ORDER_NONE];
};

<?php } ?>

Blockly.Blocks['date_now'] = {
  init: function() {
    this.appendDummyInput()
        .setAlign(Blockly.ALIGN_RIGHT)
        .appendField("Date actuelle")
        .appendField(new Blockly.FieldDropdown([["Jour du mois (1 à 31)","j"], ["Jour de la semaine (1 (pour Lundi) à 7 (pour Dimanche)","N"], ["Jour de l'année (Jour de l'année 0 à 365)","z"], ["Mois (1 à 12)","n"], ["Année sur 4 chiffres (1999 ou 2003...)","Y"]]), "format");
    this.setOutput(true, "Number");
    this.setColour(0);
 this.setTooltip("La date du moment sous différent format");
 this.setHelpUrl("https://www.php.net/manual/fr/function.date.php");
  }
};
Blockly.JavaScript['date_now'] = function(block) {
  var dropdown_format = block.getFieldValue('format');
  var m = new Date();
  if (dropdown_format == 'j') {
    var code = m.getDate();
  } else if (dropdown_format == 'N') {
    var code = m.getDay();
  } else if (dropdown_format == 'z') {
    var now = m;
    var start = new Date(now.getFullYear(), 0, 0);
    var diff = now - start;
    var oneDay = 1000 * 60 * 60 * 24;
    var code = Math.floor(diff / oneDay);
  } else if (dropdown_format == 'n') {
    var code = m.getMonth()+1;
  } else if (dropdown_format == 'Y') {
    var code = m.getFullYear();
  } else {
      alert("Bug in date_now, " + dropdown_format + " not define");
  }
  return [code, Blockly.JavaScript.ORDER_NONE];
};
Blockly.PHP['date_now'] = function(block) {
    var dropdown_format = block.getFieldValue('format');
    var code = 'date("%'+dropdown_format+'")';
    return [code, Blockly.JavaScript.ORDER_NONE];
};

Blockly.Blocks['time_now'] = {
  init: function() {
    this.appendDummyInput()
        .appendField("Heure actuelle")
        .appendField(new Blockly.FieldDropdown([["Heure, au format 12h (0 à 12)","g"], ["Heure, au format 24h (0 à 23)","G"], ["Minutes (00 à 59)","i"], ["Secondes depuis l'époque Unix (1er Janvier 1970)","U"]]), "format");
    this.setOutput(true, "Number");
    this.setColour(0);
 this.setTooltip("L'heure du moment sous différent format");
 this.setHelpUrl("https://www.php.net/manual/fr/function.date.php");
  }
};
Blockly.JavaScript['time_now'] = function(block) {
  var dropdown_format = block.getFieldValue('format');
  var m = new Date();
  if (dropdown_format == 'g') {
    if (m.getHours() < 12) {
       var code = m.getHours();
    } else {
       var code = m.getHours()-12;
    }
  } else if (dropdown_format == 'G') {
    var code = m.getHours();
  } else if (dropdown_format == 'i') {
    var code = m.getMinutes();
  } else if (dropdown_format == 'U') {
    var code = Math.round(m.getTime()/1000, 0);
  } else {
      alert("Bug in time_now, " + dropdown_format + " not define");
  }
  return [code, Blockly.JavaScript.ORDER_NONE];
};
Blockly.PHP['time_now'] = function(block) {
  var dropdown_format = block.getFieldValue('format');
  var code = 'date("%'+dropdown_format+'")';
  return [code, Blockly.PHP.ORDER_NONE];
};

<?php

foreach ($bloklyData as $nom=>$dataSimu) {
    echo 'Blockly.Blocks[\'data_'.$nom.'\'] = {
      init: function() {
        this.appendDummyInput()
            .appendField("data")
            .appendField("'.$nom.'");
        this.appendDummyInput()
            .appendField("Pour la simulation")';
if ($dataSimu['type'] == 'number') {
    echo '
            .appendField(new Blockly.FieldNumber('.$dataSimu['value'].', '.$dataSimu['min'].', '.$dataSimu['max'].', 1), "simu_return");';
} else if ($dataSimu['type'] == 'input') {
    echo '
            .appendField(new Blockly.FieldTextInput("'.$dataSimu['value'].'"), "simu_return");';
} else if ($dataSimu['type'] == 'dropdown') {
    $dataExplode=explode('|', $dataSimu['value']);
    $dataConcat='[';
    $i=0;
    foreach($dataExplode as $oneData) {
        if ($i != 0) {
            $dataConcat.=',';
        }
        if ($dataSimu['output'] == 'Number') {
            $dataConcat.='['.$oneData.','.$oneData.']';
        } else {
            $dataConcat.='["'.$oneData.'","'.$oneData.'"]';
        }
        $i++;
    }
    $dataConcat.=']';
    echo '
            .appendField(new Blockly.FieldDropdown('.$dataConcat.'), "simu_return");';  
}
echo '
        this.setOutput(true, "'.$dataSimu['output'].'");
        this.setColour(23);
     this.setTooltip('.$dataSimu['tooltip'].');
     this.setHelpUrl("");
      }
    };';
    echo '
    Blockly.PHP[\'data_'.$nom.'\'] = function(block) {
      var code = \'$data[\\\''.$nom.'\\\']\';
      return [code, Blockly.PHP.ORDER_NONE];
    };';
    echo '
    Blockly.JavaScript[\'data_'.$nom.'\'] = function(block) {
      var dropdown_simu_return = block.getFieldValue(\'simu_return\');';
      if ($dataSimu['output'] == 'Number') {
        echo '
      var code = dropdown_simu_return;';
      } else {
        echo '
      var code = \'\\\'\'+dropdown_simu_return+\'\\\'\';';
      }
      echo'
      return [code, Blockly.JavaScript.ORDER_NONE];
    };';
}
?>

// Pour mettre en gras "enregistrer" (signifier que ce n'est pas enregistré)
$( "#content_blocks" ).click(function( event ) {
	if (!$( "#saveScript" ).hasClass( "noSave" )) {
		$( "#saveScript" ).addClass( "noSave" );
	}
});
$( "#saveScript" ).click(function( event ) {
    // Generer le code XML
    var xmlDom = Blockly.Xml.workspaceToDom(Code.workspace);
    var xmlText = Blockly.Xml.domToPrettyText(xmlDom);
    $( "#script_xml" ).val(xmlText);
    try {
        var xml = Blockly.Xml.textToDom(xmlText)
    } catch (e) {
        alert(e);
        return;
    }
    // Create a headless workspace.
    var demoWorkspace = new Blockly.Workspace();
    Blockly.Xml.domToWorkspace(xml, demoWorkspace);
    var code = Blockly.PHP.workspaceToCode(demoWorkspace);
    $( "#script_php" ).val(code);
    $( "#saveScript" ).removeClass( "noSave" );
    $( "#script_save" ).submit();
});

</script>
<?php } ?>
		<script>
		$( ".submit" ).click(function( event ) {
			// Not exemple
			if (this.name == 'edit') {
				$( "#exampleScript"+this.form.title).val('');
			}
			this.form.submit();
		});
		$('.exampleScripts').on('change', function() {
			$('.exampleScriptsDesc').hide();
			if (this.value != '') {
				$('#'+this.value).show();
			}
		});
		</script>
        </div>
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
			<label for="domoEdit-password">Mot de passe : </label>
			<input type="password" name="domoEdit-password" />
			<input type="submit" />
			</form>';
		}
		?>
    </div>
</body>
</html>
<?php @include_once('./footer.php');  ?>
