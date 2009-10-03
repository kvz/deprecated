#!/bin/bash
#/**
# * Template for interactive menu's
# * Will include all nescesary code to quickly deploy menu's.
# * 
# * @author    Kevin van Zonneveld <kevin@vanzonneveld.net>
# * @copyright 2008 Kevin van Zonneveld (http://kevin.vanzonneveld.net)
# * @license   http://www.opensource.org/licenses/bsd-license.php New BSD Licence
# * @version   SVN: Release: $Id: menu.sh 199 2008-11-10 11:26:34Z kevin $
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
source $(echo "$(dirname ${0})/../functions/getTempFile.sh") # make::include

source $(echo "$(dirname ${0})/../functions/kvzProgInstall.sh") # make::include
source $(echo "$(dirname ${0})/../functions/kvzProgExecute.sh") # make::include
source $(echo "$(dirname ${0})/../functions/boxList.sh") # make::include
source $(echo "$(dirname ${0})/../functions/boxYesNo.sh") # make::include


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

commandTestHandle "tee" "coreutils" "EMERG"
commandTestHandle "pwd" "coreutils" "EMERG"
commandTestHandle "wget" "wget" "EMERG"
commandTestHandle "chmod" "coreutils" "EMERG"
commandTestHandle "tempfile" "debianutils" "EMERG"
commandTestHandle "dialog" "dialog" "EMERG"

# Usage:
# boxList "Title" "Description" "option1=One, a good choice|option2=Two, maybe even better"
# echo ${boxReturn}
# 
# boxYesNo "Title" "Do you want to say no?" "0"
# echo ${boxReturn}