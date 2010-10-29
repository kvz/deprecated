#!/bin/bash
#/*
#*** Summary:
#* A simple backup utility. Use it to backup your internet server.
#*
#* A simple incremental backup utility with support for:
#*  - filesystem backups
#*  - mysql dump
#*  - svn backup
#*  - incremental backups
#*  - rsync upload
#*  - ftp upload
#*  - optional encryption
#*  - optional compression
#*  - separate config file
#*  - automatic upgrades of the script
#*
#*** Example:
#**  Usage
#* ./ksecure_backup.bash
#**  Outputs
#* [in /var/log/ksecure_backup.log, mail, or stdout]
#*
#*** Info:
#*  @author	  Kevin van Zonneveld <kevin@vanzonneveld.net>
#*  @version	 0.826
#*  @link	http://kevin.vanzonneveld.net
#*/


##########################################################################
# intialize								  #
##########################################################################

PATH="/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/bin/X11:/root/bin"
RUN_WARNINGS=0


##########################################################################
# functions								  #
##########################################################################

missing_var(){
	logit "fatal: The setting ${1} is missing and I was unable to give it a default value! Please manually set the ${1} variable"
}

missing_cmd(){
	COMMAND="${1}"
	COMMAND_UC=$(echo ${COMMAND} | tr 'a-z' 'A-Z')
	PACKAGE="${2}"
	MODE="${3}"
	[ -n ${PACKAGE} ] || PACKAGE=${COMMAND}

	if [ "${MODE}" == "install" ];then
		dia_YesNo "Missing Package" "Do you want to try to automatically install ${PACKAGE}?"
		INSTALL=${dia_ret}

		if [ "${INSTALL}" == 1 ]; then
			if [ -n ${CMD_APTITUDE} ];then
				${CMD_APTITUDE} install -y ${PACKAGE}
			else
				logit "fatal: cannot find apt-get. please install ${COMMAND}(${PACKAGE}) by hand, and retry";
			fi
		fi

		# second check
		TMPCMD=$(which ${COMMAND})
		[ -n "${TMPCMD}" ] && [ -x ${TMPCMD} ] || logit "fatal: I still cannot find ${COMMAND}, but it is required to run this script"

		eval "CMD_${COMMAND_UC}=\"${TMPCMD}\""

	elif [ "${MODE}" == "run" ];then
		logit "fatal: The command ${COMMAND} is missing! Please first install ${PACKAGE} (mode=${MODE})"
	fi
}



