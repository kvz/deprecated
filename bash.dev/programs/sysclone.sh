#!/bin/bash
set +x
#/**
# * Clones a system's: database, config, files, etc. Extremely dangerous!!!
# * 
# * @author    Kevin van Zonneveld <kevin@vanzonneveld.net>
# * @copyright 2007 Kevin van Zonneveld (http://kevin.vanzonneveld.net)
# * @license   http://www.opensource.org/licenses/bsd-license.php New BSD Licence
# * @version   SVN: Release: $Id$
# * @link      http://kevin.vanzonneveld.net/
# */


# Includes
###############################################################
source $(realpath "$(dirname ${0})/../functions/log.sh")     # make::include
source $(realpath "$(dirname ${0})/../functions/toUpper.sh") # make::include
source $(realpath "$(dirname ${0})/../functions/commandInstall.sh") # make::include
source $(realpath "$(dirname ${0})/../functions/commandTest.sh") # make::include
source $(realpath "$(dirname ${0})/../functions/commandTestHandle.sh") # make::include
source $(realpath "$(dirname ${0})/../functions/getWorkingDir.sh") # make::include
source $(realpath "$(dirname ${0})/../functions/installKeyAt.sh") # make::include


# Check for program requirements
###############################################################
commandTestHandle "bash" "bash" "EMERG" "NOINSTALL"
commandTestHandle "aptitude" "aptitude" "DEBUG" "NOINSTALL" # Just try to set CMD_APTITUDE, produces DEBUG msg if not found
commandTestHandle "egrep" "pcregrep"
commandTestHandle "awk"
commandTestHandle "sort"
commandTestHandle "uniq"
commandTestHandle "realpath"


# Config
###############################################################
OUTPUT_DEBUG=1
DIR_ROOT=$(getWorkingDir)
FILE_CONFIG=${DIR_ROOT}/sysclone.conf.default

CMD_MYSQL="/usr/bin/mysql"
CMD_MYSQLDUMP="/usr/bin/mysqldump"
CMD_RSYNCDEL="rsync -a --itemize-changes --delete"
CMD_RSYNC="rsync -a --itemize-changes"

[ -f  ${FILE_CONFIG} ] || log "No config file found. Maybe: cp -af ${FILE_CONFIG}.default ${FILE_CONFIG} && nano ${FILE_CONFIG}" "EMERG"
source ${FILE_CONFIG}


# Setup run parameters
###############################################################

if [ "${HOST_GET}" != "localhost" ] && [ "${HOST_PUT}" != "localhost" ]; then
    echo "Error. Either HOST_GET or HOST_PUT needs to be localhost. Can't sync between 2 remote machines. I'm not superman."
    exit 0
fi

if [ "${HOST_GET}" = "localhost" ]; then
    RSYNC_HOST_GET=""
    AT_HOST_GET=""
else
    HOST_SSH="${HOST_GET}"
	RSYNC_HOST_GET="${HOST_GET}:"
	AT_HOST_GET="ssh ${HOST_GET}"
fi

if [ "${HOST_PUT}" = "localhost" ]; then
    RSYNC_HOST_PUT=""
    AT_HOST_PUT=""
else
    HOST_SSH="${HOST_PUT}"
	RSYNC_HOST_PUT="${HOST_PUT}:"
	AT_HOST_PUT="ssh ${HOST_PUT}"
fi


# Run
###############################################################

echo "install ssh key at ${HOST_SSH}"
installKeyAt ${HOST_SSH}

echo -n "package sources sync"
if [ "${DO_SOURCES}" = 1 ]; then
    ${CMD_RSYNC} ${RSYNC_HOST_GET}/etc/apt/sources.list  ${RSYNC_HOST_PUT}/etc/apt/
    ${AT_HOST_PUT} aptitude -y update && aptitude -y dist-upgrade
    echo " [done] "
else 
    echo " [skipped] "
fi

exit 1

echo "package sync"
if [ "${DO_PACKAGES}" = 1 ]; then
    ssh ${HOST_GET} 'dpkg --get-selections > /tmp/dpkglist.txt'
    ${CMD_RSYNC} ${RSYNC_HOST_GET}/tmp/dpkglist.txt ${RSYNC_HOST_PUT}/tmp/
    ssh ${HOST_PUT} 'dpkg --set-selections < /tmp/dpkglist.txt'
    ssh ${HOST_PUT} 'apt-get -y update'
    ssh ${HOST_PUT} 'apt-get -y dselect-upgrade'
    echo " [done] "
