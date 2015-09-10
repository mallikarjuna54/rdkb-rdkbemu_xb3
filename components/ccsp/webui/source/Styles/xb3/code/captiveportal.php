<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="description" content="">
		<meta name="author" content="">
		<title>XFINITY Smart Internet</title>
		<link rel="stylesheet" href="cmn/css/styles.css">
	</head>

<?php include('includes/utility.php'); ?>
<?php
	//should we allow to Configure WiFi
	/*-------- redirection logic - uncomment the code below while checking in --------*/

	$CONFIGUREWIFI	= getStr("Device.DeviceInfo.X_RDKCENTRAL-COM_ConfigureWiFi");
	if(strstr($CONFIGUREWIFI, "false"))	header('Location:index.php');

	//WiFi Defaults are same for 2.4Ghz and 5Ghz
	$network_name	= getStr("Device.WiFi.SSID.1.SSID");
	$network_pass	= getStr("Device.WiFi.AccessPoint.1.Security.X_CISCO_COM_KeyPassphrase");
	$ipv4_addr 	= getStr("Device.X_CISCO_COM_DeviceControl.LanManagementEntry.1.LanIPAddress");

	/*------	logic to figure out LAN or WiFi from Connected Devices List	------*/
	/*------	get clients IP		------*/
	// Known prefix
	$v4mapped_prefix_hex = '00000000000000000000ffff';
	$v4mapped_prefix_bin = pack("H*", $v4mapped_prefix_hex);

	// Parse
	$addr = $_SERVER['REMOTE_ADDR'];
	$addr_bin = inet_pton($addr);
	if( $addr_bin === FALSE ) {
	  // Unparsable? How did they connect?!?
	  die('Invalid IP address');
	}

	// Check prefix
	if( substr($addr_bin, 0, strlen($v4mapped_prefix_bin)) == $v4mapped_prefix_bin) {
	  // Strip prefix
	  $addr_bin = substr($addr_bin, strlen($v4mapped_prefix_bin));
	}

	// Convert back to printable address in canonical form
	$clientIP = inet_ntop($addr_bin);

	/*------	cross check IP in Connected Devices List	------*/
	function ProcessLay1Interface($interface){
   
		if (stristr($interface, "WiFi")){
			if (stristr($interface, "WiFi.SSID.1")) {
				$host['networkType'] = "Private";
				$host['connectionType'] = "Wi-Fi 2.4G";
			}
			elseif (stristr($interface, "WiFi.SSID.2")) {
				$host['networkType'] = "Private";
				$host['connectionType'] = "Wi-Fi 5G";
			}
			else {
				$host['networkType'] = "Public";
				$host['connectionType'] = "Wi-Fi";
			}
		}
		elseif (stristr($interface, "MoCA")) {
			$host['connectionType'] = "MoCA";
			$host['networkType'] = "Private";
		}
		elseif (stristr($interface, "Ethernet")) {
			$host['connectionType'] = "Ethernet";
			$host['networkType'] = "Private";
		} 
		else{
			$host['connectionType'] = "Unknown";
			$host['networkType'] = "Private";
		}
    
    	return $host;
	}

	$connectionType = "none";

	$rootObjName    = "Device.Hosts.Host.";
	$paramNameArray = array("Device.Hosts.Host.");
	$mapping_array  = array("IPAddress", "Layer1Interface");

	$HostIndexArr = DmExtGetInstanceIds("Device.Hosts.Host.");
	if(0 == $HostIndexArr[0]){  
	    // status code 0 = success   
		$HostNum = count($HostIndexArr) - 1;
	}
	if(!empty($HostNum)){
		$Host = getParaValues($rootObjName, $paramNameArray, $mapping_array);
		if(!empty($Host)){
			foreach ($Host as $key => $value) {
				if(stristr($value["IPAddress"], $clientIP)){
					if(stristr($value["Layer1Interface"], "Ethernet")){ $connectionType = "Ethernet"; }
					else if(stristr($value["Layer1Interface"], "WiFi.SSID.1")){ $connectionType = "WiFi"; }//WiFi 2.4GHz
					else if(stristr($value["Layer1Interface"], "WiFi.SSID.2")){ $connectionType = "WiFi"; }//WiFi 5GHz
				}
			}
		}//end of if empty host
	}//end of if empty hostNums

	//allow redirect config only over Ethernet, Private WiFi 2.4G or 5G
	if(!(stristr($connectionType, "Ethernet") || stristr($connectionType, "WiFi"))){
		echo '<h2><br/>Access Denied!<br/><br/>Access is allowed only over Ethernet, Private WiFi 2.4GHz or 5GHz</h2>';
		exit(0);
	}

