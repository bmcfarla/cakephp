<?php
/**
 *
 * @author bmcfarla
 *
 */
class Ipmam extends AppModel
{
    /**
     * Constructor
     */
    function __construct(){
        parent::__construct();

        App::import('Vendor', 'Ipmam SOAP interface', array('file' => 'ipmam/IpmamSoapInterface.php'));
        $this->_ipmam = initIpmamInterface();

        // Create clients
        $this->_ipmam->client('um', 'UserManagementWS\UM');
        $this->_ipmam->client('dmSearch', 'DataManagerWS\DMSearch');
        $this->_ipmam->client('dmEssencesPackages', 'DataManagerWS\DMEssencePackages');
        $this->_ipmam->client('essenceManager', 'EssenceManagerWS\EssenceManager');
        $this->_ipmam->client('dmObjectAccess', 'DataManagerWS\DMObjectAccess');

    }

    /**
     * Destructor
     */
    function __destruct()
    {
       $this->logout();
    }

    /**
     * login user
     */
    function login($user = 'admin', $pass = 'nimda') {

        // Get accesskey object
        $login = $this->_ipmam->f('Login', array($user, $pass));

        $this->_ipmam->vars['accessKey'] = $this->_ipmam->client('um')->Login($login)->LoginResult;

        //print_r($this->_ipmam->vars['accessKey']);
        return $this->_ipmam->vars['accessKey'];
    }

    /**
     * logout user
     */
    function logout() {
        if (isset($this->_ipmam->vars['accessKey'])) {
            $logout = $this->_ipmam->f('Logout',array($this->_ipmam->vars['accessKey']));
            $this->_ipmam->client('um')->Logout($logout);
            unset($this->_ipmam->vars['accessKey']);
        }
    }
/** getQuery **********************************************************/
    /**
     * return ipmam query string
     */
    function getQueryDoc($search) {
        $axfArray = array();
        $axfArray[] = "<AXFRoot>";

        $dmQuery = $this->_getDmquery();

        foreach ($search as $key=>$val) {
            //echo "KEY: $key<br>";
            if ($key == 'ATTRIBUTESEARCH') {
                //echo "KEY: $key<br>";

                $attributeSearch = $this->_getAttributeSearch();

                foreach ($val as $key=>$value) {
                    //echo "KEY: $key<br>";
                    $attributeSearch = preg_replace("/>$key</",">$value<",$attributeSearch);
                }
                $dmQuery .= $attributeSearch;
            } else {
                $dmQuery = preg_replace("/>$key</",">$val<",$dmQuery);
            }
        }

        $axfArray[] = $dmQuery;
        $axfArray[] = "</AXFRoot>";

        return implode("\n",$axfArray);
    }

    /**
     *
     * @return string
     */
    function _getDmquery() {
        return "    <MAObject type='default' mdclass='DMQuery'>
        <GUID>theID</GUID>
        <Meta name='OBJECTCLASSES' format='string'>OBJECTCLASSES</Meta>
        <Meta name='SIMPLESEARCH' format='string'>SIMPLESEARCH</Meta>
        <Meta name='FIRSTHIT' format='string'>FIRSTHIT</Meta>
        <Meta name='MAXHITS' format='string'>MAXHITS</Meta>
        <Meta name='HITLISTID' format='string'>01 - Default</Meta>
    </MAObject>";
    }

    /**
     *
     * @return string
     */
    function _getAttributeSearch() {
        return "    <MAObject type='default' mdclass='AttributeSearch'>
        <GUID />
        <Ref mdclass='DMQuery' name='QUERY'>theID</Ref>
        <Meta name='ATTRIBUTE' format='string'>ATTRIBUTE</Meta>
        <Meta name='SEARCHSTRING' format='string'>SEARCHSTRING</Meta>
        <Meta name='GROUP' format='string'>1</Meta>
    </MAObject>";
    }

    /**
     *
     * @param unknown_type $fields
     * @return string
     */
    function getHitListDoc($fields) {
        $axfArray = array();
        $axfArray[] = "<AXFRoot>";

        $guidId = 0;

        foreach ($fields as $field) {
            $hitlistDoc[] = "    <MAObject type='default' mdclass='ModelHitlistAttribute'>
        <GUID dmname=''>$guidId</GUID>
        <Ref mdclass='ModelHitlist' name='HITLIST'>theHitlist</Ref>
        <Meta name='NAME' format='string' frate=''>$field</Meta>
    </MAObject>";
            $guidId++;
        }

        $hitlistDoc[] = "    <MAObject type='default' mdclass='ModelHitlist'>
        <GUID dmname=''>theHitlist</GUID>
        <Meta name='MODIFYABLE' format='string' frate=''>1</Meta>
        <Meta name='USAGE' format='string' frate=''>RETRIEVAL</Meta>
        <Meta name='NAME' format='string' frate=''>theHitlist</Meta>
    </MAObject>";
        $axfArray[] = implode("\n",$hitlistDoc);
        $axfArray[] = "</AXFRoot>";

        return implode("\n",$axfArray);
    }

/************************************************************************/

