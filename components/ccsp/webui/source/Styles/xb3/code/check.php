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
    if (isset($_POST["username"]))
	{
		session_start();
		//echo("You are logging...");

		$client_ip		= $_SERVER["REMOTE_ADDR"];			// $client_ip="::ffff:10.0.0.101";
		$server_ip		= $_SERVER["SERVER_ADDR"];
		$timeout_val 		= intval(getStr("Device.X_CISCO_COM_DeviceControl.WebUITimeout"));
		("" == $timeout_val) && ($timeout_val = 900);
		$_SESSION["timeout"]	= $timeout_val - 60;	//dmcli param is returning 900, GUI expects 840 - then GUI adds 60
		$_SESSION["sid"]	= session_id();
		$_SESSION["loginuser"]	= $_POST["username"];

		/*=============================================*/
		// $dev_mode = true;
		if (file_exists("/var/ui_dev_mode")) {
			$_SESSION["timeout"] = 100000; 
			if ($_POST["password"] == "dev") {
				if ($_POST["username"] == "mso") {
					header("location:at_a_glance.php");
				}
				elseif ($_POST["username"] == "cusadmin") {
					header("location:at_a_glance.php");
				}	
				elseif ($_POST["username"] == "admin") {
					header("location:at_a_glance.php");
				}			
				return; 
			} 
		}
		/*===============================================*/

        if ($_POST["username"] == "mso")
	{
	   //triggering password validation in back end
	   setStr("Device.Users.User.1.X_CISCO_COM_Password",$_POST["password"],true);
	   sleep(1);
	   $curPwd1 = getStr("Device.Users.User.1.X_CISCO_COM_Password");
	   
	    if ( innerIP($client_ip) || (if_type($server_ip)=="rg_ip") )
            {
            	session_destroy();
                echo '<script type="text/javascript"> alert("Access denied!"); history.back(); </script>';
            }
            elseif ($curPwd1 == "Good_PWD")
            {	
            	exec("/usr/bin/logger -t GUI -p local5.notice 'User:mso login'");
                header("location:at_a_glance.php");
            }
            elseif ("" == $curPwd1)
            {
				session_destroy();
				echo '<script type="text/javascript"> alert("Can not get password for mso from backend!"); history.back(); </script>';
            }
            else
          	{
				session_destroy();
				echo '<script type="text/javascript"> alert("Incorrect password for mso!"); history.back(); </script>';
            }
        }
        elseif ($_POST["username"] == "cusadmin")
		{
		$curPwd2 = getStr("Device.Users.User.2.X_CISCO_COM_Password");
			if ( innerIP($client_ip) || (if_type($server_ip)=="rg_ip") )
			{
				session_destroy();
				echo '<script type="text/javascript"> alert("Access denied!"); history.back(); </script>';
			}
			elseif ($_POST["password"] == $curPwd2) 
            {
				exec("/usr/bin/logger -t GUI -p local5.notice 'User:cusadmin login'");
				header("location:at_a_glance.php");
			}
            elseif ("" == $curPwd2)
            {
				session_destroy();
				echo '<script type="text/javascript"> alert("Can not get password for cusadmin from backend!"); history.back(); </script>';
            }
            else
          	{
				session_destroy();
				echo '<script type="text/javascript"> alert("Incorrect password for cusadmin!"); history.back(); </script>';
            }
        }
        elseif ($_POST["username"] == "admin")
		{
			$curPwd3 = getStr("Device.Users.User.3.X_CISCO_COM_Password");
			if ($_POST["password"] == $curPwd3) 
            {
				if ( !innerIP($client_ip) && (if_type($server_ip)!="rg_ip") )
				{
					session_destroy();
					echo '<script type="text/javascript"> alert("Access denied!"); history.back(); </script>';
				}
				else
				{
					exec("/usr/bin/logger -t GUI -p local5.notice 'User:admin login'");
					if ($curPwd3 == 'password')
					{
						echo '<script type="text/javascript"> if (confirm("You are using a default password, would you like to change it?")) {location.href = "password_change.php";} else {location.href = "at_a_glance.php";} </script>';
					}
					else
					{
                        header("location:at_a_glance.php");
					}
				}
            }
            elseif ("" == $curPwd3)
            {
				session_destroy();
				echo '<script type="text/javascript"> alert("Can not get password for admin from backend!"); history.back(); </script>';
            }
            else
            {
				session_destroy();
				echo '<script type="text/javascript"> alert("Incorrect password for admin!"); history.back(); </script>';
            }
        }
        else
		{
			session_destroy();
			echo '<script type="text/javascript"> alert("Incorrect user name!"); history.back(); </script>';
        }
    }

	function innerIP($client_ip){		//for compatibility, $client_ip is not used
		$out		= array();
		$tmp		= array();
		$lan_ip		= array();
		$server_ip	= $_SERVER["SERVER_ADDR"];
		
		if (strpos($server_ip, ".")){		//ipv4, something like "::ffff:10.1.10.1"
			$tmp		= explode(":", $server_ip);
			$server_ip	= array_pop($tmp);
		}
		
		exec("ifconfig brlan0", $out);

		foreach ($out as $v){
			if (strpos($v, 'inet addr')){
				$tmp = explode('Bcast', $v);
				$tmp = explode('addr:', $tmp[0]);
				array_push($lan_ip, trim($tmp[1]));
			}
			else if (strpos($v, 'inet6 addr')){
				$tmp = explode('Scope', $v);
				$tmp = explode('addr:', $tmp[0]);
				$tmp = explode('/', $tmp[1]);
				array_push($lan_ip, trim($tmp[0]));
			}
		}
		
		return in_array($server_ip, $lan_ip);
	}

	function get_ips($if_name){
		$out = array();
		$tmp = array();
		$ret = array();
	
		exec("ip addr show ".$if_name, $out);
		
		foreach ($out as $v){
			if (strstr($v, 'inet')){
				$tmp = explode('/', $v);
				$tmp = explode(' ', $tmp[0]);
				array_push($ret, trim(array_pop($tmp)));
			}
		}
		return $ret;
	}
	
	function if_type($ip_addr){
		$tmp	= array();
		$lan_ip	= get_ips("brlan0");
		$cm_ip	= get_ips("wan0");
		
		if (strstr($ip_addr, ".")){		//ipv4, something like "::ffff:10.1.10.1"
			$tmp	 = explode(":", $ip_addr);
			$ip_addr = array_pop($tmp);
		}
		
		if (in_array($ip_addr, $lan_ip)){
			return "lan_ip";
		}
		else if (in_array($ip_addr, $cm_ip)){
			return "cm_ip";
		}
		else{
			return "rg_ip";
		}
		
		// print_r($lan_ip);
		// print_r($cm_ip);
	}


