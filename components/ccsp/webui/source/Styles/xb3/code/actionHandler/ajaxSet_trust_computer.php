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

$flag = json_decode($_REQUEST['TrustFlag'], true);

if( $flag['trustFlag'] == "true" ){
    // "no" => "yes"
    //if device not in trusted user table, add this device to Trusted user table, set the trusted flag == true
    //if already exist, just set the trusted flag  == true
    
    $IDs  = getInstanceIds("Device.X_Comcast_com_ParentalControl.ManagedSites.TrustedUser.");
    $idArr = explode(",", $IDs);
    $deviceExist = false;

    foreach ($idArr as $key => $value) {
        if ($flag['IPAddress'] == getStr("Device.X_Comcast_com_ParentalControl.ManagedSites.TrustedUser.$value.IPAddress")) {
           $deviceExist = true;
           setStr("Device.X_Comcast_com_ParentalControl.ManagedSites.TrustedUser.$value.Trusted", $flag['trustFlag'], true);
           break; 
        }
    }

    if (!$deviceExist)
    {
        addTblObj("Device.X_Comcast_com_ParentalControl.ManagedSites.TrustedUser."); 
    
        $IDs  = getInstanceIds("Device.X_Comcast_com_ParentalControl.ManagedSites.TrustedUser.");
        $idArr = explode(",", $IDs);
        $instanceid = array_pop($idArr);

        setStr("Device.X_Comcast_com_ParentalControl.ManagedSites.TrustedUser.$instanceid.HostDescription", $flag['HostName'], false);
        setStr("Device.X_Comcast_com_ParentalControl.ManagedSites.TrustedUser.$instanceid.IPAddress", $flag['IPAddress'], false);
        if ( strpbrk($flag['IPAddress'], ':') != FALSE ){
            setStr("Device.X_Comcast_com_ParentalControl.ManagedSites.TrustedUser.$instanceid.IPAddressType", "IPv6", false);
        }
        else{
            setStr("Device.X_Comcast_com_ParentalControl.ManagedSites.TrustedUser.$instanceid.IPAddressType", "IPv4", false);
        }
        setStr("Device.X_Comcast_com_ParentalControl.ManagedSites.TrustedUser.$instanceid.Trusted", $flag['trustFlag'], true);
    }
    
}
else{
    // "yes" => "no" not trusted
    $IDs  = getInstanceIds("Device.X_Comcast_com_ParentalControl.ManagedSites.TrustedUser.");
    $idArr = explode(",", $IDs);

    foreach ($idArr as $key => $value) {
        if ($flag['IPAddress'] == getStr("Device.X_Comcast_com_ParentalControl.ManagedSites.TrustedUser.$value.IPAddress")) {
           $index = $value;
           break; 
        }
    }

    setStr("Device.X_Comcast_com_ParentalControl.ManagedSites.TrustedUser.$index.Trusted", 'false', true);
    //delTblObj("Device.X_Comcast_com_ParentalControl.ManagedSites.TrustedUser.$index.");

}

?>
