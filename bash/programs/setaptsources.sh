#!/bin/bash
#/**
# * Resets Ubuntu APT sources.list
# * And enables all the standard types: main restricted universe multiverse
# * Makes a backup to /etc/apt/sources.list.bak
# * 
# * @author    Kevin van Zonneveld <kevin@vanzonneveld.net>
# * @copyright 2008 Kevin van Zonneveld (http://kevin.vanzonneveld.net)
# * @license   http://www.opensource.org/licenses/bsd-license.php New BSD Licence
# * @version   SVN: Release: $Id$
# * @link      http://kevin.vanzonneveld.net/
# */
MIRROR="nl"

# Find lsb-release
sudo echo "Determining Ubuntu Release"
if [ ! -f /etc/lsb-release ]; then
	sudo echo "File /etc/lsb-release not found"
	exit 1
fi
UBUNTU_DISTR=$(sudo cat /etc/lsb-release| sudo awk -F'=' '/CODENAME/ {print $2}')

# For added safety, only perform on known versions
UBUNTU_FOUND=0
[ "warty"    = "${UBUNTU_DISTR}" ] && UBUNTU_FOUND=1
[ "hoary"    = "${UBUNTU_DISTR}" ] && UBUNTU_FOUND=1
[ "breezy"   = "${UBUNTU_DISTR}" ] && UBUNTU_FOUND=1
[ "dapper"   = "${UBUNTU_DISTR}" ] && UBUNTU_FOUND=1
[ "edgy"     = "${UBUNTU_DISTR}" ] && UBUNTU_FOUND=1
[ "feisty"   = "${UBUNTU_DISTR}" ] && UBUNTU_FOUND=1
[ "gutsy"    = "${UBUNTU_DISTR}" ] && UBUNTU_FOUND=1
[ "hardy"    = "${UBUNTU_DISTR}" ] && UBUNTU_FOUND=1
[ "intrepid" = "${UBUNTU_DISTR}" ] && UBUNTU_FOUND=1
 
if [ "${UBUNTU_FOUND}" = 0 ]; then
    sudo echo "Version: '${UBUNTU_DISTR}' is not supported (yet)"
    exit 1
fi
	
# Backup sources.list	
if [ ! -f /etc/apt/sources.list ]; then
	sudo echo "File /etc/apt/sources.list not found. Cannot backup file."
else
	sudo echo "Backing up /etc/apt/sources.list to /etc/apt/sources.list.bak"
	sudo cp -af /etc/apt/sources.list{,.bak}
fi

# Write sources.list
sudo echo "Writing new /etc/apt/sources.list"
sudo echo "deb http://${MIRROR}.archive.ubuntu.com/ubuntu/ ${UBUNTU_DISTR} main restricted universe multiverse
deb http://${MIRROR}.archive.ubuntu.com/ubuntu/ ${UBUNTU_DISTR}-updates main restricted universe multiverse
deb http://${MIRROR}.archive.ubuntu.com/ubuntu/ ${UBUNTU_DISTR}-backports main restricted universe multiverse
deb http://${MIRROR}.archive.ubuntu.com/ubuntu/ ${UBUNTU_DISTR}-security main restricted universe multiverse" | sudo tee /etc/apt/sources.list

# Update package list
sudo echo "Updating package list..."
sudo aptitude -y update > /dev/null
sudo echo "Sources are now complete and up to date!"
