#/**
# * Convert a decimal to a binary string
# * 
# * @author    Kevin van Zonneveld <kevin@vanzonneveld.net>
# * @copyright 2007 Kevin van Zonneveld (http://kevin.vanzonneveld.net)
# * @license   http://www.opensource.org/licenses/bsd-license.php New BSD Licence
# * @version   SVN: Release: $Id: dec2bin.sh 89 2008-09-05 20:52:48Z kevin $
# * @link      http://kevin.vanzonneveld.net/
# * 
# * @param integer INPUT the integer to convert
# */
function dec2bin { 
    local input=$1
    local result=""
    while [ $input -gt 0 ]; do
        if [ $((input%2)) -gt 0 ]; then
            result="1$result"
        else
            result="0$result"
        fi
        input=$((input/2))
    done;
    echo "${result}"
}