<?php
/*
 * If not stated otherwise in this file or this component's Licenses.txt file the 
 * following copyright and licenses apply:
 *
 * Copyright 2016 RDK Management
 *
 * Licensed under the Apache License, Version 2.0 (the License);
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and 
 * limitations under the License.
*/
?>
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
