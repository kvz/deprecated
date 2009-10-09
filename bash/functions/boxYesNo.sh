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