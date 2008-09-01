#!/bin/bash
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
source $(realpath "$(dirname ${0})/../functions/commandTestHandle.sh") # make::include
source $(realpath "$(dirname ${0})/../functions/commandInstall.sh") # make::include
source $(realpath "$(dirname ${0})/../functions/commandTest.sh") # make::include

# Config
###############################################################
OUTPUT_DEBUG=0

# Check for program requirements
###############################################################
commandTestHandle "bash" "bash" "EMERG" "NOINSTALL"
commandTestHandle "aptitude" "aptitude" "DEBUG" "NOINSTALL" # Just try to set CMD_APTITUDE, produces DEBUG msg if not found
commandTestHandle "egrep" "pcregrep"
commandTestHandle "awk"
commandTestHandle "sort"
commandTestHandle "uniq"
commandTestHandle "awk"
commandTestHandle "lsof"

# Config file found?
[ -f ./sysclone.conf ] || log "No config file found. Maybe: cp -af ./sysclone.conf.default ./sysclone.conf && nano ./sysclone.conf"


log "sad" "EMERG"
exit 1;  


exit 1

# Run
###############################################################


#echo "package source sync"
#rsync -a --progress ${HOST_SRC}:/etc/apt/sources.list ${HOST_DST}:/etc/apt/

echo "package upgrade"
ssh ${HOST_DST} 'aptitude -y update && aptitude -y dist-upgrade'

echo "package sync"
ssh ${HOST_SRC} 'dpkg --get-selections > /tmp/dpkglist.txt'
scp ${HOST_SRC}:/tmp/dpkglist.txt ${HOST_DST}:/tmp
dpkg --set-selections < /tmp/dpkglist.txt
apt-get -y update
apt-get -y dselect-upgrade

echo "account sync"
rsync -a --progress ${HOST_SRC}:/etc/passwd  ${HOST_DST}:/etc/
rsync -a --progress ${HOST_SRC}:/etc/passwd- ${HOST_DST}:/etc/
rsync -a --progress ${HOST_SRC}:/etc/shadow  ${HOST_DST}:/etc/
rsync -a --progress ${HOST_SRC}:/etc/shadow- ${HOST_DST}:/etc/
rsync -a --progress ${HOST_SRC}:/etc/group   ${HOST_DST}:/etc/

echo "config sync"
rsync -a --progress --delete ${HOST_SRC}:/etc/mysql/   ${HOST_DST}:/etc/mysql
rsync -a --progress --delete ${HOST_SRC}:/etc/apache2/ ${HOST_DST}:/etc/apache2
rsync -a --progress --delete ${HOST_SRC}:/etc/php5/    ${HOST_DST}:/etc/php5
rsync -a --progress --delete ${HOST_SRC}:/etc/postfix/ ${HOST_DST}:/etc/postfix

echo "database sync"
DATABASES=`echo "SHOW DATABASES;" | ${CMD_MYSQL} -p${DB_PASS_SRC} -u ${DB_USER_SRC} -h ${DB_HOST_SRC}`
for DATABASE in $DATABASES; do
  if [ "${DATABASE}" != "Database" ]; then
    echo "transmitting ${DATABASE}"
    echo "CREATE DATABASE IF NOT EXISTS ${DATABASE}" | ${CMD_MYSQL} -p${DB_PASS_DST} -u ${DB_USER_DST} -h ${DB_HOST_DST}
    ${CMD_MYSQLDUMP} -Q -B --create-options --delayed-insert --complete-insert --quote-names --add-drop-table -p${DB_PASS_SRC} -u${DB_USER_SRC} -h${DB_HOST_SRC} ${DATABASE} | ${CMD_MYSQL} -p${DB_PASS_DST} -u ${DB_USER_DST} -h ${DB_HOST_DST} ${DATABASE}
  fi
done

echo "directory sync"
# geen etc want dan gaat host, md0 naar de kloten!
rsync -a --progress --delete ${HOST_SRC}:/root/    ${HOST_DST}:/root
rsync -a --progress --delete ${HOST_SRC}:/home/    ${HOST_DST}:/home
rsync -a --progress --delete ${HOST_SRC}:/var/www/ ${HOST_DST}:/var/www
