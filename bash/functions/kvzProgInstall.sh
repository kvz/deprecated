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