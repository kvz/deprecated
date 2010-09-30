#!/bin/bash
#/*
#*** Summary:
#* A simple firewalling utility.  Use it to secure your internet server.
#*
#* This bash script can be used to protect servers against hack attempts,
#* it genrates IPTABLES rules according to your access specifications
#* to allow certain traffic, all other traffic will be blocked.
#* You can specify private ports (only accessible from a couple IPs)
#* and public ports (accessible for everyone).
#* Outgoing and related traffic will always be allowd
#* Script works fine, but do not install it on a LVS loadbalancer because it will frustrate direct routing.
#*
#*** Example:
#**  Usage
#* ./ksecure_firewall.bash
#**  Outputs
#* OK
#*
#*** Info:
#*  @author      Kevin van Zonneveld <kevin@vanzonneveld.net>
#*  @version     0.860
#*  @link        http://kevin.vanzonneveld.net
#*/

#
# /sbin/iptables -A PREROUTING -t nat -p tcp --destination $RIP --dport $DPORT -j REDIRECT --to $RPORT
#
set +x

function missing_cmd(){
    COMMAND="${1}"
    COMMAND_UC=$(echo ${COMMAND} | tr 'a-z' 'A-Z')
    PACKAGE="${2}"
    MODE="${3}"
    [ -n ${PACKAGE} ] || PACKAGE=${COMMAND}

    if [ "${MODE}" == "install" ];then
	dia_YesNo "Missing Package" "Do you want to try to automatically install ${PACKAGE}?"
	if [ "${dia_ret}" == 1 ]; then
	    aptitude install -y ${PACKAGE}
	else
	    echo "fatal: I need this package in order to function"
	    exit 1
	fi

	# second check
	TMPCMD=$(which ${COMMAND})
	if [ -n "${TMPCMD}" ] && [ -x ${TMPCMD} ];then
	    eval "CMD_${COMMAND_UC}=\"${TMPCMD}\""
	else
	    echo "fatal: I still cannot find ${COMMAND}, but it is required to run this script"
	    exit 1
	fi
    elif [ "${MODE}" == "run" ];then
	echo "fatal: The command ${COMMAND} is missing! Please first install ${PACKAGE}"
	exit 1
    fi
}

function dia_YesNo(){
    # arg1    = title
    # arg2    = description
    xtra=""
    if [ "${3}" == "0" ]; then
	xtra="--defaultno"
    fi

    if [ -n "${CMD_DIALOG}" ] && [ -x ${CMD_DIALOG} ]; then
	${CMD_DIALOG} ${xtra} --title "${1}" --clear \
	    --yesno "${2}" 10 70

	case $? in
	    0)
		dia_ret=1;;
	    1)
		dia_ret=0;;
	    255)
		clear
		echo "ESC pressed."
		exit 0
	    ;;
	esac
    else
	while true; do
	    echo -n "${1}. ${2} (Y/n) "
	    read yn
	    case $yn in
		"y" | "Y" | "" )
		    dia_ret=1
		    break ;;
		"n" | "N" )
		    dia_ret=0
		    break ;;
		* ) echo "unknown response.  Asking again" ;;
	    esac
	done
    fi
}

function dia_Input(){
    # arg1    = title
    # arg2    = description
    # arg3    = default
    ${CMD_DIALOG} --title "${1}" --clear \
	--inputbox "${2}" 16 71 "${3}" 2> $tempfile

    retval=$?

    case $retval in
	0)
	    dia_ret=$(cat $tempfile);;
	1)
	    clear
	    echo "Cancel pressed."
	    exit 0
	;;
	255)
	    if test -s $tempfile ; then
		cat $tempfile
	    else
		clear
		echo "ESC pressed."
		exit 0
	    fi
	;;
    esac
}

function dia_Select(){
    # arg1    = title
    # arg2    = description
    # arg3-8  = menu
    CHOICES=""
    [ -z "${3}" ] || CHOICES="${CHOICES}${3} <-"
    [ -z "${4}" ] || CHOICES="${CHOICES} ${4} <-"
    [ -z "${5}" ] || CHOICES="${CHOICES} ${5} <-"
    [ -z "${6}" ] || CHOICES="${CHOICES} ${6} <-"
    [ -z "${7}" ] || CHOICES="${CHOICES} ${7} <-"
    [ -z "${8}" ] || CHOICES="${CHOICES} ${8} <-"

    ${CMD_DIALOG} --clear --title "${1}" \
	--menu "${2}" 16 51 6 \
	${CHOICES} 2> $tempfile

    retval=$?

    choice=`cat $tempfile`
    case $retval in
	0)
	    dia_ret=${choice};;
	1)
	    clear
	    echo "Cancel pressed."
	    exit 0
	;;
	255)
	    clear
	    echo "ESC pressed."
	    exit 0
	;;
    esac
}


