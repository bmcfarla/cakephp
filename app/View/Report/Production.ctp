<?php

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
        <?php echo $totContent ?>
    </div>
    <div class=summary-block id=summary-size>
        Total Size<br>
        <hr>
        <?php echo $totSize ?>
    </div>
</div>

<div class=section id=barcodes>
    <h1>Barcodes</h1>

    <div id=summary-barcodes-list>
        <table id="barcode-table">
            <tr>
                <th>Item</th>
                <th>Barcode</th>
                <th>Description</th>
                <th>Type</th>
                <th>Clips</th>
                <th>Duration</th>
                <th>Size</th>
            </tr>
            <?php
                $i = 0;
                foreach($data['barcodeCount']['barcodes'] as $barcode => $value) : ?>
                <tr>
                    <td><?php $i++; echo $i; ?></td>
                    <td><?php echo $barcode; ?></td>
                    <td><?php echo $value['description']; ?></td>
                    <td><?php echo $value['type']; ?></td>
                    <td><?php echo $value['clips']; ?></td>
                    <td><?php echo $value['duration']; ?></td>
                    <td><?php echo $value['size']; ?></td>
                </tr>
            <?php endforeach;?>

        </table>
    </div>
</div>
