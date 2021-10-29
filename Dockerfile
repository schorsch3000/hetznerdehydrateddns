FROM debian:11
RUN apt-get update && apt-get upgrade -y && apt-get install -y dehydrated php-cli php-curl php-yaml
RUN cp -rp /var/lib/dehydrated /var/lib/dehydrated.orig
RUN cp -rp /etc/dehydrated /etc/dehydrated.orig
ADD drun.sh /usr/local/bin/
ADD hooks.php /usr/local/bin/
