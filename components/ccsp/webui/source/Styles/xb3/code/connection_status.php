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
<?php include('includes/header.php'); ?>
<?php include('includes/utility.php'); ?>
<!-- $Id: connection_status.php 3158 2010-01-08 23:32:05Z slemoine $ -->

<div id="sub-header">
	<?php include('includes/userbar.php'); ?>
</div><!-- end #sub-header -->

<?php include('includes/nav.php'); ?>

<?php  

    $interface = getStr("com.cisco.spvtg.ccsp.pam.Helper.FirstDownstreamIpInterface");
    // $interface = "Device.IP.Interface.2.";
	
	// initial some variable to suppress some error
    $ipv6_local_addr = "";
    $ipv6_global_addr = "";
    $ipv6_DNS = "";
	$DNSv6Index = "";

    /*local ipv6 address */
    $idArr = explode(",", getInstanceIds($interface."IPv6Address."));
    foreach ($idArr as $key => $value) {
        $ipv6addr = getStr($interface."IPv6Address.$value.IPAddress");
        if (stripos($ipv6addr, "fe80::") !== false) {
          $ipv6_local_addr = $ipv6addr;
        }
        else{
          $ipv6_global_addr = $ipv6addr;
        }
    }

	/*ipv6 dns*/
	$idArr = explode(",", getInstanceIds("Device.DNS.Client.Server."));
    foreach ($idArr as $key => $value) {
        if ( !strcasecmp(php_getstr("Device.DNS.Client.Server.$value.Type"), "DHCPv6") ) {
            $DNSv6Index = $value;
            break;
        }
    }

    $ipv6_DNS = php_getstr("Device.DNS.Client.Server.$DNSv6Index.DNSServer");

    // $ipv6_local_addr = "fe80::250:f1ff:fe80:0";
    // $ipv6_global_addr = "2018:cafe:aaaa::fccc";
    // $ipv6_DNS = "2018:cafe::20c:29ff:fe97:fccc";

	// !!! move "get hotspot status" to edit_public.php, no change on the "Edit" link 

?>


<script type="text/javascript">
$(document).ready(function() {
    comcast.page.init("Gateway > Connection > Status", "nav-connection-status");

    var isBridge = "<?php echo $_SESSION["lanMode"]; ?>";

    if(isBridge == 'bridge-static'){
        $('.localIPNetwork *').addClass('disabled');
        $('.localBtn').click(function(e){
            e.preventDefault();
        }) ;
        //$('.localIPNetwork').remove();

        $('.private-wifi *').addClass('disabled');
        $('.private-wifi .btn').click(function(e) {
            e.preventDefault();
        });
    };

	if("Enabled"=="<?php echo $_SESSION["psmMode"]; ?>") {
		$(".wifi_section").remove();
		$(".moca_section").remove();
		$(".wifi_text").show();
		$(".moca_text").show();
	}

  /** tweack ipv6 address field according to its length **/
  var ipv6_max_len = 25;
  var local_v6 = "<?php echo $ipv6_local_addr; ?>";
  var global_v6 = "<?php echo $ipv6_global_addr; ?>";
  var ipv6_DNS = "<?php echo $ipv6_DNS; ?>";
  // console.log(local_v6.length);
  // console.log(global_v6.length);
  // console.log(ipv6_DNS.length);

  if(local_v6.length > ipv6_max_len){
    $('#local-v6-addr').removeClass('value').addClass('ipv6-style');
  }

  if(global_v6.length > ipv6_max_len){
    $('#global-v6-addr').removeClass('value').addClass('ipv6-style');
  }

  if(ipv6_DNS.length > ipv6_max_len){
    $('#v6-dns').removeClass('value').addClass('ipv6-style');
  }

});

</script>

<style type="text/css">

.ipv6-style{
  font-weight: bold;
  margin-left: 45px;
}

</style>

