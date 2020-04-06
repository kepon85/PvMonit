<xml xmlns="https://developers.google.com/blockly/xml" id="toolbox" style="display: none">
<category name="PvMonit" colour='60'>
	<block type="variables_get">
	  <field name="VAR" id="GV6h1rQ^p9]JM0qQb5eW">retour_log</field>
	</block>
	<block type="variables_get">
	  <field name="VAR" id="0qQb5eGV3j7rv^pE]J3W">retour_mod</field>
	</block>
	<block type="thisid"></block>
	<block type="thismod"></block>
	<block type="thisetat"></block>
	<block type="relaymod"></block>
	<block type="relayetat"></block>
	<block type="relayuptoday"></block>
	<block type="relayupdowntoday"></block>
	<block type="relaylastup"></block>
	<block type="relaylastupauto"></block>
	<block type="relaylastdown"></block>	
	<block type="timeupmin"></block>	
	<block type="timeupmax"></block>	
	<?php if (isset($config['domo']['valueUse']['CS'])) { ?>
	<block type="mpptflo"></block>	
	<block type="mpptabsorflo"></block>	
	<?php } ?>
</category>
<category name="Donnée" colour='23'>
    <?php 
	foreach ($config['domo']['valueUse'] as $value=>$regex)  {
	    echo '<block type="data_'.$value.'"></block>';
	}
    ?>
</category>
<category name="Date" colour='0'>
	<block type="date_now"></block>
	<block type="time_now"></block>
</category>
<category name="%{BKY_CATLOGIC}" colour="%{BKY_LOGIC_HUE}">
  <block type="controls_if"></block>
  <block type="logic_compare"></block>
  <block type="logic_operation"></block>
  <block type="logic_negate"></block>
  <block type="logic_boolean"></block>
  <block type="logic_null"></block>
  <block type="logic_ternary"></block>
</category>
<category name="%{BKY_CATLOOPS}" colour="%{BKY_LOOPS_HUE}">
  <block type="controls_repeat_ext">
	<value name="TIMES">
	  <shadow type="math_number">
		<field name="NUM">10</field>
	  </shadow>
	</value>
  </block>
  <block type="controls_whileUntil"></block>
  <block type="controls_for">
	<value name="FROM">
	  <shadow type="math_number">
		<field name="NUM">1</field>
	  </shadow>
	</value>
	<value name="TO">
	  <shadow type="math_number">
		<field name="NUM">10</field>
	  </shadow>
	</value>
	<value name="BY">
	  <shadow type="math_number">
		<field name="NUM">1</field>
	  </shadow>
	</value>
  </block>
  <block type="controls_forEach"></block>
  <block type="controls_flow_statements"></block>
</category>
<category name="%{BKY_CATMATH}" colour="%{BKY_MATH_HUE}">
  <block type="math_number">
	<field name="NUM">123</field>
  </block>
  <block type="math_arithmetic">
	<value name="A">
	  <shadow type="math_number">
		<field name="NUM">1</field>
	  </shadow>
	</value>
	<value name="B">
	  <shadow type="math_number">
		<field name="NUM">1</field>
	  </shadow>
	</value>
  </block>
  <block type="math_single">
	<value name="NUM">
	  <shadow type="math_number">
		<field name="NUM">9</field>
	  </shadow>
	</value>
  </block>
  <block type="math_trig">
	<value name="NUM">
	  <shadow type="math_number">
		<field name="NUM">45</field>
	  </shadow>
	</value>
  </block>
  <block type="math_constant"></block>
  <block type="math_number_property">
	<value name="NUMBER_TO_CHECK">
	  <shadow type="math_number">
		<field name="NUM">0</field>
	  </shadow>
	</value>
  </block>
  <block type="math_round">
	<value name="NUM">
	  <shadow type="math_number">
		<field name="NUM">3.1</field>
	  </shadow>
	</value>
  </block>
  <block type="math_on_list"></block>
  <block type="math_modulo">
	<value name="DIVIDEND">
	  <shadow type="math_number">
		<field name="NUM">64</field>
	  </shadow>
	</value>
	<value name="DIVISOR">
	  <shadow type="math_number">
		<field name="NUM">10</field>
	  </shadow>
	</value>
  </block>
  <block type="math_constrain">
	<value name="VALUE">
	  <shadow type="math_number">
		<field name="NUM">50</field>
	  </shadow>
	</value>
	<value name="LOW">
	  <shadow type="math_number">
		<field name="NUM">1</field>
	  </shadow>
	</value>
	<value name="HIGH">
	  <shadow type="math_number">
		<field name="NUM">100</field>
	  </shadow>
	</value>
  </block>
  <block type="math_random_int">
	<value name="FROM">
	  <shadow type="math_number">
		<field name="NUM">1</field>
	  </shadow>
	</value>
	<value name="TO">
	  <shadow type="math_number">
		<field name="NUM">100</field>
	  </shadow>
	</value>
  </block>
  <block type="math_random_float"></block>
  <block type="math_atan2">
	<value name="X">
	  <shadow type="math_number">
		<field name="NUM">1</field>
	  </shadow>
	</value>
	<value name="Y">
	  <shadow type="math_number">
		<field name="NUM">1</field>
	  </shadow>
	</value>
  </block>
