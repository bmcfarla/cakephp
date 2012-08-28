<?php

function formatBytes($num, $cnt=0){
    $placeValues = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB');

    if ($num/1024 < 1) {
        return sprintf("%0.2f %s", $num, $placeValues[$cnt]);
    } else {
        return formatBytes(($num/1024),++$cnt);
    }

}
?>
<div class=section id=summary>
    <h1>Summary</h1>
    <div class=summary-block id=summary-barcodes>
        Barcodes<br>
        <hr>
        <?php echo $data['barcodeCount']['count'] ?>
    </div>
    <div class=summary-block id=summary-files>
        # Files<br>
        <hr>
        <?php echo $data['barcodeCount']['clips'] ?>
    </div>
    <div class=summary-block id=summary-content>
        Total Content<br>
        <hr>
        <?php echo $data['barcodeCount']['content'] ?>
    </div>
    <div class=summary-block id=summary-size>
        Total Size<br>
        <hr>
        <?php echo formatBytes($data['barcodeCount']['filesize']) ?>
    </div>
</div>

<div class=section id=barcodes>
    <h1>Barcodes</h1>

    <div id=summary-barcodes-list>
        <table id="barcode-table">
            <tr>
                <th class="col1" id="item">Item</th>
                <th class="col2" id="barcode">Barcode</th>
                <th class="col3" id="desc">Description</th>
                <th class="col4" id="type">Type</th>
                <th class="col5" id="clips">Clips</th>
                <th class="col6" id="trt">Duration</th>
                <th class="col7" id="size">Size</th>
            </tr>
            <?php
                $i = 0;
                foreach($data['barcodeCount']['barcodes'] as $barcode => $value) : ?>
                <tr>
                    <td class="col1" ><?php $i++; echo $i; ?></td>
                    <td class="col2" ><?php echo $barcode; ?></td>
                    <td class="col3" ><?php echo $value['description']; ?></td>
                    <td class="col4" ><?php echo $value['type']; ?></td>
                    <td class="col5" ><?php echo $value['clips']; ?></td>
                    <td class="col6" ><?php echo $value['duration']; ?></td>
                    <td class="col7" ><?php echo formatBytes($value['filesize']); ?></td>
                </tr>
            <?php endforeach;?>

        </table>
    </div>
</div>
