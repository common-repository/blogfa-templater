jQuery(function($){
	/* Media Upload control */
	var formfield = null;
	$('input.media-upload').live('click', function() {
		$('html').addClass('Image');
		formfield = $(this).prev(); /* save the text input that recieves the data */
		tb_show('', 'media-upload.php?type=image&TB_iframe=true');
		return false;
	});

	// user inserts file into post.
	//only run custom if user started process using the above process
	// window.send_to_editor(html) is how wp normally handle the received data
	window.original_send_to_editor = window.send_to_editor;
	window.send_to_editor = function(html){
		var fileurl;
		if (formfield != null) {
			fileurl = $('img',html).attr('src');
			formfield.val(fileurl);
			tb_remove();
			$('html').removeClass('Image');
			formfield = null;
		} else {
			window.original_send_to_editor(html);
		}
	};
});