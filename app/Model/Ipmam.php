<?php
/**
 *
 * Ipmam Model
 *
 */
class Ipmam extends AppModel
{
    /**
     * Constructor
     */
    function __construct(){
        // call parent construct
        parent::__construct();

        // Load ipmam soap interface lib
        App::import('Vendor', 'Ipmam SOAP interface', array('file' => 'ipmam/IpmamSoapInterface.php'));

        // Initialize the ipmam interface
        $this->_ipmam = initIpmamInterface();

        // Create clients
        $this->_ipmam->client('um', 'UserManagementWS\UM');
        $this->_ipmam->client('dmSearch', 'DataManagerWS\DMSearch');
        $this->_ipmam->client('dmEssencesPackages', 'DataManagerWS\DMEssencePackages');
        $this->_ipmam->client('essenceManager', 'EssenceManagerWS\EssenceManager');
        $this->_ipmam->client('dmObjectAccess', 'DataManagerWS\DMObjectAccess');

        // login to Ipmam; no args logs in as admin
        $this->login();
    }

    /**
     * Destructor
     */
    function __destruct()
    {
        // Logout when ipmam object is destroyed
        $this->logout();
    }

    /**
     * login user
     */
    function login($user = 'admin', $pass = 'nimda') {

        // Get a login input object
        $inputObj = $this->_ipmam->f('Login', array($user, $pass));

        // Get accessKey
        $this->_ipmam->vars['accessKey'] = $this->_ipmam->client('um')->Login($inputObj)->LoginResult;

        return $this->_ipmam->vars['accessKey'];
    }

    /**
     * logout user
     */
    function logout() {

        // If accesskey is set logout using that key
        if (isset($this->_ipmam->vars['accessKey'])) {

            // Get input object
            $inputObj = $this->_ipmam->f('Logout',array($this->_ipmam->vars['accessKey']));

            // Call function
            $this->_ipmam->client('um')->Logout($inputObj);

            // unset the variable
            unset($this->_ipmam->vars['accessKey']);
        }
    }

    /**
     * return Ipmam AXF queryDoc
     */
    function getQueryDoc($search) {
        // Init as array
        $axfArray = array();

        // Store the AXF open tag in the array
        $axfArray[] = "<AXFRoot>";

        // Get the dmQuery template
        $dmQuery = $this->_getDmqueryTemplate();

        // Replace each meta tag value with the named input value
        foreach ($search as $key=>$val) {

            // If the ATTRIBUTESEARCH key is reached - create maObjects for the items under ATTRIBUTESEARCH
            if ($key == 'ATTRIBUTESEARCH') {

                // Get the attributeSearch template
                $attributeSearch = $this->_getAttributeSearchTemplate();

                // Replace each meta tag value with the named input value
                foreach ($val as $key=>$value) {
                    $attributeSearch = preg_replace("/>$key</",">$value<",$attributeSearch);
                }

                // Cat each maObject
                $dmQuery .= $attributeSearch;
            } else {
                // Replace each meta tag value with the named input value
                $dmQuery = preg_replace("/>$key</",">$val<",$dmQuery);
            }
        }

        // Add the maObject to the array
        $axfArray[] = $dmQuery;

        // Close the AXF tag
        $axfArray[] = "</AXFRoot>";

        // Join the AXF elements and return the AXF doc
        return implode("\n",$axfArray);
    }