<div id="content" style="margin-bottom:100px;">
	<h1>Gateway > Connection > Status</h1>
	<div id="educational-tip">
			<p class="tip">View information about your network connections.</p>
			<p class="hidden">View and manage the settings for your local IP, Wi-Fi, MoCA and XFINITY networks.</p>
	</div>

  <div style="width:360px;float:left;"><!-- contain local ip, xfinity network, Moca -->
    <div class="module forms block localIPNetwork">
        <h2>Local IP Network</h2>
        <p class="button"><a tabindex='0' href="local_ip_configuration.php" class="btn localBtn">Edit</a></p>
        
                <div class="form-row ">
                    <span class="readonlyLabel">IP Address (IPv4):</span> <span class="value"> 
                         <?php echo php_getstr("Device.X_CISCO_COM_DeviceControl.LanManagementEntry.1.LanIPAddress"); ?>
                    </span>
                </div>

                <div class="form-row odd" >
                    <span class="readonlyLabel">Subnet mask:</span> <span class="value">
                     <?php echo php_getstr("Device.X_CISCO_COM_DeviceControl.LanManagementEntry.1.LanSubnetMask"); ?>
                    </span>
                </div>

                <div class="form-row ">
                        <span class="readonlyLabel">DHCPv4 Server:</span> <span class="value"> 
                  <?php if ( !strcasecmp("true", php_getstr("Device.DHCPv4.Server.Enable"))) 
                          echo "Enabled";
                          else  echo "Disabled";
                  ?> 
                        </span>
                </div>

                <div class="form-row odd">
                    <span class="readonlyLabel">DHCPv4 Lease Time:</span> 
                    <span class="value" >
                      <?php 
                      function div_mod($n, $m)
                      {
                        if (!is_numeric($n) || !is_numeric($m) || (0==$m))
                        {
                          return array(0, 0);
                        }

                        for($i=0; $n >= $m; $i++)
                        {
                          $n = $n - $m;
                        }

                        return array($i, $n);
                      }

                      function sec2dhms($sec)
                      {
                        
			if($sec == "-1") return "Forever";

			(!is_numeric($sec)) && ($sec = 0);

                        if($sec >= 604800 && $sec % 604800 == 0) return $sec/(604800)." Week";

			$tmp = div_mod($sec, 24*60*60);
                        $day = $tmp[0];

                        $tmp = div_mod($tmp[1], 60*60);
                        $hor = $tmp[0];

                        $tmp = div_mod($tmp[1],    60);
                        $min = $tmp[0];

                        return "${day}d:${hor}h:${min}m";
                      }
                       
                      $dhcp_lease_time = php_getstr("Device.DHCPv4.Server.Pool.1.LeaseTime");      
                      echo sec2dhms($dhcp_lease_time);

                      ?>
                    </span>
                </div>                

                <div class="form-row ">
                  <span class="readonlyLabel">Link Local Gateway Address (IPv6):</span> 
                  <span id="local-v6-addr" class="value"> 
                   <?php  echo $ipv6_local_addr; ?>
                  </span>
              </div>

              <div class="form-row odd">
                  <span class="readonlyLabel">Global Gateway Address (IPv6):</span> 
                  <span id="global-v6-addr" class="value">
                  <?php 
                      echo $ipv6_global_addr;
                  ?>
                </span>
              </div>    
              <div class="form-row ">
                  <span class="readonlyLabel">Delegated prefix:</span> <span class="value">
                   <?php 
                    echo php_getstr("Device.IP.Interface.1.IPv6Prefix.1.Prefix");
                   ?> 
                  </span>
              </div>
              <div class="form-row odd">
                  <span class="readonlyLabel">DHCPv6 Lease Time:</span> <span class="value">
                    <?php 
                       $dhcpV6_lease_time = php_getstr("Device.DHCPv6.Server.Pool.1.LeaseTime");      
                       echo sec2dhms($dhcpV6_lease_time);
                    ?>
                  </span>
              </div>
              <div class="form-row ">
                  <span class="readonlyLabel">IPV6 DNS:</span> 
                  <span id="v6-dns" class="value" >
                    <?php 
                       echo $ipv6_DNS;
                    ?>
                  </span>
              </div>            

              <div class="form-row odd">
                  <span class="readonlyLabel">No of Clients connected:</span> 
                  <span class="value">
                      <?php
                         echo php_getstr("Device.Hosts.X_CISCO_COM_ConnectedDeviceNumber"); 
                      ?>
                  </span>
              </div>
    </div><!-- end .module local ip network-->

    <div class="module forms block" style="margin-bottom:0px">
        <h2>XFINITY Network</h2>
        <p class="button"><a tabindex='0' href="comcast_network.php" class="btn">View</a></p>
        <div class="form-row">
        <span class="readonlyLabel">Internet:</span> <span class="value">
              <?php 
               $status = php_getstr("Device.X_CISCO_COM_CableModem.CMStatus"); 
               if ( !strcasecmp($status, "Operational") ){
                  echo "Active";
               }
               else{
                  echo "Inactive";                   
               }
              ?>
        </span>
        </div>
        <div class="form-row odd">
              <span class="readonlyLabel">WAN IP Address:</span> <span class="value">
              <?php
                  $interface = php_getstr("com.cisco.spvtg.ccsp.pam.Helper.FirstUpstreamIpInterface");
                  echo php_getstr( $interface . "IPv4Address.1.IPAddress" );
              ?>
             </span>
        </div>
        <div class="form-row">
        <span class="readonlyLabel">DHCP Client:</span> <span class="value"> 
        <?php 
         if("DHCP" == php_getstr("Device.X_CISCO_COM_DeviceControl.WanAddressMode")) 
             echo "Enabled";
         else
             echo "Disabled";
        ?>
        </span>
        </div>

        <div class="form-row odd">
          <span class="readonlyLabel">DHCP Expire Time:</span> <span class="value">
                    <?php

                    $expire_time = php_getstr("Device.DHCPv4.Client.1.LeaseTimeRemaining");
                    echo sec2dhms($expire_time);

                    ?>
                </span>
        </div>
    </div><!-- end .module xfinity network-->
 
    <div class="module forms block moca_section" style="position:relative;top:7px;right:0px;">
            <h2>MoCA</h2>
            <p class="button"><a tabindex='0' href="moca.php" class="btn">Edit</a></p>

            <div class="form-row odd">
              <span class="readonlyLabel">MoCA Network:</span> <span class="value">
                <?php 
                    if("true" == php_getstr("Device.MoCA.Interface.1.Enable")) 
                    echo "Active";
                    else echo "Inactive";
                ?>
              </span>
            </div>

            <div class="form-row">
              <span class="readonlyLabel">MoCA Privacy:</span> 
              <span class="value">
                <?php 
                    if("true" == php_getstr("Device.MoCA.Interface.1.PrivacyEnabledSetting")) 
                    echo "Enabled";
                    else echo "Disabled";
                ?>

              </span>
            </div>

            <div class="form-row odd ">
              <span class="readonlyLabel">MoCA Channel:</span> <span class="value">
			<?php
			$channel		= php_getstr("Device.MoCA.Interface.1.CurrentOperFreq");			
			switch ($channel)
			{
			//MoCA 1.1
			case "1150":
				echo "D1 (1150 MHz)";
				break;  
			case "1200":
				echo "D2 (1200 MHz)";
				break;  
			case "1250":
				echo "D3 (1250 MHz)";
				break;
			case "1300":
				echo "D4 (1300 MHz)";
				break;
			case "1350":
				echo "D5 (1350 MHz)";
				break;
			case "1400":
				echo "D6 (1400 MHz)";
				break;
			case "1450":
				echo "D7 (1450 MHz)";
				break;
			case "1500":
				echo "D8 (1500 MHz)";
				break;
			case "1550":
				echo "D9 (1550 MHz)";
				break;
			case "1600":
				echo "D10 (1600 MHz)";
				break;
			//MoCA 2.0
			case "1175":
				echo "D1a (1175 MHz)";
				break;
			case "1225":
				echo "D2a (1225 MHz)";
				break;
			case "1275":
				echo "D3a (1275 MHz)";
				break;
			case "1325":
				echo "D4a (1325 MHz)";
				break;
			case "1375":
				echo "D5a (1375 MHz)";
				break;
			case "1425":
				echo "D6a (1425 MHz)";
				break;
			case "1475":
				echo "D7a (1475 MHz)";
				break;
			case "1525":
				echo "D8a (1525 MHz)";
				break;
			case "1575":
				echo "D9a (1575 MHz)";
				break;
			case "1625":
				echo "D10a (1625 MHz)";
				break;
			default:
				echo "D1 (1150 MHz)";
			}
			?>
            </span>
            </div>

            <div class="form-row ">
              <span class="readonlyLabel">No of Nodes:</span> <span class="value">
               <?php echo intval(php_getstr("Device.MoCA.Interface.1.AssociatedDeviceNumberOfEntries"))+1; ?>
             </span>
            </div>
            <div class="form-row odd">
              <span class="readonlyLabel">No of Clients Connected:</span> <span class="value">
               <?php echo php_getstr("Device.MoCA.Interface.1.X_CISCO_COM_NumberOfConnectedClients"); ?>
              </span>
            </div>
    </div><!-- end .module MoCA -->
  </div>

  <div style="width:355px;float:left;position:relative;left:5px;" class="wifi_section"><!-- contain private and public Wi-Fi -->
    <div class="module forms block private-wifi">
        <h2>Private Wi-Fi Network-<?php echo php_getstr("Device.WiFi.SSID.1.SSID"); ?></h2>
        <p class="button"><a tabindex='0' href="wireless_network_configuration_edit.php?id=1" class="btn">Edit</a></p>
        <div class="form-row">
          <span class="readonlyLabel">Wireless Network (Wi-Fi 2.4 GHz):</span> <span class="value">
          <?php 
              if("true" == php_getstr("Device.WiFi.SSID.1.Enable")) 
                echo "Active";
              else
                echo "Inactive";
          ?>
          </span>
        </div>

        <div class="form-row odd">
          <span class="readonlyLabel">Supported Protocols:</span> <span class="value">
          <?php echo strtoupper(php_getstr("Device.WiFi.Radio.1.OperatingStandards")); ?>
          </span>
        </div>

		<div class="form-row">
			<span class="readonlyLabel">Security:</span> <span class="value">
			<?php 
				//echo php_getstr("Device.WiFi.AccessPoint.2.Security.ModeEnabled");
				$encrypt_mode	= php_getstr("Device.WiFi.AccessPoint.1.Security.ModeEnabled");
				$encrypt_method	= php_getstr("Device.WiFi.AccessPoint.1.Security.X_CISCO_COM_EncryptionMethod");
				//echo encrypt_map($encrypt_mode, $encrypt_method);
				echo getStr("Device.WiFi.AccessPoint.1.Security.ModeEnabled");
				echo "-";
				echo getStr("Device.WiFi.AccessPoint.1.Security.X_CISCO_COM_EncryptionMethod");
			?>
			</span>
		</div>

        <div class="form-row odd">
          <span class="readonlyLabel">No of Clients connected:</span> <span class="value">
          <?php echo php_getstr("Device.WiFi.AccessPoint.1.AssociatedDeviceNumberOfEntries"); ?> 
          </span>
        </div>
    </div><!-- end .module private wifi 2.4-->  
     
    <div class="module forms block private-wifi" style="position:relative;top:0px;right:0px;">
        <h2>Private Wi-Fi Network-<?php echo php_getstr("Device.WiFi.SSID.2.SSID"); ?> </h2>

        <p class="button"><a tabindex='0' href="wireless_network_configuration_edit.php?id=2" class="btn">Edit</a></p>
        <div class="form-row">
          <span class="readonlyLabel">Wireless Network (Wi-Fi 5 GHz):</span> <span class="value">
          <?php 
              if("true" == php_getstr("Device.WiFi.SSID.2.Enable")) 
                echo "Active";
              else
                echo "Inactive";
          ?>
          </span>
        </div>

        <div class="form-row odd">
          <span class="readonlyLabel">Supported Protocols:</span> <span class="value">
          <?php echo strtoupper(php_getstr("Device.WiFi.Radio.2.OperatingStandards")); ?>
          </span>
        </div>

		<div class="form-row">
			<span class="readonlyLabel">Security:</span> <span class="value">
			<?php 
				//echo php_getstr("Device.WiFi.AccessPoint.2.Security.ModeEnabled");
				$encrypt_mode	= php_getstr("Device.WiFi.AccessPoint.2.Security.ModeEnabled");
				$encrypt_method	= php_getstr("Device.WiFi.AccessPoint.2.Security.X_CISCO_COM_EncryptionMethod");
				echo encrypt_map($encrypt_mode, $encrypt_method);
			?>
			</span>
		</div>

        <div class="form-row odd">
          <span class="readonlyLabel">No of Clients connected:</span> <span class="value">
          <?php echo php_getstr("Device.WiFi.AccessPoint.2.AssociatedDeviceNumberOfEntries"); ?>
          </span>
        </div>
    </div><!-- end .module private wifi 5 --> 

