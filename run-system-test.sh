#!/usr/bin/env bash
set -e
set -o pipefail

# This script is used by to run the systemtests. You can also run them

export SCRIPTNAME="systest"
export BASE_DIR=$(dirname "$(readlink -f "$0")") || { echo "Unable to identify basedir from $0" ; exit 1; }
. "${BASE_DIR}/shared_test.sh" || { echo "Unable to load shared file shared_test.sh" ; exit 1; }

function setSysVars() {
  info "Setting systest variables"
  # For the compose files, export DOCKER_IMAGE_TAG to match the tag. Note that : is prefixed.
  export DOCKER_IMAGE_TAG=:${tag}
  export TEST_PATH="${BASE_DIR}/docker/compose/systemtest"
  export COMPOSE_FILE="${TEST_PATH}/docker-compose.yml"
  # In order to allow concurrent build in Jenkins, append tag, if != latest
  if [ "${tag}" != "latest" ]; then
    export COMPOSE_PROJECT_NAME=openagency-phpsystemtest-${tag}
  else
    export COMPOSE_PROJECT_NAME=openagency-phpsystemtest
  fi
  debug "COMPOSE_PROJECT_NAME=${COMPOSE_PROJECT_NAME}"
}

TESTRUN_PASSED=1
function runTest() {
  info "Starting test"
  JUNIT_RESULT_DIR=${BASE_DIR}/junit_results
  [ -d "$JUNIT_RESULT_DIR" ] || mkdir "$JUNIT_RESULT_DIR"
  JUNIT_RESULT_DIR=$(realpath "$JUNIT_RESULT_DIR")
  info "Starting oa brute force tester"
  TEST_IP_PORT=$(getIPAndPortOfContainer "$WS_SERVICE")
  GOLD_IP_PORT=$(getIPAndPortOfContainer "$WS_SERVICE_GOLD")
  docker run --rm -e BUILD_NUMBER -v "$JUNIT_RESULT_DIR:/output" docker-i.dbc.dk/oa-tester full "http://$GOLD_IP_PORT/gold_oa/" "http://$TEST_IP_PORT/test_oa/"
  RESULT=$?
  info "Result of test is : " ${RESULT}
  TESTRUN_PASSED=${RESULT}
}


function main()  {
  check_on_path docker docker-compose
  info "Running tests of service"

  setSysVars
  cd ${TEST_PATH} || die "cd ${TEST_PATH}"
  info "Stopping any old related containers"
  ${DOCKER_COMPOSE} down
  [[ ${pull} = true ]] && pullImages
  [[ ${keep} = true ]] && stopContainersInfo
  startBaseContainers
  waitForOk

  runTest
  RESULT=$(( ${TESTRUN_PASSED} ))
  if [[ ${RESULT} -ne 0 ]] ; then
    info "Dumping logs from ws image"
    ${DOCKER_COMPOSE} logs ${WS_SERVICE}
  fi

  stopContainers ${keep}
  # Collate results. Yes, you can add exit codes if you are careful and lucky.                                                                                                       
  info "Result of test is : ${RESULT}. (0 == Passed, everything else == Failed)"
    if [[ ${RESULT} -eq 0 ]] ; then
    info "Test PASSED"
  else
    error "Test FAILED"
  fi
  exit ${RESULT}

}

################################################################################
function usage() {
    echo "Usage: $0 [options]"
    echo "Script to run the systemtest for vip-php"
    echo
    echo "Options:"
    echo
    echo "-t, --tag <tag>         Tag to use all for all images [${tag}]"
    echo "-p, --pull              Pull/update non-project containers from repo"
    echo "-d, --debug             Output extra debug information"
    echo "-k, --keep              Keep containers on exit (for those that do not autoterminate)"
    echo "-h, --help              Display this help"
}

################################################################################
# Actual script starts here.
# Use getopt to parse arguments.
! getopt --test > /dev/null
if [[ ${PIPESTATUS[0]} -ne 4 ]]; then
    echo 'I’m sorry, `getopt --test` failed in this environment.'
    exit 1
fi

# Global options. Set below, when parsing the command line.
d=false pull=false tag="latest" keep=false

OPTIONS=dhpt:k
LONGOPTS=debug,help,pull,tag:,keep

# -use ! and PIPESTATUS to get exit code with errexit set
# -temporarily store output to be able to check for errors
# -activate quoting/enhanced mode (e.g. by writing out “--options”)
# -pass arguments only via   -- "$@"   to separate them correctly
! PARSED=$(getopt --options=$OPTIONS --longoptions=$LONGOPTS --name "$0" -- "$@")
if [[ ${PIPESTATUS[0]} -ne 0 ]]; then
    # e.g. return value is 1
    #  then getopt has complained about wrong arguments to stdout
    usage
    exit 2
fi
# read getopt’s output this way to handle the quoting right:
eval set -- "$PARSED"

# now enjoy the options in order and nicely split until we see --
while true; do
    case "$1" in
        -d|--debug)
            d=true
            shift
            ;;
        -p|--pull)
            pull=true
            shift
            ;;
        -k|--keep)
            keep=true
            shift
            ;;
        -t|--tag)
            tag="$2"
            shift 2
            ;;
        -h|--help)
            usage
            exit 0
            ;;
        --)
            shift
            break
            ;;
        *)
            die "Internal error while parsing arguments. Matching on $1"
            exit 3
            ;;
    esac
done

# handle non-option arguments
#if [[ $# -ne 1 ]]; then
#    echo "$0: A single input file is required."
#    exit 4
#fi

if [ $d = true ] ; then
    debug "debug: $d, pull: ${pull}, keep: ${keep}, tag: ${tag}, help: ${help}"
fi

main

