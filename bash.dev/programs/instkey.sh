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
source $(echo "$(dirname ${0})/../functions/log.sh")     # make::include
source $(echo "$(dirname ${0})/../functions/toUpper.sh") # make::include
source $(echo "$(dirname ${0})/../functions/commandInstall.sh") # make::include
source $(echo "$(dirname ${0})/../functions/commandTest.sh") # make::include
source $(echo "$(dirname ${0})/../functions/commandTestHandle.sh") # make::include
source $(echo "$(dirname ${0})/../functions/getWorkingDir.sh") # make::include

source $(echo "$(dirname ${0})/../functions/isPortOpen.sh") # make::include
source $(echo "$(dirname ${0})/../functions/sshKeyInstall.sh") # make::include

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