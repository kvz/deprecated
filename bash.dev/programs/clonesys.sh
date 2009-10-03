#!/bin/bash
#/**
# * Will destroy your servers and ruin your career
# * Or it will try to copy all important packages, settings, and files from
# * one ubuntu server to another. Extremely dangerous!!! Only use in testing
# * environments!
# * 
# * @author    Kevin van Zonneveld <kevin@vanzonneveld.net>
# * @copyright 2008 Kevin van Zonneveld (http://kevin.vanzonneveld.net)
# * @license   http://www.opensource.org/licenses/bsd-license.php New BSD Licence
# * @version   SVN: Release: $Id: clonesys.sh 199 2008-11-10 11:26:34Z kevin $
# * @link      http://kevin.vanzonneveld.net/
# */

# Includes
###############################################################
source $(echo "$(dirname ${0})/../functions/log.sh")     # make::include
source $(echo "$(dirname ${0})/../functions/toUpper.sh") # make::include
source $(echo "$(dirname ${0})/../functions/commandInstall.sh") # make::include
source $(echo "$(dirname ${0})/../functions/commandTest.sh") # make::include
source $(echo "$(dirname ${0})/../functions/commandTestHandle.sh") # make::include
source $(echo "$(dirname ${0})/../functions/getWorkingDir.sh") # make::include

source $(echo "$(dirname ${0})/../functions/sshKeyInstall.sh") # make::include
source $(echo "$(dirname ${0})/../functions/sshKeyVerify.sh") # make::include
source $(echo "$(dirname ${0})/../functions/isPortOpen.sh") # make::include

# Private Functions
###############################################################

function usage {
	
	if [ -n "${1}" ]; then
	    echo "" 
		echo "Error: ${1}"
	fi
	echo "";
    echo "This program will totally destory your servers and ruin your carreer."
    echo "Or it will try to copy all important packages, settings, and files from"
    echo "one ubuntu server to another.  Extremely dangerous!!! Only use in testing"
    echo "environments!"
    echo ""
    echo "Usage: ${0} localhost HOST_DEST"
    echo "  or   ${0} HOST_SOURCE localhost"
    echo ""
    echo "Options:"
    echo "--help Shows this page" 
    echo ""
    echo "Config:"
    echo "Must be defined in ${FILE_CONFIG}" 

    exit 0
}

function exeSource {
    local cmd="${1}"
    if [ "${HOST_SOURCE}" = "${HOST_SSH}" ]; then
        ssh ${HOST_SOURCE} "${cmd}"
    else
        /bin/bash -c "${cmd}"
    fi
}

function exeDest {
    local cmd="${1}"
    if [ "${HOST_DEST}" = "${HOST_SSH}" ]; then
        ssh ${HOST_DEST} "${cmd}"
    else
        /bin/bash -c "${cmd}"
    fi
}

# Essential config
###############################################################
OUTPUT_DEBUG=0
DIR_ROOT=$(getWorkingDir)
FILE_CONFIG=${DIR_ROOT}/sysclone.conf

# Check for program requirements
###############################################################
commandTestHandle "bash" "bash" "EMERG" "NOINSTALL"
commandTestHandle "aptitude" "aptitude" "DEBUG" "NOINSTALL" # Just try to set CMD_APTITUDE, produces DEBUG msg if not found
commandTestHandle "sed" "sed" "DEBUG" "NOINSTALL" # Just try to set CMD_SED, helps with locating CMDs with dashes in it
commandTestHandle "egrep" "grep" "EMERG"
commandTestHandle "grep" "grep" "EMERG"
commandTestHandle "awk" "gawk" "EMERG"
commandTestHandle "sort" "coreutils" "EMERG"
commandTestHandle "uniq" "coreutils" "EMERG"
commandTestHandle "dirname" "coreutils" "EMERG"
commandTestHandle "realpath" "realpath" "EMERG"
commandTestHandle "sed" "sed" "EMERG"

commandTestHandle "ping" "iputils-ping" "EMERG"
commandTestHandle "whoami" "coreutils" "EMERG"
commandTestHandle "netcat" "coreutils" "EMERG"
commandTestHandle "ssh" "openssh-client" "EMERG"
commandTestHandle "tail" "coreutils" "EMERG"

# Config
###############################################################
CMD_MYSQL="/usr/bin/mysql"
CMD_MYSQLDUMP="/usr/bin/mysqldump"

[ -f ${FILE_CONFIG} ] || log "No config file found. Maybe: cp -af ${FILE_CONFIG}.default ${FILE_CONFIG} && nano ${FILE_CONFIG}" "EMERG"
source ${FILE_CONFIG}

# Setup run parameters
###############################################################
HOST_SOURCE="${1}"
HOST_DEST="${2}"

# Parameter Check
[ -n "${HOST_SOURCE}" ] || usage "Missing parameter 1: HOST_SOURCE"
[ -n "${HOST_DEST}" ] || usage "Missing parameter 2: HOST_DEST"

