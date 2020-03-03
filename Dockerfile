FROM docker.dbc.dk/dbc-apache-php7:old-202010

LABEL maintainer="iScrum Team <iscrum@dbc.dk>" \
      APACHE_SERVER_NAME="The VirtualHost ServerName set for Apache. The global is set to localhost always. [localhost]" \
      MY_DOMAIN="The domain the service is operating from. [dbc.dk]" \
      URL_PATH="An URL path to alias the server to, e.g. staging_2.34" \
      # MY_DOMAIN_IP_LIST="The list of IP adresses considered inhouse. [127.0.0.1;172.16.0.0-172.31.255.255;193.111.162.0-193.111.162.255]" \
      LOGFILE="Configuration of logfile [php://stdout]" \
      VERBOSE_LEVEL="Level of log verbosity [WARNING+ERROR+FATAL+STAT+TIMER+TRACE]" \
      VIP_CREDENTIALS="Vip credential string, on the form ora_user/ora_paswd@my.oracle.server" \
      FORS_RIGHTS="An FORS request string." \
      Z3950_URL="Z39.50 address. [z3950.dbc.dk:210/danbib]"\
      COPA_RS_URL="Url to the COPA RS service, e.g. https://iso18626.addi.dk/copa-rs/app/iso18626/" \
      COPA_RS_PASSWORD="Password to the COPA RS service" \
      HTTP_PROXY="NCIP HTTP proxy - assumed only used for tests. [<empty string>]" \
      HTTP_PROXY_INSECURE_MODE_FOR_TEST_ONLY="Use insecure mode (ignore certificates) for https communication when HTTP_PROXY enabled in ncip mode. Use ONLY in tests. Set to 1 to enable [<empty string>]" \
      CACHE_EXPIRE_LIBRARYRULES="Cache expire time in seconds for libraryRules. [600]"

# Setup the www directory.

COPY --chown=sideejer:sideejer src/xml ${DBC_PHP_INSTALL_DIR}/xml
COPY --chown=sideejer:sideejer src/OLS_class_lib ${DBC_PHP_INSTALL_DIR}/OLS_class_lib
COPY --chown=sideejer:sideejer src/*.php src/*_INSTALL src/*.xsd ${DBC_PHP_INSTALL_DIR}/

RUN rm -Rf ${DBC_PHP_INSTALL_DIR}/OLS_class_lib/.svn ${DBC_PHP_INSTALL_DIR}/OLS_class_lib/test ${DBC_PHP_INSTALL_DIR}/simpletest && \
    ln -s server.php ${DBC_PHP_INSTALL_DIR}/index.php

ENV VERBOSE_LEVEL=-WARNING+ERROR+FATAL+STAT+TIMER+TRACE \
    CACHE_EXPIRE_LIBRARYRULES=600
