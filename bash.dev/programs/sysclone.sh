#!/bin/bash
#/**
# * Clones a system's: database, config, files, etc. Extremely dangerous!!!
# * 
# * @author    Kevin van Zonneveld <kevin@vanzonneveld.net>
# * @copyright 2008 Kevin van Zonneveld (http://kevin.vanzonneveld.net)
# * @license   http://www.opensource.org/licenses/bsd-license.php New BSD Licence
# * @version   SVN: Release: $Id$
# * @link      http://kevin.vanzonneveld.net/
# */


# Includes
###############################################################
source $(realpath "$(dirname ${0})/../functions/log.sh")     # make::include
source $(realpath "$(dirname ${0})/../functions/toUpper.sh") # make::include
source $(realpath "$(dirname ${0})/../functions/commandInstall.sh") # make::include
source $(realpath "$(dirname ${0})/../functions/commandTest.sh") # make::include
source $(realpath "$(dirname ${0})/../functions/commandTestHandle.sh") # make::include
source $(realpath "$(dirname ${0})/../functions/getWorkingDir.sh") # make::include
source $(realpath "$(dirname ${0})/../functions/sshKeyInstall.sh") # make::include
source $(realpath "$(dirname ${0})/../functions/sshKeyVerify.sh") # make::include
source $(realpath "$(dirname ${0})/../functions/isPortOpen.sh") # make::include


# Check for program requirements
###############################################################
commandTestHandle "bash" "bash" "EMERG" "NOINSTALL"
commandTestHandle "aptitude" "aptitude" "DEBUG" "NOINSTALL" # Just try to set CMD_APTITUDE, produces DEBUG msg if not found
commandTestHandle "egrep" "pcregrep"
commandTestHandle "awk"
commandTestHandle "ping"
commandTestHandle "sort"
commandTestHandle "uniq"
commandTestHandle "realpath"
commandTestHandle "whoami"
commandTestHandle "netcat"
commandTestHandle "ssh"
commandTestHandle "tail"


# Config
###############################################################
OUTPUT_DEBUG=1
DIR_ROOT=$(getWorkingDir)
FILE_CONFIG=${DIR_ROOT}/sysclone.conf

CMD_MYSQL="/usr/bin/mysql"
CMD_MYSQLDUMP="/usr/bin/mysqldump"
CMD_RSYNCDEL="rsync -a --itemize-changes --delete"
CMD_RSYNC="rsync -a --itemize-changes"

[ -f  ${FILE_CONFIG} ] || log "No config file found. Maybe: cp -af ${FILE_CONFIG}.default ${FILE_CONFIG} && nano ${FILE_CONFIG}" "EMERG"
source ${FILE_CONFIG}

if [ "${HOST_GET}" == "localhost" ] && [ -f /etc/mysql/debian.cnf ]; then
	CMD_MYSQL_GET="${CMD_MYSQL} --defaults-file=/etc/mysql/debian.cnf"
	CMD_MYSQLDUMP_GET="${CMD_MYSQLDUMP} --defaults-file=/etc/mysql/debian.cnf"
else
	CMD_MYSQL_GET="${CMD_MYSQL} -p${DB_PASS_GET} -u${DB_USER_GET} -h${HOST_GET}"
	CMD_MYSQLDUMP_GET="${CMD_MYSQLDUMP} -p${DB_PASS_GET} -u${DB_USER_GET} -h${HOST_GET}"
fi

if [ "${HOST_PUT}" == "localhost" ] && [ -f /etc/mysql/debian.cnf ]; then
    CMD_MYSQL_PUT="${CMD_MYSQL} --defaults-file=/etc/mysql/debian.cnf"
    CMD_MYSQLDUMP_PUT="${CMD_MYSQLDUMP} --defaults-file=/etc/mysql/debian.cnf"
else
    CMD_MYSQL_PUT="${CMD_MYSQL} -p${DB_PASS_PUT} -u${DB_USER_PUT} -h${HOST_PUT}"
    CMD_MYSQLDUMP_PUT="${CMD_MYSQLDUMP} -p${DB_PASS_PUT} -u${DB_USER_PUT} -h${HOST_PUT}"
fi

# Setup run parameters
###############################################################

if [ "${HOST_GET}" != "localhost" ] && [ "${HOST_PUT}" != "localhost" ]; then
    echo "Error. Either HOST_GET or HOST_PUT needs to be localhost. Can't sync between 2 remote machines. I'm not superman."
    exit 0
fi

if [ "${HOST_GET}" = "localhost" ]; then
    RSYNC_HOST_GET=""
else
    HOST_SSH="${HOST_GET}"
	RSYNC_HOST_GET="${HOST_GET}:"
fi

if [ "${HOST_PUT}" = "localhost" ]; then
    RSYNC_HOST_PUT=""
else
    HOST_SSH="${HOST_PUT}"
	RSYNC_HOST_PUT="${HOST_PUT}:"
fi


# Private Functions
###############################################################
function exeGet {
    local cmd="${1}"
    if [ "${HOST_GET}" = "${HOST_SSH}" ]; then
    	ssh ${HOST_GET} "${cmd}"
    else
        /bin/bash -c "${cmd}"
    fi
}

function exePut {
	set -x
	local cmd="${1}"
    if [ "${HOST_PUT}" = "${HOST_SSH}" ]; then
    	ssh ${HOST_PUT} "${cmd}"
    else
        /bin/bash -c "${cmd}"
    fi
    set +x
}


# Run
###############################################################

