#include <stdio.h>
#include <string.h>
#include <sys/types.h>
#include <sys/socket.h>
#include <sys/ioctl.h>
#include <netinet/in.h>
#include <net/if.h>
#include<fcntl.h>
#include <unistd.h>
#include <arpa/inet.h>
#include "ccsp_hal_dhcpv4_emu_api.h"

/*Getting the dhcpv4 configuration (starting and ending)values */
int CcspHalGetConfigValue(char *key, char *value, int size)
{

        FILE *fp;
        char line[FILE_SIZE], *ptr;
        int result_found=0;

        fp =fopen(UDHCPD_CONF_FILE_PATH ,"r+");
        while(fgets(line,FILE_SIZE, fp) != NULL) {
                if((strstr(line, key)) != NULL) {
                       for(ptr = line; *ptr&&*ptr!=SPACE ;ptr++);
                        if(*ptr==SPACE)ptr++;
                       if(*ptr) result_found=1;
                       break;
                }
         }


        if(result_found == 0)
                printf("\nSorry, couldn't find a match.\n");
        else
                snprintf(value, size, "%s", ptr);
        close(fp);
        return 0;

}

/*Getting the dhcpv4 configuration(lease time)value */
int CcspHalGetConfigLeaseValue(char *key, char *value, int size)
{

        FILE *fp;
        char line[FILE_SIZE], *ptr;
        int result_found=0;

        fp =fopen(UDHCPD_CONF_FILE_PATH ,"r+");
        while(fgets(line,FILE_SIZE, fp) != NULL) {
                if((strstr(line, key)) != NULL) {
                       for(ptr = line; *ptr&&*ptr!=SPACE;ptr++);
                        if(*ptr==SPACE)ptr++;
                        for(*ptr;*ptr!=SPACE;ptr++);
                        if(*ptr==SPACE)ptr++;
		if(*ptr) result_found=1;
                       break;
                }
         }


        if(result_found == 0)
                printf("\nSorry, couldn't find a match.\n");
        else
                snprintf(value, size, "%s", ptr);
        close(fp);
        return 0;

}

/*passing the inputs to  dhcpv4 configuration file */
int CcspHal_change_config_value(char *field_name, char *field_value, char *buf, unsigned int *nbytes)
{
        int found=0, old_value_length, adjustment_bytes, count=0;
        char *p, *buf_base = buf, *value_start_pos;
        while(*buf)
        {
                for(;*buf&&(*buf=='\t'||*buf=='\n'||*buf==SPACE);buf++);
                p = field_name;
                for(;*buf&&*p&&!(*buf^*p); p++, buf++);
                if(!*p)
                {
                        printf("FOUND\n");
                        found = 1;
                        for(;*buf&&(*buf=='\t'||*buf=='\n'||*buf==SPACE);buf++);
                        printf("buf:%s\n", buf);
                        for(old_value_length=0;*buf&&*buf^NEW_LINE;buf++) old_value_length++;
                        break;
                }
                else
                {
                        printf("NOT FOUND\n");
                        for(;*buf&&*buf^NEW_LINE;buf++);
                        buf++;//going past \n
                }
        }

        if (!found)
        {
                printf("Invalid field name\n");
                return -1;
        }

        //KEEPING NOTE OF POSITION WHERE VALUE HAS TO BE CHANGED
        value_start_pos = buf-old_value_length;

        //FOR BUFFER ADJUSTMENTS
        adjustment_bytes = strlen(field_value)-old_value_length;// bytes to be adjusted either way
        *nbytes += adjustment_bytes;

        if(adjustment_bytes<0)
        {//shifting buffer content to left
                printf("NEGATIVE\n");
                for(;*buf;buf++)*(buf+adjustment_bytes) = *buf;
        }
        if(adjustment_bytes>0)
        {//shifting buffer content to right
                printf("POSITIVE\n");
                p = buf;
                for(;*buf;++buf);
		 buf--;//moving back to last character
         for(;buf>=p;buf--)*(buf+adjustment_bytes) = *buf;
        }
        while(*field_value) *value_start_pos++ = *field_value++; //replacing old value with new value.
        return 0;
}

