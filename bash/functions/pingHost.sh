#/**
# * Ping a hostname, testing connectivity and returns 0 or 1 
# * 
# * @author    Kevin van Zonneveld <kevin@vanzonneveld.net>
# * @copyright 2008 Kevin van Zonneveld (http://kevin.vanzonneveld.net)
# * @license   http://www.opensource.org/licenses/bsd-license.php New BSD Licence
# * @version   SVN: Release: $Id: pingHost.sh 218 2009-01-12 14:59:57Z kevin $
# * @link      http://kevin.vanzonneveld.net/
# *
# * @param string REMOTE_HOST The host to ping
# */
function pingHost {
    local PINGRESULTS="$(${CMD_PING} -c2 $1 2> /dev/null | ${CMD_AWK} -F ',' '/transmitted/ {print $2}' |${CMD_AWK} '{print $1}')"
    local PINGSUCCESS=0
    
    if [ "${PINGRESULTS}" = "2" ]; then
        PINGSUCCESS=1
    fi
    
    echo "${PINGSUCCESS}"
}