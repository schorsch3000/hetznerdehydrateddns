#!/usr/bin/env php
<?php

$params = $_SERVER['argv'];
array_shift($params);
array_shift($params);
switch ($_SERVER['argv'][1]) {
    case 'deploy_challenge':
        deploy_challenge(...$params);
        break;
    case 'bundle':
        bundle(...$params);
        break;
    case 'clean_challenge':
        clean_challenge(...$params);
        break;
    case 'this_hookscript_is_broken__dehydrated_is_working_fine__please_ignore_unknown_hooks_in_your_script':
        exit;

    default:
        exit;
}
function bundle()
{
    $certs = [];
    $keys = [];
    foreach(glob('/bundle/*.json') as $jsonFile){
        unlink($jsonFile);
    }
    foreach(glob('/bundle/*.yaml') as $jsonFile){
        unlink($jsonFile);
    }
    foreach (glob("/var/lib/dehydrated/certs/*", GLOB_ONLYDIR) as $certDir) {
        $certName = basename($certDir);
        $keys[$certName] = file_get_contents($certDir . '/privkey.pem');
        $certs[$certName] = [
            "cert" => file_get_contents($certDir . '/cert.pem'),
            "chain" => file_get_contents($certDir . '/chain.pem'),
            "fullchain" => file_get_contents($certDir . '/fullchain.pem'),
        ];
    }
    file_put_contents('/bundle/certs.json',json_encode($certs,JSON_PRETTY_PRINT));
    file_put_contents('/bundle/certs.yaml',yaml_emit($certs));
    foreach($certs as $name => $cert){
        file_put_contents("/bundle/cert_$name.json",json_encode($cert,JSON_PRETTY_PRINT));
        file_put_contents("/bundle/cert_$name.yaml",yaml_emit($cert));
    }


}

function deploy_challenge($domain, $tokenFile, $tokenValue)
{
    $zoneId = getZoneId($domain, $d);
    echo " +-+ creating TXT record for $domain";

    createRecord("_acme-challenge." . substr($domain, 0, -1 - strlen($d)), 60, 'TXT', $tokenValue, $zoneId);
    echo " Done \n | + waiting for DNS to propagate";
    $maxWait = time() + 60 * 5;
    do {
        $records = dns_get_record("_acme-challenge.$domain", DNS_TXT);
        foreach ($records as $record) {


            if ($record['txt'] === $tokenValue) break 2;
        }
        sleep(1);
        echo ".";

    } while ($maxWait > time());
    echo "\n";
    echo " | + Waiting additional 15 sec";
    for($i=0; $i<15; $i++){
        sleep(1);
        echo ".";
    }
    echo "\n;"
}

function clean_challenge($domain, $tokenFile, $tokenValue)
{
    $zoneId = getZoneId($domain);
    deleteRecord("_acme-challenge", $zoneId, $tokenValue);

}

function getZoneId($domain, &$d = null)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://dns.hetzner.com/api/v1/zones');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Auth-API-Token: ' . $_SERVER['HETZNER_API_TOKEN'],
    ]);
    $response = curl_exec($ch);
    if (!$response) {
        die('Error: "' . curl_error($ch) . '" - Code: ' . curl_errno($ch));
    }
    curl_close($ch);
    $data = json_decode($response);

    foreach ($data->zones as $zone) {
        if ($zone->is_secondary_dns) continue;
        if (strpos($domain, $zone->name) !== false) {
            $d = $zone->name;
            return $zone->id;
        }
    }
    die("Can'T find zone for $domain\n");
}

function createRecord($name, $ttl, $type, $value, $zone_id)
{
    if ($name === "_acme-challenge.") $name = "_acme-challenge";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://dns.hetzner.com/api/v1/records');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Auth-API-Token: ' . $_SERVER['HETZNER_API_TOKEN'],
    ]);
    $json_array = [
        'value' => $value,
        'ttl' => $ttl,
        'type' => $type,
        'name' => $name,
        'zone_id' => $zone_id
    ];
    $body = json_encode($json_array);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    $response = curl_exec($ch);
    if (!$response) {
        die('Error: "' . curl_error($ch) . '" - Code: ' . curl_errno($ch));
    }
    curl_close($ch);
}

function deleteRecord($domain, $zoneId, $tokenValue)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://dns.hetzner.com/api/v1/records?zone_id=' . $zoneId);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Auth-API-Token: ' . $_SERVER['HETZNER_API_TOKEN'],
    ]);
    $response = curl_exec($ch);
    if (!$response) {
        die('Error: "' . curl_error($ch) . '" - Code: ' . curl_errno($ch));
    }
    $data = json_decode($response);
    curl_close($ch);
    foreach ($data->records as $record) {
        if ($record->type !== 'TXT') continue;
        if ($record->value !== $tokenValue) continue;
        if (0 !== strpos($record->name, '_acme-challenge')) continue;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://dns.hetzner.com/api/v1/records/' . $record->id);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Auth-API-Token: ' . $_SERVER['HETZNER_API_TOKEN'],
        ]);
        $response = curl_exec($ch);
        if (!$response) {
            die('Error: "' . curl_error($ch) . '" - Code: ' . curl_errno($ch));
        }
        curl_close($ch);
    }
}



