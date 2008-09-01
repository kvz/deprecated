#!/bin/bash
set -x
#/**
# * Clones a system's: database, config, files, etc. Extremely dangerous!!!
# * 
# * @author    Kevin van Zonneveld <kevin@vanzonneveld.net>
# * @copyright 2007 Kevin van Zonneveld (http://kevin.vanzonneveld.net)
# * @license   http://www.opensource.org/licenses/bsd-license.php New BSD Licence
# * @version   SVN: Release: $Id$
# * @link      http://kevin.vanzonneveld.net/
# */

# Includes
###############################################################

# ('log' included from '/../functions/log.sh')
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

# ('toUpper' included from '/../functions/toUpper.sh')
#/**
# * Converts a string to uppercase
# * 
# * @param string $1 String
# */
function toUpper(){
   echo "$(echo ${1} |tr '[:lower:]' '[:upper:]')"
}

# ('commandInstall' included from '/../functions/commandInstall.sh')
#/**
# * Tries to install a package
# * Also saved command location in CMD_XXX
# *
# * @param string $1 Command name
# * @param string $1 Package name
# */
function commandInstall() {
    # Init
    local command=${1}
    local package=${2}
    
    # Show
    echo "Trying to install ${package}"
    
    if [ -n "${CMD_APTITUDE}" ]; then
        ${CMD_APTITUDE} -y install ${package}
    else
        echo "No supported package management tool found"
    fi
}

# ('commandTest' included from '/../functions/commandTest.sh')
#/**
# * Tests if a command exists, and returns it's location or an error string.
# * Also saved command location in CMD_XXX.
# *
# * @param string $1 Command name
# * @param string $2 Package name
# */
function commandTest(){
    # Init
    local command=${1}
    local package=${2}
    local located=$(which ${command})
    
    # Checks
    if [ ! -n "${located}" ]; then
        echo "Command ${command} not found at all, please install before running this program."
    elif [ ! -x "${located}" ]; then
        echo "Command ${command} not executable at ${located}, please install before running this program."
    else
        echo "${located}" 
    fi
}

# ('commandTestHandle' included from '/../functions/commandTestHandle.sh')
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
    
    # Checks
    [ -n "${command}" ] || log "testcommand_handle needs a command" "EMERG"
    
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
    
    if [ "${success}" == 1 ]; then
        log "Testing for ${command} succeeded" "DEBUG"
        # Okay, Save location in CMD_XXX variable 
        eval ${varname}="${located}"
    fi
}

# ('getWorkingDir' included from '/../functions/getWorkingDir.sh')
#/**
# * Determines script's working directory
# * 
# * @author    Kevin van Zonneveld <kevin@vanzonneveld.net>
# * @copyright 2008 Kevin van Zonneveld (http://kevin.vanzonneveld.net)
# * @license   http://www.opensource.org/licenses/bsd-license.php New BSD Licence
# * @version   SVN: Release: $Id$
# * @link      http://kevin.vanzonneveld.net/
# * 
# * @param string PATH Optional path to add
# */
function getWorkingDir {
    echo $(realpath "$(dirname ${0})${1}")
}

# ('installKeyAt' included from '/../functions/installKeyAt.sh')
#/**
# * Installs SSH Keys remotely
# * 
# * @author    Kevin van Zonneveld <kevin@vanzonneveld.net>
# * @copyright 2007 Kevin van Zonneveld (http://kevin.vanzonneveld.net)
# * @license   http://www.opensource.org/licenses/bsd-license.php New BSD Licence
# * @version   SVN: Release: $Id$
# * @link      http://kevin.vanzonneveld.net/
# *
# * @param string REMOTE_HOST The host to install the key at
# * @param string REMOTE_USER The user to install the key under
# */

