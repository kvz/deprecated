#/**
# * Displays a List dialog
# * 
# * @param string $1 Title
# * @param string $2 Description
# * @param string $3 Items
# */
function boxList(){
    if [ -z "${CMD_DIALOG}" ]; then
        echo "Dialog command not found or not initialized"
        exit 1
    fi

    if [ -z "${CMD_SED}" ]; then
        echo "Sed command not found or not initialized"
        exit 1
    fi

	# Determine static arguments
	TITLE="${1}"
	DESCR="${2}"
	ITEMS=""
    
    # Collect remaining arguments items
    for i in `seq 3 2 $#`; do
    	let "j = i + 1"
    	
    	eval key=\$${i}
    	eval val=\$${j}
    	
    	combi=$(echo "echo \"${key} \\\"${val}\\\"\"" |bash)
    	
        ITEMS="${ITEMS}${combi} "
    done
    
    tempFile=$(getTempFile)
    echo ${tempFile}
    
    eval ${CMD_DIALOG} --clear --title \"${TITLE}\"  --menu \"${DESCR}\" 16 51 6 ${ITEMS}
    
    retval=$?
    
    choice=`cat $tempFile`
    case ${retval} in
        0)
            dia_ret=${choice}
        ;;
        1)
            #clear
            echo "Cancel ${retval} pressed."
            cat ${tempFile}
            exit 0
        ;;
        255)
            #clear
            echo "ESC ${retval} pressed."
            cat ${tempFile}
            exit 0
        ;;
    esac
}