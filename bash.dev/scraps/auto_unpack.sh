#!/bin/bash

# definitions
EXT_GOOD=".+\.avi|.+\.nfo|.+\.srt|.+\.idx|.+\.vob|.+\.iso"
EXT_BAD=".+\.jpg|.+\.url|.+\.txt|.+\.sfv"

# goto dir
cd /data/downloads

# move donze nzbs & torrents to download root
/usr/bin/find /data/downloads/_tflux/sharea/ -mindepth 1 -maxdepth 1 -mmin +530 -print0 |/usr/bin/xargs -0r  mv --target-directory /data/downloads
#/usr/bin/find /data/downloads/_tflux/admin/ -mindepth 1 -maxdepth 1 -mmin +530 -print0 |/usr/bin/xargs -0r  mv --target-directory /data/downloads
/usr/bin/find /data/downloads/_hellanzb/done/ -mindepth 1 -maxdepth 1 -mmin +530 -print0 |/usr/bin/xargs -0r  mv --target-directory /data/downloads

# unrar everything to /data/downloads
/usr/bin/find /data/downloads -regextype posix-extended -maxdepth 3 -type f -mmin +540 -iregex '(.+part0?1\.rar|.+[^0-9]{1,2}\.rar)$' -print0 |xargs -0r /usr/bin/unrar e -y -o- -p-

# delete all rars
/usr/bin/find /data/downloads -regextype posix-extended -maxdepth 3 -type f -mmin +540 -iregex '(.+\.r[0-9]{1,2}|.+\.rar)$' -print0 |xargs -0r /bin/rm

# move avi's & stuff to root
/usr/bin/find /data/downloads -regextype posix-extended -mindepth 2 -size +0M -type f -mmin +540 -iregex "(${EXT_GOOD})\$" -print0 |/usr/bin/xargs -0r  mv -f --target-directory /data/downloads
#/usr/bin/find /data/downloads -regextype posix-extended -mindepth 2 -size -9M -type f -mmin +540 -iregex "(${EXT_GOOD})\$" -print0 |/usr/bin/xargs -0r  mv -f --target-directory /data/downloads
# make sure it is gone
/usr/bin/find /data/downloads -regextype posix-extended -mindepth 2 -size +0M -type f -mmin +540 -iregex "(${EXT_GOOD})\$" -print0 |/usr/bin/xargs -0r /bin/rm
#/usr/bin/find /data/downloads -regextype posix-extended -mindepth 2 -size -9M -type f -mmin +540 -iregex "(${EXT_GOOD})\$" -print0 |/usr/bin/xargs -0r /bin/rm

# delete unuseful stuff
/usr/bin/find /data/downloads -regextype posix-extended -type f -mmin +540 -iregex "(${EXT_BAD})\$" -print0 |/usr/bin/xargs -0r /bin/rm

# delete sample files
/usr/bin/find /data/downloads/ -regextype posix-extended -size -60M -type f -mmin +540 -iregex '.*sample.*' -print0 |/usr/bin/xargs -0r /bin/rm

# delete empty dirs
/usr/bin/find /data/downloads -regextype posix-extended -type d -empty -mmin +540 -regex '^/data/downloads/[^_].+' -print0 |/usr/bin/xargs -0r rmdir --ignore-fail-on-non-empty

# chown entire /data dir minus _servers
/usr/bin/find /data -regextype egrep -maxdepth 1 -mindepth 1 -type d -regex '^/data/[^_].*' -print0 |/usr/bin/xargs -0r /bin/chown -R sharea.sharea

# convert to lowercae
rename -v 'y/A-Z/a-z/' /data/downloads/*

# clear logs
/usr/bin/find /var/log -regextype posix-egrep -type f -mmin +300 -iregex '(.*/samba.*|.*/mail.*|*./vsftp.*)' -print0 |/usr/bin/xargs -0r /bin/rm