function installKeyAt(){
    if [ -n "${1}" ];then
        REMOTE_HOST="${1}"
    else
        log "1st argument should be the remote hostname." "EMERG"
    fi
 
    if [ -n "${2}" ];then
        REMOTE_USER="${2}"
    else
        REMOTE_USER="$(whoami)"
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
        echo -n "Install my local SSH key at ${REMOTE_HOST} (Y/n) "
        read yn
        case $yn in
            "y" | "Y" | "" )
                echo "Local SSH key present, installing remotely..."
                cat ~/.ssh/id_dsa.pub | ssh ${REMOTE_USER}@${REMOTE_HOST} "if [ ! -d ~${REMOTE_USER}/.ssh ];then mkdir -p ~${REMOTE_USER}/.ssh ; fi && if [ ! -f ~${REMOTE_USER}/.ssh/authorized_keys2 ];then touch ~${REMOTE_USER}/.ssh/authorized_keys2 ; fi &&  sh -c 'cat - >> ~${REMOTE_USER}/.ssh/authorized_keys2 && chmod 600 ~${REMOTE_USER}/.ssh/authorized_keys2'"
                [ $? -eq 0 ] || log "ssh returned errors!" "EMERG"
             break ;;
            "n" | "N" ) echo -n "" ; break ;;
            * ) echo "unknown response.  Asking again" ;;
        esac
    done
}

# Check for program requirements
###############################################################
commandTestHandle "bash" "bash" "EMERG" "NOINSTALL"
commandTestHandle "aptitude" "aptitude" "DEBUG" "NOINSTALL" # Just try to set CMD_APTITUDE, produces DEBUG msg if not found
commandTestHandle "egrep" "pcregrep"
commandTestHandle "awk"
commandTestHandle "sort"
commandTestHandle "uniq"
commandTestHandle "realpath"

# Config
###############################################################
OUTPUT_DEBUG=1
DIR_ROOT=$(getWorkingDir)
FILE_CONFIG=${DIR_ROOT}/sysclone.conf.default

CMD_MYSQL="/usr/bin/mysql"
CMD_MYSQLDUMP="/usr/bin/mysqldump"
CMD_RSYNCDEL="rsync -a --itemize-changes --delete"
CMD_RSYNC="rsync -a --itemize-changes"

[ -f  ${FILE_CONFIG} ] || log "No config file found. Maybe: cp -af ${FILE_CONFIG}.default ${FILE_CONFIG} && nano ${FILE_CONFIG}"
source ${FILE_CONFIG}





# Setup run parameters
###############################################################

if [ "${HOST_GET}" != "localhost" ] && [ "${HOST_PUT}" != "localhost" ]; then
    echo "Error. Either HOST_GET or HOST_PUT needs to be localhost. Can't sync between 2 remote machines. I'm not superman."
    exit 0
fi

if [ "${HOST_GET}" = "localhost" ]; then
    RSYNC_HOST_GET=""
    AT_HOST_GET=""
else
	RSYNC_HOST_GET="${HOST_GET}:"
	AT_HOST_GET="ssh ${HOST_GET}"
fi

if [ "${HOST_PUT}" = "localhost" ]; then
    RSYNC_HOST_PUT=""
    AT_HOST_PUT=""
else
	RSYNC_HOST_PUT="${HOST_PUT}:"
	AT_HOST_PUT="ssh ${HOST_PUT}"
fi

# Run
###############################################################

echo -n "package sources sync"
if [ "${DO_SOURCES}" = 1 ]; then
    ${CMD_RSYNC} ${RSYNC_HOST_GET}/etc/apt/sources.list  ${RSYNC_HOST_PUT}/etc/apt/
    ${AT_HOST_PUT} aptitude -y update && aptitude -y dist-upgrade
    echo " [done] "
else 
    echo " [skipped] "
fi

exit 1

echo "package sync"
if [ "${DO_PACKAGES}" = 1 ]; then
    ssh ${HOST_GET} 'dpkg --get-selections > /tmp/dpkglist.txt'
    ${CMD_RSYNC} ${RSYNC_HOST_GET}/tmp/dpkglist.txt ${RSYNC_HOST_PUT}/tmp/
    ssh ${HOST_PUT} 'dpkg --set-selections < /tmp/dpkglist.txt'
    ssh ${HOST_PUT} 'apt-get -y update'
    ssh ${HOST_PUT} 'apt-get -y dselect-upgrade'
    echo " [done] "
else 
    echo " [skipped] "
fi

