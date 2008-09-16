#/**
# * Tries to install a package
# * Also saved command location in CMD_XXX
# *
# * @param string $1 Command name
# * @param string $2 Package name
# */
function commandInstall() {
    # Init
    local command=${1}
    local package=${2}
    
    # Show
    echo "Trying to install ${package}"
    
    if [ -n "${CMD_APTITUDE}" ] && [ -x "${CMD_APTITUDE}" ]; then
        apt-get -qy install ${package}
    else
        echo "No supported package management tool found"
    fi
}