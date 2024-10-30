<?php
	if( ! is_active_sidebar( 'above-content' ) )
		return; ?>

	<div id="above-content">
		<?php dynamic_sidebar( 'above-content' ) ?>
	</div>