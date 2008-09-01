#!/bin/bash
#/**
# * Takes source from ./bash.dev programs, recursively follows (function) includes, 
# * and compiles standalone bash programs in ./bash
# * 
# * @author    Kevin van Zonneveld <kevin@vanzonneveld.net>
# * @copyright 2008 Kevin van Zonneveld (http://kevin.vanzonneveld.net)
# * @license   http://www.opensource.org/licenses/bsd-license.php New BSD Licence
# * @version   SVN: Release: $Id$
# * @link      http://kevin.vanzonneveld.net/
# * 
# */

# Includes
###############################################################
source $(realpath "$(dirname ${0})/functions/log.sh")
source $(realpath "$(dirname ${0})/functions/getWorkingDir.sh")


DIR_ROOT=$(getWorkingDir "/..");
DIR_SORC="${DIR_ROOT}/bash.dev"
DIR_DEST="${DIR_ROOT}/bash"

# Loop through BASH 'programs'
for filePathSource in $(find ${DIR_SORC}/*/ -type f -name '*.sh'); do
    # Determine compiled version path
    filePathDest=$(echo "${filePathSource}" |sed "s#${DIR_SORC}#${DIR_DEST}#g")
	log "${filePathSource} --> ${filePathDest}" "INFO"
    
    # Grep 'make::'includes
	depTxt=$(cat ${filePathSource} |grep '# make::include')
	depsAdded=0

    srcLines=$(cat ${filePathSource} |wc -l)
    depAt=$(cat ${filePathSource} |grep -n '# make::include' |head -n1 |awk -F':' '{print $1}')
    [ -n "${depAt}" ] || depAt=0
    let linesRemain=srcLines-depAt
    
    # Reset destination file
    echo "#!/bin/bash" > ${filePathDest}
    chmod a+x ${filePathDest}

    # Add head of original source
    cat ${filePathSource} |head -n ${depAt} |egrep -v '(# make::include|#!/bin/bash)' >> ${filePathDest} 
	
	# Walk through include lines
	for depLine in ${depTxt}; do
		# Extract filename 
		[[ ${depLine} =~ (/([\.a-zA-Z0-9\/]+)+) ]]
		depFile=${BASH_REMATCH[1]}
		
		# Include filename matched?
		if [ -n "${depFile}" ]; then
			# Create real path from include reference
		    realDepFile=$(realpath ${DIR_SORC}/programs/${depFile})
		    
		    # Real include path exists? 
			if [ ! -f "${realDepFile}" ]; then
				log "Include path '${realDepFile}' does not exist!" "EMERG"
			fi
			
			# Add dependency
			let depsAdded=depsAdded+1
			echo "" >> ${filePathDest}
			echo "# (make::included from '${depFile}')" >> ${filePathDest}
			cat ${realDepFile} >> ${filePathDest}
			echo "" >> ${filePathDest}
		fi
	done
	
    # Add remainder of original source
    cat ${filePathSource} |tail -n ${linesRemain} |egrep -v '(# make::include|#!/bin/bash)' >> ${filePathDest} 
done