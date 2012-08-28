<?php
/**
 *
 * @author bmcfarla
 *
 */
class ReportController extends AppController {

    /**
     * Present a form to provide parameters to the report
     */
    function index() {
        // If the call method is get run the report
        if ($this->request->is('get')) {
            $this->Session->setFlash('Run Report form prod X');
        } else {

        }
    }

     /**
     * Run the report
     */
    function production() {
        $time_start = microtime(true);

// Sleep for a while
usleep(100);

$time_end = microtime(true);
echo $time_end - $time_start;


        // Set prodNum to the production Number which ever way it was entered
        $prodNum = isset($this->params['named']['prod']) ? $this->params['named']['prod'] : $this->data['production']['num'];

        // Set prodNum to the maxhits which ever way it was entered
        $maxHits = isset($this->params['named']['maxhits']) ? $this->params['named']['maxhits'] : $this->data['production']['maxhits'];
        $maxHits = $maxHits == 0 ? 9999999 : $maxHits;

        // Set the title for the report
        $this->set('title_for_layout', 'Production Report');

        // The the view layout
        $this->layout = 'prodReport';

        // Die if no production given
        if (!$this->request->is('post') && !$this->params['named']['prod']) {
            echo "No Production Number";
            die;
        }

        // Set variable to view
        $this->set('prodNum', $prodNum);

        // Call ipmamController method
        $data = $this->requestAction("/ipmam/getProductionData/prod:$prodNum/maxhits:$maxHits");

        // Set variable to view
        $this->set('data', $data);

        $totSize = 0;

        // Set variable to view
        $this->set('totSize', $totSize);

        // Set variable to view
        $this->set('totContent', 0);
    }
}