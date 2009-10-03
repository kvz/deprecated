#/**
# * Determines script's working directory
# * 
# * @author    Kevin van Zonneveld <kevin@vanzonneveld.net>
# * @copyright 2008 Kevin van Zonneveld (http://kevin.vanzonneveld.net)
# * @license   http://www.opensource.org/licenses/bsd-license.php New BSD Licence
# * @version   SVN: Release: $Id: getWorkingDir.sh 218 2009-01-12 14:59:57Z kevin $
# * @link      http://kevin.vanzonneveld.net/
# * 
# * @param string PATH Optional path to add
# */
function getWorkingDir {
    echo $(realpath "$(dirname ${0})${1}")
}