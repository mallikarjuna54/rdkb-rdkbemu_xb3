#!/bin/sh

INTERFACE=$1
DEFAULT_IP_ADDRESS=192.168.5.1
udhcpd_conf_file=/etc/udhcpd.conf
KEYWORD=router
#############################################################
#Set ipaddress for $1 inetrface
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


#########################
# Start Udhcpd Server
#########################
/usr/sbin/udhcpd  $udhcpd_conf_file


#######################################################################
#To Allow IP Forwarding for router
#######################################################################
echo 1 > /proc/sys/net/ipv4/ip_forward


#######################################################################
#configure iptables to forward the packets from your internal network,
#on /dev/eth1, to your external network on /dev/eth0
#######################################################################

echo "/usr/sbin/iptables -t nat -A POSTROUTING -o eth0 -j MASQUERADE"
/usr/sbin/iptables -t nat -A POSTROUTING -o eth0 -j MASQUERADE

echo "/usr/sbin/iptables -A FORWARD -i eth0 -o eth1 -m state  --state RELATED,ESTABLISHED -j ACCEPT"
/usr/sbin/iptables -A FORWARD -i eth0 -o eth1 -m state  --state RELATED,ESTABLISHED -j ACCEPT

echo "/usr/sbin/iptables -A FORWARD -i eth1 -o eth0 -j ACCEPT"
/usr/sbin/iptables -A FORWARD -i eth1 -o eth0 -j ACCEPT