    /**
     *
     */
    function search($queryDoc, $hitlist) {

        $searchExt2 = $this->_ipmam->f(
            'SearchExt2',
            array (
                $this->_ipmam->vars['accessKey'],
                $queryDoc,
                $hitlist,
                'en'
            )
        );

        $dmSearchResponse = $this->_ipmam->client('dmSearch')->SearchExt2($searchExt2);

        return $dmSearchResponse->SearchExt2Result;
    }

    /**
     * Returns an array of dmguids
     */
    function getGuids($searchResponseXml) {
        $guids = array();
        $xml = new SimpleXMLElement($searchResponseXml);

        $guidXmls = $xml->xpath('/AXFRoot/MAObject[@mdclass="VIDEO"]/GUID');

        if ($guidXmls) {
            foreach ($guidXmls as $node) {
                $guids[] = sprintf('%s',$node);
            }
        }
        natcasesort($guids);
        return $guids;
    }

    /**
     *
     */
    function getEpGuid($dmguid, $type) {

        $inputObject = $this->_ipmam->f(
            'GetRepresentativeEssencePackage',
            array (
                $this->_ipmam->vars['accessKey'],
                $dmguid,
            )
        );

        //print_r($inputObject);
        $ep = $this->_ipmam->client('dmEssencesPackages')->GetRepresentativeEssencePackage($inputObject);

        return $ep->GetRepresentativeEssencePackageResult->EPGuid;
    }

    /**
     *
     */
    function getAllEpGuids($dmguid) {

        $inputObject = $this->_ipmam->f(
            'ListAllEssencePackages',
            array (
                $this->_ipmam->vars['accessKey'],
                $dmguid,
            )
        );

        //print_r($inputObject);
        $ep = $this->_ipmam->client('dmEssencesPackages')->ListAllEssencePackages($inputObject);

        $eps = $ep->ListAllEssencePackagesResult->EssencePackage;

        if (!is_array($eps)){
            $eps = array($eps);
        }
        $epguids = array();

        foreach ($eps as $ep) {
            if ($ep->Title == 'RAW') {
                $epguids['EPGUID-RAW'] = $ep->EPGuid;
            } elseif ($ep->Title == 'Main') {
                $epguids['EPGUID-MAIN'] = $ep->EPGuid;
            }
        }

        return $epguids;
    }

    /**
     *
     */
    function getAllEssences($epguid) {

        $inputObject = $this->_ipmam->f(
            'GetEMObject2InfosInEP',
            array (
                $this->_ipmam->vars['accessKey'],
                $epguid,
            )
        );

        //print_r($inputObject);
        $em = $this->_ipmam->client('essenceManager')->GetEMObject2InfosInEP($inputObject);
//print_r($em);
        $esssenses = $em->GetEMObject2InfosInEPResult->EMObject2;

        if (!is_array($esssenses)){
            $esssenses = array($esssenses);
        }

        return $esssenses;
    }

    /**
     *
     */
    function getAllEssencesWithLocation($epguid) {
        $essences = $this->getAllEssences($epguid);

        ///$emguidlist = $this->_ipmam->f(
        //        'emguidlist',
        //        $essences
       // );
/*
        $a = $this->_ipmam->f(
            'ArrayOfString',
            array (
                '77e171ed-775f-415c-9071-24bcfe340497',
                '0fd9475e-9f37-4c50-85d0-0b5952853add'
           )
        );
*/

        //print_r($a);
        //exit;

        foreach ($essences as $essence) {
            $emguids[] = $essence->emguid;
        };


        $inputObject = $this->_ipmam->f(
            'GetAccessPathForEMGuids',
            array (
                $this->_ipmam->vars['accessKey'],
                 $emguids,
                'UNC'
            )
        );

        $reponse = $this->_ipmam->client('essenceManager')->GetAccessPathForEMGuids($inputObject);
        $pathes = $reponse->GetAccessPathForEMGuidsResult->string;
        print_r($pathes);


        exit;
        //return $reponse;
    }

    /**
     *
     * @param unknown_type $xmlIn
     * @return multitype:string
     */
    function parseXml($xmlIn) {
        $prodTitle = '';

        //print $xmlIn;
        //$xml = new SimpleXMLElement($xmlIn);
        $xml = new SimpleXmlIterator($xmlIn);

        $data = $xml->xpath('/AXFRoot/MAObject[@mdclass="VIDEO"]');

        $guidDetails = array();

        foreach ($data as $sxi) {
            $guid = strval($sxi->{'GUID'});

            for( $sxi->rewind(); $sxi->valid(); $sxi->next() ) {
                //print_r($sxi->current());
                $a = $sxi->current();

                if (isset($a['name'])) {
                    $b = $a['name'];

                    $guidDetails[strval($a['name'])] = strval($sxi->current());
                }
            }
            $guids[$guid] = $guidDetails;
        }

        return $guids;
    }

