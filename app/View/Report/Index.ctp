<?php
echo $this->Form->create('production', array('type' => 'post', 'url' => '/report/production'));
echo $this->Form->input('num');
echo $this->Form->input('maxhits');
echo $this->Form->submit();
echo $this->Form->end();