<!--HomeSecurity wifi start-->    
	<?php
	$ssids 		= explode(",", php_getinstanceids("Device.WiFi.SSID."));
	$public_v	= array();
	$odd 		= true;
	// xb3-1.6 remove homesecurity wifi for now
	// $ssids 		= explode(",", "");
	
	// hide homesecurity for cusadmin
	if ("mso" != $_SESSION["loginuser"]) {
		$ssids	= array();
	}

	foreach ($ssids as $i)
	{
		if (intval($i)<3 || intval($i)>4){		//SSID 1,2 for Private, 3,4 for Home Security, 5,6 for Hot Spot
			continue;
		}
		$freq_id = strpos(php_getstr("Device.WiFi.SSID.$i.LowerLayers"), "Radio.1") ? "1" : "2";
		array_push($public_v, array(
			'ssid_id'		=> $i,
			'ssid_enable'	=> php_getstr("Device.WiFi.SSID.$i.Enable"),
			'ssid_name'		=> php_getstr("Device.WiFi.SSID.$i.SSID"),
			'radio_mode'	=> strtoupper(php_getstr("Device.WiFi.Radio.$freq_id.OperatingStandards")),
			'radio_freq'	=> ("1"==$freq_id) ? "2.4" : "5",
			'client_cnt'	=> php_getstr("Device.WiFi.AccessPoint.$i.AssociatedDeviceNumberOfEntries"),
			'security'		=> encrypt_map(php_getstr("Device.WiFi.AccessPoint.$i.Security.ModeEnabled"), php_getstr("Device.WiFi.AccessPoint.$i.Security.X_CISCO_COM_EncryptionMethod"))
		));
	}

	for ($j=0; $j<count($public_v); $j++)
	{
		$wifi_enable = "Inactive";
		if("true" == $public_v[$j]['ssid_enable']) 
		        $wifi_enable = "Active";
		else
		        $wifi_enable = "Inactive";
				
		echo '<div class="module forms block" style="position:relative;top:0px;right:0px;">';
		echo '<h2>HomeSecurityNetwork-'.$public_v[$j]['ssid_name'].'</h2>';
		// !!!dont goto edit_public page!!! thant page just for hotspot tunnel configuration
		echo '<p class="button"><a tabindex="0" class="btn" href="wireless_network_configuration_edit.php?id='.$public_v[$j]['ssid_id'].'">Edit</a></p>';
		echo '<div class="form-row '.(($odd=!$odd)?'odd':'').'"><span class="readonlyLabel">Wireless Network (Wi-Fi '.$public_v[$j]['radio_freq'].' GHz):</span> <span class="value">'.$wifi_enable.'</span></div>';
		echo '<div class="form-row '.(($odd=!$odd)?'odd':'').'"><span class="readonlyLabel">Supported Protocols:</span> <span class="value">'.$public_v[$j]['radio_mode'].'</span></div>';
		echo '<div class="form-row '.(($odd=!$odd)?'odd':'').'"><span class="readonlyLabel">Security:</span> <span class="value">'.$public_v[$j]['security'].'</span></div>';
		echo '<div class="form-row '.(($odd=!$odd)?'odd':'').'"><span class="readonlyLabel">No of Clients connected:</span> <span class="value">'.$public_v[$j]['client_cnt'].'</span></div>';
		echo '</div>';
	}
	?>  
