#!/bin/bash
# * Displays a List dialog
# * 
# * @param string $1 Title
# * @param string $2 Description
# * @param string $3 Items
# */
function boxList(){
	# Check if dependencies are initialized
    if [ -z "${CMD_DIALOG}" ]; then
        echo "Dialog command not found or not initialized" >&2
        exit 1
    fi

    if [ -z "${CMD_SED}" ]; then
        echo "Sed command not found or not initialized" >&2
        exit 1
    fi
    
	# Determine static arguments
	local TITLE="${1}"
	local DESCR="${2}"
	local ITEMS=""
	
	local i=0
	local combi=""
	local tempFile=""
	local choice=""
	local retVal=""
    
    # Collect remaining arguments items
    for i in `seq 3 2 $#`; do
    	let "j = i + 1"
    	eval key=\$${i}
    	eval val=\$${j}
    	combi=$(echo "echo \"${key} \\\"${val}\\\"\"" |bash)
        ITEMS="${ITEMS}${combi} "
    done
    
    # Open tempfile for non-blocking storage of choices
    tempFile=$(getTempFile)
    
    # Open dialog    
    eval ${CMD_DIALOG} --clear --title \"${TITLE}\"  --menu \"${DESCR}\" 16 51 6 ${ITEMS} 2> ${tempFile}
    retVal=$?
    
    # OK?
    choice=`cat $tempFile`
    case ${retVal} in
        0)
            # Save in global variable for non-blocking storage
            boxReturn=${choice}
        ;;
        1)
            #clear
            echo "Cancel ${retval} pressed. Result:" >&2
            cat ${tempFile} >&2
            exit 1
        ;;
        255)
            #clear
            echo "ESC ${retval} pressed. Result:" >&2
            cat ${tempFile} >&2
            exit 1
        ;;
    esac
}