function dia_Checklist(){
    # arg1    = title
    # arg2    = info
    # arg@    = choices (tag, name, value)
    CHOICES=$3

    ${CMD_DIALOG} --title "${1}" --clear \
	--checklist "${2}" 26 55 17 \
	${CHOICES} 2> $tempfile

    retval=$?

    choice=`cat $tempfile`
    case $retval in
	0)
	    dia_ret=${choice};;
	1)
	    clear
	    echo "Cancel pressed."
	    exit 0
	;;
	255)
	    #clear
	    echo "ESC pressed."
	    exit 0
	;;
    esac
}

function diaS_Services(){
    policy="${1}"
    policy_human="${2}"
    policy_confg=$(echo ${policy} |tr '[:lower:]' '[:upper:]')
    services=$(running_Services ${policy})
    dia_Checklist "${policy} services" "Select which services should be ${policy}. That means ${policy_human}" "${services}"
    udpcnt=0;tcpcnt=0;
    for openport in ${dia_ret};do
	prot=$(echo ${openport} |sed 's#"##g' |awk -F'_' '{print $1}')
	port=$(echo ${openport} |sed 's#"##g' |awk -F'_' '{print $2}')

	if [ "${prot}" == "UDP" ]; then
	    echo "PORTS_${prot}_${policy_confg}[${udpcnt}]=\"${port}\"" >> ${APP_CFGFLTMP}
	    let "udpcnt=${udpcnt}+1"
	else

	    echo "PORTS_${prot}_${policy_confg}[${tcpcnt}]=\"${port}\"" >> ${APP_CFGFLTMP}
	    let "tcpcnt=${tcpcnt}+1"
	fi
    done



    echo "" >> ${APP_CFGFLTMP}
}
function running_Services(){
    policy="${1}"

    netstat -tupln |egrep '[0-9]' | sed 's#tcp6#tcp#g' | sed 's#udp6#udp#g' |sed 's#LISTEN##g' | sed 's#tcp#TCP#g' | sed 's#udp#UDP#g' |sed 's#::#0.0.0.0#g' |sed 's#\(/\|:\)# #g' |awk "{
	type=\"${policy}\";valu=\"off\";
	prot=\$1;
	port=\$5;
	srvc=\$9;
	if(srvc && srvc!=\"-\" && srvc!=\"*\"){
	    if(type==\"open\" && ( \
		srvc==\"svnserve\" || \
		srvc==\"named\" || \
		srvc==\"named\" || \
		port==\"80\" || \
		port==\"443\" || \
		port==\"21\" || \
		port==\"25\" || \
		port==\"110\" || \
		port==\"143\" || \
		port==\"123\" ) \
	    ){
		valu=\"on\";
	    }
	    if(type==\"private\" && ( \
		port==\"22\" || \
		port==\"81\" || \
		port==\"161\" || \
		port==\"4949\" || \
		port==\"541\" || \
		port==\"873\" || \
		port==\"2049\" ) \
	    ){
		valu=\"on\";
	    }
	    print prot\"_\"port\" \"srvc\" \"valu;
	}
    }" |  sort -nt_ -k2 |uniq
}

