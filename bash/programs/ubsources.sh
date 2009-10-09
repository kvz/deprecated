#!/bin/bash
#/**
# * Resets Ubuntu APT sources lists to enable
# *
# * And enables all the standard types: main restricted universe multiverse
# * Makes a backup to /etc/apt/sources.list.{date}
# * 
# * @author    Kevin van Zonneveld <kevin@vanzonneveld.net>
# * @copyright 2008 Kevin van Zonneveld (http://kevin.vanzonneveld.net)
# * @license   http://www.opensource.org/licenses/bsd-license.php New BSD Licence
# * @version   SVN: Release: $Id: ubsources.sh 285 2009-04-22 13:19:56Z kevin $
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

# Essential config
###############################################################
OUTPUT_DEBUG=0

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

commandTestHandle "sudo" "sudo" "EMERG" "NOINSTALL"
commandTestHandle "aptitude" "aptitude" "EMERG" "NOINSTALL" # aptitude also is a hard-dependency in this case
commandTestHandle "cp" "coreutils" "EMERG" "NOINSTALL"
commandTestHandle "cat" "coreutils" "EMERG" "NOINSTALL"
commandTestHandle "date" "coreutils" "EMERG" "NOINSTALL"

# Config
###############################################################
MIRROR="nl"

# Run
###############################################################
# Find lsb-release
${CMD_SUDO} echo "Determining Ubuntu Release"
if [ ! -f /etc/lsb-release ]; then
	${CMD_SUDO} echo "File /etc/lsb-release not found"
	exit 1
fi
UBUNTU_DISTR=$(${CMD_SUDO} ${CMD_CAT} /etc/lsb-release| ${CMD_SUDO} ${CMD_AWK} -F'=' '/CODENAME/ {print $2}')

# For added safety, only perform on known versions
UBUNTU_FOUND=0
[ "warty"    = "${UBUNTU_DISTR}" ] && UBUNTU_FOUND=1
[ "hoary"    = "${UBUNTU_DISTR}" ] && UBUNTU_FOUND=1
[ "breezy"   = "${UBUNTU_DISTR}" ] && UBUNTU_FOUND=1
[ "dapper"   = "${UBUNTU_DISTR}" ] && UBUNTU_FOUND=1
[ "edgy"     = "${UBUNTU_DISTR}" ] && UBUNTU_FOUND=1
[ "feisty"   = "${UBUNTU_DISTR}" ] && UBUNTU_FOUND=1
[ "gutsy"    = "${UBUNTU_DISTR}" ] && UBUNTU_FOUND=1
[ "hardy"    = "${UBUNTU_DISTR}" ] && UBUNTU_FOUND=1
[ "intrepid" = "${UBUNTU_DISTR}" ] && UBUNTU_FOUND=1
[ "jaunty"   = "${UBUNTU_DISTR}" ] && UBUNTU_FOUND=1
 
if [ "${UBUNTU_FOUND}" = 0 ]; then
    ${CMD_SUDO} echo "Version: '${UBUNTU_DISTR}' is not supported (yet)" >&2
    exit 1
fi
	
# Backup sources.list	
if [ ! -f /etc/apt/sources.list ]; then
	${CMD_SUDO} echo "File /etc/apt/sources.list not found. Cannot backup file."
else
    CURDATE=$(${CMD_DATE} '+%Y%m%d%H%M%S')
	${CMD_SUDO} echo "Backing up /etc/apt/sources.list to /etc/apt/sources.list.${CURDATE}"
	${CMD_SUDO} ${CMD_CP} -af /etc/apt/sources.list{,.${CURDATE}}
fi

# Write sources.list
${CMD_SUDO} echo "Writing new /etc/apt/sources.list"
${CMD_SUDO} echo "deb http://${MIRROR}.archive.ubuntu.com/ubuntu/ ${UBUNTU_DISTR} main restricted universe multiverse
deb http://${MIRROR}.archive.ubuntu.com/ubuntu/ ${UBUNTU_DISTR}-updates main restricted universe multiverse
deb http://${MIRROR}.archive.ubuntu.com/ubuntu/ ${UBUNTU_DISTR}-backports main restricted universe multiverse
deb http://${MIRROR}.archive.ubuntu.com/ubuntu/ ${UBUNTU_DISTR}-security main restricted universe multiverse" | ${CMD_SUDO} tee /etc/apt/sources.list

# Update package list
${CMD_SUDO} echo "Updating package list..."
${CMD_SUDO} ${CMD_APTITUDE} -y update > /dev/null
${CMD_SUDO} echo "Sources are now complete and up to date! You can directly use apt."