if [ "${HOST_SOURCE}" != "localhost" ] && [ "${HOST_DEST}" != "localhost" ]; then
    usage "Either HOST_SOURCE or HOST_DEST needs to be localhost. Can't sync between 2 remote machines. I'm not superman."
fi
if [ "${HOST_SOURCE}" == "localhost" ] && [ "${HOST_DEST}" == "localhost" ]; then
    usage "Either HOST_SOURCE or HOST_DEST needs to be localhost. Can't sync locally. I'm not superman."
fi

# Rsync options
CMD_RSYNC="rsync -a --itemize-changes"
CMD_RSYNCDEL="${CMD_RSYNC}"
if [ "${CLEANSE}" = 1 ]; then
    CMD_RSYNCDEL="${CMD_RSYNCDEL} --delete"
fi 


# MySQL Order
if [ "${HOST_SOURCE}" == "localhost" ] && [ -f /etc/mysql/debian.cnf ]; then
	CMD_MYSQL_SOURCE="${CMD_MYSQL} --defaults-file=/etc/mysql/debian.cnf"
	CMD_MYSQLDUMP_SOURCE="${CMD_MYSQLDUMP} --defaults-file=/etc/mysql/debian.cnf"
else
	CMD_MYSQL_SOURCE="${CMD_MYSQL} -p${DB_PASS_SOURCE} -u${DB_USER_SOURCE} -h${HOST_SOURCE}"
	CMD_MYSQLDUMP_SOURCE="${CMD_MYSQLDUMP} -p${DB_PASS_SOURCE} -u${DB_USER_SOURCE} -h${HOST_SOURCE}"
fi

if [ "${HOST_DEST}" == "localhost" ] && [ -f /etc/mysql/debian.cnf ]; then
    CMD_MYSQL_DEST="${CMD_MYSQL} --defaults-file=/etc/mysql/debian.cnf"
    CMD_MYSQLDUMP_DEST="${CMD_MYSQLDUMP} --defaults-file=/etc/mysql/debian.cnf"
else
    CMD_MYSQL_DEST="${CMD_MYSQL} -p${DB_PASS_DEST} -u${DB_USER_DEST} -h${HOST_DEST}"
    CMD_MYSQLDUMP_DEST="${CMD_MYSQLDUMP} -p${DB_PASS_DEST} -u${DB_USER_DEST} -h${HOST_DEST}"
fi

# SSH Order
if [ "${HOST_SOURCE}" = "localhost" ]; then
    RSYNC_HOST_SOURCE=""
else
    HOST_SSH="${HOST_SOURCE}"
	RSYNC_HOST_SOURCE="${HOST_SOURCE}:"
fi

if [ "${HOST_DEST}" = "localhost" ]; then
    RSYNC_HOST_DEST=""
else
    HOST_SSH="${HOST_DEST}"
	RSYNC_HOST_DEST="${HOST_DEST}:"
fi

# Run
###############################################################

# Test Port
log "verifying connectivity of ${HOST_SSH}"
OK=$(isPortOpen ${HOST_SSH} 22 1)
if [ "${OK}" = "0" ]; then
	log "Unable to reach ${HOST_SSH} at port 22" "EMERG"
else
    log " [okay] "
fi 

# SSH Keys
log "verifying ssh access of ${HOST_SSH}"
OK=$(sshKeyVerify ${HOST_SSH} root)
if [ "${OK}" = "0" ]; then
	log "install ssh key at ${HOST_SSH}"
	sshKeyInstall ${HOST_SSH} root NOASK 
	OK=$(sshKeyVerify ${HOST_SSH} root)
	if [ "${OK}" = "0" ]; then
	    log "Unable to install ssh keys ${HOST_SSH} at port 22" "EMERG"
	else
	    log " [okay]"
	fi
fi 

# Start syncing
log "package sources sync"
if [ "${DO_SOURCES}" = 1 ]; then
    ${CMD_RSYNC} ${RSYNC_HOST_SOURCE}/etc/apt/sources.list  ${RSYNC_HOST_DEST}/etc/apt/
    ${AT_HOST_DEST} aptitude -y update > /dev/null && aptitude -y dist-upgrade
    log " [done] "
else 
    log " [skipped] "
fi

log "package sync"
if [ "${DO_PACKAGES}" = 1 ]; then
	exeSource "dpkg --get-selections > /tmp/dpkglist.txt"
    ${CMD_RSYNC} ${RSYNC_HOST_SOURCE}/tmp/dpkglist.txt ${RSYNC_HOST_DEST}/tmp/
    exeDest "cat /tmp/dpkglist.txt | dpkg --set-selections"
    exeDest "apt-get -y update > /dev/null"
    exeDest "apt-get -y dselect-upgrade"
    exeDest ""
    log " [done] "
