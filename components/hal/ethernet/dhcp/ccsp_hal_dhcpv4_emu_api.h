#ifndef __DHCPV4_EMU_API_H__
#define __DHCPV4_EMU_API_H__



#define GATEWAY 1
#define SUBNET_MASK 2
#define DHCP_STARTING_RANGE 4
#define DHCP_ENDING_RANGE 8
#define DHCP_LEASE_TIME 16
#define UDHCPD_CONF_FILE_PATH "/etc/udhcpd.conf"
#define FILE_SIZE 1024
#define SPACE 32
#define NEW_LINE 10
#define BUFFER_ADJUSTMENT 128
#define DHCP_PID "> /tmp/pidof"
#define DHCP_PATH "/tmp/pidof"
#define DHCPv4_PID "pidof "
#define ULONG unsigned long


typedef struct config_values
{
        char *gateway;
        char *subnet;
        char *start;
        char *end;
        char *lease_time;
}ConfigValues;





/*Getting the dhcpv4 configuration (starting and ending)values */
int CcspHalGetConfigValue(char *key, char *value, int size);


/*Getting the dhcpv4 configuration(lease time)value */
int CcspHalGetConfigLeaseValue(char *key, char *value, int size);


/*passing the inputs to  dhcpv4 configuration file */
int CcspHal_change_config_value(char *field_name, char *field_value, char *buf, unsigned int *nbytes);


/*Setting the inputs values to dhcpv4 configuration value  */
int CcspHalSetDHCPConfigValues(unsigned int value_flag, ConfigValues *config_value);

/* setting the eth1 interface(ip address) */
int CcspHalInterfacesetval(char *name,char *str);

/*setting the eth1 interface(netmask) */

int CcspHalNetmasksetvalue(char *name,char *str);

/* Getting the process id of dhcp server */
int CcspHalGetPIDbyName(char* pidName);

/* Getting number of client connected devices*/
ULONG CcspHalNoofClientConnected();




#endif



