#!/bin/bash
#
#  usage ... unlock_podman.sh host_name user_name user_password locked_user
#
#  ex) # ./unlock_podman.sh podman.hogebar.jp podman passwd j20000aa
#

if [ -n "$SSH_PASSWORD" ]; then
    echo $SSH_PASSWORD
    exit 0
fi

#
if [ $# -lt 4 ]; then
    exit 1
fi

printf -v SSH_HOST '%q' "$1"
printf -v SSH_USER '%q' "$2"
printf -v SSH_PASS '%q' "$3"
printf -v LOCK_USR '%q' "$4"

#
LOCK_FL="/var/lib/containers/storage/overlay-containers/volatile-containers.json"
UNLOCK_BIN="/usr/local/bin/unlock_podman_containers"
UNLOCK_COM=${UNLOCK_BIN}" "${LOCK_FL}" "${LOCK_USR}
#
export SSH_PASSWORD=$SSH_PASS
export SSH_ASKPASS=$0
export DISPLAY=:0.0
#
setsid ssh -oStrictHostKeyChecking=no -oServerAliveInterval=120 -oServerAliveCountMax=3 ${SSH_USER}@${SSH_HOST} ${UNLOCK_COM}

