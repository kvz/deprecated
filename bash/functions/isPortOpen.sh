#/**
# * Connects to host & port, validating connectivity and returns 0 or 1 based on success 
# * 
# * @author    Kevin van Zonneveld <kevin@vanzonneveld.net>
# * @copyright 2008 Kevin van Zonneveld (http://kevin.vanzonneveld.net)
# * @license   http://www.opensource.org/licenses/bsd-license.php New BSD Licence
# * @version   SVN: Release: $Id: isPortOpen.sh 94 2008-09-16 09:24:10Z kevin $
# * @link      http://kevin.vanzonneveld.net/
# *
# * @param string  REMOTE_HOST The host to connect to
# * @param integer REMOTE_PORT The port to connect to
# * @param integer TIMEOUT     Timeout in seconds
# */
function isPortOpen {
	REMOTE_HOST="${1}"
	REMOTE_PORT="${2}"
	TIMEOUT="${3}"
	
	[ -n "${REMOTE_HOST}" ] || log "No host to connect to" "EMERG"
	[ -n "${REMOTE_PORT}" ] || log "No port to connect to" "EMERG"
	[ -n "${TIMEOUT}" ] || TIMEOUT="10" 
	
    echo "test" | ${CMD_NETCAT} -w ${TIMEOUT} ${REMOTE_HOST} ${REMOTE_PORT}
    if [ "$?" = "0" ];then
    	echo "1"
    else
        echo "0"
    fi
}