#!/bin/bash
set +x
#/**
# * Takes source from ./bash.dev programs, recursively follows (function) includes, 
# * and compiles standalone bash programs in ./bash
# * 
# * @author    Kevin van Zonneveld <kevin@vanzonneveld.net>
# * @copyright 2008 Kevin van Zonneveld (http://kevin.vanzonneveld.net)
# * @license   http://www.opensource.org/licenses/bsd-license.php New BSD Licence
# * @version   SVN: Release: $Id: make.sh 218 2009-01-12 14:59:57Z kevin $
# * @link      http://kevin.vanzonneveld.net/
# * 
# */

# Includes
###############################################################
source $(echo "$(dirname ${0})/functions/log.sh")
source $(echo "$(dirname ${0})/functions/commandTestHandle.sh")
source $(echo "$(dirname ${0})/functions/commandTest.sh")
source $(echo "$(dirname ${0})/functions/commandInstall.sh")
source $(echo "$(dirname ${0})/functions/toUpper.sh")
source $(echo "$(dirname ${0})/functions/getWorkingDir.sh")

# Essential config
###############################################################
OUTPUT_DEBUG=0

# Check for program requirements
###############################################################
commandTestHandle "bash" "bash" "EMERG" "NOINSTALL"
commandTestHandle "aptitude" "aptitude" "DEBUG" "NOINSTALL" # Just try to set CMD_APTITUDE, produces DEBUG msg if not found
commandTestHandle "egrep" "grep" "EMERG"
commandTestHandle "grep" "grep" "EMERG"
commandTestHandle "awk" "gawk" "EMERG"
commandTestHandle "sort" "coreutils" "EMERG"
commandTestHandle "uniq" "coreutils" "EMERG"
commandTestHandle "dirname" "coreutils" "EMERG"
commandTestHandle "realpath" "realpath" "EMERG"
commandTestHandle "sed" "sed" "EMERG"

commandTestHandle "tail" "coreutils" "EMERG"
commandTestHandle "head" "coreutils" "EMERG"

# Config
###############################################################
DIR_ROOT=$(getWorkingDir "/..");
DIR_SORC="${DIR_ROOT}/bash.dev"
DIR_DEST="${DIR_ROOT}/bash"

# Run
###############################################################

# Loop through BASH 'programs'
for filePathSource in $(find ${DIR_SORC}/*/ -type f -name '*.sh'); do
	
    fileSourceBase=$(basename ${filePathSource})
	
    # Determine compiled version path
    filePathDest=$(echo "${filePathSource}" |sed "s#${DIR_SORC}#${DIR_DEST}#g")
	log "${filePathSource} --> ${filePathDest}" "DEBUG"
    
    # Grep 'make::'includes
	depTxt=$(cat ${filePathSource} |grep '# make::include')
    depsAdded=0

	# Walk through include lines
    if [ -z "${depTxt}" ]; then
        cp -af ${filePathSource} ${filePathDest}
        log "No includes for: '${fileSourceBase}', just copied to ${filePathDest}" "INFO"
    else

        srcLines=$(cat ${filePathSource} |wc -l)
        depAt=$(cat ${filePathSource} |grep -n '# make::include' |head -n1 |awk -F':' '{print $1}')
        [ -n "${depAt}" ] || depAt=0
        let linesRemain=srcLines-depAt

        # Reset destination file
        echo "#!/bin/bash" |tee ${filePathDest} > /dev/null
        [ -f ${filePathDest} ] || log "Unable to create file: '${filePathDest}'" "EMERG"
        chmod a+x ${filePathDest}

        # Add head of original source
        cat ${filePathSource} |head -n ${depAt} |egrep -v '(# make::include|#!/bin/bash)' |tee -a ${filePathDest} > /dev/null

        for depPart in ${depTxt}; do
            # Extract filename
            [[ ${depPart} =~ (/([\.a-zA-Z0-9\/]+)+) ]]
            depFile=${BASH_REMATCH[1]}

            # Include filename matched?
            if [ -n "${depFile}" ]; then
                # Create real path from include reference
                realDepFile=$(realpath ${DIR_SORC}/programs/${depFile})
                realDepBase=$(basename ${realDepFile} ".sh")

                # Real include path exists?
                if [ ! -f "${realDepFile}" ]; then
                    log "Include path '${realDepFile}' does not exist!" "EMERG"
                else
                    log "Added include: '${realDepBase}' to '${fileSourceBase}'" "DEBUG"
                fi

                # Add dependency
                let depsAdded=depsAdded+1
                echo "" |tee -a ${filePathDest} > /dev/null
                echo "# ${realDepBase}() was auto-included from '${depFile}' by make.sh" |tee -a ${filePathDest} > /dev/null
                cat ${realDepFile}  |tee -a ${filePathDest} > /dev/null
                echo "" |tee -a ${filePathDest} > /dev/null
            fi
        done

        if [ "${depsAdded}" -gt 0 ]; then
            log "Added ${depsAdded} includes for: '${fileSourceBase}'" "INFO"
        fi

        # Add remainder of original source
        cat ${filePathSource} |tail -n ${linesRemain} |egrep -v '(# make::include|#!/bin/bash)' |tee -a ${filePathDest} > /dev/null
    fi
	
done