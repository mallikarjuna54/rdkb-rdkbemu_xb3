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


$jsConfig = $_REQUEST['configInfo'];
//$jsConfig = '{"newPassword": "11111111", "instanceNum": "1", "oldPassword": "111"}';

//if request is from "password_change.php"
//$jsConfig = '{"newPassword": "11111111", "instanceNum": "1", "oldPassword": "111", "ChangePassword": "true"}';

$arConfig = json_decode($jsConfig, true);
//print_r($arConfig);

$i = $arConfig['instanceNum'];

$p_status = "MisMatch";

setStr("Device.Users.User.3.X_RDKCENTRAL-COM_ComparePassword",$arConfig['oldPassword'],true);
sleep(1);
//Good_PWD, Default_PWD, Invalid_PWD
$passVal= getStr("Device.Users.User.3.X_RDKCENTRAL-COM_ComparePassword");
if ($passVal=="Good_PWD" || $passVal=="Default_PWD")
{
        if($arConfig['ChangePassword']){
                setStr("Device.Users.User.3.X_CISCO_COM_Password", $arConfig['newPassword'], true);
        }
        $p_status = "Match";
}
else {
        $p_status = "MisMatch";
}

$arConfig = array('p_status'=>$p_status);
				
$jsConfig = json_encode($arConfig);

header("Content-Type: application/json");
echo $jsConfig;	

?>
