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