<?php 

//dmzInfo = '{"IsEnabledDMZ":"'+isEnabledDMZ+'", "Host":"'+host+'"}';

$dmzInfo = json_decode($_REQUEST['dmzInfo'], true);

//echo $dmzInfo['IsEnabled'];
//echo "<br />";

$isEnabledDMZ = $dmzInfo['IsEnabledDMZ'];
$ip = $dmzInfo['Host'];
$hostv6 = $dmzInfo['hostv6'];

$rootObjName = "Device.NAT.X_CISCO_COM_DMZ.";

if($isEnabledDMZ == "true") {
	$paramArray = 
		array (
			array($rootObjName."InternalIP", "string", $ip),
			array($rootObjName."Enable", "bool", $isEnabledDMZ)
		);
	$retStatus = DmExtSetStrsWithRootObj($rootObjName, TRUE, $paramArray);	
}
else if($isEnabledDMZ == "false") {
	setStr($rootObjName."Enable", $isEnabledDMZ,true);
}

?>
