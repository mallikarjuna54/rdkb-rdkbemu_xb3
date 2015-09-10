<?php 


$jsConfig = $_REQUEST['configInfo'];
//$jsConfig = '{"newPassword": "11111111", "instanceNum": "1", "oldPassword": "111"}';

//if request is from "password_change.php"
//$jsConfig = '{"newPassword": "11111111", "instanceNum": "1", "oldPassword": "111", "ChangePassword": "true"}';

$arConfig = json_decode($jsConfig, true);
//print_r($arConfig);

$i = $arConfig['instanceNum'];

$p_status = "MisMatch";

if (getStr("Device.Users.User.$i.X_CISCO_COM_Password") ==  $arConfig['oldPassword']) 
{
	if($arConfig['ChangePassword']){
		setStr("Device.Users.User.3.X_CISCO_COM_Password", $arConfig['newPassword'], true);
	}
	$p_status = "Match";
	//setStr("Device.Users.User.$i.X_CISCO_COM_Password", $arConfig['newPassword'], true);	
}

$arConfig = array('p_status'=>$p_status);
				
$jsConfig = json_encode($arConfig);

header("Content-Type: application/json");
echo $jsConfig;	

?>
