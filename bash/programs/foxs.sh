#!/bin/bash
#/**
# * Makes ssh:// links open in gnome-terminal from Firefox 
# * 
# * @author    Kevin van Zonneveld <kevin@vanzonneveld.net>
# * @copyright 2008 Kevin van Zonneveld (http://kevin.vanzonneveld.net)
# * @license   http://www.opensource.org/licenses/bsd-license.php New BSD Licence
# * @version   SVN: Release: $Id: menu.sh 177 2008-09-29 11:31:35Z kevin $
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

# getTempFile() was auto-included from '/../functions/getTempFile.sh' by make.sh
#/**
# * Returns a unique temporary filename
# * 
# */
function getTempFile(){
	if [ -z "${CMD_TEMPFILE}" ]; then
	    echo "Dialog command not found or not initialized"
	    exit 1
	fi
	
	tempFile=`${CMD_TEMPFILE} 2>/dev/null` || tempFile=/tmp/test$$
	echo "" > ${tempFile};
	#trap "rm -f $tempFile" 0 1 2 5 15
	echo $tempFile
}

# kvzProgInstall() was auto-included from '/../functions/kvzProgInstall.sh' by make.sh
#/**
# * Tries to install a bash program from remote KvzLib repository
# * to /root/bin/
# *
# * @param string $1 KvzLib Program name
# * @param string $2 Options (like 'silent')
# */
function kvzProgInstall() {
    # Check if dependencies are initialized
    if [ -z "${CMD_WGET}" ]; then
        echo "wget command not found or not initialized" >&2
        exit 1
    fi

    if [ -z "${CMD_CHMOD}" ]; then
        echo "chmod command not found or not initialized" >&2
        exit 1
    fi
	
    # Init
    local PROGRAM=${1}
    local OPTIONS=${2}
    
    local KVZLIBURL="http://kvzlib.net/b"
    local INSTALLDIR="/root/bin"
    local URL=${KVZLIBURL}/${PROGRAM}
    local DEST=${INSTALLDIR}/${PROGRAM}.sh
    
    if [ ! -d "${INSTALLDIR}" ]; then
    	mkdir -p ${INSTALLDIR}
    	[ "${OPTIONS}" = "silent" ] || echo "Created ${INSTALLDIR}"
    fi
    
    # Do
    [ "${OPTIONS}" = "silent" ] || echo "Downloading ${URL}"
    ${CMD_WGET} -qO ${DEST}  ${URL}
    if [ $? != 0 ]; then
        echo "download of ${URL} failed" >&2
        exit 1
    fi
    
    [ "${OPTIONS}" = "silent" ] || echo "Saved as ${DEST}"
    ${CMD_CHMOD} ug+x ${DEST}
     
    cd ${OLDDIR}  
}

# kvzProgExecute() was auto-included from '/../functions/kvzProgExecute.sh' by make.sh
#/**
# * Tries to execute a bash program from remote KvzLib repository
# * directly
# *
# * @param string $1 KvzLib Program name
# */
function kvzProgExecute() {
    # Check if dependencies are initialized
    if [ -z "${CMD_WGET}" ]; then
        echo "wget command not found or not initialized" >&2
        exit 1
    fi

    if [ -z "${CMD_PWD}" ]; then
        echo "pwd command not found or not initialized" >&2
        exit 1
    fi

    # Init
    local PROGRAM=${1}
    local KVZLIBURL="http://kvzlib.net/b"
    local URL=${KVZLIBURL}/${PROGRAM}
    
    # Do
    [ "${OPTIONS}" = "silent" ] || echo "Downloading & Executing ${URL}"
    ${CMD_WGET} -qO- ${URL} |bash 
    
    if [ $? != 0 ]; then
        echo "execution of ${URL} failed" >&2
        exit 1
    fi
}