</category>
<category name="%{BKY_CATTEXT}" colour="%{BKY_TEXTS_HUE}">
  <block type="text"></block>
  <block type="text_join"></block>
  <block type="text_append">
	<value name="TEXT">
	  <shadow type="text"></shadow>
	</value>
  </block>
  <block type="text_length">
	<value name="VALUE">
	  <shadow type="text">
		<field name="TEXT">abc</field>
	  </shadow>
	</value>
  </block>
  <block type="text_isEmpty">
	<value name="VALUE">
	  <shadow type="text">
		<field name="TEXT"></field>
	  </shadow>
	</value>
  </block>
  <block type="text_indexOf">
	<value name="VALUE">
	  <block type="variables_get">
		<field name="VAR">{textVariable}</field>
	  </block>
	</value>
	<value name="FIND">
	  <shadow type="text">
		<field name="TEXT">abc</field>
	  </shadow>
	</value>
  </block>
  <block type="text_charAt">
	<value name="VALUE">
	  <block type="variables_get">
		<field name="VAR">{textVariable}</field>
	  </block>
	</value>
  </block>
  <block type="text_getSubstring">
	<value name="STRING">
	  <block type="variables_get">
		<field name="VAR">{textVariable}</field>
	  </block>
	</value>
  </block>
  <block type="text_changeCase">
	<value name="TEXT">
	  <shadow type="text">
		<field name="TEXT">abc</field>
	  </shadow>
	</value>
  </block>
  <block type="text_trim">
	<value name="TEXT">
	  <shadow type="text">
		<field name="TEXT">abc</field>
	  </shadow>
	</value>
  </block>
  <block type="text_print">
	<value name="TEXT">
	  <shadow type="text">
		<field name="TEXT">abc</field>
	  </shadow>
	</value>
  </block>
  <block type="text_prompt_ext">
	<value name="TEXT">
	  <shadow type="text">
		<field name="TEXT">abc</field>
	  </shadow>
	</value>
  </block>
</category>
<category name="%{BKY_CATLISTS}" colour="%{BKY_LISTS_HUE}">
  <block type="lists_create_with">
	<mutation items="0"></mutation>
  </block>
  <block type="lists_create_with"></block>
  <block type="lists_repeat">
	<value name="NUM">
	  <shadow type="math_number">
		<field name="NUM">5</field>
	  </shadow>
	</value>
  </block>
  <block type="lists_length"></block>
  <block type="lists_isEmpty"></block>
  <block type="lists_indexOf">
	<value name="VALUE">
	  <block type="variables_get">
		<field name="VAR">{listVariable}</field>
	  </block>
	</value>
  </block>
  <block type="lists_getIndex">
	<value name="VALUE">
	  <block type="variables_get">
		<field name="VAR">{listVariable}</field>
	  </block>
	</value>
  </block>
  <block type="lists_setIndex">
	<value name="LIST">
	  <block type="variables_get">
		<field name="VAR">{listVariable}</field>
	  </block>
	</value>
  </block>
  <block type="lists_getSublist">
	<value name="LIST">
	  <block type="variables_get">
		<field name="VAR">{listVariable}</field>
	  </block>
	</value>
  </block>
  <block type="lists_split">
	<value name="DELIM">
	  <shadow type="text">
		<field name="TEXT">,</field>
	  </shadow>
	</value>
  </block>
  <block type="lists_sort"></block>
</category>
<sep></sep>
<category name="%{BKY_CATVARIABLES}" colour="%{BKY_VARIABLES_HUE}" custom="VARIABLE"></category>
<category name="%{BKY_CATFUNCTIONS}" colour="%{BKY_PROCEDURES_HUE}" custom="PROCEDURE"></category>
</xml>
