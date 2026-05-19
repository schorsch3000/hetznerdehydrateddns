FROM debian:13
RUN apt-get update && apt-get upgrade -y && apt-get install -y dehydrated php-cli php-curl php-yaml wget
RUN cp -rp /var/lib/dehydrated /var/lib/dehydrated.orig
RUN cp -rp /etc/dehydrated /etc/dehydrated.orig
WORKDIR /tmp
RUN wget https://github.com/hetznercloud/cli/releases/download/v1.61.0/hcloud-linux-386.tar.gz
RUN tar -xvzf hcloud-linux-386.tar.gz && mv hcloud /usr/local/bin/
ADD drun.sh /usr/local/bin/
ADD hooks.php /usr/local/bin/
