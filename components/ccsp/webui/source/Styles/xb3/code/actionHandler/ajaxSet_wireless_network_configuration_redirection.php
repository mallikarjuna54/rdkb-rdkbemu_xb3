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