?>

<script type="text/javascript" src="./cmn/js/lib/jquery-1.9.1.js"></script>

<script>
$(document).ready(function(){

/*------	logic t0 figure out LAN or WiFi from Connected Devices List	------*/
	var connectionType	= "<?php echo $connectionType;?>"; //"Ethernet", "WiFi", "none"

	var goNextName		= false;
	var goNextPassword	= false;
	var goTextSMS		= false;
	
	function GWReachable(){
		//location.href = "http://customer.comcast.com";
		// Handle IE and more capable browsers
		var xhr = new ( window.ActiveXObject || XMLHttpRequest )( "Microsoft.XMLHTTP" );
		var status;

		// Open new request as a HEAD to the root hostname with a random param to bust the cache
		xhr.open( "HEAD", "http://<?php echo $ipv4_addr; ?>/check.php", false );// + (new Date).getTime()

		// Issue request and handle response
		try {
			xhr.send();
			if( xhr.status >= 200 && xhr.status < 304 ){
				location.href = "http://customer.comcast.com";
			} else {
				//console.log("else "+xhr.status);
				GWReachable();
			}
		} catch (error) {
			//console.log("error "+xhr.status);
			GWReachable();
		}
	}

	function goToReady(){
		if(connectionType == "WiFi"){ //"Ethernet", "WiFi", "none"
			GWReachable();
		} else {
			$("#ready").show();
			$("#complete").hide();
			setTimeout(function(){ location.href = "http://customer.comcast.com"; }, 5000);
		}
	}

	function saveConfig(){
		var network_name 	= $("#WiFi_Name").val();
		var network_password 	= $("#WiFi_Password").val();
		var jsConfig = '{"network_name":"'+network_name+'", "network_password":"'+network_password+'"}';

		$.ajax({
			type: "POST",
			url: "actionHandler/ajaxSet_wireless_network_configuration_redirection.php",
			data: { rediection_Info: jsConfig }
		});
		setTimeout(function(){ goToReady(); }, 25000);
	}

	function messageHandler(target, topMessage, bottomMessage){
		//target	- "name", "password"
		//topMessage	- top message to show
		//bottomMessage	- bottom message to show

		if(target == "name"){
			$("#NameContainer").show();
			$("#NameMessageTop").text(topMessage);
			$("#NameMessageBottom").text(bottomMessage);
		}
		else {//password
			$("#PasswordContainer").show();
			$("#PasswordMessageTop").text(topMessage);
			$("#PasswordMessageBottom").text(bottomMessage);
		}
	}

	function passStars(val){
		var textVal="";
		for (i = 0; i < val.length; i++) {
			textVal += "*";
		}
		return textVal;
	}

	function toShowNext(){
		if(goNextName && goNextPassword){
			setTimeout(function(){
				$("#PasswordContainer").hide();
				$("#NameContainer").hide();
			}, 2000);
			$("#button_next").show();
			$("#WiFi_Name_01").text($("#WiFi_Name").val());
			$("#WiFi_Password_01").text($("#WiFi_Password").val());
			$("#WiFi_Password_pass_01").text(passStars($("#WiFi_Password").val()));
		}
		else {
			$("#button_next").hide();
		}
	}

	$("#button_next").click(function(){
		//button >> personalize
		$("#personalize").hide();
		$("#confirm").show();
	});

	$("#button_previous_01").click(function(){
		//button >> confirm - Previous
		$("#personalize").show();
		$("#confirm").hide();
	});

	$("#button_next_01").click(function(){
		$("[id^='WiFi_Name_0']").text($("#WiFi_Name").val());
		$("[id^='WiFi_Password_0']").text($("#WiFi_Password").val());
		$("[id^='WiFi_Password_pass_0']").text(passStars($("#WiFi_Password").val()));

		if(connectionType == "WiFi"){ //"Ethernet", "WiFi", "none"
			//button >> confirm - Next
			$("#setup").show();
			$("#confirm").hide();
			saveConfig();
		} else {
			/*$("#confirm03").show();
			$("#confirm").hide();*/
			/*- Text (SMS) is out of scope for now -*/
			$("#complete").show();
			$("#confirm").hide();
			setTimeout(function(){ saveConfig(); }, 2000);
		}
	});

	/*- Text (SMS) is out of scope for now -*/
	$("#button_previous_03").click(function(){
		//button >> confirm - Previous
		$("#confirm").show();
		$("#confirm03").hide();
	});

	/*- Text (SMS) is out of scope for now -*/
	$("#button_next_03").click(function(){
		/*$("#TextSMS").focus();
		$("#TextSMSContainer").show();*/
		$("#complete").show();
		$("#confirm03").hide();
		setTimeout(function(){ saveConfig(); }, 2000);
	});

	$("#WiFi_Name").keyup(function() {
		
		//VALIDATION for wifi_name
		/*return !param || /^[a-zA-Z0-9\-_.]{3,31}$/i.test(value);
		"3 to 31 characters combined with alphabet, digit, underscore, hyphen and dot");

		return value.toLowerCase().indexOf("xhs")==-1 && value.toLowerCase().indexOf("xfinitywifi")==-1;
		'SSID containing "XHS" and "Xfinitywifi" are reserved !'

		return value.toLowerCase().indexOf("optimumwifi")==-1 && value.toLowerCase().indexOf("twcwifi")==-1 && value.toLowerCase().indexOf("cablewifi")==-1;
		'SSID containing "optimumwifi", "TWCWiFi" and "CableWiFi" are reserved !');*/

		var val	= $("#WiFi_Name").val();
		
		isValid		= /^[a-zA-Z0-9\-_.]{3,31}$/i.test(val);
		isXHS		= val.toLowerCase().indexOf("xhs")==-1;

		//isOther checks for "wifi" || "xfinity" && "cable" && "twc" && "optimum"
		var str = val.replace(/[\.,-\/#@!$%\^&\*;:{}=\-_`~()\s]/g,'').toLowerCase();
		isOther	= str.indexOf("wifi") == -1 || str.indexOf("xfinity") == -1 && str.indexOf("cable") == -1 && str.indexOf("twc") == -1 && str.indexOf("optimum") == -1;

		if(val == ""){
			goNextName = false;
			$("#WiFi_Name").addClass("error").removeClass("success");
			
			messageHandler("name", "Let's try that again", "Please enter WiFi Name.");
		}
		else if("<?php echo $network_name;?>" == val){
			goNextName = false;
			$("#WiFi_Name").addClass("error").removeClass("success");
			messageHandler("name", "Let's try that again", "Choose a different name than the one provided on your gateway.");
		}
		else if(!isXHS){
			goNextName	= false;
			$("#WiFi_Name").addClass("error").removeClass("success");
			messageHandler("name", "Let's try that again", 'SSID is invalid/reserved.');
		}
		else if(!isOther){
			goNextName	= false;
			$("#WiFi_Name").addClass("error").removeClass("success");
			messageHandler("name", "Let's try that again", 'SSID is invalid/reserved.');
		}
		else if(!isValid){
			goNextName	= false;
			$("#WiFi_Name").addClass("error").removeClass("success");
			messageHandler("name", "Let's try that again", "3 to 31 characters combined with alphabet, digit, underscore, hyphen and dot");
		}
		else {
			goNextName = true;
			$("#WiFi_Name").addClass("success").removeClass("error");
			messageHandler("name", "WiFi Name", "This identifies your WiFi network from other nearby networks.");
		}
		toShowNext();
	});

	$("#password_field").keyup(function() {
		/*
			return !param || /^[a-zA-Z0-9\-_.]{8,20}$/i.test(value); "8-20 characters. Alphanumeric only. No spaces. Case sensitive."
		*/

		//VALIDATION for WiFi_Password

		var val = $("#WiFi_Password").val();

		isValid	= /^[a-zA-Z0-9\-_.]{8,20}$/i.test(val);

		if(val == ""){
			goNextPassword	= false;
			$("#WiFi_Password").addClass("error").removeClass("success");
			messageHandler("password", "Let's try that again", "Please enter WiFi Password.");
		}
		else if("<?php echo $network_pass;?>" == val){
			goNextPassword	= false;
			$("#WiFi_Password").addClass("error").removeClass("success");
			messageHandler("password", "Let's try that again", "Choose a different password than the one provided on your gateway.");
		}
		else if(!isValid){
			goNextPassword	= false;
			$("#WiFi_Password").addClass("error").removeClass("success");
			messageHandler("password", "Let's try that again", "Passwords are case sensitive and should include 8-20 alphanumeric characters with no spaces.");
		}
		else {
			goNextPassword	= true;
			$("#WiFi_Password").addClass("success").removeClass("error");
			messageHandler("password", "WiFi Password", "Passwords are case sensitive and should include 8-20 alphanumeric characters with no spaces.");
		}
		toShowNext();
	});

	//to show password on click
	$("#showPass").change(function() {
		passwordVal = $("#WiFi_Password").val();
		classVal = $("#WiFi_Password").attr('class');

		if ($("#check").is(":checked")) {
			document.getElementById("password_field").innerHTML = '<input id="WiFi_Password" type="text" placeholder="Minimum Eight Characters" class="">';
			$("[id^='WiFi_Password_0']").show();
			$("[id^='WiFi_Password_pass_0']").hide();
			$("[id^='check']").prop('checked', true);
		}
		else {
			document.getElementById("password_field").innerHTML = '<input id="WiFi_Password" type="password" placeholder="Minimum Eight Characters" class="">';
			$("[id^='WiFi_Password_0']").hide();
			$("[id^='WiFi_Password_pass_0']").show();
			$("[id^='check']").prop('checked', false);
		}
		
		$("#WiFi_Password").val(passwordVal).addClass(classVal); 
	});

	$("[id^='showPass0']").change(function() {
		varid = this.id.split("0");
		if ($("#check_0"+varid[1]).is(":checked")) {
			$("[id^='WiFi_Password_0']").show();
			$("[id^='WiFi_Password_pass_0']").hide();
			$("[id^='check']").prop('checked', true);
		}
		else {
			$("[id^='WiFi_Password_0']").hide();
			$("[id^='WiFi_Password_pass_0']").show();
			$("[id^='check']").prop('checked', false);
		}
		$("#showPass").trigger("change");
	});

	//check all the check boxes by default
	$("[id^='check']").prop('checked', true);
	$("[id^='showPass0']").trigger("change");
	$("#showPass").trigger("change");

/*
	$( window ).resize(function() {
		$("#append").append( '<div class="container" >'+$(document).width()+'</div>');
		$("#topbar").width($(document).width());
	});
*/

});
</script>

	<body>
		<div id="topbar">
			<img src="cmn/img/logo.png"/>
		</div>
		<div id="personalize" class="portal">
			<h1>Personalize Your WiFi</h1>
			<p>
				Now, let’s get personal. Create a unique name and password for your WiFi.<br/>
				Choose something easy to remember.
			</p>
			<div id="NameContainer" class="container" style="display: none;">
				<div class="requirements">
					<div id="NameMessageTop" class="top">Let's try that again.</div>
					<div id="NameMessageBottom" class="bottom">Choose a different name than the one printed on your gateway.</div>
					<div class="arrow"></div>
				</div>
			</div>
<!-- debugging code to fix vertical view port issue in mobile  -->
<!--div id="append">WiFi Name</div-->
			<p>WiFi Name</p>
			<input id="WiFi_Name" type="text" placeholder="Example: [account name] WiFi" class="">
			<br/><br/><br/>
			<div id="PasswordContainer" class="container" style="display: none;">
				<div class="requirements">
					<div id="PasswordMessageTop" class="top">Let's try that again.</div>
					<div id="PasswordMessageBottom" class="bottom">Choose a different name than the one printed on your gateway.</div>
					<div class="arrow"></div>
				</div>
			</div>
			<p>WiFi Password</p>
			<span id="password_field"><input id="WiFi_Password" type="password" placeholder="Minimum Eight Characters" class=""></span>
			<div id="showPass" class="checkbox">
				<input id="check" type="checkbox" name="check">  
			    	<label for="check"></label> 
			    	<div class="check-copy">Display Password</div>
		    	</div>
			<br/><br/>
			<div>
				<button id="button_next" style="text-align: center; width: 215px; display: none;">Next</button>
			</div>
			<br/><br/>
		</div>
		<div id="confirm" style="display: none;" class="portal">
			<h1>Confirm WiFi Settings</h1>
			<hr>
			<p>WiFi Name</p>
			<div id="WiFi_Name_01" class="final-settings"></div>
			<p>WiFi Password</h2>
			<div id="WiFi_Password_01" style="display: none;" class="final-settings"></div>
			<div id="WiFi_Password_pass_01" class="final-settings"></div>
			<div id="showPass01" class="checkbox">
				<input id="check_01" type="checkbox" name="check_01">  
			    	<label for="check_01"></label>
			    	<div class="check-copy">Display Password</div>
		    	</div>
			<hr>
			<!--div id="link_example">
				<a href="javascript:void(0)">Share your new WiFi name and password with your family and friends. (optional)</a>
			</div>
			<div-->
				<button id="button_previous_01" class="transparent">Previous Step</button>&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp
				<button id="button_next_01">Next</button>
			</div>
			<br/><br/>
		</div>
		<div id="setup" style="display: none;" class="portal">
			<h1>Setup is Complete</h1>
			<p>Your WiFi will begin broadcasting in a minute or less.</p>
			<hr>
			<p>WiFi Name</p>
			<div id="WiFi_Name_02" class="final-settings"></div>
			<p>WiFi Password</h2>
			<div id="WiFi_Password_02" style="display: none;" class="final-settings"></div>
			<div id="WiFi_Password_pass_02"  class="final-settings"></div>
			<div id="showPass02" class="checkbox">
				<input id="check_02" type="checkbox" name="check_02">
			    	<label for="check_02"></label> 
			    	<div class="check-copy">Display Password</div>
		    	</div>
			<hr>
			<br/><br/>
		</div>
		<div id="confirm03" style="display: none;" class="portal">
			<h1>Confirm WiFi Settings</h1>
			<hr>
			<p>WiFi Name</p>
			<div id="WiFi_Name_03" class="final-settings"></div>
			<p>WiFi Password</h2>
			<div id="WiFi_Password_03" style="display: none;" class="final-settings"></div>
			<div id="WiFi_Password_pass_03" class="final-settings"></div>
			<div id="showPass03" class="checkbox">
				<input id="check_03" type="checkbox" name="check_03">
			    	<label for="check_03"></label>
			    	<div class="check-copy">Display Password</div>
		    	</div>
			<hr>
			<div id="link_example">
				<a href="javascript:void(0)">Share your new WiFi name and password with your family and friends. (optional)</a>
			</div>
			<div id="TextSMSContainer" class="container" style="display: none;">
				<div class="requirements" style="top: -7px;">
					<div id="TextSMSTop" class="top">Text (SMS)</div>
					<div id="TextSMSBottom" class="bottom">Textes are not encrypted. You can always view WiFi name/password under My Account instead.</div>
					<div class="arrow"></div>
				</div>
			</div>
			<h2>Text (SMS)</h2>
			<input id="TextSMS" type="text" placeholder="1(  )  -  " class=""><br/><br/>
			<div id="link_example" style="margin: 5px 0;">
				<a href="javascript:void(0)">Add More Mobile Numbers</a>
			</div><br/><br/>
			<div>
				<button id="button_previous_03" class="transparent">Back to "Previous"</button>&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp
				<button id="button_next_03">Next</button>
			</div>
			<br/><br/>
		</div>
		<div id="complete" style="display: none;" class="portal">
			<h1>Your WiFi is Nearly Complete</h1>
			<img class="rotate" src="cmn/img/progress.png"/>
			<div id="link_example">
				<p>We'll have this finished up shortly.<br/>
				Once complete, you can start connecting devices.</p>
			</div>
			<hr>
			<p>WiFi Name</p>
			<div id="WiFi_Name_04" class="final-settings"></div>
			<p>WiFi Password</h2>
			<div id="WiFi_Password_04" style="display: none;" class="final-settings"></div>
			<div id="WiFi_Password_pass_04" class="final-settings"></div>
			<div id="showPass04" class="checkbox">
				<input id="check_04" type="checkbox" name="check_04">
			    	<label for="check_04"></label>
			    	<div class="check-copy">Display Password</div>
		    	</div>
			<hr>
		</div>
		<div id="ready" style="display: none;" class="portal">
			<h1>Your WiFi is Ready</h1>
			<img src="cmn/img/success_lg.png"/>
			<div id="link_example">
				<p>Start using your new WiFi whenever you'd like.</p>
			</div>
			<hr>
			<p>WiFi Name</p>
			<div id="WiFi_Name_05" class="final-settings"></div>
			<p>WiFi Password</h2>
			<div id="WiFi_Password_05" style="display: none;" class="final-settings"></div>
			<div id="WiFi_Password_pass_05" class="final-settings"></div>
			<div id="showPass05" class="checkbox">
				<input id="check_05" type="checkbox" name="check_05">
			    	<label for="check_05"></label>
			    	<div class="check-copy">Display Password</div>
		    	</div>
			<hr>
		</div>
	</body>
</html>
