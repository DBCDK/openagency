FROM docker.dbc.dk/dbc-apache-php7

COPY memcached.conf /etc/

# Download and prepare php source-code
RUN mkdir $APACHE_ROOT/openagency
ADD openagency-webservice.tar.gz $APACHE_ROOT/openagency

# openSearch default settings
ENV VERBOSE_LEVEL="WARNING+ERROR+FATAL+STAT+TIMER"

LABEL AAA_IP_RIGHTS_BLOCK="Sets authentication and access scheme; Use aaa_ip_rights and/or aaa_fors_rights" \
      VERBOSE_LEVEL=" List of log levels needed, maximum as: WARNING+ERROR+FATAL+STAT+TIMER+TRACE+DEBUG"

WORKDIR /
COPY run.sh /run.sh
COPY apache_security.conf /etc/apache2/conf-enabled/
COPY 000-default.conf /etc/apache2/sites-enabled/000-default.conf
CMD ["/run.sh"]
