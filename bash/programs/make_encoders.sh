#!/bin/bash
#/**
# * (NOT READY!) Will compile ffmpeg & mencoder with support for multi cores.
# *
# * Usefull if you you want to run ps3mediaserver.org
# *
# * @author    Kevin van Zonneveld <kevin@vanzonneveld.net>
# * @copyright 2009 Kevin van Zonneveld (http://kevin.vanzonneveld.net)
# * @license   http://www.opensource.org/licenses/bsd-license.php New BSD Licence
# * @version   SVN: Release: $Id$
# * @link      http://kevin.vanzonneveld.net/
# */

set -x
# http://ps3mediaserver.org/forum/viewtopic.php?f=3&t=315

# apt-get install subversion build-essential git-core checkinstall yasm libgpac-dev

# apt-get install em8300-headers gawk gettext html2text intltool-debian \
# ladspa-sdk libaa1-dev libartsc0 libartsc0-dev libasound2-dev libatk1.0-dev libaudio-dev \
# libaudio2 libaudiofile-dev libavahi-client-dev libavahi-common-dev libcaca-dev \
# libcairo2-dev libcdparanoia0-dev libcucul-dev libdbus-1-dev libdbus-glib-1-dev \
# libdirectfb-dev libdirectfb-extra libdts-dev libdv4-dev libenca-dev libenca0 \
# libesd0-dev libexpat1-dev libfaac-dev libfaac0 libfontconfig1-dev libfreebob0 \
# libfreetype6-dev libfribidi-dev libggi-target-x libggi2 libggi2-dev libggimisc2 \
# libggimisc2-dev libgif-dev libgii1 libgii1-dev libgii1-target-x libgl1-mesa-dev \
# libglib2.0-dev libglide2 libglu1-mesa-dev libglu1-xorg-dev libgtk2.0-dev libice-dev \
# libjack-dev libjack0 libjpeg62-dev liblzo-dev liblzo1 liblzo2-2 liblzo2-dev libmad0 \
# libmad0-dev libmail-sendmail-perl libmp3lame-dev libmp3lame0 libmpcdec-dev libmpcdec3 \
# libncurses5-dev libogg-dev libopenal-dev libopenal1 libpango1.0-dev libpixman-1-dev \
# libpng12-dev libpopt-dev libpthread-stubs0 libpthread-stubs0-dev libpulse-dev \
# libpulse-mainloop-glib0 libsdl1.2-dev libslang2-dev libsm-dev libsmbclient-dev \
# libspeex-dev libsvga1 libsvga1-dev libsys-hostname-long-perl libsysfs-dev \
# libtheora-dev libtwolame-dev libtwolame0 libvorbis-dev libx11-dev libxau-dev \
# libxcb-render-util0-dev libxcb-render0-dev libxcb-xlib0-dev libxcb1-dev \
# libxcomposite-dev libxcursor-dev libxdamage-dev libxdmcp-dev libxext-dev libxfixes-dev \
# libxft-dev libxi-dev libxinerama-dev libxrandr-dev libxrender-dev libxt-dev libxv-dev \
# libxvidcore4 libxvidcore4-dev libxvmc-dev libxvmc1 libxxf86dga-dev libxxf86vm-dev \
# mesa-common-dev po-debconf sharutils x11proto-composite-dev x11proto-core-dev \
# x11proto-damage-dev x11proto-fixes-dev x11proto-input-dev x11proto-kb-dev \
# x11proto-randr-dev x11proto-render-dev x11proto-video-dev x11proto-xext-dev \
# x11proto-xf86dga-dev x11proto-xf86vidmode-dev x11proto-xinerama-dev \
# xtrans-dev zlib1g-dev libschroedinger-dev libstdc++5 libfaad-dev \
# libgsm1-dev libdc1394-22-dev libfaad-dev libsdl1.2-dev

# apt-get remove ffmpeg mencoder

svn checkout svn://svn.mplayerhq.hu/mplayer/trunk /usr/src/mplayer || exit 1

if [ -f "/usr/src/ffmpeg-mt/configure" ]; then
    cd "/usr/src/ffmpeg-mt"
    git pull || exit 1
else
    git clone git://gitorious.org/ffmpeg/ffmpeg-mt.git "/usr/src/ffmpeg-mt" || exit 1
fi

if [ -f "/usr/src/ffmpeg-mt/libswscale/Makefile" ]; then
    cd "/usr/src/ffmpeg-mt/libswscale"
    git pull || exit 1
else
    [ -d "/usr/src/ffmpeg-mt/libswscale" ] && rmdir "/usr/src/ffmpeg-mt/libswscale" || exit 1
    git clone git://git.ffmpeg.org/libswscale/ "/usr/src/ffmpeg-mt/libswscale" || exit 1
fi

if [ -f "/usr/src/x264/configure" ]; then
    cd "/usr/src/x264"
    git pull || exit 1
else
    git clone git://git.videolan.org/x264.git "/usr/src/x264" || exit 1
fi

cd "/usr/src/x264"
./configure --enable-shared || exit 1
make || exit 1
checkinstall --fstrans=no --install=yes --pkgname=x264 --pkgversion "1:0.svn`date +%Y%m%d`-ubuntu" || exit 1
ldconfig || exit 1

cd "/usr/src/ffmpeg-mt"
./configure || exit 1
make  || exit 1
make install || exit 1


cd /usr/src/mplayer || exit 1
cp -rf ../ffmpeg-mt/libavcodec libavcodec || exit 1
cp -rf ../ffmpeg-mt/libavformat libavformat || exit 1
cp -rf ../ffmpeg-mt/libavutil libavutil || exit 1
./configure || exit 1
make || exit 1
make install || exit 1
export LD_LIBRARY_PATH=/usr/local/lib/ || exit 1

echo "All Done."  || exit 1
