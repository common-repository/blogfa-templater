<?php
/**
 * The main template file.
 *
 * Renders every page of our website if the plugin is enabled.
 *
 * @package Blogfa Templater
 * @since 0.3
 */

/* call the magic function that renders the output */
blogfa_templater_render();

// for debugging purposes only: this shows the compiled Blogfa template to WordPress' template
// echo $blogfa_templater->options['compiled'];