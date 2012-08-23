<?php

class ProductionController extends AppController {
    var $scaffold;
    var $tmpFile;

    function uploadExcel() {
        if ($this->request->is('post')) {
            $this->Session->setFlash('Your file has been uploaded.');
            //$this->processExcel($this->request->data['Production']['submittedfile']['tmp_name']);
            $this->processExcel();
        }
    }

    function processExcel() {
        set_time_limit(240);    //4minutes
        ini_set('memory_limit', '64M');

        $tmpFile = $this->data['Production']['submittedfile']['tmp_name'];

        App::import('Vendor', 'Spreadsheet_Excel_Reader', array('file' => 'phpExcelReader/Excel/reader.php'));

        $data = new Spreadsheet_Excel_Reader();

        // Set output Encoding.
        $data->setOutputEncoding('CP1251');

        $data->read($tmpFile);

        $headings = array_keys($this->Production->schema());

        for ($i = 1; $i <= $data->sheets[0]['numRows']; $i++) {
            for ($j = 1; $j <= $data->sheets[0]['numCols']; $j++) {
                if($i == 1) {
                    //this is the headings row, each column (j) is a header
                    //$headings[$j] = $data->sheets[0]['cells'][$i][$j];
                } else {
                    //column of data
                    $row_data[$headings[$j-1]] = isset($data->sheets[0]['cells'][$i][$j]) ? $data->sheets[0]['cells'][$i][$j] : '';
                }
            }
            if($i > 1) {
                $xls_data[] = array('Production' => $row_data);
            }
        }

        if(isset($this->data['Production']['overwrite']) and $this->data['Production']['overwrite'] == 1) {
            $this->Production->deleteAll('1=1');
        }

        if($this->Production->saveAll($xls_data, array('validate'=>false))) {
            $this->Session->setFlash('Success. Imported '. count($xls_data) .' records.');
        } else {
            $this->Session->setFlash('Error.  Unable to import records. Please try again.');
        }

        $this->redirect(array('action' => 'index'));
    }

    function report($production) {

    }

}

?>
