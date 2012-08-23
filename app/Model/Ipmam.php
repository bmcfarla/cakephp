<?php

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

    function _getAttributeSearch() {
        return "    <MAObject type='default' mdclass='AttributeSearch'>
        <GUID />
        <Ref mdclass='DMQuery' name='QUERY'>theID</Ref>
        <Meta name='ATTRIBUTE' format='string'>ATTRIBUTE</Meta>
        <Meta name='SEARCHSTRING' format='string'>SEARCHSTRING</Meta>
        <Meta name='GROUP' format='string'>1</Meta>
    </MAObject>";
    }


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
     * Returns an array of dmguids
     */
    function getProductionTitle($searchResponseXml) {
        $productionTitles = array();
        $titles = array();

        $xml = new SimpleXMLElement($searchResponseXml);

        $productionTitles = $xml->xpath('/AXFRoot/MAObject[@mdclass="VIDEO"]/Meta[@name="PRODUCTION_TITLE"]');

        if ($productionTitles) {
            foreach ($productionTitles as $node) {
                $titles[] = sprintf('%s',$node);
            }
        }

        return $titles[0];
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

        $esssenses = $em->GetEMObject2InfosInEPResult->EMObject2;

        if (!is_array($esssenses)){
            $esssenses = array($esssenses);
        }

        return $esssenses;
    }

    function parseXml($xmlIn) {
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

}










