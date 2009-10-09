#!/bin/bash
output_ext="html"

set -x
rootdir="$(realpath $(dirname $0))"
phpdir="${rootdir}/php/samples"
for htmlfile in `find ${phpdir} -type f -iname "*.${output_ext}"`; do
    htmlfileinroot=$(echo "${htmlfile}" | sed "s#${rootdir}/##g")
    git rm -f ${htmlfileinroot}
done

commitfiles=""
for phpfile in `find ${phpdir} -type f -iname '*.php'`; do
  htmlfile=$(echo "${phpfile}" | sed "s#\.php#\.${output_ext}#g")

  htmlfileinroot=$(echo "${htmlfile}" | sed "s#${rootdir}/##g")
  phpfileinroot=$(echo "${phpfile}" | sed "s#${rootdir}/##g")

  /usr/bin/php -q ${phpfile} > ${htmlfile}
  commitfiles="${commitfiles}${phpfileinroot} ${htmlfileinroot} "
done

git status
read -p "Clean working dir? Continue. Otherwise CTRL+C!" COMMENT

git add ${commitfiles}
git commit -m 'Built samplefiles'
git push origin master

