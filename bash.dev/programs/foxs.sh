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