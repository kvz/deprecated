#!/bin/bash
#/**
# * Installs SSH Keys remotely
# * 
# * @author    Kevin van Zonneveld <kevin@vanzonneveld.net>
# * @copyright 2007 Kevin van Zonneveld (http://kevin.vanzonneveld.net)
# * @license   http://www.opensource.org/licenses/bsd-license.php New BSD Licence
# * @version   SVN: Release: $Id: instkey.sh 159 2008-09-18 11:25:49Z kevin $
# * @link      http://kevin.vanzonneveld.net/
# *
# * @param string REMOTE_HOST The host to install the key at
# * @param string REMOTE_USER The user to install the key under
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


# Essential config
###############################################################
OUTPUT_DEBUG=0

# Check for program requirements
###############################################################
commandTestHandle "bash" "bash" "EMERG" "NOINSTALL"
commandTestHandle "aptitude" "aptitude" "DEBUG" "NOINSTALL" # Just try to set CMD_APTITUDE, produces DEBUG msg if not found
commandTestHandle "egrep" "grep" "EMERG"
commandTestHandle "grep" "grep" "EMERG"
commandTestHandle "awk" "gawk" "EMERG"
commandTestHandle "sort" "coreutils" "EMERG"
commandTestHandle "uniq" "coreutils" "EMERG"
commandTestHandle "dirname" "coreutils" "EMERG"
commandTestHandle "realpath" "realpath" "EMERG"
commandTestHandle "sed" "sed" "EMERG"

commandTestHandle "whoami" "coreutils" "EMERG"
commandTestHandle "ssh" "openssh-client" "EMERG"

sshKeyInstall ${1} ${2}
