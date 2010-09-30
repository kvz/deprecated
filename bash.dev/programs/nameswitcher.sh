#!/bin/bash
#/**
# * Switches resolving nameservers in case of trouble
# *
# * You can define multiple nameservers in /etc/resolv.conf, but in reality 
# * this works like crap because the timeout before your system switches to a
# * secondary nameserver is way to long for any serious networked application.
# *
# * So not reaching that 1 nameserver can potentially result in gigantic loads
# * and server outage.
# *
# * To prevent, you could schedule this script which will periodically check
# * nameserver health, and switch to Google's nameserver in case of trouble.
# * Also switches back when trouble is over.
# *
# * Tested as cronjob on Ubuntu servers
# *
# * @author    Kevin van Zonneveld <kevin@vanzonneveld.net>
# * @copyright 2010 Kevin van Zonneveld (http://kevin.vanzonneveld.net)
# * @license   http://www.opensource.org/licenses/bsd-license.php New BSD Licence
# * @version   0.3
# * @link      http://kevin.vanzonneveld.net/
# */

#set -x

APP="nameswitcher"
CURRENT=$(/bin/grep \^nameserver /etc/resolv.conf |/usr/bin/head -n1 |/usr/bin/awk '{print $2}')
PRIMARY="213.193.210.250"
FALLBACK="8.8.8.8"
DATE=$(/bin/date '+%Y%m%d-%k%M%S')

test_nameserver () {
	/usr/bin/dig +short true.nl @${1} && return 0
	return 1
}

if [ $(test_nameserver "${PRIMARY}") ]; then
	if [ "${CURRENT}" != "${PRIMARY}" ]; then
		# Restore primary
		/bin/sed -i.bak${DATE} "s/${CURRENT}/${PRIMARY}/" /etc/resolv.conf
		/usr/bin/logger -p user.notice "${APP}: Resolving nameserver ${PRIMARY} came back online. Replaced ${CURRENT} with ${PRIMARY} in resolv.conf!"
	fi
elif [ $(test_nameserver "${FALLBACK}") ]; then
	if [ "${CURRENT}" != "${FALLBACK}" ]; then
		# Set fallback
		/bin/sed -i.bak${DATE} "s/${CURRENT}/${FALLBACK}/" /etc/resolv.conf
		/usr/bin/logger -p user.crit "${APP}: Resolving nameserver ${PRIMARY} failed. Replaced ${CURRENT} with ${FALLBACK} in resolv.conf!"
	else
		# Stay on fallback
		/usr/bin/logger -p user.notice "${APP}: Resolving nameserver ${PRIMARY} still down. Staying on ${FALLBACK} for now"
	fi
else
	# Could reach none
	/usr/bin/logger -p user.crit "${APP}: Resolving nameservers ${PRIMARY} and ${FALLBACK} both. Probably network outage on my side so doing nothing."
fi
