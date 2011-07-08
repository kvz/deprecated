#!/bin/bash
set -ex
if [ ! -x /usr/local/bin/wkhtmltopdf ]; then
	VERSION="0.9.9"
	ARCH="i386"
	[ "$(uname -m)" = "x86_64" ] && ARCH="amd64"

	cd /usr/src
	wget http://wkhtmltopdf.googlecode.com/files/wkhtmltopdf-${VERSION}-static-${ARCH}.tar.bz2
	tar -jxvf wkhtmltopdf-${VERSION}-static-${ARCH}.tar.bz2
	cp -af /usr/src/wkhtmltopdf-${ARCH} /usr/local/bin/wkhtmltopdf
	chmod 755 /usr/local/bin/wkhtmltopdf
fi