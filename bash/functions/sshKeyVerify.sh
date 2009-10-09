#/**
# * Verifies SSH Keys remotely
# * 
# * @author    Kevin van Zonneveld <kevin@vanzonneveld.net>
# * @copyright 2007 Kevin van Zonneveld (http://kevin.vanzonneveld.net)
# * @license   http://www.opensource.org/licenses/bsd-license.php New BSD Licence
# * @version   SVN: Release: $Id: sshKeyVerify.sh 89 2008-09-05 20:52:48Z kevin $
# * @link      http://kevin.vanzonneveld.net/
# *
# * @param string REMOTE_HOST The host to install the key at
# * @param string REMOTE_USER The user to install the key under
# * @param string OPTIONS      Options like: NOASK
# */
function sshKeyVerify {
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
	
	${CMD_SSH} -o BatchMode=yes -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null ${REMOTE_USER}@${REMOTE_HOST} '/bin/true'
	if [ "$?" = "0" ]; then
		echo "1"
	else
	    echo "0"
	fi
}