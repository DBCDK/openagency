#!/usr/bin/env bash

set -e

# Set defaults for stuff that are optional
APACHE_SERVER_NAME=${APACHE_SERVER_NAME:-localhost}
MY_DOMAIN=${MY_DOMAIN:-dbc.dk}
#MY_DOMAIN_IP_LIST=${MY_DOMAIN_IP_LIST:-127.0.0.1;172.16.0.0-172.31.255.255;193.111.162.0-193.111.162.255}
LOGFILE=${LOGFILE:-php://stdout}
VERBOSE_LEVEL=${VERBOSE_LEVEL:-WARNING+ERROR+FATAL+STAT+TIMER+TRACE}
# Leave HTTP_PROXY and HTTP_PROXY_INSECURE_MODE_FOR_TEST_ONLY emtpy, if not set.
HTTP_PROXY=${HTTP_PROXY:- }
HTTP_PROXY_INSECURE_MODE_FOR_TEST_ONLY=${HTTP_PROXY_INSECURE_MODE_FOR_TEST_ONLY:- }
Z3950_URL=${Z3950_URL:-z3950.dbc.dk:210/danbib}
CACHE_EXPIRE_LIBRARYRULES=${CACHE_EXPIRE_LIBRARYRULES:-600}

ALL_VARS="APACHE_ROOT APACHE_SERVER_NAME MY_DOMAIN URL_PATH LOGFILE VERBOSE_LEVEL VIP_CREDENTIALS FORS_RIGHTS Z3950_URL COPA_RS_URL COPA_RS_PASSWD HTTP_PROXY HTTP_PROXY_INSECURE_MODE_FOR_TEST_ONLY CACHE_EXPIRE_LIBRARYRULES"
# AAA_IP_RIGHTS_BLOCK << Not sure if this is needed ot not.
# Helper functions
SCRIPTNAME=entrypoint.sh
function die() {
  echo "[$SCRIPTNAME] $(date +"%T.%N") ERROR:" "$@"
  exit 1
}

function info() {
  echo "[$SCRIPTNAME] $(date +"%T.%N") INFO:" "$@"
}

function error() {
  echo "[$SCRIPTNAME] $(date +"%T.%N") ERROR:" "$@"
}

function usage() {
  echo "Usage: $0 <apache-conf-file>"
  exit 1
}

# Check that we have an argument pointing to the apache configuration file
APACHE_CONF_FILE=$1
test -z "${APACHE_CONF_FILE}" && usage
test -e "${APACHE_CONF_FILE}" || die "The provided apache conf file ${APACHE_CONF_FILE} does not appear to be a file"

# Check that a variable is set. If not, complain and set fail flag.
VAR_CHECK_FAILED=false
function checkVar() {
    VAR=$1
    if [ -z "${!VAR}" ] ; then
        error "No value set for ${VAR}"
        VAR_CHECK_FAILED=true
   fi
}

# Check that all environment variables needed are set
info "Checking environment variables set"
for VAR in ${ALL_VARS} ; do
    checkVar ${VAR}
done
if [ "${VAR_CHECK_FAILED}" = true ] ; then
    die "Some environment variables were missing"
fi

# Creating the project configuration files
STEM=openagency
INI=${APACHE_ROOT}/${STEM}.ini
INI_INSTALL="${INI}_INSTALL"
WSDL=${APACHE_ROOT}/${STEM}.wsdl
WSDL_INSTALL="${WSDL}_INSTALL"

info "Creating ${WSDL} from ${WSDL_INSTALL}"
cp "${WSDL_INSTALL}" "${WSDL}"

info "Setting Apache global ServerName to localhost"
echo "ServerName localhost" > /etc/apache2/conf-available/servername.conf
a2enconf servername || die "Unable to enable Apache servername configuration"

info "Creating ini file by filling out with environment variables."
cp "${INI_INSTALL}" "${INI}"
for VAR in ${ALL_VARS} ; do
    if [ ${VAR} = "VIP_CREDENTIALS" ] ; then
        info "Substituting '${VAR}' with value 'XXXXXXXXXXXXXXXXXX'"
    else
        info "Substituting '${VAR}' with value '${!VAR}'"
    fi
    sed -i "s/@${VAR}@/$(echo ${!VAR} | sed -e 's/\//\\\//g; s/&/\\\&/g')/g" ${INI}
    sed -i "s/@${VAR}@/$(echo ${!VAR} | sed -e 's/\//\\\//g; s/&/\\\&/g')/g" ${APACHE_CONF_FILE}
done

# Check we do not have missing settings in the ini files
if [ -n "`grep '@[A-Z_]*@' ${INI}`" ] ; then
    printf "\nMissed some settings:\n"
    echo "------------------------------"
    echo "In ${INI}"
    grep '@[A-Z_]*@' ${INI}
    echo "In ${APACHE_CONF_FILE}"
    grep '@[A-Z_]*@' ${APACHE_CONF_FILE}
    echo "------------------------------"
    printf "\nAdd the missing setting(s) and try again\n\n"
    exit 1
fi

info "Changing owner on config files"
chown www-data:www-data "${INI}" "${WSDL}" || die "Unable to change owner to www-data:www-data on ${INI} and ${WSDL}"

# Make sure that apache errors and access to stdout
info "Configuring apache access and error logs to link to stdout/stderr"
ln -sf /proc/self/fd/1 /var/log/apache2/access.log || die "Unable to link access log"
ln -sf /proc/self/fd/2 /var/log/apache2/error.log  || die "Unable to link error log"

# Start memcached.
/etc/init.d/memcached start

# Start Apache, and let it take it from there.
info "Starting Apache the Old hacked Way "
export APACHE_RUN_DIR=/var/run/apache2
export APACHE_CONFDIR=/etc/apache2/
# the path to the environment variable file
APACHE_ENVVARS="$APACHE_CONFDIR/envvars"
# pick up any necessary environment variables
if test -f $APACHE_ENVVARS; then
  . $APACHE_ENVVARS
fi

exec /usr/sbin/apache2 -DFOREGROUND
