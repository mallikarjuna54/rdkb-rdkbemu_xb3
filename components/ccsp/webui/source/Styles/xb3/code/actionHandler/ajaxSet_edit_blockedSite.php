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

function time_date_conflict($TD1, $TD2) {
	$ret = false;
	$days1 = explode(",", $TD1[2]);
	$days2 = explode(",", $TD2[2]);

	foreach ($days1 as &$value) {
		if (in_array($value, $days2)) {
			//deMorgan's law - to find if ranges are overlapping
			//(StartA <= EndB)  and  (EndA >= StartB)
			if((strtotime($TD1[0]) < strtotime($TD2[1])) and (strtotime($TD1[1]) > strtotime($TD2[0]))){
	  			$ret = true;
	  			break;
			} 
		}
	}
	return $ret;
}

$blockedSiteInfo = json_decode($_REQUEST['BlockInfo'], true);

$objPrefix = "Device.X_Comcast_com_ParentalControl.ManagedSites.BlockedSite.";
$rootObjName = $objPrefix;
$index = $blockedSiteInfo['InstanceID'];

$idArr = explode(",", getInstanceIds("Device.X_Comcast_com_ParentalControl.ManagedSites.BlockedSite."));

$block=$blockedSiteInfo['alwaysBlock'];
$startTime=$blockedSiteInfo['StartTime'];
$endTime=$blockedSiteInfo['EndTime'];
$blockDays=$blockedSiteInfo['blockedDays'];

$result = "";