<!--HomeSecurity wifi end-->

<!--HotSpot wifi start-->    
	<?php
	$ssids 		= explode(",", php_getinstanceids("Device.WiFi.SSID."));
	$public_v	= array();
	$odd 		= true;
	// xb3-1.6 add hotspot wifi
	// $ssids 		= explode(",", "");
	
	// hide hotspot for cusadmin
	if ("mso" != $_SESSION["loginuser"]) {
		$ssids	= array();
	}

	foreach ($ssids as $i)
	{
		if (intval($i)<5 || intval($i)>6){		//SSID 1,2 for Private, 3,4 for Home Security, 5,6 for Hot Spot
			continue;
		}
		$freq_id = strpos(php_getstr("Device.WiFi.SSID.$i.LowerLayers"), "Radio.1") ? "1" : "2";
		
		$RemoteEndpoints	= php_getstr("Device.X_COMCAST_COM_GRE.Interface.1.RemoteEndpoints");	// 2.4G and 5G share one gre tunnel
		$RemoteEndpointsV4	= array();
		$RemoteEndpointsV6	= array();
		$ips = explode(",", $RemoteEndpoints);
		foreach($ips as $v){
			if (strpos($v, ".")){
				array_push($RemoteEndpointsV4, $v);
			}
			else if (strpos($v, ":")){
				array_push($RemoteEndpointsV6, $v);
			}
		}
		$wlan_gw = "";
		if (isset($RemoteEndpointsV4[0])) $wlan_gw = $RemoteEndpointsV4[0];
		if (isset($RemoteEndpointsV6[0])) $wlan_gw = $wlan_gw."/".$RemoteEndpointsV6[0];
	
		array_push($public_v, array(
			'ssid_id'		=> $i,
			'ssid_enable'	=> php_getstr("Device.WiFi.SSID.$i.Enable"),
			'ssid_name'		=> php_getstr("Device.WiFi.SSID.$i.SSID"),
			'xf_capable'	=> php_getstr("Device.DeviceInfo.X_COMCAST-COM_xfinitywifiCapableCPE"),
			'time_last'		=> sec2dhms(php_getstr("Device.X_COMCAST_COM_GRE.Interface.1.LastChange")),	// 2.4G and 5G share one gre tunnel
			'wlan_gw'		=> $wlan_gw,
			'radio_mode'	=> strtoupper(php_getstr("Device.WiFi.Radio.$freq_id.OperatingStandards")),
			'radio_freq'	=> ("1"==$freq_id) ? "2.4" : "5",
			'client_cnt'	=> php_getstr("Device.WiFi.AccessPoint.$i.AssociatedDeviceNumberOfEntries"),
			'security'		=> encrypt_map(php_getstr("Device.WiFi.AccessPoint.$i.Security.ModeEnabled"), php_getstr("Device.WiFi.AccessPoint.$i.Security.X_CISCO_COM_EncryptionMethod"))
		));
	}

	for ($j=0; $j<count($public_v); $j++)
	{
		echo '<div class="module forms block tr_hotspot" style="position:relative;top:0px;right:0px;">';
		echo '<h2>Public Wi-Fi Network-'.$public_v[$j]['ssid_name'].'</h2>';
		echo '<p class="button"><a class="btn" href="wireless_network_configuration_edit_public.php?id='.$public_v[$j]['ssid_id'].'">Edit</a></p>';
		echo '<div class="form-row '.(($odd=!$odd)?'odd':'').'"><span class="readonlyLabel">Xfinity Wi-Fi Capable:</span> <span class="value">'.("true"==$public_v[$j]['xf_capable']?"Yes":"No").'</span></div>';
		echo '<div class="form-row '.(($odd=!$odd)?'odd':'').'"><span class="readonlyLabel">Wireless Network (Wi-Fi '.$public_v[$j]['radio_freq'].' GHz):</span> <span class="value">'.("true"==$public_v[$j]['ssid_enable']?"Active":"Inactive").'</span></div>';
		echo '<div class="form-row '.(($odd=!$odd)?'odd':'').'"><span class="readonlyLabel">Time Since Last Status:</span> <span class="value">'.$public_v[$j]['time_last'].'</span></div>';
		echo '<div class="form-row '.(($odd=!$odd)?'odd':'').'"><span class="readonlyLabel">WLAN Gateway:</span> <span class="value">'.$public_v[$j]['wlan_gw'].'</span></div>';
		echo '<div class="form-row '.(($odd=!$odd)?'odd':'').'"><span class="readonlyLabel">Supported Protocols:</span> <span class="value">'.$public_v[$j]['radio_mode'].'</span></div>';
		echo '<div class="form-row '.(($odd=!$odd)?'odd':'').'"><span class="readonlyLabel">Security:</span> <span class="value">'.$public_v[$j]['security'].'</span></div>';
		echo '<div class="form-row '.(($odd=!$odd)?'odd':'').'"><span class="readonlyLabel">No of Clients connected:</span> <span class="value">'.$public_v[$j]['client_cnt'].'</span></div>';
		echo '</div>';
	}
	?>  
<!--HotSpot wifi end-->
	
	</div>

	<div class="module forms block wifi_text" style="display: none;">
		<h2>No Wi-Fi information available</h2>
		<strong>Gateway operating in battery mode.</strong>
	</div> <!-- end .module -->	

	<div class="module forms block moca_text" style="display: none;">
		<h2>No MoCA information available</h2>
		<strong>Gateway operating in battery mode.</strong>
	</div> <!-- end .module -->	  

</div><!-- end #content -->


<?php include('includes/footer.php'); ?>
