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