#@IgnoreInspection BashAddShebang

# Shared declarations and functions between system test and the performance test

# Note: Clients must set the following variables
# SCRIPTNAME           = The name of the client (systemtest or performancetest)
# BASE_DIR             = The base directory for the client calling (which is also the basedir for this script)
# TEST_PATH            = Path to where the test will run.
# COMPOSE_PROJECT_NAME = Optional prefix for the compose test.
# COMPOSE_FILE         = : seperated paths for compose files. (https://docs.docker.com/compose/reference/envvars/#compose_file)

# These tests uses the followings services/images.

# PROXY         : The proxy/hoverfly server used during tests (test image)
# VIP_POSTGRES  : Test / fake database
# VIP_CORE      : Only used to initialise the database in the test image.
# WS            : The actual service under test (production image)


# Names of the docker compose services. These should match the ones in the systemtest/docker-compose.yml file
WS_SERVICE="openagency-php"
WS_SERVICE_GOLD="openagency-gold"
VIP_POSTGRES_SERVICE="vip-postgres"

# Names of the actual docker images. These should match the ones in the systemtest/docker-compose.yml file
# SOAPUI_IMAGE="docker-i.dbc.dk/soapui-java:latest"
VIP_POSTGRES_IMAGE="docker-i.dbc.dk/vip-dit-test-data:latest"

# Use this to append "rare" extra arguments to docker-compose. Note, for yml files, use COMPOSE_FILE
DOCKER_COMPOSE="docker-compose"

# Common helper functions. Note the usage of SCRIPTNAME as an output variable.
# Lets color a bit. This is clearly a waste of time... (setup in load function).
OUTPUTCOLOR=
NOCOLOR=

function info() {
  echo "${OUTPUTCOLOR}[${SCRIPTNAME}]${NOCOLOR} $(date +"%T.%N") INFO:" "$@"
}

function error() {
  echo "${OUTPUTCOLOR}[${SCRIPTNAME}]${NOCOLOR} $(date +"%T.%N") ERROR:" "$@"
}

function debug() {
    if [ $d = true ] ; then
        echo "${OUTPUTCOLOR}[${SCRIPTNAME}]${NOCOLOR} $(date +"%T.%N") DEBUG:" "$@"
    fi
}

function die() {
  error "$@"
  stopContainers ${keep}
  exit 1
}

# Check if a name of an executable is available on the path - die if not
function check_on_path() {
    for VAR in "$@"
    do
        which "$VAR" &> /dev/null || die "Unable to find executable '$VAR' on PATH - please install '$VAR'"
    done
}

# Pull the relevant images from artifactory. This is meant to ensure that the Jenkins jobs are always up-to-date.
# Pulling is not performed, if we are running locally.
function pullImages() {
  info "Updating non project images to newest from docker-i.dbc.dk. Errors are ignored."
  ${DOCKER_COMPOSE} pull --ignore-pull-failures ${VIP_POSTGRES_SERVICE} ${WS_SERVICE_GOLD}
}

# Start the basecontainers. These are the containers that are used in both tests.
function startBaseContainers() {
  info "Starting base containers"
  ${DOCKER_COMPOSE} up --force-recreate -d  || die "docker-compose up -d "
  docker pull docker-i.dbc.dk/oa-tester
}

# Arguments:
# 1: URL
# 2: TIMEOUT
# 3: LABEL
function waitFor200() {
    URL=$1
    TIMEOUT=$2
    LABEL=$3
    test -z "${LABEL}" && { error "Usage: waitFor200 <url> <timeout> <label>" ; return 1; }
    START_TIME=$(date '+%s')
    OK_COUNT=0
    OUTPUT=$(mktemp)
    info "Waiting for webservice ${LABEL} to be ready for a maximum of ${TIMEOUT} seconds on ${URL}"
    for i in $(seq 1 ${TIMEOUT}) ; do
        RES=$(curl -s -o ${OUTPUT} -m 5 -w "%{http_code}" "${URL}" 2>/dev/null)
        # debug "RES=$RES"
        if [[ "$RES" == *200 ]] ; then
            OK_COUNT=$((OK_COUNT+1))
            echo -n !
        else
            echo -n .
        fi
        if [ "${OK_COUNT}" -gt 1 ] ; then
            echo
            info "Service ready in " $(( $(date '+%s') - START_TIME )) " seconds.";
            info "Last output : '$(cat ${OUTPUT})'"
            rm ${OUTPUT}
            return 0
        fi
        sleep 1
    done
    echo
    error "Waited more than ${TIMEOUT} seconds, failing status OK check. Service is not OK."
    error "Last output : '$(cat ${OUTPUT})'"
    rm ${OUTPUT}
    return 1
}