else 
    log " [skipped] "
fi

log "PEAR package sync"
if [ "${DO_PEARPKG}" = 1 ]; then
    exeSource "sudo pear -q list | egrep 'alpha|beta|stable' |awk '{print \$1}' > /tmp/pearlist.txt"
    ${CMD_RSYNC} ${RSYNC_HOST_SOURCE}/tmp/pearlist.txt ${RSYNC_HOST_DEST}/tmp/
    exeDest "cat /tmp/pearlist.txt |awk '{print \"pear install -f \"\$0}' |sudo bash"
    log " [done] "
else 
    log " [skipped] "
fi

log "account sync"
if [ "${DO_ACCOUNTS}" = 1 ]; then
    ${CMD_RSYNC} ${RSYNC_HOST_SOURCE}/etc/passwd  ${RSYNC_HOST_DEST}/etc/
    ${CMD_RSYNC} ${RSYNC_HOST_SOURCE}/etc/passwd- ${RSYNC_HOST_DEST}/etc/
    ${CMD_RSYNC} ${RSYNC_HOST_SOURCE}/etc/shadow  ${RSYNC_HOST_DEST}/etc/
    ${CMD_RSYNC} ${RSYNC_HOST_SOURCE}/etc/shadow- ${RSYNC_HOST_DEST}/etc/
    ${CMD_RSYNC} ${RSYNC_HOST_SOURCE}/etc/group   ${RSYNC_HOST_DEST}/etc/
    log " [done] "
else 
    log " [skipped] "
fi

log "config sync"
if [ "${DO_CONFIG}" = 1 ]; then
    ${CMD_RSYNCDEL} ${RSYNC_HOST_SOURCE}/etc/mysql/   ${RSYNC_HOST_DEST}/etc/mysql
    ${CMD_RSYNCDEL} ${RSYNC_HOST_SOURCE}/etc/apache2/ ${RSYNC_HOST_DEST}/etc/apache2
    ${CMD_RSYNCDEL} ${RSYNC_HOST_SOURCE}/etc/php5/    ${RSYNC_HOST_DEST}/etc/php5
    ${CMD_RSYNCDEL} ${RSYNC_HOST_SOURCE}/etc/postfix/ ${RSYNC_HOST_DEST}/etc/postfix
    log " [done] "
else 
    log " [skipped] "
fi

log "database sync"
if [ "${DO_DATABASE}" = 1 ]; then
	log "verifying mysql source connection"
	# Test MySQL_SOURCE access 
	OK=$(echo "SELECT User FROM user WHERE User='root' LIMIT 1" | ${CMD_MYSQL_SOURCE} --connect-timeout=3 mysql | ${CMD_TAIL} -n1)
	if [ "${OK}" != "root" ]; then
		log "Unable to access MySQL Source: ${OK}" "EMERG"
	else
	    log " [okay] "
	fi
	
    log "verifying mysql destination connection"
    # Test MySQL_DEST access 
    OK=$(echo "SELECT User FROM user WHERE User='root' LIMIT 1" | ${CMD_MYSQL_DEST} --connect-timeout=3 mysql | ${CMD_TAIL} -n1)
    if [ "${OK}" != "root" ]; then
        log "Unable to access MySQL Destination: ${OK}" "EMERG"
    else
        log " [okay] "
    fi
    
	# Export everything
    DATABASES=$(echo "SHOW DATABASES;" | ${CMD_MYSQL_SOURCE})
    for DATABASE in $DATABASES; do
        if [ "${DATABASE}" != "Database" ]; then
            log "transmitting ${DATABASE}"
            echo "CREATE DATABASE IF NOT EXISTS ${DATABASE}" | ${CMD_MYSQL_DEST}
            ${CMD_MYSQLDUMP_SOURCE} -Q -B --create-options --delayed-insert \
                --complete-insert --quote-names --add-drop-table ${DATABASE} | ${CMD_MYSQL_DEST} ${DATABASE}
        fi
    done
    log " [done] "
else 
    log " [skipped] "
fi

log "directory sync"
if [ "${DO_DIRS}" = 1 ]; then
    # root must be copied like /*, because of ssh keys
    # actually, don't do root at all because of sync script!
    #${CMD_RSYNCDEL} ${RSYNC_HOST_SOURCE}/root/* ${RSYNC_HOST_DEST}/root/
    ${CMD_RSYNCDEL} ${RSYNC_HOST_SOURCE}/home/ ${RSYNC_HOST_DEST}/home
    ${CMD_RSYNCDEL} ${RSYNC_HOST_SOURCE}/var/www/ ${RSYNC_HOST_DEST}/var/www
    ${CMD_RSYNCDEL} ${RSYNC_HOST_SOURCE}/var/lib/svn/ ${RSYNC_HOST_DEST}/var/lib/svn
    log " [done] "
else 
    log " [skipped] "
fi