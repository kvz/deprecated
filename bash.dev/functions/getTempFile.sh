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