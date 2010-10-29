#!/bin/bash
set -x
# From: http://code.google.com/p/wkhtmltopdf/issues/detail?id=3

DISP=$RANDOM let "DISP %= 500"
while [ -f /tmp/.X${DISP}-lock ]; do
	DISP=$RANDOM let "DISP %= 500"
done
echo "Taking display: ${DISP}"
XAUTHORITY=`tempfile`
Xvfb -kb -screen 0 1024x768x24 -dpi 96 -terminate -auth $XAUTHORITY -nolisten tcp :$DISP &DISPLAY=:$DISP wkhtmltopdf $3 $4 $5 $6 $7 $8 $9 ${10} ${11} ${12} ${13} ${14} ${15} ${16} ${17} ${18} ${19} ${20} ${21} ${22} ${23} ${24} ${25} $1 $2
rm $XAUTHORITY
kill $! 2>/dev/null
