<?php
require_once('classes/ClientService.php');
require_once('classes/GenerateSoapClient.php');
require_once('classes/IpmamWsClientFactory.php');

/**
 *  Return an ipmamClientFactory object
 */
function initIpmamInterface()
{
    if (!isset($ipmam)) {
        $ipmam = new IpmamWsClientFactory();
    }
    
    return $ipmam;
}