<?php
	if( ! is_active_sidebar( 'below-content' ) )
		return; ?>

	<div id="below-content">
		<?php dynamic_sidebar( 'below-content' ) ?>
	</div>