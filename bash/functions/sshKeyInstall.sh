#/**
# * Installs SSH Keys remotely
# * 
# * @author    Kevin van Zonneveld <kevin@vanzonneveld.net>
# * @copyright 2007 Kevin van Zonneveld (http://kevin.vanzonneveld.net)
# * @license   http://www.opensource.org/licenses/bsd-license.php New BSD Licence
# * @version   SVN: Release: $Id: sshKeyInstall.sh 89 2008-09-05 20:52:48Z kevin $
# * @link      http://kevin.vanzonneveld.net/
# *
# * @param string REMOTE_HOST The host to install the key at
# * @param string REMOTE_USER The user to install the key under
# * @param string OPTIONS      Options like: NOASK
# */

function sshKeyInstall {
    if [ -n "${1}" ]; then
        REMOTE_HOST="${1}"
    else
        log "1st argument should be the remote hostname." "EMERG"
    fi
    
    if [ -n "${2}" ]; then
        REMOTE_USER="${2}"
    else
        REMOTE_USER="$(whoami)"
    fi
    
    if [ -n "${3}" ]; then
        OPTIONS="${3}"
    else
        OPTIONS=""
    fi
    
    [ -d "~/.ssh" ] || mkdir -p ~/.ssh
    if [ ! -f ~/.ssh/id_dsa.pub ];then
        echo "Local SSH key does not exist. Creating..."
        echo "JUST PRESS ENTER WHEN ssh-keygen ASKS FOR A PASSPHRASE!"
        echo ""
        ssh-keygen -t dsa -f ~/.ssh/id_dsa
        
        [ $? -eq 0 ] || log "ssh-keygen returned errors!" "EMERG"
    fi
    
    [ -f ~/.ssh/id_dsa.pub ] || log "unable to create a local SSH key!" "EMERG"
    
    while true; do
        if [ "${OPTIONS}" = "NOASK" ];then
            yn="Y"
        else
            echo -n "Install my local SSH key at ${REMOTE_HOST} (Y/n) "
            read yn
        fi
        
        case $yn in
            "y" | "Y" | "" )
                echo "Local SSH key present, installing remotely..."
                cat ~/.ssh/id_dsa.pub | ssh ${REMOTE_USER}@${REMOTE_HOST} "if [ ! -d ~${REMOTE_USER}/.ssh ];then mkdir -p ~${REMOTE_USER}/.ssh ; fi && if [ ! -f ~${REMOTE_USER}/.ssh/authorized_keys2 ];then touch ~${REMOTE_USER}/.ssh/authorized_keys2 ; fi &&  sh -c 'cat - >> ~${REMOTE_USER}/.ssh/authorized_keys2 && chmod 600 ~${REMOTE_USER}/.ssh/authorized_keys2'"
                [ $? -eq 0 ] || log "ssh returned errors!" "EMERG"
                break 
                ;;
            "n" | "N" ) 
                echo -n ""
                break 
                ;;
            * ) 
                echo "unknown response.  Asking again"
                ;;
        esac
    done
}