# Arguments:
# 1: URL
# 2: LABEL
# 3: MATCH - a string to match.
# Checks that the output match a string. Must return 200.
function checkServiceMatch() {
    URL=$1
    LABEL=$2
    MATCH=$3
    test -z "${MATCH}" && { error "Usage: checkServiceMatch <url> <label> <match>" ; return 1; }
    OUTPUT=$(mktemp)
    info "Get data from  ${LABEL} and match against ${MATCH} on ${URL}"
    RES=$(curl -s -o ${OUTPUT} -m 5 -w "%{http_code}" "${URL}" 2>/dev/null)
    if [[ "$RES" == *200 ]] ; then
        if grep "${MATCH}" "${OUTPUT}" &> /dev/null ; then
            info "Match against '${MATCH}' found in service output. Service is OK"
            debug "output: '$(cat ${OUTPUT})'"
            return 0
        fi
    fi
    echo
    error "Data did not match, service is not OK"
    error "Last output : '$(cat ${OUTPUT})'"
    rm ${OUTPUT}
    return 1
}

function getIPAndPortOfContainer() {
  ${DOCKER_COMPOSE} port "$1" 80 | sed -e "s-0.0.0.0-${HOST_IP}-" | tr -d '\n'
}

function getLast25ErrorLogLinesOfContainer() {
  ${DOCKER_COMPOSE} logs --no-color "$1" | cut -f 2- -d '|' | grep '{' | grep PHP | jq -Cc '. | select(.level=="ERROR")' | tail -n 25
}

# Wait for OK from the WS service.
function waitForOk() {
  info "Waiting on base containers"
  # It is assumed that the proxy container is up very quickly, and that the vip db container is up quicker than the vip container.

  # Wait for the service/gui under test to be ready
  WS_SERVICE_IP_PORT=$(getIPAndPortOfContainer ${WS_SERVICE})

  info "WS_SERVICE_IP_PORT=${WS_SERVICE_IP_PORT}"
  info "Waiting for ws container to be ready"
  # The normal HowRU requires a bunch of data in the database and returns 503, when not ready.
  # We just want - at this point - to make sure we are talking to the database
  # and that the datamodel is sort of OK
  # Abuse this to check that the service is running.
  waitFor200 "http://${WS_SERVICE_IP_PORT}/test_oa/server.php?HowRU" 300 openagency-php || die "openagency-php service not ready in 300 seconds"

  # This uses "service"
  info "Checking openagency.service call"
  checkServiceMatch "http://${WS_SERVICE_IP_PORT}/test_oa/server.php?action=service&agencyId=710100&service=orsItemRequest" openagency-php "<oa:responder>710100</oa:responder>"
  # But, we also want this, to check the log when debugging.
  info "Checking openagency.service call basic"
  checkServiceMatch "http://${WS_SERVICE_IP_PORT}/test_oa/server.php?action=openSearchProfile&agencyId=710100&profileName=foobar&profileVersion=3" openagency-php openSearchProfileResponse
  # This is related to VP-262
  info "Checking openagency.openSearchProfile call with missing agencyId"
  checkServiceMatch "http://${WS_SERVICE_IP_PORT}/test_oa/server.php?action=openSearchProfile&agencyId=&profileVersion=3&trackingId=2019-10-02T14:40:09:509991:3648" openagency-php agency_not_found


  info "Wait for gold service to start"
  WS_SERVICE_GOLD_IP_PORT=$(getIPAndPortOfContainer ${WS_SERVICE_GOLD})

  info "WS_SERVICE_GOLD_IP_PORT=${WS_SERVICE_GOLD_IP_PORT}"
  info "Waiting for ws container to be ready"
  # The normal HowRU requires a bunch of data in the database and returns 503, when not ready.
  # We just want - at this point - to make sure we are talking to the database
  # and that the datamodel is sort of OK
  # Abuse this to check that the service is running.
  waitFor200 "http://${WS_SERVICE_GOLD_IP_PORT}/gold_oa/server.php?HowRU" 300 openagency-php || die "openagency-php service not ready in 300 seconds"


}

# Print info about how to stop containers.
function stopContainersInfo() {
  info "To shut down all containers, use:"
  info "( cd ${TEST_PATH} && COMPOSE_PROJECT_NAME=${COMPOSE_PROJECT_NAME} COMPOSE_FILE=${COMPOSE_FILE} ${DOCKER_COMPOSE} down && docker network prune -f )"
}

# Stop all containers, unless KEEP_CONTAINERS is true
# Args
# 1 : Keep containers or not. False == containers are stopped, True == Containers are allowed to live.
function stopContainers() {
  local keep=$1
  if [ "${keep}x" = "x" ] ; then
    # We may be called from die, so do not call die again from this method.
    error "stopContainers require a true/false argument - non was set"
    exit 1
  fi
  if [ "${keep}" = false ] ; then
    info "Stopping containers"
    ${DOCKER_COMPOSE} down || { error "docker-compose down" ; exit 1; }
  else
    info "Keep is true - keeping containers running"
    stopContainersInfo
  fi
}

# Set some variables on load - most "important": If tty output, lets put some colors on.
function sharedTestOnLoad() {
  if [ -t 1 ] ; then
    OUTPUTCOLOR=$(tput setaf 2)  # Green
    NOCOLOR=$(tput sgr0)
  fi
  export HOST_IP=$(ip addr show | grep -A 99 '^2' | grep inet | grep -o '[0-9]\{1,3\}\.[0-9]\{1,3\}\.[0-9]\{1,3\}\.[0-9]\{1,3\}' |grep -v '^127.0.0.1' | head -1)
  info "Using host IP: ${HOST_IP}"
}
sharedTestOnLoad
