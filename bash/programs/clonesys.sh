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

# log() was auto-included from '/../functions/log.sh' by make.sh
#/**
# * Logs a message
# * 
# * @param string $1 String
# * @param string $2 Log level. EMERG exists app.
# */
function log(){
    # Levels:
    # EMERG
    # ALERT
    # CRIT
    # ERR
    # WARNING
    # NOTICE
    # INFO
    # DEBUG
    
    # Init
    local line="${1}"
    local levl="${2}"

    # Defaults
    [ -n "${levl}" ] || levl="INFO"
    local show=0
    
    # Allowed to show?  
    if [ "${levl}" == "DEBUG" ]; then
        if [ "${OUTPUT_DEBUG}" = 1 ]; then
            show=1
        fi
    else
        show=1
    fi
    
    # Show
    if [ "${show}" = 1 ];then
        echo "${levl}: ${1}"
    fi
    
    # Die?
    if [ "${levl}" = "EMERG" ]; then
        exit 1
    fi
}

# toUpper() was auto-included from '/../functions/toUpper.sh' by make.sh
#/**
# * Converts a string to uppercase
# * 
# * @param string $1 String
# */
function toUpper(){
   echo "$(echo ${1} |tr '[:lower:]' '[:upper:]')"
}

# commandInstall() was auto-included from '/../functions/commandInstall.sh' by make.sh
#/**
# * Tries to install a package
# * Also saved command location in CMD_XXX
# *
# * @param string $1 Command name
# * @param string $2 Package name
# */
function commandInstall() {
    # Init
    local command=${1}
    local package=${2}
    
    # Show
    echo "Trying to install ${package}"
    
    if [ -n "${CMD_APTITUDE}" ] && [ -x "${CMD_APTITUDE}" ]; then
    	# A new bash session is needed, otherwise apt will break the program flow
        aptRes=$(echo "${CMD_APTITUDE} -y install ${package}" |bash)
    else
        echo "No supported package management tool found"
    fi
}

# commandTest() was auto-included from '/../functions/commandTest.sh' by make.sh
#/**
# * Tests if a command exists, and returns it's location or an error string.
# * Also saved command location in CMD_XXX.
# *
# * @param string $1 Command name
# * @param string $2 Package name
# */
function commandTest(){
    # Init
    local test="/usr/bin/which"; [ -x "${test}" ] && [ -z "${CMD_WHICH}" ] && CMD_WHICH="${test}"
    local command=${1}
    local package=${2}
    local located=$(${CMD_WHICH} ${command})
    
    # Checks
    if [ ! -n "${located}" ]; then
        echo "Command ${command} not found at all, please install before running this program."
    elif [ ! -x "${located}" ]; then
        echo "Command ${command} not executable at ${located}, please install before running this program."
    else
        echo "${located}" 
    fi
}

# commandTestHandle() was auto-included from '/../functions/commandTestHandle.sh' by make.sh
#/**
# * Tests if a command exists, tries to install package,
# * resorts to 'handler' argument on fail. 
# *
# * @param string $1 Command name
# * @param string $2 Package name. Optional. Defaults to Command name
# * @param string $3 Handler. Optional. (Any of the loglevels. Defaults to emerg to exit app)
# * @param string $4 Additional option. Optional.
# */
function commandTestHandle(){
    # Init
    local command="${1}"
    local package="${2}"
    local handler="${3}"
    local optionl="${4}"
    local success="0"
    local varname="CMD_$(toUpper ${command})"
    
    # Only if sed has been found already, use it to replace dashes with underscores
    if [ -n "${CMD_SED}" ] && [ -x "${CMD_SED}" ]; then
        varname=$(echo "${varname}" |${CMD_SED} 's#-#_#g')
    fi
    
    # Checks
    [ -n "${command}" ] || log "testcommand_handle needs a command argument" "EMERG"
    
    # Defaults
    [ -n "${package}" ] || package=${command}
    [ -n "${handler}" ] || handler="EMERG"
    [ -n "${optionl}" ] || optionl=""
    
    # Test command
    local located="$(commandTest ${command} ${package})"
    if [ ! -x "${located}" ]; then
        if [ "${optionl}" != "NOINSTALL" ]; then
            # Try automatic install
            commandInstall ${command} ${package}
             
            # Re-Test command
            located="$(commandTest ${command} ${package})"
            if [ ! -x "${located}" ]; then
                # Still not found
                log "${located}" "${handler}"
            else
                success=1
            fi
        else
            # Not found, but not going to install
            log "${located}" "${handler}"            
        fi
    else
        success=1
    fi
    
    if [ "${success}" = 1 ]; then
        log "Testing for ${command} succeeded" "DEBUG"
        # Okay, Save location in CMD_XXX variable 
        eval ${varname}="${located}"
    fi
}

# getWorkingDir() was auto-included from '/../functions/getWorkingDir.sh' by make.sh
#/**
# * Determines script's working directory
# * 
# * @author    Kevin van Zonneveld <kevin@vanzonneveld.net>
# * @copyright 2008 Kevin van Zonneveld (http://kevin.vanzonneveld.net)
# * @license   http://www.opensource.org/licenses/bsd-license.php New BSD Licence
# * @version   SVN: Release: $Id: getWorkingDir.sh 89 2008-09-05 20:52:48Z kevin $
# * @link      http://kevin.vanzonneveld.net/
# * 
# * @param string PATH Optional path to add
# */
function getWorkingDir {
    echo $(realpath "$(dirname ${0})${1}")
}

