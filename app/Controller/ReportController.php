<?php
/**
 *
 * @author bmcfarla
 *
 */
class ReportController extends AppController {

    /**
     *
     */
    function index() {
        if ($this->request->is('get')) {
            $this->Session->setFlash('Run Report form prod X');
            //$this->processExcel($this->request->data['Production']['submittedfile']['tmp_name']);
            //$this->production();
        } else {

        }
    }

    //function production($prod = NULL, $maxHits = NULL) {
    /**
     *
     */
    function production() {
        $params = array();

        // Set the title for the report
        $this->set('title_for_layout', 'Production Report');

        // The the view layout
        $this->layout = 'prodReport';

        // Die if no production given
        if (!$this->request->is('post') && !$this->params['named']['prod']) {
            echo "No Production Number";
            die;
        }

        // Set prodNum to the production Number which ever way it was entered
        $prodNum = $this->params['named']['prod'] ?
                    $this->params['named']['prod'] : $this->data['production']['num'];
        $maxHits = $this->params['named']['maxhits'] ? $this->params['named']['maxhits'] : $this->data['production']['maxhits'];


        $this->set('prodNum', $prodNum);

        $params['prodNum'] = $prodNum;
        $params['maxhits'] = $maxHits;

        $data = $this->requestAction("/ipmam/getProductionData/prod:$prodNum/maxhits:$maxHits");

        $this->set('data', $data);

        $totSize = 0;
        $this->set('totSize', $totSize);

        $this->set('totContent', 0);
    }
}