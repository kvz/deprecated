#!/bin/bash

# Includes
###############################################################
source $(echo "$(dirname ${0})/../functions/log.sh")     # make::include
source $(echo "$(dirname ${0})/../functions/toUpper.sh") # make::include
source $(echo "$(dirname ${0})/../functions/commandInstall.sh") # make::include
source $(echo "$(dirname ${0})/../functions/commandTest.sh") # make::include
source $(echo "$(dirname ${0})/../functions/commandTestHandle.sh") # make::include
source $(echo "$(dirname ${0})/../functions/getWorkingDir.sh") # make::include

# Config
###############################################################
OUTPUT_DEBUG=1
FILTER=""
FILTER="${FILTER} IndentStyles(style=k&r)"
FILTER="${FILTER} NewLines(before=function:if:switch:T_CLASS,after=function)"
FILTER="${FILTER} Pear(add_header=apache)"
FILTER="${FILTER} ArrayNested()"
FILTER="${FILTER} Lowercase()"

# Check for program requirements
###############################################################
commandTestHandle "bash" "bash" "EMERG" "NOINSTALL"
commandTestHandle "aptitude" "aptitude" "DEBUG" "NOINSTALL" # Just try to set CMD_APTITUDE, produces DEBUG msg if not found
commandTestHandle "egrep" "pcregrep"
commandTestHandle "awk"
commandTestHandle "sort"
commandTestHandle "uniq"
commandTestHandle "realpath"

commandTestHandle "php_beautifier" "php-pear" "EMERG" "NOINSTALL"
 
# Run
###############################################################
if [ ! -n "${FILTERS}" ]; then
	${CMD_PHP_BEAUTIFIER} -f ${1}
else
	${CMD_PHP_BEAUTIFIER} --filters "${FILTERS}" -f ${1}
fi