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
    $zoneId = getZoneId($domain,$d);
    if(!$zoneId){
        echo "No zone found for $domain\n";
        exit(1);
    }
    echo " +-+ creating TXT record for $domain";

    createRecord("_acme-challenge." . substr($domain, 0, -1 - strlen($d)),  'TXT', $tokenValue, $zoneId);
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
    echo " | + Waiting additional 3 sec";
    for($i=0; $i<3; $i++){
        sleep(1);
        echo ".";
    }
    echo "\n";

}

function clean_challenge($domain, $tokenFile, $tokenValue)
{
    $zoneId = getZoneId($domain);
    deleteRecord("_acme-challenge", $zoneId, $tokenValue);

}

function getZoneId($domain,&$targetDomain=null)
{
    $zones=hcloud("zone","list","--output","json");
    $zones=json_decode(implode("\n",$zones));
    usort($zones,function($a,$b){
        return strlen($b->name)-strlen($a->name);
    });
    foreach($zones as $zone){
        if(str_ends_with($domain,$zone->name)){
            $targetDomain=$zone->name;
            return $zone->id;
        }
    }
    return null;
}

function createRecord($name, $type, $value, $zone_id)
{
    if ($name === "_acme-challenge.") $name = "_acme-challenge";
    hcloud("zone","set-records","--record",'"'.$value.'"',$zone_id,$name,$type);
}

function deleteRecord($domain, $zoneId, $tokenValue)
{
    hcloud("zone","remove-records","--record",'"'.$tokenValue.'"',$zoneId,$domain,'TXT');
}


function hcloud(...$args){
    array_unshift($args,'hcloud');
    $args=array_map('escapeshellarg',$args);
    $cmd=implode(' ',$args);
    exec($cmd,$out,$ret);
    if($ret!==0){
        return false;
    }
    return $out;

}


