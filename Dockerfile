FROM docker.dbc.dk/dbc-apache-php7

EXPOSE 80

RUN apt-get update && DEBIAN_FRONTEND=noninteractive apt-get -q -y install php-mbstring ca-certificates php7.0-pgsql

LABEL maintainer="iScrum Team <iscrum@dbc.dk>" \
      APACHE_SERVER_NAME="The VirtualHost ServerName set for Apache. The global is set to localhost always. [localhost]" \
      MY_DOMAIN="The domain the service is operating from. [dbc.dk]" \
      URL_PATH="An URL path to alias the server to, e.g. staging_2.34" \
      # MY_DOMAIN_IP_LIST="The list of IP adresses considered inhouse. [127.0.0.1;172.16.0.0-172.31.255.255;193.111.162.0-193.111.162.255]" \
      LOGFILE="Configuration of logfile [php://stdout]" \
      VERBOSE_LEVEL="Level of log verbosity [WARNING+ERROR+FATAL+STAT+TIMER+TRACE]" \
      VIP_CREDENTIALS="Vip credential string, on the form ora_user/ora_paswd@my.oracle.server" \
      FORS_RIGHTS="An FORS request string." \
      COPA_RS_URL="Url to the COPA RS service, e.g. https://iso18626.addi.dk/copa-rs/app/iso18626/" \
      COPA_RS_PASSWORD="Password to the COPA RS service" \
      HTTP_PROXY="NCIP HTTP proxy - assumed only used for tests. [<empty string>]" \
      HTTP_PROXY_INSECURE_MODE_FOR_TEST_ONLY="Use insecure mode (ignore certificates) for https communication when HTTP_PROXY enabled in ncip mode. Use ONLY in tests. Set to 1 to enable [<empty string>]"

# Configure memcached
COPY docker/images/ws/memcached.conf /etc/

# Configure apache settings.
COPY docker/images/ws/apache_security.conf /etc/apache2/conf-enabled/
COPY docker/images/ws/000-default.conf /etc/apache2/sites-enabled/000-default.conf

# Setup entrypoint
COPY docker/images/ws/entrypoint.sh /
RUN chmod 755 /entrypoint.sh
ENTRYPOINT ["/entrypoint.sh", "/etc/apache2/sites-enabled/000-default.conf"]

# Setup the www directory.
RUN rm -r /var/www
COPY src/OLS_class_lib/ /var/www/html/OLS_class_lib/
RUN rm -Rf /var/www/html/OLS_class_lib/.svn /var/www/html/OLS_class_lib/test /var/www/html/OLS_class_lib/simpletest
COPY src/xml/ /var/www/html/xml/
COPY src/openagency.ini_INSTALL src/openagency.wsdl_INSTALL src/openagency.xsd src/server.php src/robots.txt_INSTALL /var/www/html/
RUN chown -R www-data:www-data /var/www/html