    /**
     *
     * @param unknown_type $sxi
     * @return Ambigous <multitype:multitype: , string>
     */
    function sxiToArray($sxi){
        $a = array();
        for( $sxi->rewind(); $sxi->valid(); $sxi->next() ) {
            if(!array_key_exists($sxi->key(), $a)){
                $a[$sxi->key()] = array();
            }
            if($sxi->hasChildren()){
                $a[$sxi->key()][] = sxiToArray($sxi->current());
            }
            else{
                $a[$sxi->key()][] = strval($sxi->current());
            }
        }
        return $a;
    }

    /**
     *
     * @param unknown_type $data
     */
    function getProductionTitle($data) {
        $keys = array_keys($data['DMGUIDS']);

        return $data['DMGUIDS'][$keys[0]]['PRODUCTION_TITLE'];
    }

    /**
     *
     * @param unknown_type $data
     */
    function barcodeCount(&$data) {
        $data['barcodeCount']['content'] = '00:00:00:00';

        foreach($data['DMGUIDS'] as $key=>$dmguid) {


            if (!isset($bcc{$dmguid['BARCODE']})) {
                $bcc{$dmguid['BARCODE']}['clips'] = 0;
                $bcc{$dmguid['BARCODE']}['duration'] = '00:00:00:00';
            }

            //$bcc{$dmguid['BARCODE']}['count']++;
            $bcc{$dmguid['BARCODE']}['description'] = $this->getClipTapeDescrption($key);
            $bcc{$dmguid['BARCODE']}['type'] = $dmguid['ASSET_TAPE_FORMAT'];
            $bcc{$dmguid['BARCODE']}['clips']++;

            $duration = $bcc{$dmguid['BARCODE']}['duration'];

            $bcc{$dmguid['BARCODE']}['duration'] = $this->sumTrt($duration, $dmguid['TAPE_RUNNING_TIME']);
            $bcc{$dmguid['BARCODE']}['size'] = 'size';
            $data['barcodeCount']['content'] = $this->sumTrt($data['barcodeCount']['content'], $dmguid['TAPE_RUNNING_TIME']);
        }

        $data['barcodeCount']['barcodes'] = $bcc;
        $data['barcodeCount']['count'] = count($data['barcodeCount']['barcodes']);
        $data['barcodeCount']['clips'] = count($data['DMGUIDS']);
    }

    /**
     *
     * @param unknown_type $dmguid
     */
    function getClipTapeDescrption($dmguid) {
        if (preg_match('/^V_(.+)_.+$/',$dmguid,$matches)) {
            $vsObj = "VS_" . $matches[1];
        } elseif (preg_match('/^V?/',$dmguid)) {
            $vsObj = "VS_" . $dmguig;
        } else {
            $vsObj = $dmguig;
        }

        $inputObject = $this->_ipmam->f(
            'GetDMAttribute',
            array (
                $vsObj,
                'CLIP_TAPE_DESCRIPTION',
                $this->_ipmam->vars['accessKey'],
            )
        );

        $response = $this->_ipmam->client('dmObjectAccess')->GetDMAttribute($inputObject);

        return $response->GetDMAttributeResult;
    }

    /**
     *
     * @param unknown_type $trt
     * @param unknown_type $tapeRunningTime
     * @return unknown
     */
    function sumTrt(&$trt, $tapeRunningTime) {
        $sumSec = $this->tcToSec($trt);
        $bcSec = $this->tcToSec($tapeRunningTime);
        $trt = $this->secToTc($sumSec + $bcSec);

        return $trt;
    }

    /**
     *
     * @param unknown_type $secs
     * @param unknown_type $framerate
     * @return string
     */
    function secToTc($secs, $framerate = 29.97) {

        $parSecs = fmod($secs, 1);


        $hourMinSecs = $secs - $parSecs;

        $frames = floor($parSecs * $framerate);

        $hours = floor($hourMinSecs / 3600);
        $minSecs = $hourMinSecs - ($hours * 3660);

        $mins =  floor($minSecs / 60);
        $secs = $minSecs - ($mins * 60);

        $tc = sprintf("%02s:%02s:%02s:%02s",$hours,$mins,$secs,$frames);

        return $tc;
    }

    /**
     *
     * @param unknown_type $input
     * @param unknown_type $framerate
     * @return number
     */
    function tcToSec($input, $framerate = 29.97) {

        $input = trim($input);

        $punct= array(":", ";", ".", ",");
        $input = str_replace( $punct, ":", $input);

        $vals = explode(':', $input);

        $tc = array(
                'HOURS'=>0,
                'MINS'=>0,
                'SECS'=>0,
                'FRAMES'=>0
            );

        foreach ($tc as $key=>$val) {
            $tc[$key] = array_shift($vals);
        }

        $secs = ($tc['HOURS']*3600) + ($tc['MINS']*60) + $tc['SECS'] + ($tc['FRAMES'] / $framerate);

        return $secs;
    }

    /**
     *
     * @param unknown_type $barcode
     * @return unknown
     */
    function getFileDetails($barcode) {
        echo $barcode;
        $epGuids = $this->getAllEpGuids($barcode);

        foreach ($epGuids as $epGuid) {
            $ep{$epGuid} = $this->getAllEssencesWithLocation($epGuid);
        }
        return $ep;
    }
}










