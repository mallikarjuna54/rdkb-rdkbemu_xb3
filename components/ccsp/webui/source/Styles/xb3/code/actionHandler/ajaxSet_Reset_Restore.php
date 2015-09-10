<?php 

header("Content-Type: application/json");

$infoArray = json_decode($_REQUEST['resetInfo'], true);
// sleep(10);
$thisUser = $infoArray[2];

ob_implicit_flush(true);
ob_end_flush();

$ret = array();

//>>zqiu
function delMacFilterTable( $ssid_list ) {
	$ssids = explode(" ", $ssid_list);
	foreach ($ssids as $i)	{
		$old_id = array_filter(explode(",",getInstanceIds("Device.WiFi.AccessPoint.$i.X_CISCO_COM_MacFilterTable.")));
		foreach ($old_id as $j) {
			delTblObj("Device.WiFi.AccessPoint.$i.X_CISCO_COM_MacFilterTable.$j.");
		}
		setStr("Device.WiFi.AccessPoint.$i.X_CISCO_COM_MACFilter.Enable", false, true);
		setStr("Device.WiFi.AccessPoint.$i.X_CISCO_COM_MACFilter.FilterAsBlackList", false, true);
	}
}

function delMacFilterTables(  ) {
	delMacFilterTable("1 2 3 5 6");
	//For WECB
	setStr("Device.MoCA.X_CISCO_COM_WiFi_Extender.X_CISCO_COM_SSID_Updated", "true", true);
}
//<<
switch ($infoArray[0]) {
	case "btn1" :
		$ret["reboot"] = true;
		echo json_encode($ret);
		setStr("Device.X_CISCO_COM_DeviceControl.RebootDevice", $infoArray[1],true);
		exit(0);
	case "btn2" :
		$ret["wifi"] = true;
		echo json_encode($ret);	
		setStr("Device.X_CISCO_COM_DeviceControl.RebootDevice", $infoArray[1],true);
		//force to restart radio even no change
		setStr("Device.WiFi.X_CISCO_COM_ResetRadios", "true", true);
		exit(0);
	case "btn3" :
		$ret["wifi"] = true;
		echo json_encode($ret);	
		setStr("Device.X_CISCO_COM_DeviceControl.RebootDevice", $infoArray[1],true);
		//force to restart radio even no change
		setStr("Device.WiFi.X_CISCO_COM_ResetRadios", "true", true);
		exit(0);
	case "btn4" :
		$ret["wifi"] = true;
		echo json_encode($ret);
		delMacFilterTable("1 2");
		//setStr("Device.X_CISCO_COM_DeviceControl.FactoryReset", $infoArray[1],true);
		//when restore, radio can be restart, but also need to force it when no change
		//setStr("Device.WiFi.X_CISCO_COM_ResetRadios", "true", true);
		setStr("Device.WiFi.X_CISCO_COM_FactoryResetRadioAndAp", "1,2;1,2",true);	//radio 1, radio 2; Ap 1, Ap 2
		//For WECB
		setStr("Device.MoCA.X_CISCO_COM_WiFi_Extender.X_CISCO_COM_SSID_Updated", "true", true);
		exit(0);
	case "FactoryResetRadioAndAp" :
		$ret["wifi"] = true;
		echo json_encode($ret);
		$idxArr = explode(";", $infoArray[1]);
		//$radioIndex=$idxArr[0];
		$apIndex=$idxArr[1];		
		if($apIndex != "0") {
			delMacFilterTable( "$apIndex" );
			//For WECB
			setStr("Device.MoCA.X_CISCO_COM_WiFi_Extender.X_CISCO_COM_SSID_Updated", "true", true);
		}		
		setStr("Device.WiFi.X_CISCO_COM_FactoryResetRadioAndAp", $infoArray[1],true);		
		exit(0);
	case "btn5" :
		$ret["reboot"] = true;
		echo json_encode($ret);
		setStr("Device.X_CISCO_COM_DeviceControl.FactoryReset", $infoArray[1],true);
		exit(0);
	case "btn6" :
		//"mso" and "cusadmin" required to reset password of "admin"
		if ("mso"==$thisUser) {
			setStr("Device.Users.User.1.X_CISCO_COM_Password", "pod", true);
			setStr("Device.Users.User.3.X_CISCO_COM_Password", "password", true);
			echo "mso";
		}
		elseif ("cusadmin"==$thisUser) {
			setStr("Device.Users.User.2.X_CISCO_COM_Password", "Xfinity", true);
			setStr("Device.Users.User.3.X_CISCO_COM_Password", "password", true);
			echo "cusadmin";
		}
		else {
			setStr("Device.Users.User.3.X_CISCO_COM_Password", "password", true);
			echo "admin";
		}
		break;
	default:
		break;
}

echo json_encode($ret);
?>
