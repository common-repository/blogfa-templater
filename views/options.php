<div class="wrap">
	<?php screen_icon() ?>
	<h2><?php _e( 'Blogfa Template Renderer', $this->textdomain ) ?></h2>
	<form method="post" action="options.php">
		<?php settings_fields( 'blogfa_settings' ); ?>
		<?php settings_errors(); ?>
		<table class="form-table">
			<tbody>
				<tr valign="top">
					<th scope="row"><?php _e( 'Enable the plugin?', $this->textdomain ) ?></th>
					<td><label><input type="checkbox" name="blogfa[enable]" value="1" <?php checked( 1, $this->options['enable'] ) ?> /> <?php _e( 'Use this plugin to render your theme.', $this->textdomain ) ?></label></td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e( 'Template:', $this->textdomain ) ?></th>
					<td><textarea dir="ltr" <?php //echo 'id="newcontent"'; ?> name="blogfa[template]" rows="10" cols="50" class="large-text"><?php echo esc_html( $this->options['template'] ) ?></textarea></td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e( 'Custom HTML:', $this->textdomain ) ?></th>
					<td><textarea name="blogfa[html]" rows="5" cols="50" class="large-text code"><?php echo $this->options['html'] ?></textarea>
					<p class="description"><?php _e( '<code>[Shortcodes]</code> enabled.', $this->textdomain ) ?></p>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e( 'About Text:', $this->textdomain ) ?></th>
					<td><textarea name="blogfa[about]" rows="5" cols="50" class="large-text"><?php echo $this->options['about'] ?></textarea></td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e( 'Photo:', $this->textdomain ) ?></th>
					<td><input type="text" name="blogfa[photo]" class="regular-text" value="<?php echo $this->options['photo'] ?>" /><input type="button" class="button-secondary media-upload" value=" <?php _e( 'Upload', $this->textdomain ) ?> " /></td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e( 'Archive page:', $this->textdomain ) ?></th>
					<td><select name="blogfa[archive_page]">
						<?php $this->_pages_dropdown( $this->options['archive_page'] ) ?>
						<p class="description"><?php _e( 'Use <code>[archive]</code> in this page to display the archive.', $this->textdomain ) ?></p>
					</select></td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e( 'Posts page:', $this->textdomain ) ?></th>
					<td><select name="blogfa[posts_page]">
						<?php $this->_pages_dropdown( $this->options['posts_page'] ) ?>
						<p class="description"><?php _e( 'Use <code>[posts]</code> in this page to display the posts.', $this->textdomain ) ?></p>
					</select></td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e( 'Profile page:', $this->textdomain ) ?></th>
					<td><select name="blogfa[profile_page]">
						<?php $this->_pages_dropdown( $this->options['profile_page'] ) ?>
					</select></td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e( 'Daily Links:', $this->textdomain ) ?></th>
					<td><select name="blogfa[daily]">
						<?php $this->_links_category_dropdown( $this->options['daily'] ) ?>
					</select></td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e( 'Links:', $this->textdomain ) ?></th>
					<td><select name="blogfa[links]">
						<?php $this->_links_category_dropdown( $this->options['links'] ) ?>
					</select></td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e( 'Custom Backgrounds:', $this->textdomain ) ?></th>
					<td><label><input type="checkbox" name="blogfa[custombackgrounds]" value="1" <?php checked( 1, $this->options['custombackgrounds'] ) ?> /> <?php _e( 'Enable WP3.0 Custom Backgrounds', $this->textdomain ) ?></label></td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e( 'Post Thumbnails:', $this->textdomain ) ?></th>
					<td><label><input type="checkbox" name="blogfa[postthumbs]" value="1" <?php checked( 1, $this->options['postthumbs'] ) ?> /> <?php _e( 'Enable WP3.0 Post Thumbnails', $this->textdomain ) ?></label>
					<p class="description"><?php printf( __( 'Read more about this <a href="%s">here</a>.', $this->textdomain ), '#' ) ?></p>
					</td>
				</tr>
			</tbody>
		</table>
		<?php submit_button() ?>
	</form>
</div><!-- .wrap -->