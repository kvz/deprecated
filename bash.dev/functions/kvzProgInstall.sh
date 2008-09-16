#/**
# * Tries to install a bash program from remote KvzLib repository
# * to /root/bin/
# *
# * @param string $1 KvzLib Program name
# */
function kvzProgInstall() {
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
    local INSTALLDIR="/root/bin"
    local OLDDIR=$(${CMD_PWD})
    local URL=${KVZLIBURL}/${PROGRAM}
    
    [ -d "${INSTALLDIR}" ] || mkdir -p ${INSTALLDIR}
    cd ${INSTALLDIR}
    
    # Show
    ${CMD_WGET} -q ${URL}
    #chmod ug+x???
    cd ${OLDDIR}  
    
    if [ $? != 0 ]; then
        echo "download of ${URL} failed" >&2
        exit 1
    fi
}