# sshKeyInstall() was auto-included from '/../functions/sshKeyInstall.sh' by make.sh
#/**
# * Installs SSH Keys remotely
# * 
# * @author    Kevin van Zonneveld <kevin@vanzonneveld.net>
# * @copyright 2007 Kevin van Zonneveld (http://kevin.vanzonneveld.net)
# * @license   http://www.opensource.org/licenses/bsd-license.php New BSD Licence
# * @version   SVN: Release: $Id: sshKeyInstall.sh 89 2008-09-05 20:52:48Z kevin $
# * @link      http://kevin.vanzonneveld.net/
# *
# * @param string REMOTE_HOST The host to install the key at
# * @param string REMOTE_USER The user to install the key under
# * @param string OPTIONS      Options like: NOASK
# */

function sshKeyInstall {
    if [ -n "${1}" ]; then
        REMOTE_HOST="${1}"
    else
        log "1st argument should be the remote hostname." "EMERG"
    fi
    
    if [ -n "${2}" ]; then
        REMOTE_USER="${2}"
    else
        REMOTE_USER="$(whoami)"
    fi
    
    if [ -n "${3}" ]; then
        OPTIONS="${3}"
    else
        OPTIONS=""
    fi
    
    [ -d "~/.ssh" ] || mkdir -p ~/.ssh
    if [ ! -f ~/.ssh/id_dsa.pub ];then
        echo "Local SSH key does not exist. Creating..."
        echo "JUST PRESS ENTER WHEN ssh-keygen ASKS FOR A PASSPHRASE!"
        echo ""
        ssh-keygen -t dsa -f ~/.ssh/id_dsa
        
        [ $? -eq 0 ] || log "ssh-keygen returned errors!" "EMERG"
    fi
    
    [ -f ~/.ssh/id_dsa.pub ] || log "unable to create a local SSH key!" "EMERG"
    
    while true; do
        if [ "${OPTIONS}" = "NOASK" ];then
            yn="Y"
        else
            echo -n "Install my local SSH key at ${REMOTE_HOST} (Y/n) "
            read yn
        fi
        
        case $yn in
            "y" | "Y" | "" )
                echo "Local SSH key present, installing remotely..."
                cat ~/.ssh/id_dsa.pub | ssh ${REMOTE_USER}@${REMOTE_HOST} "if [ ! -d ~${REMOTE_USER}/.ssh ];then mkdir -p ~${REMOTE_USER}/.ssh ; fi && if [ ! -f ~${REMOTE_USER}/.ssh/authorized_keys2 ];then touch ~${REMOTE_USER}/.ssh/authorized_keys2 ; fi &&  sh -c 'cat - >> ~${REMOTE_USER}/.ssh/authorized_keys2 && chmod 600 ~${REMOTE_USER}/.ssh/authorized_keys2'"
                [ $? -eq 0 ] || log "ssh returned errors!" "EMERG"
                break 
                ;;
            "n" | "N" ) 
                echo -n ""
                break 
                ;;
            * ) 
                echo "unknown response.  Asking again"
                ;;
        esac
    done
}

# sshKeyVerify() was auto-included from '/../functions/sshKeyVerify.sh' by make.sh
#/**
# * Verifies SSH Keys remotely
# * 
# * @author    Kevin van Zonneveld <kevin@vanzonneveld.net>
# * @copyright 2007 Kevin van Zonneveld (http://kevin.vanzonneveld.net)
# * @license   http://www.opensource.org/licenses/bsd-license.php New BSD Licence
# * @version   SVN: Release: $Id: sshKeyVerify.sh 89 2008-09-05 20:52:48Z kevin $
# * @link      http://kevin.vanzonneveld.net/
# *
# * @param string REMOTE_HOST The host to install the key at
# * @param string REMOTE_USER The user to install the key under
# * @param string OPTIONS      Options like: NOASK
# */
function sshKeyVerify {
    if [ -n "${1}" ]; then
        REMOTE_HOST="${1}"
    else
        log "1st argument should be the remote hostname." "EMERG"
    fi
    
    if [ -n "${2}" ]; then
        REMOTE_USER="${2}"
    else
        REMOTE_USER="$(whoami)"
    fi
    
    if [ -n "${3}" ]; then
        OPTIONS="${3}"
    else
        OPTIONS=""
    fi
	
	${CMD_SSH} -o BatchMode=yes -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null ${REMOTE_USER}@${REMOTE_HOST} '/bin/true'
	if [ "$?" = "0" ]; then
		echo "1"
	else
	    echo "0"
	fi
}

# isPortOpen() was auto-included from '/../functions/isPortOpen.sh' by make.sh
#/**
# * Connects to host & port, validating connectivity and returns 0 or 1 based on success 
# * 
# * @author    Kevin van Zonneveld <kevin@vanzonneveld.net>
# * @copyright 2008 Kevin van Zonneveld (http://kevin.vanzonneveld.net)
# * @license   http://www.opensource.org/licenses/bsd-license.php New BSD Licence
# * @version   SVN: Release: $Id: isPortOpen.sh 94 2008-09-16 09:24:10Z kevin $
# * @link      http://kevin.vanzonneveld.net/
# *
# * @param string  REMOTE_HOST The host to connect to
# * @param integer REMOTE_PORT The port to connect to
# * @param integer TIMEOUT     Timeout in seconds
# */
function isPortOpen {
	REMOTE_HOST="${1}"
	REMOTE_PORT="${2}"
	TIMEOUT="${3}"
	
	[ -n "${REMOTE_HOST}" ] || log "No host to connect to" "EMERG"
	[ -n "${REMOTE_PORT}" ] || log "No port to connect to" "EMERG"
	[ -n "${TIMEOUT}" ] || TIMEOUT="10" 
	
    echo "test" | ${CMD_NETCAT} -w ${TIMEOUT} ${REMOTE_HOST} ${REMOTE_PORT}
    if [ "$?" = "0" ];then
    	echo "1"
    else
        echo "0"
    fi
}


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
