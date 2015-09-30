#!/bin/sh

INTERFACE=$1
DEFAULT_IP_ADDRESS=192.168.5.1
#############################################################
#Set ipaddress for $1 inetrface
#############################################################

router_ip_address=`ifconfig eth1 | grep inet | tr -s ' ' | cut -d ' ' -f3 | sed -e 's/addr://g'`
echo $router_ip_address
if [  -z $router_ip_address ]; then
 router_ip_address=$DEFAULT_IP_ADDRESS
 ifconfig $INTERFACE $DEFAULT_IP_ADDRESS
 echo "ifconfig $INTERFACE $DEFAULT_IP_ADDRESS"
 echo "set Default IP Adress $DEFAULT_IP_ADDRESS"
else
 ifconfig $INTERFACE $router_ip_address
 echo "set IP Adress $DEFAULT_IP_ADDRESS for $INTERFACE"
fi

#######################################################################
#To Allow IP Forwarding
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


