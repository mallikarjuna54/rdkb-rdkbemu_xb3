#!/bin/sh

########## Creating Interface and Waiting for interface to be up ####################
brctl addbr brlan0

count=`ifconfig | grep brlan0 | wc -l`
echo "brlan-count=$count"

while [ $count == 0 ]
do
   sleep 10
   count=`ifconfig | grep brlan0 | wc -l`
done

count=`ifconfig | grep eth2 | wc -l`
echo "eth2-count=$count"

while [ $count == 0 ]
do
    sleep 10
    count=`ifconfig | grep eth2 | wc -l`
done



count=`ifconfig | grep eth1 | wc -l`
echo "eth1count=$count"

while [ $count == 0 ]
do
    sleep 10
    count=`ifconfig | grep eth1 | wc -l`
done


count=`ifconfig | grep wlan0 | wc -l`
echo "wlan0count=$count"

while [ $count == 0 ]
do
    sleep 10
    count=`ifconfig | grep wlan0 | wc -l`
done

##############################################################
##Setting IP Address For all interfaces 
##############################################################

ifconfig eth2 192.168.56.101 up


##### Add Wired Interface to Bridge interface ##############################
ifconfig eth1 192.168.1.115 up
brctl addif brlan0 eth1


######### Add Wireless interface to Bridge interface ######################
ifconfig wlan0 192.168.1.120 up
iw dev wlan0 set 4addr on
brctl addif brlan0 wlan0


########### Set ip Address for Bridge interface for udhcpd server##########
INTERFACE=brlan0
DEFAULT_IP_ADDRESS=192.168.7.1
udhcpd_conf_file=/etc/udhcpd.conf
KEYWORD=router
#############################################################
#Set ipaddress for brlan0 interface
#############################################################


if [  -f $udhcpd_conf_file ];then
 echo "getting router ip address from $udhcpd_conf_file"
 router_ip_address=`grep $KEYWORD $udhcpd_conf_file | cut -d ' ' -f 3`
 echo "set ip address as $router_ip_address for $INTERFACE"
 ifconfig $INTERFACE $router_ip_address
else
 echo "set ip address as default $DEFAULT_IP_ADDRESS for $INTERFACE"
  ifconfig $INTERFACE $DEFAULT_IP_ADDRESS
fi

rm -f wifi_clients.txt

###### Routing Table ##################################################### 
sh iptables.sh 
