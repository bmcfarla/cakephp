<?php

class IpmamController extends AppController {

    function Index() {

        $vsObjects = array();

        $accessKey = $this->Ipmam->login();

        $this->set('accessKey', $accessKey);

        $class = "VIDEO";
        $searchString = 'crabs';
        $maxHits = "99999";

        $search = array('OBJECTCLASSES' => 'VIDEO',
                        'FIRSTHIT' => '0',
                        'MAXHITS' => '999',
                        'SIMPLESEARCH' => '99N99999_000*',
                     );

        $queryDoc = $this->Ipmam->getQueryDoc($search);

        $this->set('queryDoc',$queryDoc);

        $searchResponseXml = $this->Ipmam->search($queryDoc);

        $this->set('searchResponseXml',$searchResponseXml);

        $dmguids = $this->Ipmam->getGuids($searchResponseXml);

        $this->set('guids', $dmguids);

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
}