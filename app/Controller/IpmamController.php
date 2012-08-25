<?php

class IpmamController extends AppController {

    function Index() {

        $vsObjects = array();

        $accessKey = $this->Ipmam->login();

        $this->set('accessKey', $accessKey);

        $production = 'x65600';
        $dmguids = $this->getDmguidsByProduction($production);
        $this->set('guids', $dmguids);
/*
        $search = array('OBJECTCLASSES' => 'VIDEO',
                        'FIRSTHIT' => '0',
                        'MAXHITS' => '999999',
                        'SIMPLESEARCH' => '',
                        'ATTRIBUTESEARCH' => array(
                                        'ATTRIBUTE' => 'PRODUCTION_NUMBER',
                                        'SEARCHSTRING' => 'x65600')
                     );

        $queryDoc = $this->Ipmam->getQueryDoc($search);

        $this->set('queryDoc',$queryDoc);

        $searchResponseXml = $this->Ipmam->search($queryDoc);

        $this->set('searchResponseXml',$searchResponseXml);

        $dmguids = $this->Ipmam->getGuids($searchResponseXml);

        $this->set('guids', $dmguids);
*/
return;
        foreach ($dmguids as $dmguid) {
            //echo "GUID: $guid<br>";
            $epguids = $this->Ipmam->getAllEpGuids($dmguid);
            $vsObjects['DMGUID'][$dmguid]['EPGUID'] = $epguids;

            foreach ($epguids as $epguid) {
                $essences = $this->Ipmam->getAllEssences($epguid);
                $vsObjects['DMGUID'][$dmguid]['EPGUID'][$epguid]['ESSENCE'] = $essences;
            }
        }

        $this->set('repEpGuid',$vsObjects);

    }

    /**
     *
     *
     */
    function getDmguidsByProduction($production) {

        $search = array(
            'OBJECTCLASSES' => 'VIDEO',
            'FIRSTHIT' => '0',
            'MAXHITS' => '99999',
            'SIMPLESEARCH' => '',
            'ATTRIBUTESEARCH' => array(
                'ATTRIBUTE' => 'PRODUCTION_NUMBER',
                'SEARCHSTRING' => $production
            )
        );

        $queryDoc = $this->Ipmam->getQueryDoc($search);

        $hitlist = $this->Ipmam->getHitListDoc(
                    array(
                        'BARCODE',
                        'CLIP_ID',
                        'PRODUCTION_NUMBER',
                        'PRODUCTION_TITLE',
                        'ASSET_TAPE_FORMAT',
                        'CONTENT_TYPE',
                        'START_TC',
                        'END_TC',
                        'TAPE_RUNNING_TIME'
                    )
                );

        $this->set('queryDoc',$queryDoc);
        $this->set('queryDoc',$hitlist);

        $searchResponseXml = $this->Ipmam->search($queryDoc, $hitlist);
        $data['DMGUIDS'] = $this->Ipmam->parseXml($searchResponseXml);
//print_r($dmguids);
//exit;
        //$this->set('searchResponseXml',$searchResponseXml);

        return $data;
    }

    function getBarcodesByProduction($production) {

        set_time_limit(120);

        $this->Ipmam->login();

        $data = $this->getDmguidsByProduction($production);

        $data['prodTitle'] = $this->Ipmam->getProductionTitle($data);
        $this->Ipmam->barcodeCount($data);

        //print_r($data);
        return $data;

    }

    function getFiles($barcode) {
        $this->Ipmam->login();
        $data = $this->Ipmam->getFileDetails($barcode);

        $this->set('data', $data);
    }

}


