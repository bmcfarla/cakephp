<h1>Upload File</h1>
<?php
    echo $this->Form->create('Production', array('type' => 'file'));
    echo $this->Form->file('submittedfile');
    echo $this->Form->input('overwrite',array('type' => 'checkbox'));

    echo $this->Form->end('Upload File');
?>