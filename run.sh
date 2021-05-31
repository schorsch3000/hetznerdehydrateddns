#!/bin/bash
docker build -t dehydrated_hetzner_wildcard_cert .

docker run --rm -t -v "$PWD/config:/etc/dehydrated" -v "$PWD/bundle:/bundle" -v "$PWD/data:/var/lib/dehydrated" dehydrated_hetzner_wildcard_cert /usr/local/bin/drun.sh