else 
    echo " [skipped] "
fi

echo "PEAR package sync"
if [ "${DO_PEARPKG}" = 1 ]; then
    ssh ${HOST_GET} "sudo pear -q list | egrep 'alpha|beta|stable' |awk '{print \$1}' > /tmp/pearlist.txt"
    ${CMD_RSYNC} ${RSYNC_HOST_GET}/tmp/pearlist.txt ${RSYNC_HOST_PUT}/tmp/
    ssh ${HOST_PUT} "cat /tmp/pearlist.txt |awk '{print \"pear install -f \"\$0}' |sudo bash"
    echo " [done] "
else 
    echo " [skipped] "
fi

echo "account sync"
if [ "${DO_ACCOUNTS}" = 1 ]; then
    ${CMD_RSYNC} ${RSYNC_HOST_GET}/etc/passwd  ${RSYNC_HOST_PUT}/etc/
    ${CMD_RSYNC} ${RSYNC_HOST_GET}/etc/passwd- ${RSYNC_HOST_PUT}/etc/
    ${CMD_RSYNC} ${RSYNC_HOST_GET}/etc/shadow  ${RSYNC_HOST_PUT}/etc/
    ${CMD_RSYNC} ${RSYNC_HOST_GET}/etc/shadow- ${RSYNC_HOST_PUT}/etc/
    ${CMD_RSYNC} ${RSYNC_HOST_GET}/etc/group   ${RSYNC_HOST_PUT}/etc/
    echo " [done] "
else 
    echo " [skipped] "
fi

echo "config sync"
if [ "${DO_CONFIG}" = 1 ]; then
    ${CMD_RSYNCDEL} ${RSYNC_HOST_GET}/etc/mysql/   ${RSYNC_HOST_PUT}/etc/mysql
    ${CMD_RSYNCDEL} ${RSYNC_HOST_GET}/etc/apache2/ ${RSYNC_HOST_PUT}/etc/apache2
    ${CMD_RSYNCDEL} ${RSYNC_HOST_GET}/etc/php5/    ${RSYNC_HOST_PUT}/etc/php5
    ${CMD_RSYNCDEL} ${RSYNC_HOST_GET}/etc/postfix/ ${RSYNC_HOST_PUT}/etc/postfix
    echo " [done] "
else 
    echo " [skipped] "
fi

echo "database sync"
if [ "${DO_DATABASE}" = 1 ]; then
    DATABASES=`echo "SHOW DATABASES;" | ${CMD_MYSQL} -p${DB_PASS_GET} -u ${DB_USER_GET} -h ${DB_HOST_GET}`
    for DATABASE in $DATABASES; do
        if [ "${DATABASE}" != "Database" ]; then
            echo "transmitting ${DATABASE}"
            echo "CREATE DATABASE IF NOT EXISTS ${DATABASE}" | ${CMD_MYSQL} -p${DB_PASS_PUT} -u ${DB_USER_PUT} -h ${DB_HOST_PUT}
            ${CMD_MYSQLDUMP} -Q -B --create-options --delayed-insert --complete-insert --quote-names --add-drop-table -p${DB_PASS_GET} -u${DB_USER_GET} -h${DB_HOST_GET} ${DATABASE} | ${CMD_MYSQL} -p${DB_PASS_PUT} -u ${DB_USER_PUT} -h ${DB_HOST_PUT} ${DATABASE}
        fi
    done
    echo " [done] "
else 
    echo " [skipped] "
fi

echo "directory sync"
if [ "${DO_DIRS}" = 1 ]; then
    # root must be copied like /*, because of ssh keys
    # actually, don't do root at all because of sync script!
    #${CMD_RSYNCDEL} ${RSYNC_HOST_GET}/root/* ${RSYNC_HOST_PUT}/root/
    ${CMD_RSYNCDEL} ${RSYNC_HOST_GET}/home/ ${RSYNC_HOST_PUT}/home
    ${CMD_RSYNCDEL} ${RSYNC_HOST_GET}/var/www/ ${RSYNC_HOST_PUT}/var/www
    #${CMD_RSYNCDEL} ${RSYNC_HOST_GET}/var/lib/svn/ ${RSYNC_HOST_PUT}/var/lib/svn
    echo " [done] "
else 
    echo " [skipped] "
fi
