<?php
//USE ;end TO SEPERATE SQL STATEMENTS. DON'T USE ;end IN ANY OTHER PLACES!

$sql = array();
$count = 0;

//v0.1.00
$sql[$count][0] = '0.1.00';
$sql[$count][1] = '-- First version, nothing to update';

//v1.0.00
$sql[$count][0] = '1.0.00';
$sql[$count][1] = '';

//v1.0.01
$sql[$count][0] = '1.0.01';
$sql[$count][1] = '';

//v1.0.02
$sql[$count][0] = '1.0.02';
$sql[$count][1] = '';

//v1.0.03
$sql[$count][0] = '1.0.03';
$sql[$count][1] = "
INSERT INTO `gibbonSetting` (`gibbonSettingID` ,`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES (NULL , 'Stream', 'showPreviousYear', 'Show Previous Year', 'Should posts from the immediately previous year be displayed in Stream?', 'N');end
";

//v1.0.04
$sql[$count][0] = '1.0.04';
$sql[$count][1] = "
";

//v1.0.05
$sql[$count][0] = '1.0.05';
$sql[$count][1] = "
";

//v1.0.06
$sql[$count][0] = '1.0.06';
$sql[$count][1] = "
";

//v1.0.07
$sql[$count][0] = '1.0.07';
$sql[$count][1] = "
";
