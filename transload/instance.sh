#!/bin/bash
# http://docs.amazonwebservices.com/AWSEC2/2007-08-29/DeveloperGuide/
#
# This file can be run from your workstation to either create or config
# one of these instance-types:
# - worker
# - loadbalancer
# - monitor
#
# Syntax:
# ./instance.sh create worker|loadbalancer|monitor
# ./instance.sh config worker <instance_id>
# ./instance.sh show monitor <instance_id>
#

DIR_HOME="/home/${USER}"
DIR_SECR="/home/${USER}/.ec2"
DIR_PROG="${DIR_HOME}/ec2-api-tools"
KEY_PAIR="gsg-keypair-kev"
FLE_PAIR="${DIR_SECR}/${KEY_PAIR}"
INS_TYPE="m1.small"

export JAVA_HOME="/usr/lib/jvm/java-6-sun-1.6.0.10"
export EC2_HOME="${DIR_PROG}"
export PATH=$PATH:$EC2_HOME/bin
export EC2_PRIVATE_KEY="${DIR_HOME}/.ec2/pk-PEG7W7Z46Z6PKC6G4S3UKWMK3JVHSCCG.pem"
export EC2_CERT="${DIR_HOME}/.ec2/cert-PEG7W7Z46Z6PKC6G4S3UKWMK3JVHSCCG.pem"

ACTION="${1}"
OPTION="${2}"
IDENTI="${3}"

if [ "${ACTION}" != "config" ] && [ "${ACTION}" != "create" ] && [ "${ACTION}" != "show" ] ; then
    echo "First argument must be either"
    echo " - config"
    echo " - create"
    echo " - show"
    dieError ""
fi

if [ -z "${OPTION}" ]; then
    echo "Second argument must be either: "
    echo " - worker"
    echo " - loadbalancer"
    echo " - monitor"
    dieError ""
fi

if [ "${ACTION}" = "config" ] && [ -z "${IDENTI}" ]; then
    dieError "Third argument must be an instance ID"
fi

function dieError() {
    echo "${1}" 
    dieError ""
}

function configInstance() {
    TYPE="${1}"
    INST="${2}"
    HOSTNAME=$(ec2-describe-instances ${INST} |grep 'ami-' |awk '{print $4}')

    [ -f "${FLE_PAIR}" ] || dieError "Please set FLE_PAIR to the right path for id_rsa-gsg-keypair"
    x=$(ssh -i ${FLE_PAIR} root@${HOSTNAME} 'wget -O- www.google.com')
}

function showInstance() {
    TYPE="${1}"
    INST="${2}"

    echo "-- [ ${INST} ] -----------------------------------------------------"
}

# Uncomment for auto-scanning of needed image
# AMI_HARDY32=$(ec2-describe-images -a |grep 'alestic/ubuntu-8.04-hardy-base' |tail -n1 |awk '{print $2}' |grep 'ami-')
AMI_HARDY32="ami-1c5db975"

if [ -z "${AMI_HARDY32}" ]; then
    dieError "Hardy image could not be located"
fi

if [ "${ACTION}" = "show" ]; then
    showInstance ${OPTION} ${IDENTI}
elif [ "${ACTION}" = "create" ]; then
    # Run instance and gather instance data
    cnt=0
    for RAWLINE in $(ec2-run-instances ${AMI_HARDY32} --instance-type ${INS_TYPE} -k ${KEY_PAIR} |grep 'ami-' |sed 's#[[:space:]]#_#g'); do
        LINE=$(echo "${RAWLINE}" | sed 's#_# #g')
        INSTANCE_NAMES[${cnt}]=$(echo "${LINE}" | awk '{print $2}')
        INSTANCE_ROWS[${cnt}]=${LINE}
        let "cnt = cnt + 1"
    done

    # Grant access for default group
    ec2-authorize default -p 22
    ec2-authorize default -p 80

    # Go through instances
    if [ ${#INSTANCE_NAMES} ]; then
        for (( i = 0 ; i < ${#INSTANCE_NAMES[@]} ; i++ ));do
            IDENTI=${INSTANCE_NAMES[${i}]}
            showInstance ${OPTION} ${IDENTI}
        done
    fi
elif [ "${ACTION}" = "config" ]; then
    showInstance ${OPTION} ${IDENTI}
    echo configInstance ${OPTION} ${IDENTI}
fi