function diaS_Ipaddresses(){
    policy="${1}"
    policy_human="${2}"
    policy_confg=$(echo ${policy} |tr '[:lower:]' '[:upper:]')
    services=$(loggedin_Ipaddresses ${policy})
    dia_Checklist "${policy} machines" "Select which ip addresses should be ${policy}. That means ${policy_human}" "${services}"
    ipcnt=0;
    for ip in ${dia_ret};do
	ip=$(echo ${ip} |sed 's#"##g')
	echo "IPDRS_${policy_confg}[${ipcnt}]=\"${ip}\"" >> ${APP_CFGFLTMP}
	let "ipcnt=${ipcnt}+1"
    done

    while true; do
	dia_YesNo "${policy} machines" "Add another custom ${policy} machine?" "0"
	if [ "${dia_ret}" == 1 ]; then
	    dia_Input "${policy} machines" "Please specify ip address" ""
	    ip=$(echo ${dia_ret} |sed 's#[^][0-9.]##g')
	    if [ -n "${ip}" ]; then
		echo "IPDRS_${policy_confg}[${ipcnt}]=\"${ip}\"" >> ${APP_CFGFLTMP}
		let "ipcnt=${ipcnt}+1"
	    fi
	else
	    break;
	fi
    done


    echo "" >> ${APP_CFGFLTMP}
}
function loggedin_Ipaddresses(){
    policy="${1}"
    ips=""

    if [ "${policy}" == "open" ]; then
	# try to get the local network range
	if [ -f /etc/network/interfaces ] && [ -n "${CMD_IPCALC}" ]; then
	    for (( i = 0 ; i < ${#NET_ETH[@]} ; i++ ));do
		if [ "${NET_IP[$i]}" != "" ]; then
		    ips="${ips} ${NET_RANGE[$i]}"
		fi
	    done
	fi

	# get list of ips from the 'last' command
	# sorted on occurance.
	ips="${ips} $(last -ai |awk '{if($10 && $10!="0.0.0.0")print $10}' |sort | uniq -c | sort -nr |awk '{print $2}'|sed 's#[^][0-9.]##g' |head -n5)"
    else
	if [ -f /etc/munin/munin-node.conf ]; then
	    # try to get private ips from munin
	    ips="${ips} $(cat /etc/munin/munin-node.conf |grep allow |grep '\^' |sed 's#[^][0-9.]##g' |grep -v '127.0.0.1' |head -n5)"
	fi

	if [ -f /etc/apache2/apache2.conf ]; then
	    # try to get private ips from munin
	    ips="${ips} $(cat /etc/apache2/apache2.conf |grep -i 'allow from' | egrep -v "^#.*" |egrep -v "^$" |grep -iv 'from all' |awk '{print $3}' |sed 's#[^][0-9.\/]##g' |grep -v '127.0.0.1' |head -n5)"
	fi

	if [ -f /var/log/vsftpd.log ]; then
	    # try to get private ips from vsftpd
	    if [ `tail -n30 /var/log/vsftpd.log |grep 'Client "' |wc -l` -gt 2 ];then
		ips="${ips} $(cat /var/log/vsftpd.log |awk '{print $12}' |sed 's#[^][0-9.]##g' |sort | uniq -c | sort -nr |awk '{print $2}' |head -n5)"
	    else
		ips="${ips} $(cat /var/log/vsftpd.log |awk '{print $7}' |sed 's#[^][0-9.]##g' |sort | uniq -c | sort -nr |awk '{print $2}' |head -n5)"
	    fi
	fi

	if [ -z "${ips}" ]; then
	    # fallback to 'last' command
	    ips="${ips} $(last -ai |awk '{if($10 && $10!="0.0.0.0")print $10}' |sort | uniq -c | sort -nr |awk '{print $2}'|sed 's#[^][0-9.]##g' |head -n5)"
	fi
	if [ -z "${ips}" ] || [ "${ips}" == " " ] || [ "${ips}" == "  " ]; then
	    # fallback to localhost
	    ips="127.0.0.1"
	fi
    fi

    cnt=0;
    for ip in ${ips};do
	nam=$(getHostByAddr ${ip})
	echo -n "${ip} ${nam} "

	if [ ${cnt} -lt 2 ];then
	    echo  "on"
	else
	    echo  "off"
	fi

	let "cnt=${cnt}+1"
    done
}


# Function to set the file to one or zero.
function proc_enable () {
    for file in $@; do
	echo 1 2>/dev/null > $file;
    done
}
function proc_disable () {
    for file in $@; do
	echo 0 2>/dev/null > $file;
    done
}

function ipt_prereqs(){
    #  Use Selective ACK which can be used to signify that specific packets are missing.
    proc_disable /proc/sys/net/ipv4/tcp_sack
    # If the kernel should attempt to forward packets. Off by default. Routers should enable.
    proc_disable /proc/sys/net/ipv4/ip_forward
    # Protect against wrapping sequence numbers and in round trip time measurement.
    proc_disable /proc/sys/net/ipv4/tcp_timestamps
    # Help against syn-flood DoS or DDoS attacks using particular choices of initial TCP sequence numbers.
    proc_enable /proc/sys/net/ipv4/tcp_syncookies
    # Enable broadcast echo protection.
    proc_enable /proc/sys/net/ipv4/icmp_echo_ignore_broadcasts
    # Disable source routed packets.
    proc_disable  /proc/sys/net/ipv4/conf/*/accept_source_route
    # Disable ICMP Redirect acceptance.
    proc_disable /proc/sys/net/ipv4/conf/*/accept_redirects
    # Don't send Redirect messages.
    proc_disable /proc/sys/net/ipv4/conf/*/send_redirects
    # Do not respond to packets that would cause us to go out
    # a different interface than the one to which we're responding.
    proc_enable /proc/sys/net/ipv4/conf/*/rp_filter
    # Log packets with impossible addresses.
    proc_enable /proc/sys/net/ipv4/conf/*/log_martians

    # enable (ftp)connection tracking
    modprobe ip_conntrack
    modprobe ip_conntrack_ftp
}

function ipt_basics(){
    # Allow anything over loopback.
    ${CMD_IPTABLES} -A INPUT  -i lo -s 127.0.0.1 -j ACCEPT
    ${CMD_IPTABLES} -A OUTPUT -o lo -d 127.0.0.1 -j ACCEPT

    # commented this because it causes problems with direct routing (loadbalancers):
    #     # Drop any tcp packet that does not start a connection with a syn flag.
    #     ${CMD_IPTABLES} -A INPUT -p tcp ! --syn -m state --state NEW -j DROP

    # Drop any invalid packet that could not be identified.
    ###${CMD_IPTABLES} -A INPUT -m state --state INVALID -j DROP
    # Drop invalid packets.
    # commented this because it causes problems with NFS
    ###${CMD_IPTABLES} -A INPUT -p tcp -m tcp --tcp-flags FIN,SYN,RST,PSH,ACK,URG NONE -j DROP
    ###${CMD_IPTABLES} -A INPUT -p tcp -m tcp --tcp-flags SYN,FIN SYN,FIN              -j DROP
    ###${CMD_IPTABLES} -A INPUT -p tcp -m tcp --tcp-flags SYN,RST SYN,RST              -j DROP
    ###${CMD_IPTABLES} -A INPUT -p tcp -m tcp --tcp-flags FIN,RST FIN,RST              -j DROP
    ###${CMD_IPTABLES} -A INPUT -p tcp -m tcp --tcp-flags ACK,FIN FIN                  -j DROP
    ###${CMD_IPTABLES} -A INPUT -p tcp -m tcp --tcp-flags ACK,URG URG                  -j DROP
    # Reject broadcasts to 224.0.0.1
    ${CMD_IPTABLES} -A INPUT -d 224.0.0.0 -j REJECT

    # Allow TCP/UDP connections out.
    ${CMD_IPTABLES} -A OUTPUT -p tcp -m state --state NEW,ESTABLISHED -j ACCEPT
    ${CMD_IPTABLES} -A OUTPUT -p udp -m state --state NEW,ESTABLISHED -j ACCEPT
    # Keep state so conns out are allowed back in.
    ${CMD_IPTABLES} -A INPUT  -p tcp -m state --state ESTABLISHED,RELATED     -j ACCEPT
    ${CMD_IPTABLES} -A INPUT  -p udp -m state --state ESTABLISHED,RELATED     -j ACCEPT

    # Allow ICMP out and anything that went out back in.
    ${CMD_IPTABLES} -A INPUT  -p icmp -m state --state ESTABLISHED      -j ACCEPT
    ${CMD_IPTABLES} -A OUTPUT -p icmp -m state --state NEW,ESTABLISHED  -j ACCEPT
    # Allow only ICMP echo requests (ping) in. Limit rate in.
    ${CMD_IPTABLES} -A INPUT  -p icmp -m state --state NEW,ESTABLISHED --icmp-type echo-request -m limit --limit 1/s -j ACCEPT
}

function ipt_flush(){
    ${CMD_IPTABLES} -F
    ${CMD_IPTABLES} -F -t nat
    ${CMD_IPTABLES} -F -t mangle
}

function ipt_allow_port_udp_out(){
    for port in $@; do
	${CMD_IPTABLES} -A OUTPUT -p udp --dport ${port} -j ACCEPT
    done
}
function ipt_allow_port_tcp_out(){
    for port in $@; do
	${CMD_IPTABLES} -A OUTPUT -p tcp --dport ${port} -m state --state NEW -j ACCEPT
    done
}

function ipt_allow_port_tcp_in(){
    for port in $@; do
	${CMD_IPTABLES} -A INPUT -p tcp -m tcp --dport ${port}  -m state --state NEW -j ACCEPT
    done
}
function ipt_allow_port_udp_in(){
    for port in $@; do
	${CMD_IPTABLES} -A INPUT -p udp --dport ${port} -j ACCEPT
    done
}

function indexInterfaces() {
    I=0
    if [ -f /etc/network/interfaces ]; then
	for x in $(grep iface /etc/network/interfaces | egrep -v "^#.*" |egrep -v "^$" | awk '{print $2}');do
	    if [ "$x" != "lo" ]; then
		NET_ETH[$I]=$x;
		NET_IP[$I]=`/sbin/ifconfig ${NET_ETH[$I]} | grep 'Bcast' | awk '{print $2}' | cut -d : -f 2`;
		NET_BDC[$I]=`/sbin/ifconfig ${NET_ETH[$I]} | grep 'Bcast' | awk '{print $3}' | cut -d : -f 2`;
		NET_MASK[$I]=`/sbin/ifconfig ${NET_ETH[$I]} | grep 'Bcast' | awk '{print $4}' | cut -d : -f 2`;
		NET_NET[$I]=`/sbin/route -n | grep "${NET_MASK[$I]}" | awk '{print $1}'`;
		NET_LAN[$I]="${NET_NET[$I]}/24";
		NET_RANGE[$I]=`${CMD_IPCALC} ${NET_IP[$I]} ${NET_MASK[$I]} |grep 'Network: ' |awk '{print $2}'`
		NET_IP_PREF[$I]=`echo "${NET_IP[$I]}" |awk -F'.' '{print $1"."$2}'`
		let I++;
	    fi
	done
    fi
}

function getHostByAddr(){
    inp=${1}

    if [ `expr index "${inp}" "/"` -gt 3 ];then
	# this is a range
	for (( i = 0 ; i < ${#NET_ETH[@]} ; i++ ));do
	    if [ "${NET_RANGE[$i]}" == "${inp}" ]; then
		res=${NET_ETH[$i]}
	    fi
	done
    else
	# this is an ip
	res=$(host -Qqo ${inp} 2>/dev/null |grep 'Name: ' |sed 's#Name: ##g')
    fi

    [ -n "${res}" ] || res="${inp}"
    echo $res
}

usage(){
    echo "Usage ${APP_BASEEXEC} [command]"
    echo ""
    echo "${APP_HUMNNAME} is a simple backup utility. "
    echo "Use it to backup your internet server."
    echo ""
    echo "Commands:"
    echo "   config - Reconfigure the settings"
    #echo "   install - Install all the prerequisites (packages & ssh keys is nesessary)"
    #echo "   upgrade - Upgrade this script"
    #echo "   backup - Run backup procedure"
    echo "   help - This page"
    echo ""
    echo "                       This ${APP_HUMNNAME} has Super-God Masterforce Powers."
}


# FIGURE OUT FULL PATH
CURRENT_DIR="$(pwd)"
cd $(dirname ${0})
APP_DIRENAME="$(pwd)"
APP_FULLPATH="${APP_DIRENAME}/$(basename ${0})"
cd ${CURRENT_DIR}

# APP DEFAULTS
[ -n "${APP_HUMNNAME}" ] || APP_HUMNNAME="kSecure Firewall"
[ -n "${APP_BASENAME}" ] || APP_BASENAME=$(basename ${0});APP_BASENAME=${APP_BASENAME%.[^.]*}
[ -n "${APP_CFGFFILE}" ] || APP_CFGFFILE=$(echo ${APP_FULLPATH} |sed "s#${APP_BASENAME}#${APP_BASENAME}.conf#g")
[ -n "${APP_CFGFLTMP}" ] || APP_CFGFLTMP=$(echo ${APP_FULLPATH} |sed "s#${APP_BASENAME}#${APP_BASENAME}.tempcnf#g")
[ -n "${APP_BASEEXEC}" ] || APP_BASEEXEC=$(basename ${0})

# create tempfile for dialog output
tempfile=`tempfile 2>/dev/null` || tempfile=/tmp/test$$
trap "rm -f $tempfile" 0 1 2 5 15

# check if dialog is available
CMD_DIALOG=$(which "dialog")
[ -n "${CMD_DIALOG}" ] && [ -x ${CMD_DIALOG} ] || missing_cmd "dialog" "dialog" "install"

# check if ipcalc is available
CMD_IPCALC=$(which "ipcalc")
[ -n "${CMD_IPCALC}" ] && [ -x ${CMD_IPCALC} ] || missing_cmd "ipcalc" "ipcalc" "install"

# check if nano is available
CMD_NANO=$(which "nano")
[ -n "${CMD_NANO}" ] && [ -x ${CMD_NANO} ] || missing_cmd "nano" "nano" "install"

# check if iptables is available
CMD_IPTABLES=$(which "iptables")
[ -n "${CMD_IPTABLES}" ] && [ -x ${CMD_IPTABLES} ] || missing_cmd "iptables" "iptables" "install"

# we need the host package and not the bind9-host package
if [ -f /etc/debian_version ];then
    if [ "$(dpkg -l host |grep host |awk '{print $3}' |sed 's#[<>]##g')" == "none" ];then
	missing_cmd "host" "host" "install"
    fi
fi

indexInterfaces


# LOAD SETTINGS OR RUN WIZARD
if [ "${1}" == "help" ];then
    usage
    exit 0
elif [ ! -f ${APP_CFGFFILE} ] || [ "${1}" == "config" ] ; then
    echo "No config file ${APP_CFGFFILE} found, running config wizzard"
    echo "================================================================================="
    sleep 1

    # Clear out Tempfile:
    echo "" > ${APP_CFGFLTMP}

    diaS_Services "open" "available for the public, like port 80 for apache"

    diaS_Services "private" "available for a few trusted machines that we specify next"
    diaS_Ipaddresses "private" "they can access the private services on this machine that we've just specified"

    diaS_Ipaddresses "open" "they can access all services unlimited, they're Master"

    if [ -d /etc/network/if-up.d ]; then
	dia_YesNo "Startup" "Do you want to create a startup file in /etc/network/if-up.d so the rules will take effect everytime this server goes online?" 0
	startupfile="/etc/network/if-up.d/${APP_BASENAME}"
	if [ "${dia_ret}" == 1 ];then
	    echo "#!/bin/sh" > ${startupfile}
	    echo "${APP_FULLPATH}" >> ${startupfile}
	    chmod 744 ${startupfile}
	elif [ -f ${startupfile} ]; then
	    rm ${startupfile}
	fi
    fi

    dia_YesNo "Crontab" "Do you want to add iptables -F every 10 minutes to de crontab for debugging purposes?" 0
    if [ "${dia_ret}" == 1 ];then
	crontab -l | grep -v "${APP_BASENAME}" > /tmp/${APP_BASENAME}_fichier.tmp
	echo "*/10 * * * * /sbin/iptables -F" >> /tmp/${APP_BASENAME}_fichier.tmp
	crontab /tmp/${APP_BASENAME}_fichier.tmp
    fi

    echo "ENABLE_LOGGING=\"0\"" >> ${APP_CFGFLTMP}
    echo "" >> ${APP_CFGFLTMP}

    echo "# here you can add some custom commands " >> ${APP_CFGFLTMP}
    echo "# to add extra iptable rules or execute something " >> ${APP_CFGFLTMP}
    echo "CUSTOM_RULES[0]=\"\"" >> ${APP_CFGFLTMP}
    echo "" >> ${APP_CFGFLTMP}

    dia_YesNo "Configuration file" "Do you want to review the config file to make some final adjustments? This will also allow you to add some custom rules." 0
    if [ "${dia_ret}" == 1 ];then
	${CMD_NANO} ${APP_CFGFLTMP}
    fi

    clear
    
    mv "${APP_CFGFLTMP}" "${APP_CFGFFILE}"
    echo "";
    echo "Configfile written to ${APP_CFGFFILE}";
    echo "";
    usage
    exit 0
else
    source ${APP_CFGFFILE}
fi



# semi configuration (it should not be necessary to change these)
ACTION_ACCEPT="-j ACCEPT"
ACTION_ACCEPT_ALL_STATES="-m state --state NEW,ESTABLISHED,RELATED ${ACTION_ACCEPT}"
ACTION_DENY="-j DROP"
ACTION_LOG="-j LOG --log-level 4 --log-prefix KSECUREFIREWALL"

# autoflush
ipt_flush

if [ "${1}" == "flush" ];then
    # flush all current rules
    echo "all rules flushed"
    exit 0
fi

ipt_prereqs
ipt_basics

# allow all traffic between MASTER IPs en this server on all ports
if [ ${#IPDRS_OPEN} ]; then
    for (( i = 0 ; i < ${#IPDRS_OPEN[@]} ; i++ )); do
	for host in ${IPDRS_OPEN[$i]}; do
	    ${CMD_IPTABLES} -A INPUT  -s ${host} ${ACTION_ACCEPT}
	    ${CMD_IPTABLES} -A OUTPUT -d ${host} ${ACTION_ACCEPT}
	done
    done
fi
# allow incomming traffic from PRIVATE IPs, for private ports
if [ ${#IPDRS_PRIVATE} ]; then
    for (( i = 0 ; i < ${#IPDRS_PRIVATE[@]} ; i++ ));do
	for host in ${IPDRS_PRIVATE[$i]}; do
	    if [ ${#PORTS_TCP_PRIVATE} ]; then
		for (( j = 0 ; j < ${#PORTS_TCP_PRIVATE[@]} ; j++ ));do
		    for port in ${PORTS_TCP_PRIVATE[${j}]}; do
			${CMD_IPTABLES} -A INPUT  -p tcp -s ${host} --dport ${port} ${ACTION_ACCEPT_ALL_STATES}
		    done
		done
	    fi
	    if [ ${#PORTS_UDP_PRIVATE} ]; then
		for (( j = 0 ; j < ${#PORTS_UDP_PRIVATE[@]} ; j++ ));do
		    for port in ${PORTS_UDP_PRIVATE[${j}]}; do
			${CMD_IPTABLES} -A INPUT  -p udp -s ${host} --dport ${port} ${ACTION_ACCEPT_ALL_STATES}
		    done;
		done
	    fi
	done
    done
fi
# allow incomming traffic from EVERY IP for public ports
if [ ${#PORTS_TCP_OPEN} ]; then
    for (( i = 0 ; i < ${#PORTS_TCP_OPEN[@]} ; i++ ));do
	for port in ${PORTS_TCP_OPEN[${i}]}; do
	    echo "${port}"
	    ipt_allow_port_tcp_in ${port}
	done
    done
fi
if [ ${#PORTS_UDP_OPEN} ]; then
    for (( i = 0 ; i < ${#PORTS_UDP_OPEN[@]} ; i++ ));do
	for port in ${PORTS_UDP_OPEN[${i}]]}; do
	    ipt_allow_port_udp_in ${port}
	done
    done
fi
# deny all traffic from+to CLOSED IPs to this server on all ports
if [ ${#IPDRS_CLOSED} ]; then
    for (( i = 0 ; i < ${#IPDRS_CLOSED[@]} ; i++ ));do
	for host in ${IPDRS_CLOSED[${i}]]}; do
	    if [ "${ENABLE_LOGGING}" == 1 ]; then
		${CMD_IPTABLES} -A INPUT  -s ${host} ${ACTION_LOG}
		${CMD_IPTABLES} -A OUTPUT -d ${host} ${ACTION_LOG}
	    fi
	    ${CMD_IPTABLES} -A INPUT  -s ${host} ${ACTION_DENY}
	    ${CMD_IPTABLES} -D OUTPUT -d ${host} ${ACTION_DENY}
	done
    done
fi

if [ "${#CUSTOM_RULES}" ]; then
    for (( i = 0 ; i < ${#CUSTOM_RULES[@]} ; i++ ));do
	if [ -n "${CUSTOM_RULES[$i]}" ];then
	    ${CUSTOM_RULES[$i]}
	fi
    done
fi


# allow all outgoing
${CMD_IPTABLES} -A OUTPUT ${ACTION_ACCEPT_ALL_STATES}

# deny all incomming
if [ "${ENABLE_LOGGING}" == 1 ]; then
    # log(?)
    ${CMD_IPTABLES} -A INPUT ${ACTION_LOG}
fi
${CMD_IPTABLES} -A INPUT ${ACTION_DENY}