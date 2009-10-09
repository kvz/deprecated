#!/bin/bash
git status
read -p "Clean working dir? Continue. Otherwise CTRL+C!" COMMENT

set -x
rootdir="$(realpath $(dirname $0))"
phpdir="${rootdir}/php/samples"
for phpfile in `find ${phpdir} -type f -iname '*.php'`; do
  htmlfile=$(echo "${phpfile}" | sed 's#\.php#\.html#g')
  htmlfileinroot=$(echo "${htmlfile}" | sed "s#${rootdir}/##g")
  /usr/bin/php -q ${phpfile} > ${htmlfile}
  git add ${htmlfileinroot}
done
git commit -m 'Built samplefiles'
git push origin master