#!/bin/bash
#/**
# * Shows all active logs
# * 
# * @author    Kevin van Zonneveld <kevin@vanzonneveld.net>
# * @copyright 2007 Kevin van Zonneveld (http://kevin.vanzonneveld.net)
# * @license   http://www.opensource.org/licenses/bsd-license.php New BSD Licence
# * @version   SVN: Release: $Id: activelogs.sh 210 2008-11-19 11:54:52Z kevin $
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
FIRST_RUN=0
PROGRAM="activelogs"

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

commandTestHandle "cat" "coreutils" "EMERG"
commandTestHandle "touch" "coreutils" "EMERG"
commandTestHandle "lsof" "lsof" "EMERG"
commandTestHandle "logtail" "logtail" "EMERG"

# Config
###############################################################
DIR_ROOT=$(getWorkingDir)
FILE_RAN="${DIR_ROOT}/${PROGRAM}.ran" # Not using basename cause that will frustrate Execute menu
if [ ! -f "${FILE_RAN}" ]; then
	echo "First time running ${PROGRAM}. Indexing all logs, may take a long time... "
	FIRST_RUN=1
fi 

# Run
###############################################################
[ "${FIRST_RUN}" = 1 ] && ${CMD_LSOF} -bw |${CMD_AWK} '{print $NF}' |${CMD_EGREP} '(\.log$|^/var/log/)' |${CMD_EGREP} -v '(\-bin)' |${CMD_SORT} |${CMD_UNIQ} |${CMD_AWK} '{print "echo \">> "$0":\" && logtail "$0" && echo \"\""}' |${CMD_BASH} > /dev/null
[ "${FIRST_RUN}" = 1 ] || ${CMD_LSOF} -bw |${CMD_AWK} '{print $NF}' |${CMD_EGREP} '(\.log$|^/var/log/)' |${CMD_EGREP} -v '(\-bin)' |${CMD_SORT} |${CMD_UNIQ} |${CMD_AWK} '{print "echo \">> "$0":\" && logtail "$0" && echo \"\""}' |${CMD_BASH}

# Error
if [ "${?}" = 1 ]; then
	echo "Error while showing active logs" >&2
    exit 1
fi

# Success
if [ "${FIRST_RUN}" = 1 ]; then
	echo "Indexing done. Run again to show recent log activity."
	${CMD_TOUCH} ${FILE_RAN}  
fi