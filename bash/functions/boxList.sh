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