if( array_key_exists('URL', $blockedSiteInfo) ) {
	//this is to edit blocked URL
	//firstly, check whether URL exist or not
	$url = $blockedSiteInfo['URL'];
    	foreach ($idArr as $key => $value) {
		if ($index==$value) continue;
		$always_Block = getStr($objPrefix.$value.".AlwaysBlock");
		$start_Time = getStr($objPrefix.$value.".StartTime");
		$end_Time = getStr($objPrefix.$value.".EndTime");
		$block_Days = getStr($objPrefix.$value.".BlockDays");

		//Check for time and day conflicts
		$TD1=array($startTime, $endTime, $blockDays);
		$TD2=array($start_Time, $end_Time, $block_Days);
		if ((strcasecmp($url, getStr($objPrefix.$value.".Site"))==0) && ((($always_Block == "true") || ($block == "true") || time_date_conflict($TD1, $TD2)))){
			$result .= "Conflict with other blocked site rule. Please check your input!";
			break;
		}
	}

	if ($result == ""){
		if ($blockedSiteInfo['alwaysBlock'] == "true"){
			$paramArray = 
				array (
					array($objPrefix.$index.".Site", "string", $blockedSiteInfo['URL']),
					array($objPrefix.$index.".AlwaysBlock", "bool", $blockedSiteInfo['alwaysBlock']),
				);

			$retStatus = DmExtSetStrsWithRootObj($rootObjName, TRUE, $paramArray);	
			if (!$retStatus){
				$result="Success!";
			}	
			else {
				$result = 'Failed to add';
			}

			/*setStr($objPrefix.$index.".Site", $blockedSiteInfo['URL'], false);
			setStr($objPrefix.$index.".AlwaysBlock", $blockedSiteInfo['alwaysBlock'], true);*/
		}
		else{
			$paramArray = 
				array (
					array($objPrefix.$index.".Site", "string", $blockedSiteInfo['URL']),
					array($objPrefix.$index.".AlwaysBlock", "bool", $blockedSiteInfo['alwaysBlock']),
					array($objPrefix.$index.".StartTime", "string", $blockedSiteInfo['StartTime']),
					array($objPrefix.$index.".EndTime", "string", $blockedSiteInfo['EndTime']),
					array($objPrefix.$index.".BlockDays", "string", $blockedSiteInfo['blockedDays']),
				);
	
			$retStatus = DmExtSetStrsWithRootObj($rootObjName, TRUE, $paramArray);	
			if (!$retStatus){
				$result="Success!";
			}	
			else {
				$result = 'Failed to add';
			}

	/*
			setStr($objPrefix.$blockedSiteInfo['InstanceID'].".Site", $blockedSiteInfo['URL'], false);
			setStr($objPrefix.$blockedSiteInfo['InstanceID'].".AlwaysBlock", $blockedSiteInfo['alwaysBlock'], false);
			//setStr($objPrefix.$blockedSiteInfo['InstanceID'].".BlockMethod", "URL");
			setStr($objPrefix.$blockedSiteInfo['InstanceID'].".StartTime", $blockedSiteInfo['StartTime'], false);
			setStr($objPrefix.$blockedSiteInfo['InstanceID'].".EndTime", $blockedSiteInfo['EndTime'], false);
			setStr($objPrefix.$blockedSiteInfo['InstanceID'].".BlockDays", $blockedSiteInfo['blockedDays'], true);
	*/	
		}
	}
}
else{
	//this is to edit blocked Keyword
	$keyword = $blockedSiteInfo['Keyword'];
    	foreach ($idArr as $key => $value) {
		if ($index==$value) continue;
	    	$always_Block = getStr($objPrefix.$value.".AlwaysBlock");
		$start_Time = getStr($objPrefix.$value.".StartTime");
		$end_Time = getStr($objPrefix.$value.".EndTime");
		$block_Days = getStr($objPrefix.$value.".BlockDays");

		//Check for time and day conflicts
		$TD1=array($startTime, $endTime, $blockDays);
		$TD2=array($start_Time, $end_Time, $block_Days);
		if (($keyword == getStr($objPrefix.$value.".Site")) && ((($always_Block == "true") || ($block == "true") || time_date_conflict($TD1, $TD2)))){
			$result .= "Conflict with other blocked Keyword rule. Please check your input!";
			break;
		}
	}

	if ($result == ""){
		if ($blockedSiteInfo['alwaysBlock'] == "true"){
			$paramArray = 
				array (
					array($objPrefix.$index.".Site", "string", $blockedSiteInfo['Keyword']),
					array($objPrefix.$index.".AlwaysBlock", "bool", $blockedSiteInfo['alwaysBlock']),
				);

			$retStatus = DmExtSetStrsWithRootObj($rootObjName, TRUE, $paramArray);	
			if (!$retStatus){
				$result="Success!";
			}	
			else {
				$result = 'Failed to add';
			}

			/*setStr($objPrefix.$index.".Site", $blockedSiteInfo['Keyword'], false);
			setStr($objPrefix.$index.".AlwaysBlock", $blockedSiteInfo['alwaysBlock'], true);*/
		}
		else{

			$paramArray = 
				array (
					array($objPrefix.$index.".Site", "string", $blockedSiteInfo['Keyword']),
					array($objPrefix.$index.".AlwaysBlock", "bool", $blockedSiteInfo['alwaysBlock']),
					array($objPrefix.$index.".StartTime", "string", $blockedSiteInfo['StartTime']),
					array($objPrefix.$index.".EndTime", "string", $blockedSiteInfo['EndTime']),
					array($objPrefix.$index.".BlockDays", "string", $blockedSiteInfo['blockedDays']),
				);
	
			$retStatus = DmExtSetStrsWithRootObj($rootObjName, TRUE, $paramArray);	
			if (!$retStatus){
				$result="Success!";
			}	
			else {
				$result = 'Failed to add';
			}

	/*
			setStr($objPrefix.$blockedSiteInfo['InstanceID'].".Site", $blockedSiteInfo['Keyword'], false);
			setStr($objPrefix.$blockedSiteInfo['InstanceID'].".AlwaysBlock", $blockedSiteInfo['alwaysBlock'], false);
			//setStr($objPrefix.$blockedSiteInfo['InstanceID'].".BlockMethod", "Keyword");
			setStr($objPrefix.$blockedSiteInfo['InstanceID'].".StartTime", $blockedSiteInfo['StartTime'], false);
			setStr($objPrefix.$blockedSiteInfo['InstanceID'].".EndTime", $blockedSiteInfo['EndTime'], false);
			setStr($objPrefix.$blockedSiteInfo['InstanceID'].".BlockDays", $blockedSiteInfo['blockedDays'], true);
	*/	
		}
	}
}

echo $result;

?>