/*Setting the inputs values to dhcpv4 configuration value  */
int CcspHalSetDHCPConfigValues(unsigned int value_flag, ConfigValues *config_value)
{
        char buf[FILE_SIZE] = "";//Must fill the buffer with zeroes
        int fd, nbytes, ret;
        struct stat file_stat={};
        if((fd = open(UDHCPD_CONF_FILE_PATH, 0|O_RDWR))==-1)
        {
                //perror("open(/etc/udhcpd.conf) failed");
                printf("open(/etc/udhcpd.conf) failed: %m\n");
                return -1;
        }

        if(fstat(fd, &file_stat)==-1)
        {//called for getting file size
                printf("stat failed: %m\n");
                return -1;
        }
        if(file_stat.st_size+BUFFER_ADJUSTMENT> sizeof buf) //+128 bytes reserved for buffer adjustments
        {//checking whether buf size is sufficient or not
                printf("Insufficient buffer size\n");
                return -1;
        }

        if((nbytes = read(fd, buf, sizeof buf))==-1)
        {//reading contents of the file.
                printf("read(/etc/udhcpd.conf) failed: %m\n");
                return -1;
        }

        if(value_flag&GATEWAY)
                ret = CcspHal_change_config_value("opt router ", config_value->gateway, buf, &nbytes);
        if(value_flag&SUBNET_MASK)
                ret = CcspHal_change_config_value("option subnet ",config_value->subnet, buf, &nbytes);
        if(value_flag&DHCP_STARTING_RANGE)
                ret = CcspHal_change_config_value("start ", config_value->start, buf, &nbytes);
        if(value_flag&DHCP_ENDING_RANGE)
                ret = CcspHal_change_config_value("end ", config_value->end, buf, &nbytes);
        if(value_flag&DHCP_LEASE_TIME)
                ret = CcspHal_change_config_value("option lease ", config_value->lease_time, buf, &nbytes);

        if(ret == -1)
	 {
             printf("change_config_value failed\n");
                return -1;
        }

        if(ftruncate(fd, 0)==-1)
        {
                printf("ftruncate failed: %m\n");
                return -1;
        }
        if(lseek(fd, 0, SEEK_SET)==-1)
        {
                printf("lseek failed: %m\n");
                return -1;
        }
        if(write(fd, buf, nbytes)==-1)
        {
                printf("write failed: %m\n");
                return -1;
        }
        system("killall udhcpd");
        system("udhcpd /etc/udhcpd.conf");
        return 0;
}

/* setting the eth1 interface(ip address) */
int CcspHalInterfacesetval(char *name,char *str)
	{

	struct ifreq ifr;
	int fd = socket(PF_INET, SOCK_DGRAM, IPPROTO_IP);
	strncpy(ifr.ifr_name, name, IFNAMSIZ);
	ifr.ifr_addr.sa_family = AF_INET;
    	inet_pton(AF_INET, str, ifr.ifr_addr.sa_data + 2);
    	ioctl(fd, SIOCSIFADDR, &ifr);
        return 0;

	}

/*setting the eth1 interface(netmask) */	

int CcspHalNetmasksetvalue(char *name,char *str)
	{
	
	struct ifreq ifr;
	int fd = socket(PF_INET, SOCK_DGRAM, IPPROTO_IP);
        strncpy(ifr.ifr_name, name, IFNAMSIZ);
	ifr.ifr_addr.sa_family = AF_INET;
    	inet_pton(AF_INET, str, ifr.ifr_addr.sa_data + 2);
    	ioctl(fd, SIOCSIFNETMASK, &ifr);
        return 0;

	}

/* Getting the process id of dhcp server */
int CcspHalGetPIDbyName(char* pidName)
	{

        FILE *fp;
        char pidofCmd[FILE_SIZE]={0};
        int pidValue=-1;
        if(pidName != 0) {
        strcpy(pidofCmd, DHCPv4_PID);
        strcat(pidofCmd, pidName);
        strcat(pidofCmd,  DHCP_PID);
        system(pidofCmd);
        fp = fopen(DHCP_PATH, "r");
        fscanf(fp, "%d", &pidValue);
        fclose(fp);
         }
       return pidValue;
 	
	}

/* To get number of client connected devices*/
ULONG CcspHalNoofClientConnected()
{
        uint32_t ip_integer;
        char str[INET_ADDRSTRLEN];
        FILE *fp;
        ULONG l=0;
        char cmd[FILE_SIZE]= {'\0'};
        char temp[FILE_SIZE],arr[FILE_SIZE],arr1[FILE_SIZE];
        int k,j=0,i;
        ip_integer=CosaUtilGetIfAddr("eth1");
        inet_ntop(AF_INET, &(ip_integer), str, INET_ADDRSTRLEN);
        sprintf(cmd,"%s %s%s %s","nmap -sP ",str,"/24 ","> /nmap.txt");
        printf("%s %s%s %s","nmap -sP ",str,"/24 ","> nmap.txt");
        system(cmd);
        fp = fopen(FILE_NAME,"r");
        while(fgets(temp,FILE_SIZE,fp)!=NULL){
                if(strstr(temp,HOSTS_MATCH)!=NULL){
                        for(k=0;temp[k];k++)
                                {
                                arr[k] = temp[k];
                                }
                                arr[k] = '\0';
                for(i=29;arr[i]!=' ';i++)//To get current position 
                {
                        arr1[j] = arr[i];
                        j++;
                }
                }
                else
                {
                if(strstr(temp,HOST_MATCH)!=NULL){
                        for(k=0;temp[k];k++)
                                {
                                arr[k] = temp[k];
                                }
                                arr[k] = '\0';
                for(i=29;arr[i]!=' ';i++)
                {
                        arr1[j] = arr[i];
                        j++;
                }
                }
                }
                }
                fclose(fp);
                l=atoi(arr1);
                l--;
                return l;
}