/*	
	function innerIP($client_ip)
	{
		if (strstr($client_ip, "192.168.100.") && ("bridge-static"==getStr("Device.X_CISCO_COM_DeviceControl.LanManagementEntry.1.LanMode")))
		{
			return true;
		}

		if (strpos($client_ip, "."))		//IPv4
		{
			$tmp0	= explode(":", $client_ip);
			$tmp1	= array_pop($tmp0);
			$client	= explode(".", $tmp1);
			$lanip4	= explode(".", getStr("Device.X_CISCO_COM_DeviceControl.LanManagementEntry.1.LanIPAddress"));
			$lanmsk	= explode(".", getStr("Device.X_CISCO_COM_DeviceControl.LanManagementEntry.1.LanSubnetMask"));
					
			for ($i=0; $i<4; $i++)
			{
				if (((int)$lanmsk[$i]&(int)$client[$i]) != ((int)$lanmsk[$i]&(int)$lanip4[$i]))
				{
					return false;
				}
			}	
		}
		else
		{
			$prefix_dm	= getStr("Device.RouterAdvertisement.InterfaceSetting.1.Prefixes");
			$client		= explode(":", $client_ip);		
			$prefix		= explode(":", $prefix_dm);
			$prelen		= explode("/", $prefix_dm);
			$intlen		= intval(array_pop($prelen))/16;
			
			($intlen < 1) && ($intlen = 1);

			if (strtolower($client[0]) != "fe80")
			{
				for ($i=0; $i<$intlen; $i++)
				{
					if ($client[$i]!=$prefix[$i])
					{
						return false;
					}
				}
			}
		}
		
		return true;
	}
*/
?>