# boxList() was auto-included from '/../functions/boxList.sh' by make.sh
#/**
# * Displays a List dialog
# * 
# * @param string $1 Title
# * @param string $2 Description
# * @param string $3 Items, delimited with = and |
# */
function boxList(){
	# Check if dependencies are initialized
    if [ -z "${CMD_DIALOG}" ]; then
        echo "dialog command not found or not initialized" >&2
        exit 1
    fi

    if [ -z "${CMD_SED}" ]; then
        echo "sed command not found or not initialized" >&2
        exit 1
    fi
    
    if [ -z "${CMD_TEE}" ]; then
        echo "tee command not found or not initialized" >&2
        exit 1
    fi
    
    if [ -z "${CMD_AWK}" ]; then
        echo "awk command not found or not initialized" >&2
        exit 1
    fi
    
	# Determine static arguments
	local TITLE="${1}"
	local DESCR="${2}"
	local ITEMS=$(echo "${3}" |${CMD_SED} 's# #_#g')
	
	local ITEMSNEW=""
	local i=0
	local combi=""
	local answerFile=""
	local answer=""
	local retVal=""

    # Open tempfile for non-blocking storage of choices
    answerFile=$(getTempFile)
    
    # Collect remaining arguments items
    for couple in $(echo "${ITEMS}" |${CMD_SED} 's#|# #g'); do
        key=$(echo "${couple}" |${CMD_AWK} -F '=' '{print $1}')
        val=$(echo "${couple}" |${CMD_AWK} -F '=' '{print $2}' |${CMD_SED} 's#_# #g')
        
        ITEMSNEW="${ITEMSNEW}\"${key}\" \"${val}\" "
    done
    
    # Open dialog
    # --menu <text> <height> <width> <menu height> <tag1> <item1>...
    eval ${CMD_DIALOG} --clear --title \"${TITLE}\" --menu \"${DESCR}\" 36 76 26 ${ITEMSNEW} 2> ${answerFile}
    retVal=$?
    
    # OK?
    answer=$(cat ${answerFile})
    rm -f ${answerFile}
    
    case ${retVal} in
        0)
            # Save in global variable for non-blocking storage
            boxReturn=${answer}
        ;;
        1)
            #clear
            boxReturn="cancel"
        ;;
        255)
            #clear
            echo "Dialog aborted. ${answer}" >&2
            #[ -n "${answer}" ] && echo "${answer}"  
            exit 1
        ;;
    esac
}

# boxYesNo() was auto-included from '/../functions/boxYesNo.sh' by make.sh
#/**
# * Displays a Yes/No dialog
# * Returns 1 on yes, 0 on no
# * 
# * @param string $1 Title
# * @param string $2 Description
# * @param string $3 Options
# */
function boxYesNo(){
    # Check if dependencies are initialized
    if [ -z "${CMD_DIALOG}" ]; then
        echo "Dialog command not found or not initialized" >&2
        exit 1
    fi

    # Determine static arguments
    local TITLE="${1}"
    local DESCR="${2}"
    local OPTIONS="${3}"
    
    local retVal=""
    
    # Open dialog    
    ${CMD_DIALOG} ${OPTIONS} --title "${1}" --clear --yesno "${2}" 30 70
    retVal=$?
    
    if [ "${retVal}" = 1 ]; then
        boxReturn=0
    elif [ "${retVal}" = 0 ]; then
        boxReturn=1
    else
        #clear
        echo "ESC ${retVal} pressed or invalid response" >&2
        exit 1
    fi
}



# Essential config
###############################################################
OUTPUT_DEBUG=0
PROGRAM="foxs"


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

commandTestHandle "whoami" "coreutils" "EMERG"
commandTestHandle "mkdir" "coreutils" "EMERG"
commandTestHandle "cat" "coreutils" "EMERG"
commandTestHandle "tee" "coreutils" "EMERG"
commandTestHandle "pwd" "coreutils" "EMERG"
commandTestHandle "chmod" "coreutils" "EMERG"
commandTestHandle "chown" "coreutils" "EMERG"
commandTestHandle "cp" "coreutils" "EMERG"
commandTestHandle "wc" "coreutils" "EMERG"
commandTestHandle "find" "findutils" "EMERG"
commandTestHandle "tempfile" "debianutils" "EMERG"
commandTestHandle "dialog" "dialog" "EMERG"
commandTestHandle "clear" "ncurses-bin" "EMERG"
commandTestHandle "date" "coreutils" "EMERG" "NOINSTALL"
commandTestHandle "ps" "procps" "EMERG" "NOINSTALL"

commandTestHandle "firefox" "firefox" "EMERG" "NOINSTALL" # No use without Firefox
commandTestHandle "gnome-terminal" "gnome-terminal" "EMERG" "NOINSTALL"
commandTestHandle "ssh" "openssh-client" "EMERG"

# May be extended with more applications later on: 
#commandTestHandle "tsclient" "tsclient" "INFO"  
#commandTestHandle "xvnc4viewer" "xvnc4viewer" "INFO"


# Config
###############################################################
DIR_FFOXSUF="/.mozilla/firefox"


# Run
###############################################################

if [ "${1}" = "help" ] || [ "${1}" = "--help" ] || [ -z "${1}" ]; then
    echo ""
    echo "Usage: "
    echo "   ${0} help                             This page"
    echo "   ${0} setup                            Install firefox handler"
    echo "   ${0} test ssh://root@your.server.com  Test if it works"
    echo ""
