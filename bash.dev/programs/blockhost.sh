#!/bin/bash
#/**
# * Blocks/Unblocks a host
# *
# * Can use nullrouting or iptables as blocking mechanism
# *
# * @author    Kevin van Zonneveld <kevin@vanzonneveld.net>
# * @copyright 2009 Kevin van Zonneveld (http://kevin.vanzonneveld.net)
# * @license   http://www.opensource.org/licenses/bsd-license.php New BSD Licence
# * @version   SVN: Release: $Id$
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
commandTestHandle "head" "coreutils" "EMERG"
commandTestHandle "sort" "coreutils" "EMERG"
commandTestHandle "uniq" "coreutils" "EMERG"
commandTestHandle "dirname" "coreutils" "EMERG"
commandTestHandle "realpath" "realpath" "EMERG"
commandTestHandle "sed" "sed" "EMERG"

commandTestHandle "route" "net-tools" "EMERG"
commandTestHandle "iptables" "iptablesy" "EMERG"

# Config
###############################################################


# Functions
###############################################################

function usage() {
    echo "Usage: ${0} route|ipables block|unblock host"
    exit 1
}


# Run
###############################################################

method="${1}"
block="${2}"
host="${3}"
dif=""

if [ "${method}" = "route" ]; then
    if [ "${block}" = "block" ]; then
        dif="add"
    elif [ "${block}" = "unblock" ]; then
        dif="del"
    else
        usage
    fi

    ${CMD_ROUTE} ${dif} "${host}" dev lo
elif [ "${method}" = "iptables" ]; then
    if [ "${block}" = "block" ]; then
        dif="-A"
    elif [ "${block}" = "unblock" ]; then
        dif="-D"
    else
        usage
    fi

    ${CMD_IPTABLES} ${dif} INPUT -s "${host}" -j DROP
    ${CMD_IPTABLES} ${dif} OUTPUT -d "${host}" -j DROP
else
    usage
fi