    /**
     * return the dmQuery Template
     */
    function _getDmqueryTemplate() {
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
     * return the attributeSearch Template
     */
    function _getAttributeSearchTemplate() {
        return "    <MAObject type='default' mdclass='AttributeSearch'>
        <GUID />
        <Ref mdclass='DMQuery' name='QUERY'>theID</Ref>
        <Meta name='ATTRIBUTE' format='string'>ATTRIBUTE</Meta>
        <Meta name='SEARCHSTRING' format='string'>SEARCHSTRING</Meta>
        <Meta name='GROUP' format='string'>1</Meta>
    </MAObject>";
    }

    /**
     * Return the AXF hitList
     */
    function getHitListDoc($fields) {
        // Init as array
        $axfArray = array();

        // Add the AXF open tag to the array
        $axfArray[] = "<AXFRoot>";

        // Init value to 0; this is a unique id for the hitlist attribute objects
        $guidId = 0;

        // Create a hitList attribute object for each field and add them to an array
        foreach ($fields as $field) {
            $hitlistDoc[] = "    <MAObject type='default' mdclass='ModelHitlistAttribute'>
        <GUID dmname=''>$guidId</GUID>
        <Ref mdclass='ModelHitlist' name='HITLIST'>theHitlist</Ref>
        <Meta name='NAME' format='string' frate=''>$field</Meta>
    </MAObject>";
            $guidId++;
        }

        // Add the required hitList object to the array
        $hitlistDoc[] = "    <MAObject type='default' mdclass='ModelHitlist'>
        <GUID dmname=''>theHitlist</GUID>
        <Meta name='MODIFYABLE' format='string' frate=''>1</Meta>
        <Meta name='USAGE' format='string' frate=''>RETRIEVAL</Meta>
        <Meta name='NAME' format='string' frate=''>theHitlist</Meta>
    </MAObject>";

        // Join the hitlist elements and add to the array
        $axfArray[] = implode("\n",$hitlistDoc);

        // add the close AXF tag
        $axfArray[] = "</AXFRoot>";

        // Join and return the hitlist elements
        return implode("\n",$axfArray);
    }

    /**
     * Execute an Ipmam search and return the results
     */
    function search($queryDoc, $hitlist) {

        // Get an input object
        $inputObj = $this->_ipmam->f(
            'SearchExt2',
            array (
                $this->_ipmam->vars['accessKey'],
                $queryDoc,
                $hitlist,
                'en'
            )
        );

        $dmSearchResponse = $this->_ipmam->client('dmSearch')->SearchExt2($inputObj);

        return $dmSearchResponse->SearchExt2Result;
    }

    /**
     * Returns an array of dmguids
     */
    function getGuids($searchResponseXml) {
        // Init as array
        $guids = array();

        // Create a new SimpleXmlElement to help parse the XML
        $xml = new SimpleXMLElement($searchResponseXml);

        // Search for the GUIDs in the VIDEO class
        $guidXmls = $xml->xpath('/AXFRoot/MAObject[@mdclass="VIDEO"]/GUID');

        // If guids were found store the GUIDs in an array
        if ($guidXmls) {
            foreach ($guidXmls as $node) {
                // Store the string version of the SimpleXmlElement
                $guids[] = strval($node);
            }
        }

        // Natural Sort the GUIDs
        natcasesort($guids);

        return $guids;
    }

    /**
     * Return the Representative essence package guid
     */
    function getRepEpGuid($dmguid) {

        $inputObject = $this->_ipmam->f(
            'GetRepresentativeEssencePackage',
            array (
                $this->_ipmam->vars['accessKey'],
                $dmguid,
            )
        );

        $ep = $this->_ipmam->client('dmEssencesPackages')->GetRepresentativeEssencePackage($inputObject);

        return $ep->GetRepresentativeEssencePackageResult->EPGuid;
    }

    /**
     * Return all EP guids
     */
    function getAllEpGuids($dmguid) {

        // Get input Object
        $inputObject = $this->_ipmam->f(
            'ListAllEssencePackages',
            array (
                $this->_ipmam->vars['accessKey'],
                $dmguid,
            )
        );

        // Get all essences
        $ep = $this->_ipmam->client('dmEssencesPackages')->ListAllEssencePackages($inputObject);

        $eps = $ep->ListAllEssencePackagesResult->EssencePackage;

        // If an array is not return create an array
        if (!is_array($eps)){
            $eps = array($eps);
        }

        // Init as array
        $epguids = array();

        // Get the EPGUIDS and label them as RAW or MAIN
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
     * Get all EM objects
     */
    function getAllEmObjects($epguid) {

        // Get input object
        $inputObject = $this->_ipmam->f(
            'GetEMObject2InfosInEP',
            array (
                $this->_ipmam->vars['accessKey'],
                $epguid,
            )
        );

        // Get EM objects
        $em = $this->_ipmam->client('essenceManager')->GetEMObject2InfosInEP($inputObject);

        $emObjs = $em->GetEMObject2InfosInEPResult->EMObject2;

        if (!is_array($emObjs)){
            $emObjs = array($emObjs);
        }
        //print_r($emObjs);

        return $emObjs;
    }

    /**
     * Get all EM objects with location
     */
    function getAllEssencesWithLocation($epguid) {

        // Get all of the epguids
        $emObjs = $this->getAllEmObjects($epguid);

        $emObjFilesizeTotal = 0;

        // Get the emguid for each emObj
        foreach ($emObjs as $emObj) {
            $emguids[] = $emObj->emguid;

            // Get input object
            $inputObject = $this->_ipmam->f(
                'GetEMObjectWithLocations',
                array (
                    $this->_ipmam->vars['accessKey'],
                    $emObj->emguid
                )
            );

            // Get emObjects with location
            $response = $this->_ipmam->client('essenceManager')->GetEMObjectWithLocations($inputObject);

            $emObjFilesize = $response->GetEMObjectWithLocationsResult->locations->EMLocation->filesize;
            $emObjFilesizeTotal += $emObjFilesize;

            $data['emguids'][$emObj->emguid]['filesize'] = $emObjFilesize;
            $data['emguids'][$emObj->emguid]['streamtype'] = $response->GetEMObjectWithLocationsResult->emobj->streamtype;


            // Get input object
            $inputObject = $this->_ipmam->f(
                'GetAccessPath',
                array (
                    $this->_ipmam->vars['accessKey'],
                    $emObj->emguid,
                    'UNC',
                    'BOTH'
                )
            );
            $response = $this->_ipmam->client('essenceManager')->GetAccessPath($inputObject);

            $data['emguids'][$emObj->emguid]['location'] = $response->GetAccessPathResult;

        };

        $data['emobjFilesize'] = $emObjFilesizeTotal;

        // Get input object
        $inputObject = $this->_ipmam->f(
            'GetAccessPathForEMGuids',
            array (
                $this->_ipmam->vars['accessKey'],
                 $emguids,
                'UNC'
            )
        );

        // Get access paths
        //$response = $this->_ipmam->client('essenceManager')->GetAccessPathForEMGuids($inputObject);

        //$locations = $response->GetAccessPathForEMGuidsResult->string;
/* print_r($data);
print_r($locations);
exit; */
        return $data;
    }

    /**
     *
     */
    function getDmguidFromSearchResults($xmlIn) {
        $prodTitle = '';
        $guids = array();
        //print $xmlIn;
        //$xml = new SimpleXMLElement($xmlIn);
        $xml = new SimpleXmlIterator($xmlIn);

        $data = $xml->xpath('/AXFRoot/MAObject[@mdclass="VIDEO"]/GUID');

        foreach ($data as $guid){
            $guids[] = strval($guid);
        }

        return $guids;
    }

    /**
     *
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
     * Return the production title
     */
    function getProductionTitle($data) {
        $keys = array_keys($data['DMGUIDS']);

        return $data['DMGUIDS'][$keys[0]]['PRODUCTION_TITLE'];
    }

    /**
     * @param unknown_type $data
     */
    function barcodeCount(&$data) {
        $data['barcodeCount']['content'] = '00:00:00:00';
        $grandTotal = 0;

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

            list($emGuids, $dmFilesize) = $this->getFilelocationsWithSize($key);
            //continue;
            $bcc{$dmguid['BARCODE']}['locations'] = $emGuids;
            $bcc{$dmguid['BARCODE']}['filesize'] = $dmFilesize;

            $grandTotal += $dmFilesize;

            $data['barcodeCount']['content'] = $this->sumTrt($data['barcodeCount']['content'], $dmguid['TAPE_RUNNING_TIME']);

        }

        $data['barcodeCount']['barcodes'] = $bcc;
        $data['barcodeCount']['count'] = count($data['barcodeCount']['barcodes']);
        $data['barcodeCount']['clips'] = count($data['DMGUIDS']);
        $data['barcodeCount']['filesize'] = $grandTotal;
    }

    /**
     * Get CLIP_TAPE_DESCRIPTION using dmObjectAccess so that the data is not truncated
     */
    function getClipTapeDescrption($dmguid) {

        // A video source object is needed so build the guid if it is not supplied
        if (preg_match('/^V_(.+)_.+$/',$dmguid,$matches)) {
            $vsObj = "VS_" . $matches[1];
        } elseif (preg_match('/^V?/',$dmguid)) {
            $vsObj = "VS_" . $dmguig;
        } else {
            $vsObj = $dmguig;
        }

        // Get input object
        $inputObject = $this->_ipmam->f(
            'GetDMAttribute',
            array (
                $vsObj,
                'CLIP_TAPE_DESCRIPTION',
                $this->_ipmam->vars['accessKey'],
            )
        );

        // Get CLIP_TAPE_DESCRIPTION
        $response = $this->_ipmam->client('dmObjectAccess')->GetDMAttribute($inputObject);

        return $response->GetDMAttributeResult;
    }

    /**
     * sum the TRT
     */
    function sumTrt(&$trt, $tapeRunningTime) {
        $sumSec = $this->tcToSec($trt);
        $bcSec = $this->tcToSec($tapeRunningTime);
        $trt = $this->secToTc($sumSec + $bcSec);

        return $trt;
    }

    /**
     * Convert seconds to timecode
     */
    function secToTc($secs, $framerate = 29.97) {

        // get the fractional part of the seconds
        $parSecs = fmod($secs, 1);

        // Get the whole seconds
        $hourMinSecs = $secs - $parSecs;

        // Calculate the number of frames
        $frames = floor($parSecs * $framerate);

        // Calculate the number of hours
        $hours = floor($hourMinSecs / 3600);

        // Calculate the remaining seconds
        $minSecs = $hourMinSecs - ($hours * 3660);

        // Calculate the number of minutes
        $mins =  floor($minSecs / 60);

        // Calculate the remaining seconds
        $secs = $minSecs - ($mins * 60);

        // Build the timecode string
        $tc = sprintf("%02s:%02s:%02s:%02s",$hours,$mins,$secs,$frames);

        return $tc;
    }

    /**
     * Convert timecode to seconds
     */
    function tcToSec($input, $framerate = 29.97) {

        // remove any extra spaces around the input
        $input = trim($input);

        // list of possible delimiters
        $punct= array(":", ";", ".", ",");

        // Replace all delimiters with :
        $input = str_replace( $punct, ":", $input);

        // Break TC into segments
        $vals = explode(':', $input);

        // array to hold tc segments
        $tc = array(
            'HOURS'=>0,
            'MINS'=>0,
            'SECS'=>0,
            'FRAMES'=>0
        );

        // stack segments into array
        foreach ($tc as $key=>$val) {
            $tc[$key] = array_shift($vals);
        }

        // Multiply each segment by it's place value then divide by the framerate to get the seconds value
        $secs = ($tc['HOURS']*3600) + ($tc['MINS']*60) + $tc['SECS'] + ($tc['FRAMES'] / $framerate);

        return $secs;
    }

    /**
     * Get the files for the gven barcode
     */
    function getFilelocationsWithSize($dmguid) {
        //echo $dmguid;
        $epGuids = $this->getAllEpGuids($dmguid);

        $dmguidFilesize = 0;

        foreach ($epGuids as $epGuid) {
            $emguid = $this->getAllEssencesWithLocation($epGuid);
            $dmguidFilesize += $emguid['emobjFilesize'];
            $emguids[] = $emguid;
        }

        //print_r($emguids);
        //exit;

        return array($emguids, $dmguidFilesize);
    }

    function getObjectAccessData($dmguids, $metaTags){

        $axfDoc = $this->getAxfDoc($dmguids, $metaTags);
        //echo $axfDoc;

        $includeAttributes = 1;
        $includeStrata = 0;
        $includeAssociations = 0;
        $includeEssencePackages = 1;

        // Get input object
        $inputObject = $this->_ipmam->f(
            'GetDMObjectEx',
            array (
                $axfDoc,
                $this->_ipmam->vars['accessKey'],
                $includeAttributes,
                $includeStrata,
                $includeAssociations,
                $includeEssencePackages
            )
        );

        $response = $this->_ipmam->client('dmObjectAccess')->GetDMObjectEx($inputObject);
        print_r($response);
    }

    function getAxfDoc($dmguids, $metas) {
        $metaTagArray = array();
        $maObjects = array();

        foreach ($metas as $meta) {
            $metaTagArray[] = $this->_getAxfDocMaObjectMeta($meta);
        }

        $metaTags = implode("\n",$metaTagArray);

        foreach ($dmguids as $dmguid) {
            $maObjects[] = $this->_getAxfDocMaObject($dmguid, $metaTags);
        }

        $axfDoc = "<AXFRoot>\n";
        $axfDoc .= implode("\n",$maObjects);
        $axfDoc .= "\n</AXFRoot>";

        return $axfDoc;

    }

    /**
     * return the attributeSearch Template
     */
    function _getAxfDocMaObject($dmguid, $metaTags) {
        return "    <MAObject>
        <GUID>$dmguid</GUID>
        $metaTags
    </MAObject>";
    }

    /**
     * return the attributeSearch Template
     */
    function _getAxfDocMaObjectMeta($meta) {
        return "<Meta name=\"$meta\"></Meta>";
    }
}










