#!/bin/bash


# Config
###############################################################
OUTPUT_DEBUG=1


# Functions
###############################################################
#/**
# * Converts a string to uppercase
# * 
# * @param string $1 String
# */
function toupper(){
   echo "$(echo ${1} |tr '[:lower:]' '[:upper:]')"
}

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
	if [ "${levl}" != "DEBUG" ]; then
		if [ "${OUTPUT_DEBUG}" = 1 ]; then
	        show=1
		fi
	else
	    show=1
	fi
	
	# Show
	if [ "{show}" = 1 ];then
	    echo "${levl}: ${1}"
	fi
    
    # Die?
	if [ "${levl}" = "EMERG" ]; then
	    exit 1
	fi
}

#/**
# * Tests if a command exists, tries to install package,
# * resorts to 'handler' argument on fail. 
# *
# * @param string $1 Command name
# * @param string $2 Package name. Optional. Defaults to Command name
# * @param string $3 Handler. Optional. (Any of the loglevels. Defaults to emerg to exit app)
# * @param string $4 Additional option. Optional.
# */
function testcommand_handle(){
	# Init
	local command="${1}"
	local package="${2}"
	local handler="${3}"
	local optionl="${4}"
	local success="0"
	local varname="CMD_$(toupper ${command})"
	
	# Checks
	[ -n "${command}" ] || log "testcommand_handle needs a command" "EMERG"
	
	# Defaults
	[ -n "${package}" ] || package=${command}
	[ -n "${handler}" ] || handler="EMERG"
	[ -n "${optionl}" ] || optionl=""
	
	# Test command
	local located="$(testcommand ${command} ${package})"
	if [ ! -x "${located}" ]; then
		if [ "${optionl}" != "NOINSTALL" ]; then
	        # Try automatic install
	        installcommand ${command} ${package}
			 
		    # Re-Test command
		    located="$(testcommand ${command} ${package})"
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

#/**
# * Tests if a command exists, and returns it's location or an error string.
# * Also saved command location in CMD_XXX.
# *
# * @param string $1 Command name
# * @param string $2 Package name
# */
function testcommand(){
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

#/**
# * Tries to install a package
# * Also saved command location in CMD_XXX
# *
# * @param string $1 Command name
# */
function installcommand() {
    # Init
    local command=${1}
    local package=${2}
    
    # Show
    echo "Trying to install ${package}"
    
    if [ -z "${CMD_APTITUDE}" ]; then
    	aptitude -y install ${package}
    fi
}


# Check for program requirements
###############################################################
testcommand_handle "bash" "bash" "EMERG" "NOINSTALL"
testcommand_handle "aptitude" "aptitude" "DEBUG" "NOINSTALL" # Just try to set CMD_APTITUDE, produces DEBUG msg if not found
testcommand_handle "egrep" "pcregrep"
testcommand_handle "awk"
testcommand_handle "sort"
testcommand_handle "uniq"
testcommand_handle "awk"
testcommand_handle "lsof"


# Run
###############################################################
${CMD_LSOF} |${CMD_EGREP} '\.log$' |${CMD_AWK} '{print $NF}' |${CMD_SORT} |${CMD_UNIQ} |${CMD_AWK} '{print "echo \"\" && echo \""$0":\" && logtail " $0}' #|bash