echo "PEAR package sync"
if [ "${DO_PEARPKG}" = 1 ]; then
    ssh ${HOST_GET} "sudo pear -q list | egrep 'alpha|beta|stable' |awk '{print \$1}' > /tmp/pearlist.txt"
    ${CMD_RSYNC} ${RSYNC_HOST_GET}/tmp/pearlist.txt ${RSYNC_HOST_PUT}/tmp/
    ssh ${HOST_PUT} "cat /tmp/pearlist.txt |awk '{print \"pear install -f \"\$0}' |sudo bash"
    echo " [done] "
else 
    echo " [skipped] "
fi

echo "account sync"
if [ "${DO_ACCOUNTS}" = 1 ]; then
    ${CMD_RSYNC} ${RSYNC_HOST_GET}/etc/passwd  ${RSYNC_HOST_PUT}/etc/
    ${CMD_RSYNC} ${RSYNC_HOST_GET}/etc/passwd- ${RSYNC_HOST_PUT}/etc/
    ${CMD_RSYNC} ${RSYNC_HOST_GET}/etc/shadow  ${RSYNC_HOST_PUT}/etc/
    ${CMD_RSYNC} ${RSYNC_HOST_GET}/etc/shadow- ${RSYNC_HOST_PUT}/etc/
    ${CMD_RSYNC} ${RSYNC_HOST_GET}/etc/group   ${RSYNC_HOST_PUT}/etc/
    echo " [done] "
else 
    echo " [skipped] "
fi

echo "config sync"
if [ "${DO_CONFIG}" = 1 ]; then
    ${CMD_RSYNCDEL} ${RSYNC_HOST_GET}/etc/mysql/   ${RSYNC_HOST_PUT}/etc/mysql
    ${CMD_RSYNCDEL} ${RSYNC_HOST_GET}/etc/apache2/ ${RSYNC_HOST_PUT}/etc/apache2
    ${CMD_RSYNCDEL} ${RSYNC_HOST_GET}/etc/php5/    ${RSYNC_HOST_PUT}/etc/php5
    ${CMD_RSYNCDEL} ${RSYNC_HOST_GET}/etc/postfix/ ${RSYNC_HOST_PUT}/etc/postfix
    echo " [done] "
else 
    echo " [skipped] "
fi

echo "database sync"
if [ "${DO_DATABASE}" = 1 ]; then
    DATABASES=`echo "SHOW DATABASES;" | ${CMD_MYSQL} -p${DB_PASS_GET} -u ${DB_USER_GET} -h ${DB_HOST_GET}`
    for DATABASE in $DATABASES; do
        if [ "${DATABASE}" != "Database" ]; then
            echo "transmitting ${DATABASE}"
            echo "CREATE DATABASE IF NOT EXISTS ${DATABASE}" | ${CMD_MYSQL} -p${DB_PASS_PUT} -u ${DB_USER_PUT} -h ${DB_HOST_PUT}
            ${CMD_MYSQLDUMP} -Q -B --create-options --delayed-insert --complete-insert --quote-names --add-drop-table -p${DB_PASS_GET} -u${DB_USER_GET} -h${DB_HOST_GET} ${DATABASE} | ${CMD_MYSQL} -p${DB_PASS_PUT} -u ${DB_USER_PUT} -h ${DB_HOST_PUT} ${DATABASE}
        fi
    done
    echo " [done] "
else 
    echo " [skipped] "
fi

echo "directory sync"
if [ "${DO_DIRS}" = 1 ]; then
    # root must be copied like /*, because of ssh keys
    # actually, don't do root at all because of sync script!
    #${CMD_RSYNCDEL} ${RSYNC_HOST_GET}/root/* ${RSYNC_HOST_PUT}/root/
    ${CMD_RSYNCDEL} ${RSYNC_HOST_GET}/home/ ${RSYNC_HOST_PUT}/home
    ${CMD_RSYNCDEL} ${RSYNC_HOST_GET}/var/www/ ${RSYNC_HOST_PUT}/var/www
    #${CMD_RSYNCDEL} ${RSYNC_HOST_GET}/var/lib/svn/ ${RSYNC_HOST_PUT}/var/lib/svn
    echo " [done] "
else 
    echo " [skipped] "
fi