elif [ "${1}" = "setup" ]; then
    if [ "$(${CMD_WHOAMI})" != "root" ]; then
        log "Setup should be ran as root" "EMERG"
    fi

    if [ "$(${CMD_PS} auxf |${CMD_GREP} 'firefox' |${CMD_GREP} -v 'grep' |${CMD_WC} -l)" != "0" ]; then
        log "Please shut down firefox first. Otherwise the about:config will changes will be overwritten." "EMERG"
    fi

    # Users
    USERS=$($CMD_FIND /home -maxdepth 1 -mindepth 1 |$CMD_GREP -v ftp |$CMD_AWK -F '/' '{print $NF"=/home/"$NF"|"}')
    boxList "User" "For who should we setup firefox ssh handling? Found:" "${USERS}"
    USER="${boxReturn}"
    
    # Pref files
    DIR_FFOXPREF="/home/${USER}${DIR_FFOXSUF}"
    [ -d "${DIR_FFOXPREF}" ] || log "Unable to locate ${DIR_FFOXPREF}" "EMERG"
    
    prefs=$($CMD_FIND ${DIR_FFOXPREF} -mindepth 2 -maxdepth 2 -name prefs.js |$CMD_AWK -F '/' '{print "/"$6"/"$7"="$6"|"}')
    boxList "Preference file" "Please pick the right firefox profile for ${USER}. Found:" "${prefs}"
    FILE_PREFS="${DIR_FFOXPREF}${boxReturn}"
    
    ${CMD_CLEAR}
    [ -f "${FILE_PREFS}" ] || log "Unable to locate ${FILE_PREFS}" "EMERG"
    
    # Bin dir
    DIR_BIN="/home/${USER}/bin"
    [ -d "${DIR_BIN}" ] || ${CMD_MKDIR} -p ${DIR_BIN} && ${CMD_CHOWN} ${USER}.${USER} ${DIR_BIN}
    [ -d "${DIR_BIN}" ] || log "Unable to create ${DIR_BIN}" "EMERG"
    
    # Store this program
    FILE_FOXS="${DIR_BIN}/${PROGRAM}.sh"
    ${CMD_CP} -af ${0} ${FILE_FOXS}
    [ "${?}" = 0 ] || log "Unable to copy ${0} to ${FILE_FOXS}" "EMERG"
    ${CMD_CHOWN} ${USER}.${USER} ${FILE_FOXS}
    ${CMD_CHMOD} ug+x ${FILE_FOXS}
    
    # Backup
    CURDATE=$(${CMD_DATE} '+%Y%m%d%H%M%S')
    ${CMD_SUDO} echo "Backing up ${FILE_PREFS} to ${FILE_PREFS}.${CURDATE}"
    ${CMD_CP} -af ${FILE_PREFS}{,.${CURDATE}}
    [ "${?}" = 0 ] || log "Unable to backup ${FILE_PREFS}" "EMERG"
    
    # Add line
    LINE="user_pref(\"network.protocol-handler.app.ssh\", \"${FILE_FOXS}\");"
    FOUND_ALREADY=$(${CMD_CAT} ${FILE_PREFS} |${CMD_GREP} 'network.protocol-handler.app.ssh' |${CMD_WC} -l)
    if [ "${FOUND_ALREADY}" = 1 ]; then
        log "Firefox preferences file was already adjusted" "INFO"
    elif [ "${FOUND_ALREADY}" -gt 1 ]; then
        log "Firefox preferences file adjusted too many times! Please manually edit ${FILE_PREFS} and look for 'ssh'" "EMERG"
    else
        echo ${LINE} >> ${FILE_PREFS}
        [ "${?}" = 0 ] || log "Unable change ${FILE_PREFS}" "EMERG"
        log "Successfully updated Firefox preferences file" "INFO"
    fi
else
    if [ "${1}" = "test" ]; then
        [ -n "${2}" ] || log "You need to supply a second argument to test with" "EMERG"
        INPUT=${2}
    else 
        INPUT=${1}
    fi
    
    PROT=$(echo "${INPUT}" |${CMD_AWK} -F'://' '{print $1}')
    HOST=$(echo "${INPUT}" |${CMD_AWK} -F'://' '{print $2}')
    
    if [ "${PROT}" = "ssh" ]; then
        APPLICATION="${CMD_SSH}"
    fi
    # May be extended with more applications later on
    
    if [ -z "${APPLICATION}" ]; then
        log "No application found for protocol: ${PROT}" "EMERG"
    fi
    
    ${CMD_GNOME_TERMINAL} -e "${APPLICATION} ${HOST}"
fi
