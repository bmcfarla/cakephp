<?php
    echo $this->Html->link('Get Barcodes', array('controller' => 'Ipmams', 'action' => 'barcodes'));

    echo "<p>".$accessKey."</p>";
    echo "<pre>";
    echo htmlentities($queryDoc);
    echo "</pre>";

    echo "<p>";
    echo "<pre>";
    echo htmlentities($searchResponseXml);
    echo "</pre>";
    echo "</p>";

    echo "<pre>";
    print_r($guids);
    echo "</pre>";
    echo "</p>";

    echo "<pre>";
    print_r($repEpGuid);
    echo "</pre>";
    echo "</p>";


?>

