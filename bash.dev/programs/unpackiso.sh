#!/bin/bash
#/**
# * (NOT READY!) Will extract the files from an .iso file
# *
# * By mounting it in the background, copying the contents and then unmounting it.
# * 
# * @author    Kevin van Zonneveld <kevin@vanzonneveld.net>
# * @copyright 2008 Kevin van Zonneveld (http://kevin.vanzonneveld.net)
# * @license   http://www.opensource.org/licenses/bsd-license.php New BSD Licence
# * @version   SVN: Release: $Id: ubsources.sh 160 2008-09-18 11:27:21Z kevin $
# * @link      http://kevin.vanzonneveld.net/
# */

FILE_ISO=${1}
DIR_MOUNT="/mnt/unpackiso"
DIR_UNPACK=$(dirname "${FILE_ISO}")

function die {
    echo "${1}"
    exit 0;
}

# Bail out
[ -f "${FILE_ISO}" ] || die "File '${FILE_ISO}' does not exist"

# Try to create
[ -d "${DIR_MOUNT}" ] || mkdir -p "${DIR_MOUNT}"
# Bail out
[ -d "${DIR_MOUNT}" ] || die "Dir '${DIR_MOUNT}' does not exist"

mount -o loop "${FILE_ISO}" "${DIR_MOUNT}"
[ "${?}" = 0 ] || die "Unable to mount '${FILE_ISO}' to ${DIR_MOUNT}"

rsync -a --progress "${DIR_MOUNT}"/* "${DIR_UNPACK}/"

umount -lf "${DIR_MOUNT}"
echo "Done."