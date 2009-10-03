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