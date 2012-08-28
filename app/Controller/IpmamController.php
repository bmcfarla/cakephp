<?php
/**
 *
 * @author bmcfarla
 *
 */
class IpmamController extends AppController {
    /**
     *
     */
    function Index() {

        $vsObjects = array();

        $accessKey = $this->Ipmam->login();

        $this->set('accessKey', $accessKey);

        $production = 'x65600';
        $dmguids = $this->getDmguidsByProduction($production);
        $this->set('guids', $dmguids);

    }

    /**
     *
     *
     */
    function getDmguidsByProduction($prodNum, $maxHits) {
        // Define the settings for the search queryDoc
        $search = array(
            'OBJECTCLASSES' => 'VIDEO',
            'FIRSTHIT' => '0',
            'MAXHITS' => $maxHits,
            'SIMPLESEARCH' => '',
            'ATTRIBUTESEARCH' => array(
                'ATTRIBUTE' => 'PRODUCTION_NUMBER',
                'SEARCHSTRING' => $prodNum
            )
        );

        // Get the AXF queryDoc from the ipmam model
        $queryDoc = $this->Ipmam->getQueryDoc($search);

        // Define data types to include in the hitList
        $itemsToRetrive = array(
            'BARCODE',
            'CLIP_ID',
            'PRODUCTION_NUMBER',
            'PRODUCTION_TITLE',
            'ASSET_TAPE_FORMAT',
            'CONTENT_TYPE',
            'START_TC',
            'END_TC',
            'TAPE_RUNNING_TIME'
        );

        // Get the AXF hitList from the ipmam model
        $hitlist = $this->Ipmam->getHitListDoc($itemsToRetrive);

        // Execute the search
        $searchResponseXml = $this->Ipmam->search($queryDoc, $hitlist);

        // Parse the hitList XML and build an array of DMGUIDS
        $data['DMGUIDS'] = $this->Ipmam->parseXml($searchResponseXml);

        return $data;
    }


    /**
     *
     * Get the details for the given production
     */
    function getProductionData() {
        $prodNum = $this->params['named']['prod'];
        $maxHits = $this->params['named']['maxhits'];

        // Set the max run time for the web session
        set_time_limit(360);



        // Get the top level search results for this production
        $data = $this->getDmguidsByProduction($prodNum, $maxHits);

        // Get the production title
        $data['prodTitle'] = $this->Ipmam->getProductionTitle($data);

        // Get the count of the barcodes returned
        $this->Ipmam->barcodeCount($data);

        //print_r($data);
        return $data;
    }

    /**
     *
     * @param unknown_type $barcode
     */
    function getFiles($barcode) {
        $this->Ipmam->login();
        $data = $this->Ipmam->getFileDetails($barcode);

        $this->set('data', $data);
    }


}