# Test Port
log "verifying connectivity of ${HOST_SSH}"
OK=$(isPortOpen ${HOST_SSH} 22 1)
if [ "${OK}" = "0" ]; then
	log "Unable to reach ${HOST_SSH} at port 22" "EMERG"
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
	fi
fi 

# Start syncing
log "package sources sync"
if [ "${DO_SOURCES}" = 1 ]; then
    ${CMD_RSYNC} ${RSYNC_HOST_GET}/etc/apt/sources.list  ${RSYNC_HOST_PUT}/etc/apt/
    ${AT_HOST_PUT} aptitude -y update > /dev/null && aptitude -y dist-upgrade
    log " [done] "
else 
    log " [skipped] "
fi

log "package sync"
if [ "${DO_PACKAGES}" = 1 ]; then
	exeGet "dpkg --get-selections > /tmp/dpkglist.txt"
    ${CMD_RSYNC} ${RSYNC_HOST_GET}/tmp/dpkglist.txt ${RSYNC_HOST_PUT}/tmp/
    exePut "cat /tmp/dpkglist.txt | dpkg --set-selections"
    exePut "apt-get -y update > /dev/null"
    exePut "apt-get -y dselect-upgrade"
    exePut ""
    log " [done] "
else 
    log " [skipped] "
fi

log "PEAR package sync"
if [ "${DO_PEARPKG}" = 1 ]; then
    exeGet "sudo pear -q list | egrep 'alpha|beta|stable' |awk '{print \$1}' > /tmp/pearlist.txt"
    ${CMD_RSYNC} ${RSYNC_HOST_GET}/tmp/pearlist.txt ${RSYNC_HOST_PUT}/tmp/
    exePut "cat /tmp/pearlist.txt |awk '{print \"pear install -f \"\$0}' |sudo bash"
    log " [done] "
else 
    log " [skipped] "
fi

log "account sync"
if [ "${DO_ACCOUNTS}" = 1 ]; then
    ${CMD_RSYNC} ${RSYNC_HOST_GET}/etc/passwd  ${RSYNC_HOST_PUT}/etc/
    ${CMD_RSYNC} ${RSYNC_HOST_GET}/etc/passwd- ${RSYNC_HOST_PUT}/etc/
    ${CMD_RSYNC} ${RSYNC_HOST_GET}/etc/shadow  ${RSYNC_HOST_PUT}/etc/
    ${CMD_RSYNC} ${RSYNC_HOST_GET}/etc/shadow- ${RSYNC_HOST_PUT}/etc/
    ${CMD_RSYNC} ${RSYNC_HOST_GET}/etc/group   ${RSYNC_HOST_PUT}/etc/
    log " [done] "
else 
    log " [skipped] "
fi

log "config sync"
if [ "${DO_CONFIG}" = 1 ]; then
    ${CMD_RSYNCDEL} ${RSYNC_HOST_GET}/etc/mysql/   ${RSYNC_HOST_PUT}/etc/mysql
    ${CMD_RSYNCDEL} ${RSYNC_HOST_GET}/etc/apache2/ ${RSYNC_HOST_PUT}/etc/apache2
    ${CMD_RSYNCDEL} ${RSYNC_HOST_GET}/etc/php5/    ${RSYNC_HOST_PUT}/etc/php5
    ${CMD_RSYNCDEL} ${RSYNC_HOST_GET}/etc/postfix/ ${RSYNC_HOST_PUT}/etc/postfix
    log " [done] "
else 
    log " [skipped] "
fi

log "database sync"
if [ "${DO_DATABASE}" = 1 ]; then
	log "verifying mysql source connection"
	# Test MySQL_GET access 
	OK=$(echo "SELECT User FROM user WHERE User='root' LIMIT 1" | ${CMD_MYSQL_GET} --connect-timeout=3 mysql | ${CMD_TAIL} -n1)
	if [ "${OK}" != "root" ]; then
		log "Unable to access MySQL Source: ${OK}" "EMERG"
	else
	    log " [okay] "
	fi
	
    log "verifying mysql destination connection"
    # Test MySQL_PUT access 
    OK=$(echo "SELECT User FROM user WHERE User='root' LIMIT 1" | ${CMD_MYSQL_PUT} --connect-timeout=3 mysql | ${CMD_TAIL} -n1)
    if [ "${OK}" != "root" ]; then
        log "Unable to access MySQL Destination: ${OK}" "EMERG"
    else
        log " [okay] "
    fi
    
	# Export everything
    DATABASES=`echo "SHOW DATABASES;" | ${CMD_MYSQL_GET}`
    for DATABASE in $DATABASES; do
        if [ "${DATABASE}" != "Database" ]; then
            log "transmitting ${DATABASE}"
            echo "CREATE DATABASE IF NOT EXISTS ${DATABASE}" | ${CMD_MYSQL_PUT}
            ${CMD_MYSQLDUMP_GET} -Q -B --create-options --delayed-insert \
                --complete-insert --quote-names --add-drop-table ${DATABASE} | ${CMD_MYSQL_PUT} ${DATABASE}
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
    #${CMD_RSYNCDEL} ${RSYNC_HOST_GET}/root/* ${RSYNC_HOST_PUT}/root/
    ${CMD_RSYNCDEL} ${RSYNC_HOST_GET}/home/ ${RSYNC_HOST_PUT}/home
    ${CMD_RSYNCDEL} ${RSYNC_HOST_GET}/var/www/ ${RSYNC_HOST_PUT}/var/www
    ${CMD_RSYNCDEL} ${RSYNC_HOST_GET}/var/lib/svn/ ${RSYNC_HOST_PUT}/var/lib/svn
    log " [done] "
else 
    log " [skipped] "
fi
