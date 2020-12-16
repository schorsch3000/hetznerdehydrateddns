#!/bin/bash
touch /etc/dehydrated/domains.txt
echo "nameserver 8.8.8.8" >/etc/resolv.conf
echo "nameserver 1.1.1.1" >/etc/resolv.conf
test -f /etc/dehydrated/conf.d/hooks.sh || echo 'HOOK="/usr/local/bin/hooks.php"' > /etc/dehydrated/conf.d/hooks.sh
test -f /etc/dehydrated/conf.d/challengetype.sh || echo 'CHALLENGETYPE="dns-01"' > /etc/dehydrated/conf.d/challengetype.sh
test -f /etc/dehydrated/conf.d/hetzner_api_token.sh || echo 'export HETZNER_API_TOKEN=MY_API_TOKEN' > /etc/dehydrated/conf.d/hetzner_api_token.sh
test -f /etc/dehydrated/conf.d/ca.sh || echo -e 'CA="https://acme-staging-v02.api.letsencrypt.org/directory"\n# do not delete this file, deleting the line above is fine.' > /etc/dehydrated/conf.d/ca.sh

/usr/bin/dehydrated --register --accept-terms
/usr/bin/dehydrated --cron
/usr/local/bin/hooks.php bundle