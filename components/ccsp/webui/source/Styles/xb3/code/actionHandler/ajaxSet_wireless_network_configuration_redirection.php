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

$jsConfig = $_REQUEST['rediection_Info'];
// $jsConfig = '{"network_name":"ssid1", "network_password":"123456789"';

$arConfig = json_decode($jsConfig, true);
//print_r($arConfig);

$network_name = $arConfig['network_name'];

$network_name_arr = array(
	"1" => $network_name,//."-2.4",
	"2" => $network_name,//."-5",
);

// this method for only restart a certain SSID
function MiniApplySSID($ssid) {
	$apply_id = (1 << intval($ssid)-1);
	$apply_rf = (2  - intval($ssid)%2);
	setStr("Device.WiFi.Radio.$apply_rf.X_CISCO_COM_ApplySettingSSID", $apply_id, false);
	setStr("Device.WiFi.Radio.$apply_rf.X_CISCO_COM_ApplySetting", "true", true);
}

for($i = "1"; $i < 3; $i++){

	$r = (2 - intval($i)%2);	//1,3,5,7 == 1(2.4G); 2,4,6,8 == 2(5G)

	// check if the SSID status is enabled
	if ("false" == getStr("Device.WiFi.SSID.$i.Enable")){
		setStr("Device.WiFi.Radio.$r.Enable", "true", true);
	}

	// check if the LowerLayers radio is enabled
	if ("false" == getStr("Device.WiFi.Radio.$r.Enable")){
		setStr("Device.WiFi.Radio.$r.Enable", "true", true);
	}

	setStr("Device.WiFi.SSID.$i.SSID", $network_name_arr[$i], true);
	setStr("Device.WiFi.AccessPoint.$i.Security.X_CISCO_COM_KeyPassphrase", $arConfig['network_password'], true);

	// setStr("Device.WiFi.Radio.$r.X_CISCO_COM_ApplySetting", "true", true);
	MiniApplySSID($i);
}

sleep(10);
setStr("Device.DeviceInfo.X_RDKCENTRAL-COM_ConfigureWiFi", "false", true);

echo $jsConfig;
?>
