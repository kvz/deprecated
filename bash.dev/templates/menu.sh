#!/bin/bash
#/**
# * Template for interactive menu's
# * Will include all nescesary code to quickly deploy menu's.
# * 
# * @author    Kevin van Zonneveld <kevin@vanzonneveld.net>
# * @copyright 2008 Kevin van Zonneveld (http://kevin.vanzonneveld.net)
# * @license   http://www.opensource.org/licenses/bsd-license.php New BSD Licence
# * @version   SVN: Release: $Id$
# * @link      http://kevin.vanzonneveld.net/
# *
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

source $(echo "$(dirname ${0})/../functions/boxList.sh") # make::include
source $(echo "$(dirname ${0})/../functions/boxYesNo.sh") # make::include

# Check for program requirements
###############################################################
commandTestHandle "bash" "bash" "EMERG" "NOINSTALL"
commandTestHandle "aptitude" "aptitude" "DEBUG" "NOINSTALL" # Just try to set CMD_APTITUDE, produces DEBUG msg if not found
commandTestHandle "egrep" "pcregrep"
commandTestHandle "awk"
commandTestHandle "sort"
commandTestHandle "uniq"
commandTestHandle "realpath"
commandTestHandle "sed"

commandTestHandle "tempfile"
commandTestHandle "dialog"

# Usage:
# boxList "Title" "Description" "option1" "One, a good choice" "option2" "Two, maybe even better"
# echo ${boxReturn}
# 
# boxYesNo "Title" "Do you want to say no?" "0"
# echo ${boxReturn}