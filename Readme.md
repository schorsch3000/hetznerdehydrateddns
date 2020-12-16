# Generate wildcard Certificates with Let's encrypt using the Hetzner DNS API

## How to
Run `./run.sh` to initialize, it will fail the first time, but you get 3 folder populated, `config` `data` and `bundle`


The `config` folder is mapped to `/etc/dehydrated` and a few extra config files are populated.

 - `ca.sh`: set's the CA to the staging CA, so you can try to set up everything without running into the rate-limit., comment the `CA=...` line out after your setup is finished.
 - `hetzner_api_token.sh`: set Your Hetzner API token here
 
 The `data` folder is mapped to `/var/lib/dehydrated`, your certificated and keys are stored here
 
 In the `bundle` folder are json and yaml files containing every cert, one by one and bundled.
 You need to bring the key to every cert to your production machine over a secure channel. Your Certificates on the other hand are no secrets, you may publish all files within `./bundle` on a public http-server and pull the certificates on a regular basis onto your production-machines
 
 You need to run `./run.sh` on a regular basis (eg. daily) to keep your certificates valid.       