<?php

?>
<div class=section id=summary>
    <h1>Summary</h1>
    <div class=summary-block id=summary-barcodes>
        Barcodes<br>
        <hr>
        <?php echo $barcodes['BARCODE_COUNT'] ?>
    </div>
    <div class=summary-block id=summary-files>
        # Files<br>
        <hr>
        <?php echo $barcodes['CLIP_COUNT'] ?>
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

    <div id=summary-barcodes>
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
                foreach($barcodes['BARCODES'] as $barcode => $details) : ?>
                <tr>
                    <td><?php $i++; echo $i; ?></td>
                    <td><?php echo $barcode; ?></td>
                    <td><?php echo $details['details']['desc']; ?></td>
                    <td><?php echo $details['details']['type']; ?></td>
                    <td><?php echo $details['details']['clips']; ?></td>
                    <td><?php echo $details['details']['duration']; ?></td>
                    <td><?php echo $details['details']['size']; ?></td>
                </tr>
            <?php endforeach;?>

        </table>
    </div>
</div>

<div class=section id=barcode-detail>
    BARCODE DETAILS
</div>
