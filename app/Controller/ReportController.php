<?php

class ReportController extends AppController {

    function index() {
        if ($this->request->is('get')) {
            $this->Session->setFlash('Run Report form prod X');
            //$this->processExcel($this->request->data['Production']['submittedfile']['tmp_name']);
            $this->runReport();
        } else {

        }
    }

    function production($prod = NULL) {
        $this->set('title_for_layout', 'Production Report');
        $this->layout = 'prodReport';

        if (!$this->request->is('post') && !$prod) {
            echo "No Production Number";
            die;
        }

        $prodNum = $prod ? $prod : $this->data['production']['num'];

        $this->set('prodNum', $prodNum);

        $data = $this->requestAction('/ipmam/getBarcodesByProduction/x65600');

        $this->set('barcodes', $data['barcodes']);

        $this->set('prodTitle', $data['productionTitle']);

        $totSize = 0;
        $this->set('totSize', $totSize);

        $this->set('totContent', 0);



    }
}