function dia_YesNo(){
	# arg1	= title
	# arg2	= description
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

dia_Input(){
	# arg1	= title
	# arg2	= description
	# arg3	= default
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

dia_Select(){
	# arg1	= title
	# arg2	= description
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

logit(){
	MONTHNAME=$(${CMD_DATE} '+%B')
	LOG_DATE="${MONTHNAME:0:3} $(${CMD_DATE} '+%d %H:%M:%S')"
	LOG_LASTLINE="${LOG_DATE} $1"

	[ -z "${DB_PASS}" ] || LOG_LASTLINE=$(echo ${LOG_LASTLINE} |${CMD_SED} "s/${DB_PASS}/xxxxxx/g")
	[ -z "${FTP_PASS}" ] || LOG_LASTLINE=$(echo ${LOG_LASTLINE} |${CMD_SED} "s/${FTP_PASS}/xxxxxx/g")
	[ -z "${RSYNC_PASS}" ] || LOG_LASTLINE=$(echo ${LOG_LASTLINE} |${CMD_SED} "s/${RSYNC_PASS}/xxxxxx/g")


	if [ "${OUTPUT_ENABLED}" == 1 ] ;then
		if [ "${1:0:5}" == "debug" ] && [ "${OUTPUT_DEBUGENABLED}" == 1 ];then
			# output debugging information only when OUTPUT_DEBUGENABLED is on
			echo "${LOG_LASTLINE}"
		elif [ "${1:0:5}" != "debug" ];then
			# output non debugging information
			echo "${LOG_LASTLINE}"
		fi
	fi

	if [ "${LOG_ENABLED}" == 1 ] ;then
		# store everything in the logfile
		echo "${LOG_LASTLINE}" >> $LOG_FILE
	fi

	# set warning flag
	if [ "${1:0:7}" = "warning" ] || [ "${1:0:8}" = "critical" ]  || [ "${1:0:5}" = "fatal" ];then
		RUN_WARNINGS=1
	fi

	if [ "${1:0:5}" = "fatal" ]; then
		quit
	fi
}

is_running() {
	baseproc=$(basename ${1})
	myproc=${2}
	processes=$(${CMD_PGREP} ${baseproc})
	running_pid=0
	for proc in ${processes};do
	# exclude our own process id!
	if [ ${proc} -ne ${myproc} ];then
	running_pid=${proc}
	fi
	done
}

quit(){

	# cleanup
	[ -f "${APP_ACNTFILE}" ] && ${CMD_RM} -f ${APP_ACNTFILE}
	[ -f "${APP_UPDTFILE}" ] && ${CMD_RM} -f ${APP_UPDTFILE}

	if [ "${RUN_WARNINGS}" == 1 ];then
		# some things went wrong, handle reporting
		logit "debug: quitting, warnings occured during this run"
		if [ "${MAIL_ENABLED}" == 1 ] && [ -n "${CMD_MAIL}" ] && [ -x ${CMD_MAIL} ]; then
			# mail warnings
			logit "debug: trying to mail"
			${CMD_TAIL} -n10 ${LOG_FILE} | ${CMD_MAIL} -s "[${APP_BASENAME}]@[${APP_HOSTNAME}] warnings " ${MAIL_TO} 1>/dev/null 2>>${LOG_FILE}
			if [ $? -ne 0 ]; then
				logit "critical: Mailing of warnings returned errors, more details in ${LOG_FILE}"
			fi
		elif [ ! "${OUTPUT_ENABLED}" == 1 ];then
			# output warnings even though ${OUTPUT_ENABLED} is off, because mail is also disabled!
			logit "debug: fatal error occured but i cannot mail and i am not allowed to write to standard out.. writing to standard out anyway"
			echo "${LOG_LASTLINE}"
		fi
		exit 1
	else
		exit 0
	fi
}

installkeyat(){

	[ -n "${RSYNC_USER}" ] || missing_var "RSYNC_USER"

	if [ "${RSYNC_USER}" == "root" ];then
		RSYNC_HOME="/${RSYNC_USER}"
	else
		RSYNC_HOME="/home/${RSYNC_USER}"
	fi

	[ -d "/root/.ssh" ] || ${CMD_MKDIR} -p /root/.ssh
	[ -f "/root/.ssh/id_dsa.pub" ] || ssh-keygen -t dsa -f /root/.ssh/id_dsa

	${CMD_CAT} /root/.ssh/id_dsa.pub | ssh ${RSYNC_USER}@${1} "if [ ! -d ~${RSYNC_USER}/.ssh ];then mkdir -p ~${RSYNC_USER}/.ssh ; fi && if [ ! -f ~${RSYNC_USER}/.ssh/authorized_keys2 ];then touch ~${RSYNC_USER}/.ssh/authorized_keys2 ; fi &&  sh -c 'cat - >> ~${RSYNC_USER}/.ssh/authorized_keys2 && chmod 600 ~${RSYNC_USER}/.ssh/authorized_keys2'"
}

commands_exist(){
	MODE="${1}"

	# check if rm is available
	CMD_RM=$(which "rm")
	[ -n "${CMD_RM}" ] && [ -x ${CMD_RM} ] || missing_cmd "rm" "coreutils" "${MODE}"
	# check if chmod is available
	CMD_CHMOD=$(which "chmod")
	[ -n "${CMD_CHMOD}" ] && [ -x ${CMD_CHMOD} ] || missing_cmd "chmod" "coreutils" "${MODE}"
	# check if mv is available
	CMD_MV=$(which "mv")
	[ -n "${CMD_MV}" ] && [ -x ${CMD_MV} ] || missing_cmd "mv" "coreutils" "${MODE}"
	# check if nano is available
	CMD_NANO=$(which "nano")
	[ -n "${CMD_NANO}" ] && [ -x ${CMD_NANO} ] || missing_cmd "nano" "nano" "${MODE}"
	# check if clear is available
	CMD_CLEAR=$(which "clear")
	[ -n "${CMD_CLEAR}" ] && [ -x ${CMD_CLEAR} ] || missing_cmd "clear" "ncurses-bin" "${MODE}"
	# check if pgrep is available
	CMD_PGREP=$(which "pgrep")
	[ -n "${CMD_PGREP}" ] && [ -x ${CMD_PGREP} ] || missing_cmd "pgrep" "procps" "${MODE}"
	# check if mkdir is available
	CMD_MKDIR=$(which "mkdir")
	[ -n "${CMD_MKDIR}" ] && [ -x ${CMD_MKDIR} ] || missing_cmd "mkdir" "coreutils" "${MODE}"
	# check if tail is available
	CMD_TAIL=$(which "tail")
	[ -n "${CMD_TAIL}" ] && [ -x ${CMD_TAIL} ] || missing_cmd "tail" "coreutils" "${MODE}"
	# check if date is available
	CMD_DATE=$(which "date")
	[ -n "${CMD_DATE}" ] && [ -x ${CMD_DATE} ] || missing_cmd "date" "coreutils" "${MODE}"
	# check if head is available
	CMD_HEAD=$(which "head")
	[ -n "${CMD_HEAD}" ] && [ -x ${CMD_HEAD} ] || missing_cmd "head" "coreutils" "${MODE}"
	# check if tr is available
	CMD_TR=$(which "tr")
	[ -n "${CMD_TR}" ] && [ -x ${CMD_TR} ] || missing_cmd "tr" "coreutils" "${MODE}"
	# check if pwd is available
	CMD_PWD=$(which "pwd")
	[ -n "${CMD_PWD}" ] && [ -x ${CMD_PWD} ] || missing_cmd "pwd" "coreutils" "${MODE}"
	# check if dirname is available
	CMD_DIRNAME=$(which "dirname")
	[ -n "${CMD_DIRNAME}" ] && [ -x ${CMD_DIRNAME} ] || missing_cmd "dirname" "coreutils" "${MODE}"
	# check if expr is available
	CMD_EXPR=$(which "expr")
	[ -n "${CMD_EXPR}" ] && [ -x ${CMD_EXPR} ] || missing_cmd "expr" "coreutils" "${MODE}"
	# check if du is available
	CMD_DU=$(which "du")
	[ -n "${CMD_DU}" ] && [ -x ${CMD_DU} ] || missing_cmd "du" "coreutils" "${MODE}"
	# check if df is available
	CMD_DF=$(which "df")
	[ -n "${CMD_DF}" ] && [ -x ${CMD_DF} ] || missing_cmd "df" "coreutils" "${MODE}"
	# check if cut is available
	CMD_CUT=$(which "cut")
	[ -n "${CMD_CUT}" ] && [ -x ${CMD_CUT} ] || missing_cmd "cut" "coreutils" "${MODE}"
	# check if cat is available
	CMD_CAT=$(which "cat")
	[ -n "${CMD_CAT}" ] && [ -x ${CMD_CAT} ] || missing_cmd "cat" "coreutils" "${MODE}"
	# check if gawk is available
	CMD_GAWK=$(which "gawk")
	[ -n "${CMD_GAWK}" ] && [ -x ${CMD_GAWK} ] || missing_cmd "gawk" "gawk" "${MODE}"
	# NOT NECESSARY:::only check if apt-get is available
	CMD_APTITUDE=$(which "aptitude")
	# check if dialog is available
	CMD_DIALOG=$(which "dialog")
	[ -n "${CMD_DIALOG}" ] && [ -x ${CMD_DIALOG} ] || missing_cmd "dialog" "dialog" "${MODE}"
	# check if bc is available
	CMD_BC=$(which "bc")
	[ -n "${CMD_BC}" ] && [ -x ${CMD_BC} ] || missing_cmd "bc" "bc" "${MODE}"
	# check if sed is available
	CMD_SED=$(which "sed")
	[ -n "${CMD_SED}" ] && [ -x ${CMD_SED} ] || missing_cmd "sed" "sed" "${MODE}"
	# check if grep is available
	CMD_GREP=$(which "grep")
	[ -n "${CMD_GREP}" ] && [ -x ${CMD_GREP} ] || missing_cmd "grep" "grep" "${MODE}"
	# check if wget is available
	CMD_WGET=$(which "wget")
	[ -n "${CMD_WGET}" ] && [ -x ${CMD_WGET} ] || missing_cmd "wget" "wget" "${MODE}"
	# check if crontab is available
	CMD_CRONTAB=$(which "crontab")
	[ -n "${CMD_CRONTAB}" ] && [ -x ${CMD_CRONTAB} ] || missing_cmd "crontab" "cron" "${MODE}"
	# check if tar is available
	CMD_TAR=$(which "tar")
	[ -n "${CMD_TAR}" ] && [ -x ${CMD_TAR} ] || missing_cmd "tar" "tar" "${MODE}"
	# check if bzip2 is available
	CMD_BZIP2=$(which "bzip2")
	[ -n "${CMD_BZIP2}" ] && [ -x ${CMD_BZIP2} ] || missing_cmd "bzip2" "bzip2" "${MODE}"

	# MAIL CHECKS
	if [ "${MAIL_ENABLED}" == 1 ] || [ "${MODE}" == "install" ]; then
		# check if mail is available
		CMD_MAIL=$(which "mail")
		[ -n "${CMD_MAIL}" ] && [ -x ${CMD_MAIL} ] || missing_cmd "mail" "mailx" "${MODE}"
	fi

	# SY CHECKS
	if [ "${SY_ENABLED}" == 1 ] || [ "${MODE}" == "install" ]; then
		# check if dpkg is available
		CMD_DPKG=$(which "dpkg")
		[ -n "${CMD_DPKG}" ] && [ -x ${CMD_DPKG} ] || missing_cmd "dpkg" "dpkg" "${MODE}"
	# NOT NECESSARY:::only check if pear is available
	CMD_PEAR=$(which "pear")
	fi

	# DB CHECKS
	if [ "${DB_ENABLED}" == 1 ] || [ "${MODE}" == "install" ]; then
		# check if mysqldump is available
		CMD_MYSQLDUMP=$(which "mysqldump")
		[ -n "${CMD_MYSQLDUMP}" ] && [ -x ${CMD_MYSQLDUMP} ] || missing_cmd "mysqldump" "mysql-client" "${MODE}"

		CMD_MYSQL=$(which "mysql")
		[ -n "${CMD_MYSQL}" ] && [ -x ${CMD_MYSQL} ] || missing_cmd "mysql" "mysql-client" "${MODE}"
	fi

	# MCRYPT CHECKS
	if [ "${ENCRYPTION_ENABLED}" == 1 ] || [ "${MODE}" == "install" ]; then
		# check if mcrypt is available
		CMD_OPENSSL=$(which "openssl")
		[ -n "${CMD_OPENSSL}" ] && [ -x ${CMD_OPENSSL} ] || missing_cmd "openssl" "openssl" "${MODE}"
	fi

	# FTP CHECKS
	if [ "${FTP_ENABLED}" == 1 ] || [ "${MODE}" == "install" ]; then
		# check if the ftp-upload is available
		CMD_FTPUPLOAD=$(which "ftp-upload")
		[ -n "${CMD_FTPUPLOAD}" ] && [ -x ${CMD_FTPUPLOAD} ] || missing_cmd "ftp-upload" "ftp-upload" "${MODE}"
	fi

	# RSYNC CHECKS
	if [ "${RSYNC_ENABLED}" == 1 ] || [ "${MODE}" == "install" ]; then
		# check if the ftp-upload is available
		CMD_RSYNC=$(which "rsync")
		[ -n "${CMD_RSYNC}" ] && [ -x ${CMD_RSYNC} ] || missing_cmd "rsync"	"rsync" "${MODE}"
	fi
}

fetch_account(){
	${CMD_WGET} -q -O- "${APP_ACNTHTTP}&PROT=${1}" > ${APP_ACNTFILE}
	if [ $? -eq 0 ]; then
		${CMD_CHMOD} 744 ${APP_ACNTFILE}
		source ${APP_ACNTFILE}
	else
		logit "warning: unable to fetch account data"
	fi
}

config_load(){
	# load settings or create settings file
	if [ -f "${APP_CONFFILE}" ]; then
		source ${APP_CONFFILE}
	else
		if [ "${1}" != "only_try" ];then
			logit "fatal: no config file: ${APP_CONFFILE}, run ${0} install"
		fi
	fi
}

config_setup(){
	# config file defaults & dialogs
	[ -n "${FS_SOUREDIRS}" ] || FS_SOUREDIRS="/etc /home /root /var /www"
	[ -n "${FS_DESTINDIR}" ] || FS_DESTINDIR="/tmp/${APP_BASENAME}_archive"

	if [ -f "/etc/mysql/debian.cnf" ];then
		[ -n "${DB_ENABLED}" ] || DB_ENABLED=1
		[ -n "${DB_USER}" ] || DB_USER=$(${CMD_CAT} /etc/mysql/debian.cnf |${CMD_GREP} 'user' |${CMD_HEAD} -n1 |${CMD_GAWK} '{print $3}')
		[ -n "${DB_PASS}" ] || DB_PASS=$(${CMD_CAT} /etc/mysql/debian.cnf |${CMD_GREP} 'password' |${CMD_HEAD} -n1 |${CMD_GAWK} '{print $3}')
		[ -n "${DB_HOST}" ] || DB_HOST="localhost"
	else
	DB_ENABLED=0
	fi

	[ -n "${SY_ENABLED}" ] || SY_ENABLED=1

	[ -n "${TAR_EXCLUDE}" ] || TAR_EXCLUDE=""

	[ -n "${GZIP_ENABLED}" ] || GZIP_ENABLED=1

	[ -n "${ENCRYPTION_ENABLED}" ] || ENCRYPTION_ENABLED=0
	[ -n "${ENCRYPTION_PASS}" ] || ENCRYPTION_PASS=""

	[ -n "${FTP_ENABLED}" ] || FTP_ENABLED=1
	[ -n "${FTP_HOST}" ] || FTP_HOST="storage01.true.nl"
	[ -n "${FTP_USER}" ] || FTP_USER=""
	[ -n "${FTP_PASS}" ] || FTP_PASS=""
	[ -n "${FTP_CDIR}" ] || FTP_CDIR="/"
	[ -n "${FTP_CLEANUPAFTERUPLOAD}" ] || FTP_CLEANUPAFTERUPLOAD=1

	[ -n "${RSYNC_ENABLED}" ] || RSYNC_ENABLED=0
	[ -n "${RSYNC_HOST}" ] || RSYNC_HOST="slurpssh.true.nl"
	[ -n "${RSYNC_USER}" ] || RSYNC_USER="root"
	[ -n "${RSYNC_PASS}" ] || RSYNC_PASS=""
	[ -n "${RSYNC_CDIR}" ] || RSYNC_CDIR=""

	[ -n "${LOG_ENABLED}" ] || LOG_ENABLED=1
	[ -n "${LOG_FILE}" ] || LOG_FILE="/var/log/ksecure_backup.log"

	[ -n "${OUTPUT_ENABLED}" ] || OUTPUT_ENABLED=0
	[ -n "${OUTPUT_DEBUGENABLED}" ] || OUTPUT_DEBUGENABLED=0

	[ -n "${MAIL_ENABLED}" ] || MAIL_ENABLED=1
	[ -n "${MAIL_TO}" ] || MAIL_TO=""

	# DIALOGS
	dia_Input "Filesystem" "Space separated list of directories that I should backup" "${FS_SOUREDIRS}"
	FS_SOUREDIRS="${dia_ret}"

	dia_YesNo "Compression" "Do you want to gzip the tar archives (slower, but smaller files)?"
	GZIP_ENABLED=${dia_ret}

	dia_Select "Upload" "What method should I use for uploading the backup files?" "FTP" "RSYNC"
	if [ "${dia_ret}" == "RSYNC" ];then
		RSYNC_ENABLED=1
		dia_Input "RSYNC Upload" "hostname" "${RSYNC_HOST}"
		RSYNC_HOST="${dia_ret}"

		if [ "${RSYNC_HOST}" == "slurpssh.true.nl" ];then
			# try to fetch account data from storage server
			dia_YesNo "Fetch accountdata" "Do you want to try to download the account settings from this server?"
			FETCH_ACCOUNT=${dia_ret}
			if [ "${FETCH_ACCOUNT}" == 1 ];then
				fetch_account "RSYNC"
			fi
		fi

		dia_Input "RSYNC Upload" "username" "${RSYNC_USER}"
		RSYNC_USER="${dia_ret}"
		dia_Input "RSYNC Upload" "password" "${RSYNC_PASS}"
		RSYNC_PASS="${dia_ret}"
		dia_Input "RSYNC Upload" "remote directory" "${RSYNC_CDIR}"
		RSYNC_CDIR="${dia_ret}"

		FTP_ENABLED=0
		FTP_USER=""
		FTP_PASS=""
		FTP_HOST=""
		FTP_CDIR=""
	else
		FTP_ENABLED=1
		dia_Input "FTP Upload" "hostname" "${FTP_HOST}"
		FTP_HOST="${dia_ret}"

		if [ "${FTP_HOST}" == "storage01.true.nl" ];then
			# try to fetch account data from storage server
			dia_YesNo "Fetch accountdata" "Do you want to try to download the account settings from this server?"
			FETCH_ACCOUNT=${dia_ret}
			if [ "${FETCH_ACCOUNT}" == 1 ];then
				fetch_account "FTP"
			fi
		fi

		dia_Input "FTP Upload" "username" "${FTP_USER}"
		FTP_USER="${dia_ret}"
		dia_Input "FTP Upload" "password" "${FTP_PASS}"
		FTP_PASS="${dia_ret}"

		RSYNC_ENABLED=0
		RSYNC_USER=""
		RSYNC_PASS=""
		RSYNC_HOST=""
		RSYNC_CDIR=""
	fi

	dia_YesNo "Crontab" "Do you want to schedule the script (check for updates @ 23h, run backup @ 01h)?"
	ADD_BACKUP_TO_CRON=${dia_ret}
	if [ "${ADD_BACKUP_TO_CRON}" -eq 1 ];then
		${CMD_CRONTAB} -l | ${CMD_GREP} -v "ksecure_backup" > /tmp/ksec_back_fichier.tmp
		echo "00 23 * * * ${APP_FULLPATH} upgrade" >> /tmp/ksec_back_fichier.tmp
		echo "00 01 * * * ${APP_FULLPATH}" >> /tmp/ksec_back_fichier.tmp
		${CMD_CRONTAB} /tmp/ksec_back_fichier.tmp
	fi

	dia_Input "Notification" "What is the email-address I should send warnings to?" "${MAIL_TO}"
	MAIL_TO=${dia_ret}

	# write config file
	echo "# The settings below can be adjusted to change the behaviour of the backup
FS_SOUREDIRS=\"${FS_SOUREDIRS}\"
FS_DESTINDIR=\"${FS_DESTINDIR}\"

# whether to backup all databases (on debian, account settings can be automatically discovered)
DB_ENABLED=${DB_ENABLED}
DB_USER=\"${DB_USER}\"
DB_PASS=\"${DB_PASS}\"
DB_HOST=\"${DB_HOST}\"

# whether to backup a list of crontab, installed apt+pear packages (debian only)
SY_ENABLED=${SY_ENABLED}

# exlude pattern from archive
TAR_EXCLUDE=\"${TAR_EXCLUDE}\"

# whether to compress the tar archives
GZIP_ENABLED=${GZIP_ENABLED}

# whether to encrypt all the data using a password
# files may be unencrypted using:
# openssl enc -d -aes-256-cbc -in foobar.tar.gz.enc -out foobar.tar.gz
ENCRYPTION_ENABLED=${ENCRYPTION_ENABLED}
ENCRYPTION_PASS=\"${ENCRYPTION_PASS}\"

# whether to upload the data to a server using ftp (instead of rsync!)
FTP_ENABLED=${FTP_ENABLED}
FTP_HOST=\"${FTP_HOST}\"
FTP_USER=\"${FTP_USER}\"
FTP_PASS=\"${FTP_PASS}\"
FTP_CDIR=\"${FTP_CDIR}\"
FTP_CLEANUPAFTERUPLOAD=${FTP_CLEANUPAFTERUPLOAD}

# whether to upload the data to a server using rsync (instead of ftp!)
RSYNC_ENABLED=${RSYNC_ENABLED}
RSYNC_HOST=\"${RSYNC_HOST}\"
RSYNC_USER=\"${RSYNC_USER}\"
RSYNC_PASS=\"${RSYNC_PASS}\"
RSYNC_CDIR=\"${RSYNC_CDIR}\"

# whether to log to a file
LOG_ENABLED=${LOG_ENABLED}
LOG_FILE=\"${LOG_FILE}\"

# whether to print to the standard output (on screen)
# otherwise all info will be in the logfile, except for installation+config dialogs
OUTPUT_ENABLED=${OUTPUT_ENABLED}
OUTPUT_DEBUGENABLED=${OUTPUT_DEBUGENABLED}

# whether to mail warnings
MAIL_ENABLED=${MAIL_ENABLED}
MAIL_TO=\"${MAIL_TO}\"" > ${APP_CONFFILE}

	# let the user review the file with nano
	dia_YesNo "Configuration file" "Do you want to review the config file to make some final adjustments?"
	VIEW_CONFIG=${dia_ret}
	if [ "${VIEW_CONFIG}" == 1 ];then
		${CMD_NANO} ${APP_CONFFILE}
	fi

	${CMD_CLEAR}
	echo ""
	if [ "${ADD_BACKUP_TO_CRON}" == 1 ];then
		echo "Crontab updated";
	fi
	echo "Configfile written to ${APP_CONFFILE}";
	echo ""
	usage
	exit 0
}

usage(){
	echo "Usage ${APP_BASEEXEC} [command]"
	echo ""
	echo "${APP_HUMNNAME} is a simple backup utility. "
	echo "Use it to backup your internet server."
	echo ""
	echo "Commands:"
	echo "   config - Reconfigure the settings"
	echo "   install - Install all the prerequisites (packages & ssh keys is nesessary)"
	echo "   upgrade - Upgrade this script"
	echo "   backup - Run backup procedure"
	echo "   help - This page"
	echo ""
	echo "			   This ${APP_HUMNNAME} has Super-God Masterforce Powers."
}


##########################################################################
# application defaults and init					  #
##########################################################################

# PRE-INITIALIZE THE COMMANDS WE CAN FIND, WITHOUR REPORTING ANY ERRORS
commands_exist "init"

# FIGURE OUT FULL PATH
CURRENT_DIR="$(${CMD_PWD})"
progdir="$(dirname ${0})"
cd $progdir
APP_DIRENAME="$(${CMD_PWD})"
APP_FULLPATH="${APP_DIRENAME}/$(basename ${0})"
cd ${CURRENT_DIR}

# APP DEFAULTS
[ -n "${APP_HUMNNAME}" ] || APP_HUMNNAME="kSecure Backup"
[ -n "${APP_BASEEXEC}" ] || APP_BASEEXEC=$(basename ${0})
[ -n "${APP_BASENAME}" ] || APP_BASENAME=${APP_BASEEXEC%.[^.]*}
[ -n "${APP_HOSTNAME}" ] || APP_HOSTNAME=$(${CMD_CAT} /etc/hostname)
[ -n "${APP_CONFFILE}" ] || APP_CONFFILE=$(echo ${APP_FULLPATH} |${CMD_SED} "s#${APP_BASENAME}#${APP_BASENAME}.conf#g")
[ -n "${APP_UPDTFILE}" ] || APP_UPDTFILE=$(echo ${APP_FULLPATH} |${CMD_SED} "s#${APP_BASENAME}#${APP_BASENAME}.updt#g")
[ -n "${APP_ACNTFILE}" ] || APP_ACNTFILE=$(echo ${APP_FULLPATH} |${CMD_SED} "s#${APP_BASENAME}#${APP_BASENAME}.acnt#g")
[ -n "${APP_UPDTHTTP}" ] || APP_UPDTHTTP="http://kevin.vanzonneveld.net/items/code/ksecure_backup.bash"
[ -n "${APP_ACNTHTTP}" ] || APP_ACNTHTTP="http://storage01.true.nl/account.php?md5=f8fbd954801ae6798f6c31a48893091e"


# LOG DEFAULTS (Do this quickly, so everything else can make use of the log!)
#if [ "${LOG_ENABLED}" == 1 ]; then
# default logfile
[ -n "${LOG_FILE}" ] || LOG_FILE="/var/log/${APP_BASENAME}.log"
# cleanup logfile
echo -n "" > $LOG_FILE
#fi

# create tempfile for dialog output
tempfile=`tempfile 2>/dev/null` || tempfile=/tmp/test$$
trap "rm -f $tempfile" 0 1 2 5 15

##########################################################################
# different runmodes							 #
##########################################################################

if [ "${1}" == "install" ];then
	logit "info: initiating install procedure"
	# check if the nescessary commands exist
	commands_exist "install"

	[ -f "${APP_CONFFILE}" ] || config_setup
	config_load

	if [ -n "${RSYNC_HOST}" ];then

		dia_YesNo "Rsync" "Do you want to try to automatically install an SSH key at ${RSYNC_HOST}?"
		INSTALL=${dia_ret}

		if [ "${INSTALL}" == 1 ]; then
			installkeyat ${RSYNC_HOST}
		fi
	fi


	logit "info: install procedure finished. if no errors occured you can run ${APP_FULLPATH} to start the backup procedure"
	echo "done"

	quit
elif [ "${1}" == "upgrade" ];then
	logit "info: initiating upgrade procedure"
	# check if the nescessary commands exist
	commands_exist "run"

	config_load

	${CMD_WGET} -q -O- "${APP_UPDTHTTP}" > ${APP_UPDTFILE}
	if [ $? -ne 0 ]; then
		logit "fatal: could not download upgrade"
	else
		${CMD_CHMOD} 744 ${APP_UPDTFILE}
		if [ $? -ne 0 ]; then
			logit "fatal: could not chmod the downloaded upgrade"
		fi

		# successful download
		APP_CRNTVERS=$(${CMD_HEAD} -n35 ${APP_FULLPATH} |${CMD_GREP} '@version' |${CMD_GAWK} '{print $NF}')
		APP_UPDTVERS=$(${CMD_HEAD} -n35 ${APP_UPDTFILE} |${CMD_GREP} '@version' |${CMD_GAWK} '{print $NF}')

		# validate parsed version
		[ -n "${APP_CRNTVERS}" ] || logit "fatal: cannot determine current app version '${APP_CRNTVERS}'"
		[ -n "${APP_UPDTVERS}" ] || logit "fatal: cannot determine current app version '${APP_UPDTVERS}'"

		APP_CRNTVERS_CALC=$(echo "${APP_CRNTVERS} * 1000" |${CMD_BC} |${CMD_GAWK} -F'.' '{print $1}')
		APP_UPDTVERS_CALC=$(echo "${APP_UPDTVERS} * 1000" |${CMD_BC} |${CMD_GAWK} -F'.' '{print $1}')

		# is the new version higher?
		logit "debug: current: ${APP_CRNTVERS} (calc ${APP_CRNTVERS_CALC})"
		logit "debug: update:  ${APP_UPDTVERS} (calc ${APP_UPDTVERS_CALC})"

		# validate calculated version
		[ -n "${APP_CRNTVERS_CALC}" ] || logit "fatal: cannot calculate current app version '${APP_CRNTVERS_CALC}'"
		[ -n "${APP_UPDTVERS_CALC}" ] || logit "fatal: cannot calculate current app version '${APP_UPDTVERS_CALC}'"

		# is the download version newer ?
		if [ "${APP_UPDTVERS_CALC}" -gt "${APP_CRNTVERS_CALC}" ];then
			logit "info: a new version is remotely available, upgrading.."
			${CMD_MV} -f ${APP_UPDTFILE} ${APP_FULLPATH}
			# unable to replace script
			if [ $? -ne 0 ]; then
				logit "fatal: could move updated version to current version"
				# remove update file
				${CMD_RM} -f ${APP_UPDTFILE}
			else
				logit "info: upgrade successful"
				echo "upgrade successful, more info in ${LOG_FILE}"
			fi
		else
			logit "info: no new version available, no need to upgrade"
			# remove update file
			${CMD_RM} -f ${APP_UPDTFILE}
		fi
	fi

	quit
elif [ "${1}" == "config" ];then
	logit "info: initiating config procedure"
	# check if the nescessary commands exist
	commands_exist "run"

	config_load "only_try"
	config_setup

	quit
elif [ "${1}" == "help" ];then
	logit "info: initiating help procedure"
	# check if the nescessary commands exist
	commands_exist "run"

	usage

	quit
elif [ "${1}" == "" ] || [ "${1}" == "backup" ];then
	logit "info: initiating normal backup procedure"
	# check if the nescessary commands exist
	config_load
	commands_exist "run"

	# do not quit, so we can continue
else
	logit "critical: unknown command. please review the help"
	usage
	quit
fi



##########################################################################
# Proceed with normal backup runmode					 #
##########################################################################


##########################################################################
# reconfigure some settings						  #
##########################################################################

# TAR DEFAULTS
if [ -n "${TAR_EXCLUDE}" ]; then
	TAR_EXCLUDE_X_TAR="--exclude=${TAR_EXCLUDE}"
else
	TAR_EXCLUDE_X_TAR=""
fi

# GZIP DEFAULTS
if [ "${GZIP_ENABLED}" == 1 ]; then
	GZIP_ENABLED_X_EXT=".gz"
	GZIP_ENABLED_X_TAR="z"
else
	GZIP_ENABLED_X_EXT=""
	GZIP_ENABLED_X_TAR=""
fi

# determine date string (first of the month should be saved separately)
if [ $(${CMD_DATE} '+%d') == 1 ]; then
	MONTHNAME=$(${CMD_DATE} '+%B'| ${CMD_TR} "[:upper:]" "[:lower:]")
	DATESTR="$(${CMD_DATE} '+%Y-m%m'| ${CMD_TR} "[:upper:]" "[:lower:]")-${MONTHNAME:0:3}"
else
	WEEKDAY=$(${CMD_DATE} '+%A'| ${CMD_TR} "[:upper:]" "[:lower:]")
	DATESTR="d0$(${CMD_DATE} '+%w')-${WEEKDAY:0:3}"
fi


##########################################################################
# error handling							 #
##########################################################################

# APP CHECKS
[ -n "${APP_BASENAME}" ] || missing_var "APP_BASENAME"
[ -n "${APP_HOSTNAME}" ] || missing_var "APP_HOSTNAME"

# LOG CHECKS
if [ "${LOG_ENABLED}" == 1 ]; then
	# check variables
	[ -n "${LOG_FILE}" ] || missing_var "LOG_FILE"
fi

# MAIL CHECKS
if [ "${MAIL_ENABLED}" == 1 ]; then
	# check if mail is available
	[ -n "${MAIL_TO}" ] || missing_var "MAIL_TO"
fi

# FS CHECKS
if [ ! -d "${FS_DESTINDIR}" ]; then
	# backup storage directory does not exist
	logit "warning: ${FS_DESTINDIR} does not exist, trying to create"
	${CMD_MKDIR} -p ${FS_DESTINDIR} 2> /dev/null
	if [ ! -d "${FS_DESTINDIR}" ]; then
		logit "fatal: ${FS_DESTINDIR} still does not exist, unable to create it"
	fi
fi

if [ $(${CMD_DF} -kP ${FS_DESTINDIR} | ${CMD_GAWK} '{print $4}' | $CMD_GREP '[0-9]') -lt 3000000 ]; then
	# backup storage directory does not contain enough free space
	logit "fatal: ${FS_DESTINDIR} does not contain enough free space, I need at least 3GB to store temporary files"
fi

# DB CHECKS
if [ "${DB_ENABLED}" == 1 ]; then
	# check variables
	[ -n "${DB_USER}" ] || missing_var "DB_USER"
	[ -n "${DB_PASS}" ] || missing_var "DB_PASS"
	[ -n "${DB_HOST}" ] || missing_var "DB_HOST"
fi

# FTP CHECKS
if [ "${FTP_ENABLED}" == 1 ] && [ "${RSYNC_ENABLED}" == 1 ]; then
	logit "fatal: please choose between FTP_ENABLED and RSYNC_ENABLED"
fi
if [ "${FTP_ENABLED}" == 1 ]; then
	# check variables
	[ -n "${FTP_USER}" ] || missing_var "FTP_USER"
	[ -n "${FTP_PASS}" ] || missing_var "FTP_PASS"
	[ -n "${FTP_HOST}" ] || missing_var "FTP_HOST"
	[ -n "${FTP_CDIR}" ] || missing_var "FTP_CDIR"
fi

# RSYNC CHECKS
if [ "${RSYNC_ENABLED}" == 1 ]; then
	# check variables
	[ -n "${RSYNC_HOST}" ] || missing_var "RSYNC_HOST"
	[ -n "${RSYNC_CDIR}" ] || missing_var "RSYNC_CDIR"
fi


##########################################################################
# start backup							   #
##########################################################################

is_running ${APP_BASENAME} $$
if [ ${running_pid} -gt 0 ]; then
	logit "fatal: another ${APP_BASENAME} with pid ${running_pid} is already running"
fi

# FS BACKUP
logit "debug: commencing FS Backup"
for FS_SOUREDIR in ${FS_SOUREDIRS}; do
	if [ ! -d "${FS_SOUREDIR}" ];then
		logit "critical: trying to create an FS backup of ${FS_SOUREDIR} but it does not exist, more details in ${LOG_FILE}"
	else
		FS_SOURCESIZE=$(${CMD_DU} --si -s ${FS_SOUREDIR} |${CMD_GAWK} '{print $1}')
		DIRASFILE=$(echo "${FS_SOUREDIR}" |${CMD_SED} 's#/#-#g' |${CMD_CUT} -d'-' -f'2-');
		DEST="${FS_DESTINDIR}/${DATESTR}-FS-${DIRASFILE}.tar${GZIP_ENABLED_X_EXT}"
		SNAR="${FS_DESTINDIR}/${DATESTR}-FS-${DIRASFILE}.tar${GZIP_ENABLED_X_EXT}.snar"
		ENCR="${FS_DESTINDIR}/${DATESTR}-FS-${DIRASFILE}.tar${GZIP_ENABLED_X_EXT}.enc"

		if [ "${ENCRYPTION_ENABLED}" == 1 ]; then
			# encrypt. do not use incremental in case of encryption
			logit "info: backing up and encrypting directory ${FS_SOUREDIR} (${FS_SOURCESIZE}) to ${ENCR}"
	 ${CMD_TAR} ${TAR_EXCLUDE_X_TAR} -pc${GZIP_ENABLED_X_TAR} ${FS_SOUREDIR} 2>>${LOG_FILE} | ${CMD_OPENSSL} enc -aes-256-cbc -salt -k ${ENCRYPTION_PASS} -out ${ENCR}
	CMD="${CMD_TAR} ${TAR_EXCLUDE_X_TAR} -pc${GZIP_ENABLED_X_TAR} ${FS_SOUREDIR} 2>>${LOG_FILE} | ${CMD_OPENSSL} enc -aes-256-cbc -salt -k ${ENCRYPTION_PASS} -out ${ENCR}"
	else
			#normal tar+gzip
			logit "info: backing up directory ${FS_SOUREDIR} (${FS_SOURCESIZE}) to ${DEST}"
	 ${CMD_TAR} ${TAR_EXCLUDE_X_TAR} -pc${GZIP_ENABLED_X_TAR}f ${DEST} --listed-incremental=${SNAR} ${FS_SOUREDIR} 1>/dev/null 2>>${LOG_FILE}
			CMD="${CMD_TAR} ${TAR_EXCLUDE_X_TAR} -pc${GZIP_ENABLED_X_TAR}f ${DEST} --listed-incremental=${SNAR} ${FS_SOUREDIR} 1>/dev/null 2>>${LOG_FILE}"
		fi

		if [ $? -ne 0 ]; then
			logit "critical: FS backup of ${FS_SOUREDIR} (${FS_SOURCESIZE}) returned errors (${CMD}), more details in ${LOG_FILE}"
		else
			logit "debug: (${CMD}) finished without errors"
		fi
	fi
done

# SY BACKUP
if [ "${SY_ENABLED}" == 1 ]; then
	logit "debug: commencing SY Backup"
	# dpkg get-selections
	if [ -n "${CMD_DPKG}" ];then
		DEST="${FS_DESTINDIR}/${DATESTR}-SY-dpkg-selection.txt.bz2"
		ENCR="${FS_DESTINDIR}/${DATESTR}-SY-dpkg-selection.txt.bz2.enc"

		if [ "${ENCRYPTION_ENABLED}" == 1 ]; then
			# encrypt.
			logit "info: backing up and encrypting dpkg-selection to ${ENCR}"
	 ${CMD_DPKG} --get-selections | ${CMD_BZIP2} -qzc1 | ${CMD_OPENSSL} enc -aes-256-cbc -salt -k ${ENCRYPTION_PASS} -out ${ENCR}
			CMD="${CMD_DPKG} --get-selections | ${CMD_BZIP2} -qzc1 | ${CMD_OPENSSL} enc -aes-256-cbc -salt -k ${ENCRYPTION_PASS} -out ${ENCR}"
		else
			#normal bzip2
			logit "info: backing up dpkg-selection to ${DEST}"
	 ${CMD_DPKG} --get-selections | ${CMD_BZIP2} -qzc1 >${DEST}
			CMD="${CMD_DPKG} --get-selections | ${CMD_BZIP2} -qzc1 >${DEST}"
		fi

		if [ $? -ne 0 ]; then
			logit "critical: SY dpkg backup failed (${CMD}), more details in ${LOG_FILE}"
	else
	logit "debug: (${CMD}) finished without errors"
	fi
	fi

	# installed pear packages
	if [ -n "${CMD_PEAR}" ]; then
		DEST="${FS_DESTINDIR}/${DATESTR}-SY-pear-list.txt.bz2"
		ENCR="${FS_DESTINDIR}/${DATESTR}-SY-pear-list.txt.bz2.enc"
		if [ "${ENCRYPTION_ENABLED}" == 1 ]; then
			# encrypt.
			logit "info: backing up and encrypting pear-list to ${ENCR}"
	 ${CMD_PEAR} list | ${CMD_BZIP2} -qzc1 | ${CMD_OPENSSL} enc -aes-256-cbc -salt -k ${ENCRYPTION_PASS} -out ${ENCR}
			CMD="${CMD_PEAR} list | ${CMD_BZIP2} -qzc1 | ${CMD_OPENSSL} enc -aes-256-cbc -salt -k ${ENCRYPTION_PASS} -out ${ENCR}"
		else
			#normal bzip2
			logit "info: backing up pear-list to ${DEST}"
	 ${CMD_PEAR} list | ${CMD_BZIP2} -qzc1 >${DEST}
			CMD="${CMD_PEAR} list | ${CMD_BZIP2} -qzc1 >${DEST}"
		fi

		if [ $? -ne 0 ]; then
			logit "warning: SY pear backup failed (${CMD}), more details in ${LOG_FILE}"
	else
	logit "debug: (${CMD}) finished without errors"
	fi
	fi

	# installed crontab packages
	if [ -n "${CMD_CRONTAB}" ]; then
	DEST="${FS_DESTINDIR}/${DATESTR}-SY-crontab-list.txt.bz2"
	ENCR="${FS_DESTINDIR}/${DATESTR}-SY-crontab-list.txt.bz2.enc"
	if [ "${ENCRYPTION_ENABLED}" == 1 ]; then
	# encrypt.
	logit "info: backing up and encrypting crontab-list to ${ENCR}"
	 ${CMD_CRONTAB} -l | ${CMD_BZIP2} -qzc1 | ${CMD_OPENSSL} enc -aes-256-cbc -salt -k ${ENCRYPTION_PASS} -out ${ENCR}
	CMD="${CMD_CRONTAB} -l | ${CMD_BZIP2} -qzc1 | ${CMD_OPENSSL} enc -aes-256-cbc -salt -k ${ENCRYPTION_PASS} -out ${ENCR}"
	else
	#normal bzip2
	logit "info: backing up crontab-list to ${DEST}"
	 ${CMD_CRONTAB} -l | ${CMD_BZIP2} -qzc1 >${DEST}
	CMD="${CMD_CRONTAB} -l | ${CMD_BZIP2} -qzc1 >${DEST}"
	fi

	if [ $? -ne 0 ]; then
	logit "warning: SY crontab backup failed (${CMD}), more details in ${LOG_FILE}"
	else
	logit "debug: (${CMD}) finished without errors"
	fi
	fi
fi

# DB BACKUP
if [ "${DB_ENABLED}" == 1 ]; then
	logit "debug: commencing DB Backup"
	# db data backup
	if [ -n "${CMD_MYSQL}" ] && [ -f "${CMD_MYSQL}" ] && [ -x "${CMD_MYSQL}" ];then
		DATABASES=`echo "SHOW DATABASES;" | ${CMD_MYSQL} -p${DB_PASS} -u ${DB_USER} -h ${DB_HOST}`
		for DATABASE in $DATABASES; do
			if [ "${DATABASE}" != "Database" ]; then
				DEST="${FS_DESTINDIR}/${DATESTR}-DB-${DATABASE}.sql.bz2"
				ENCR="${FS_DESTINDIR}/${DATESTR}-DB-${DATABASE}.sql.bz2.enc"
				if [ "${ENCRYPTION_ENABLED}" == 1 ]; then
					# encrypt.
					logit "info: backing up (${CMD_MYSQL}) and encrypting database ${DATABASE} to ${DEST}"
		 ${CMD_MYSQLDUMP} -Q -B --all --complete-insert --quote-names --add-drop-table -p${DB_PASS} -u${DB_USER} -h${DB_HOST} ${DATABASE} 2>>${LOG_FILE} | ${CMD_BZIP2} -qzc1 | ${CMD_OPENSSL} enc -aes-256-cbc -salt -k ${ENCRYPTION_PASS} -out ${ENCR}
					CMD="${CMD_MYSQLDUMP} -Q -B --all --complete-insert --quote-names --add-drop-table -p${DB_PASS} -u${DB_USER} -h${DB_HOST} ${DATABASE} 2>>${LOG_FILE} | ${CMD_BZIP2} -qzc1 | ${CMD_OPENSSL} enc -aes-256-cbc -salt -k ${ENCRYPTION_PASS} -out ${ENCR}"
				else
					# normal bzip2
					logit "info: backing up (${CMD_MYSQL}) database ${DATABASE} to ${DEST}"
		 ${CMD_MYSQLDUMP} -Q -B --all --complete-insert --quote-names --add-drop-table -p${DB_PASS} -u${DB_USER} -h${DB_HOST} ${DATABASE} 2>>${LOG_FILE} | ${CMD_BZIP2} -qzc1 >${DEST}
					CMD="${CMD_MYSQLDUMP} -Q -B --all --complete-insert --quote-names --add-drop-table -p${DB_PASS} -u${DB_USER} -h${DB_HOST} ${DATABASE} 2>>${LOG_FILE} | ${CMD_BZIP2} -qzc1 >${DEST}"
				fi

				if [ $? -ne 0 ]; then
					logit "critical: DB backup of ${DATABASE} returned errors, more details in ${LOG_FILE}"
	else
		logit "debug: (${CMD}) finished without errors"
	fi
			fi
		done
	else
		logit "critical: MYSQL command(${CMD_MYSQL}) is not available"
	fi
fi

# FTP BACKUP
if [ "${FTP_ENABLED}" == 1 ]; then
	logit "debug: commencing FTP Upload"
	# ftp upload
	if [ -n "${CMD_FTPUPLOAD}" ] && [ -f "${CMD_FTPUPLOAD}" ] && [ -x "${CMD_FTPUPLOAD}" ];then
		logit "info: uploading ${FS_DESTINDIR}/* to ftp://${FTP_HOST}:${FTP_CDIR}/"

	is_running "ftp-upload" $$
	if [ ${running_pid} -gt 0 ]; then
	logit "critical: another ftp-upload with pid ${running_pid} is already running"
	else
	 ${CMD_FTPUPLOAD} -h ${FTP_HOST} -u ${FTP_USER} --password ${FTP_PASS} -b -d ${FTP_CDIR} ${FS_DESTINDIR}/* 1>/dev/null 2>>${LOG_FILE}
	CMD="${CMD_FTPUPLOAD} -h ${FTP_HOST} -u ${FTP_USER} --password ${FTP_PASS} -b -d ${FTP_CDIR} ${FS_DESTINDIR}/* 1>/dev/null 2>>${LOG_FILE}"

	if [ $? -ne 0 ]; then
	logit "critical: FTP upload returned errors, more details in ${LOG_FILE}"
	else
	logit "debug: (${CMD}) finished without errors"
	fi
	fi

		# cleanup after upload
		if [ "${FTP_CLEANUPAFTERUPLOAD}" == 1 ]; then
			logit "info: cleaning up ${FS_DESTINDIR}"
			${CMD_RM} -f ${FS_DESTINDIR}/${DATESTR}-FS-*
			${CMD_RM} -f ${FS_DESTINDIR}/${DATESTR}-DB-*
			${CMD_RM} -f ${FS_DESTINDIR}/${DATESTR}-SY-*
		fi
	else
		logit "critical: FTPUPLOAD command(${CMD_FTPUPLOAD}) is not available"
	fi
fi

# RSYNC BACKUP
if [ "${RSYNC_ENABLED}" == 1 ]; then
	logit "debug: commencing RSYNC Upload"
	# rsync upload
	if [ -n "${CMD_RSYNC}" ] && [ -f "${CMD_RSYNC}" ] && [ -x "${CMD_RSYNC}" ];then
		logit "info: rsyncing ${FS_DESTINDIR}/* to ${RSYNC_USER}@${RSYNC_HOST}:${RSYNC_CDIR}/"

	is_running "rsync" $$
	if [ ${running_pid} -gt 0 ]; then
	logit "critical: another rsync with pid ${running_pid} is already running"
	else
	 ${CMD_RSYNC} -raz ${FS_DESTINDIR}/* ${RSYNC_USER}@${RSYNC_HOST}:${RSYNC_CDIR}/  1>/dev/null 2>>${LOG_FILE}
	CMD="${CMD_RSYNC} -raz ${FS_DESTINDIR}/* ${RSYNC_USER}@${RSYNC_HOST}:${RSYNC_CDIR}/  1>/dev/null 2>>${LOG_FILE}"

	if [ $? -ne 0 ]; then
	logit "critical: RSYNC upload returned errors, more details in ${LOG_FILE}"
	else
	logit "debug: (${CMD}) finished without errors"
	fi
	fi
	else
		logit "critical: RSYNC command(${CMD_RSYNC}) is not available"
	fi
fi

logit "info: ${APP_BASENAME} completed"
quit
