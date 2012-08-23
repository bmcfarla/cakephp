<?php

?>
<!DOCTYPE html5>
<html>
<head>
	<?php echo $this->Html->charset(); ?>
	<title>
		<?php echo $title_for_layout; ?>
	</title>
	<?php
		echo $this->Html->meta('icon');

		echo $this->Html->css('cake.generic');
		echo $this->Html->css('mycss');
		echo $this->fetch('meta');
		echo $this->fetch('css');
		echo $this->fetch('script');
	?>
</head>
<body>
	<div id="container">
		<div id="header">
		    <h1>Production Deletion Report for: <span id="header-prod-num"><?php echo "$prodNum - " . $data['prodTitle'] ?></span></h1>
		</div>
		<div id="content">

			<?php echo $this->Session->flash(); ?>
            <?php echo $this->fetch('summary'); ?>
			<?php echo $this->fetch('content'); ?>
		</div>
		<div id="footer">
		</div>
	</div>
</body>
</html>
