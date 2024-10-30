<?php
/*
Plugin Name:	Blogfa Templater
Description:	Enables you to use blogfa.com templates in WordPress.
Author:			Hassan Derakhshandeh
Version:		0.3
Author URI:		http://tween.ir/


		* 	Copyright (C) 2011  Hassan Derakhshandeh
		*	http://tween.ir/
		*	hassan.derakhshandeh@gmail.com

		This program is free software; you can redistribute it and/or modify
		it under the terms of the GNU General Public License as published by
		the Free Software Foundation; either version 2 of the License, or
		(at your option) any later version.

		This program is distributed in the hope that it will be useful,
		but WITHOUT ANY WARRANTY; without even the implied warranty of
		MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
		GNU General Public License for more details.

		You should have received a copy of the GNU General Public License
		along with this program; if not, write to the Free Software
		Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class Blogfa_Templater {

	var $options,
		$textdomain,
		$dir,
		$url,
		$version;

	function __construct() {
		/* first, set some vars */
		$this->url = plugins_url( '',__FILE__ );
		$this->dir = plugin_dir_path( __FILE__ );
		$this->version = '0.3';
		$this->textdomain = 'blogfa-templater';
		$this->options = get_option( 'blogfa', array() );

		/* load the language files */
		load_plugin_textdomain( $this->textdomain, false, basename( dirname( __FILE__ ) ) . '/langs' );

		/* aaaand Action! */
		add_action( 'admin_init', array( &$this, 'register_settings' ) );
		add_action( 'admin_menu', array( &$this, 'admin' ) );

		/* check if the user has enabled the plugin */
		if( isset( $this->options['enable'] ) && $this->options['enable'] == 1 ) {
			/* it's party time! */
			add_action( 'template_redirect', array( &$this, 'queue' ) );

			/* Oh yeah baby, we have a template of our own! */
			add_filter( 'template_directory', array( &$this, 'template_directory' ) );
			add_filter( 'template_directory_uri', array( &$this, 'template_directory_uri' ) );
			add_filter( 'stylesheet_directory', array( &$this, 'template_directory' ) );
			add_filter( 'stylesheet_directory_uri', array( &$this, 'template_directory_uri' ) );

			/* create the blogfa_templater_render() function */
			eval( 'function blogfa_templater_render(){ global $wp_query, $blogfa_templater; ?>'. $this->options['compiled'] . '<?php }' );

			/* some general template functions */
			include_once( dirname( __FILE__ ) . '/general-template.php' );
		}
	}

	function template_directory( $dir ) {
		return $this->dir . '/theme';
	}

	function template_directory_uri( $uri ) {
		return $this->url . '/theme';
	}

	function admin() {
		$options_page = add_theme_page(
			__( 'Blogfa Options', $this->textdomain ), // Name of page
			__( 'Blogfa Options', $this->textdomain ), // Label in menu
			'edit_theme_options',                  // Capability required
			'blogfa-options',                       // Menu slug, used to uniquely identify the page
			array( &$this, 'options_page' )            // Function that renders the options page
		);
		add_action( "admin_print_styles-{$options_page}", array( &$this, 'admin_queue' ) );
	}

	function options_page() {
		include_once( $this->dir . '/views/options.php' );
	}

	function admin_queue() {
		add_thickbox();
		wp_enqueue_script( 'btr-admin', plugins_url( 'js/admin.js', __FILE__ ), array( 'thickbox' ) );
	}

	function register_settings() {
		register_setting( 'blogfa_settings', 'blogfa', array( &$this, 'sanitize_options' ) );
	}

	function sanitize_options( $options ) {
		if( ! ( stripos( $options['template'], '<?php' ) === false ) ) {
			/* hey, no php tags allowed inside the template */
			$options['template'] = $this->options['template'];
		}
		$options['compiled'] = $this->compile( $options );
		return $options;
	}

	function queue() {
		wp_enqueue_style( 'wp', get_template_directory_uri() . '/wp.css' );
	}

	function compile( $options ) {
		$template = preg_replace( '/<-BlogTitle->/', '<?php bloginfo("name") ?>', $options['template'] );
		$template = preg_replace( '/<-BlogDescription->/', '<?php bloginfo("description") ?>', $template );
		$template = preg_replace( '/<-BlogEmail->/', '<?php bloginfo("admin_email") ?>', $template );
		$template = preg_replace( '/<-BlogUrl->/', '<?php bloginfo("url") ?>', $template );
		$template = preg_replace( '/<-BlogAuthor->/', '<?php get_admin_name() ?>', $template );
		$template = preg_replace( '/<-BlogXmlLink->/', '<?php bloginfo("rss_url") ?>', $template );
		$template = preg_replace( '/<-BlogAndPostTitle->/', '<?php echo ( is_singular() ) ? ( get_bloginfo("name") . " - " .get_the_title() ) : get_bloginfo("name"); ?>', $template );
		$template = preg_replace( '/<BLOGFA>/', '<?php do_action( "before_blogfa_tag" ); global $query_string; query_posts( $query_string ); if( have_posts() ) : while( have_posts() ) : the_post(); ?>', $template );
		$template = preg_replace( '/<\/BLOGFA>/', '<?php endwhile; endif; do_action( "after_blogfa_tag" ); ?>', $template );
		$template = preg_replace( '/<-PostTitle->/', '<?php the_title() ?>', $template );
		/* since 0.3, always call the_content, the user should be in control to whether to show a limited portion of text, or not */
		$template = preg_replace( '/<-PostContent->/', '<?php the_content(); ?>', $template );
		$template = preg_replace( '/<-PostAuthor->/', '<?php the_author() ?>', $template );
		$template = preg_replace( '/<-PostDate->/', '<?php echo function_exists( "jdate" ) ? jdate( get_option("date_format"), strtotime( $post->post_date ) ) : get_the_date(); ?>' /* support for jalali calendar */, $template );
		$template = preg_replace( '/<-PostTime->/', '<?php the_time() ?>', $template );
		$template = preg_replace( '/<-PostLink->/', '<?php the_permalink() ?>', $template );
		$template = preg_replace( '/<-PostId->/', '<?php the_ID() ?>', $template );
		$template = preg_replace( '/<BlogExtendedPost>/', '<?php if( ! is_singular() ) : ?>', $template );
		$template = preg_replace( '/<\/BlogExtendedPost>/', '<?php endif; ?>', $template );
		$template = preg_replace( '/<-PostCategory->/', '<?php echo get_the_category_list(" - ") ?>', $template );
		$template = preg_replace( '/<BlogNextAndPreviousBlock>/', '<?php if ( $wp_query->max_num_pages > 1 ) : ?>', $template );
		$template = preg_replace( '/<BlogPreviousPageBlock>/', '<?php if ( $wp_query->max_num_pages > 1 ) : ?>', $template );
		$template = preg_replace( '/<BlogNextPageBlock>/', '<?php if ( $wp_query->max_num_pages > 1 ) : ?>', $template );
		$template = preg_replace( '/<\/BlogNextAndPreviousBlock>/', '<?php endif; ?>', $template );
		$template = preg_replace( '/<\/BlogPreviousPageBlock>/', '<?php endif; ?>', $template );
		$template = preg_replace( '/<\/BlogNextPageBlock>/', '<?php endif; ?>', $template );
		$template = preg_replace( '/<-BlogPreviousPageLink->/', '<?php echo get_previous_posts_page_link() ?>', $template );
		$template = preg_replace( '/<-BlogNextPageLink->/', '<?php echo get_next_posts_page_link() ?>', $template );
		$template = preg_replace( '/<BlogProfile>/', '', $template );
		$template = preg_replace( '/<\/BlogProfile>/', '', $template );
		$template = preg_replace( '/<-BlogAbout->/', '<?php blogfa_about_text() ?>', $template );
		$template = preg_replace( '/<-BlogCustomHtml->/', '<?php blogfa_custom_html() ?>', $template );
		$template = preg_replace( '/<-BlogTimeZone->/', '""', $template );

		/* internal pages */
		$template = preg_replace( '/<-BlogArchiveLink->/', '<?php blogfa_archive_link() ?>', $template );
		$template = preg_replace( '/href=["\']\/posts\/?["\']/', '<?php blogfa_posts_link() ?>', $template );
		$template = preg_replace( '/<BlogProfileLinkBlock>/', '<?php if( isset( $blogfa_templater->options["profile_page"] ) && $blogfa_templater->options["profile_page"] !== "none" ) : ?>', $template );
		$template = preg_replace( '/<\/BlogProfileLinkBlock>/', '<?php endif; ?>', $template );
		$template = preg_replace( '/<-BlogProfileLink->/', '<?php echo get_permalink( $blogfa_templater->options["profile_page"] ) ?>', $template );

		/* body_class */
		$template = preg_replace( '/<body>/', '<body <?php body_class() ?>>', $template );

		/* comments */
		$template = preg_replace( '/<BlogComment>(.*?)<\/BlogComment>/s', '<?php blogfa_comment_link() ?>', $template );

		/* BlogPreviousItemsBlock */
		$template = preg_replace( '/<BlogPreviousItemsBlock>/', '<?php if( have_posts() ) : ?>', $template );
		$template = preg_replace( '/<\/BlogPreviousItemsBlock>/', '<?php endif; ?>', $template );
		$template = preg_replace( '/<BlogPreviousItems(\sitems="(\d+)")?\s?>/', '<?php $limit = \'$2\'; if( empty( $limit ) ) $limit = 10; query_posts("posts_per_page={$limit}"); while( have_posts() ) : the_post(); ?>', $template );
		$template = preg_replace( '/<\/BlogPreviousItems>/', '<?php endwhile; rewind_posts(); ?>', $template );

		/* BlogAuthorsBlock */
		$template = preg_replace( '/<BlogAuthorsBlock>/', '<?php if( check_for_multi_author() ) : ?>', $template );
		$template = preg_replace( '/<\/BlogAuthorsBlock>/', '<?php endif; ?>', $template );
		$template = preg_replace( '/<BlogAuthors>(.*?)<\/BlogAuthors>/s', '<?php wp_list_authors("show_fullname=1&exclude_admin=0") ?>', $template );

		/**
		 * BlogCategories
		 * BlogCategoriesBlock is disabled since in WordPress, at least one category exists at a time.
		 */
		$template = preg_replace( '/<BlogCategories>(.*?)<\/BlogCategories>/s', '<?php blogfa_blog_categories(\'$1\') ?>', $template );
		$template = preg_replace( array( '/<BlogCategoriesBlock>/', '/<\/BlogCategoriesBlock>/' ), '', $template );

		/**
		 * BlogPostCategories
		 * BlogPostCategoriesBlock is disabled since in WordPress, at least one category exists at a time.
		 */
		$template = preg_replace( '/<BlogPostCategories(\sseparator="(.*?)")?\s?>(.*?)<\/BlogPostCategories>/s', '<?php blogfa_blog_post_categories(\'$3\') ?>', $template );
		$template = preg_replace( '/<BlogPostCategoriesBlock>/', '<?php if( ! is_page() ) : ?>', $template );
		$template = preg_replace( '/<\/BlogPostCategoriesBlock>/', '<?php endif; ?>', $template );

		/* BlogTagsBlock */
		$template = preg_replace( '/<BlogTagsBlock>/', '<?php if( blog_has_tags() ) : ?>', $template );
		$template = preg_replace( '/<\/BlogTagsBlock>/', '<?php endif; ?>', $template );
		$template = preg_replace( '/<BlogTags>(.*?)<\/BlogTags>/s', '<?php blogfa_blog_tags(\'$1\') ?>', $template );

		/* BlogPostTagsBlock */
		$template = preg_replace( '/<BlogPostTagsBlock>/', '<?php if( ( ! is_page() ) && has_tag() ) : ?>', $template );
		$template = preg_replace( '/<\/BlogPostTagsBlock>/', '<?php endif; ?>', $template );
		$template = preg_replace( '/<BlogPostTags(\sseparator="(.*?)")?\s?>(.*)?<\/BlogPostTags>/s', '<?php blogfa_post_tags(\'$3\', \'$2\') ?>', $template );

		/* BlogArchive */
		$template = preg_replace( '/<BlogArchive>(.*?)<\/BlogArchive>/s', '<?php blogfa_get_archives(\'$1\') ?>', $template );

		/**
		 * BlogLinks & BlogLinkDump
		 * WP's link categories is disabled by default
		 * 
		 */
		if( isset( $options['daily'] ) && $options['daily'] !== 'none' ) {
			$template = preg_replace( '/<BlogLinkDumpBlock>/', '<?php $linksdump = get_categories("category='.$options['daily'] .'"); if( ! empty( $linksdump ) ) : ?>', $template );
			$template = preg_replace( '/<\/BlogLinkDumpBlock>/', '<?php endif; ?>', $template );
			$template = preg_replace( '/<BlogLinkDump>(.*?)<\/BlogLinkDump>/s', '<?php wp_list_bookmarks("title_li=&categorize=0&category= '. $options['daily'] .'") ?>', $template );
		} else {
			$template = preg_replace( '/<BlogLinkDumpBlock>(.*?)<\/BlogLinkDumpBlock>/s', '', $template );
			$template = preg_replace( '/<BlogLinkDump>(.*?)<\/BlogLinkDump>/s', '', $template );
		}
		if( isset( $options['links'] ) && $options['links'] !== 'none' ) {
			$template = preg_replace( '/<BlogLinksBlock>/', '<?php $links = get_categories("category='.$options['links'] .'"); if( ! empty( $links ) ) : ?>', $template );
			$template = preg_replace( '/<\/BlogLinksBlock>/', '<?php endif; ?>', $template );
			$template = preg_replace( '/<BlogLinks>(.*?)<\/BlogLinks>/s', '<?php wp_list_bookmarks("title_li=&categorize=0&category= '. $options['links'] .'") ?>', $template );
		} else {
			$template = preg_replace( '/<BlogLinksBlock>(.*?)<\/BlogLinksBlock>/s', '', $template );
			$template = preg_replace( '/<BlogLinks>(.*?)<\/BlogLinks>/s', '', $template );
		}

		/* blog photo */
		if( ! empty( $options['photo'] ) ) {
			$template = preg_replace( array( '/<BlogPhoto>/', '/<\/BlogPhoto>/' ), '', $template );
			$template = preg_replace( '/<-BlogPhotoLink->/', $options['photo'], $template );
		} else {
			$template = preg_replace( '/<BlogPhoto>(.*?)<\/BlogPhoto>/s', '', $template );
		}

		/* remove the comments script code */
		$template = preg_replace( '/\<!-- BeginCommentCode --\>(.*)\<!-- EndCommentCode --\>/s', '', $template );

		/* add wp_head & wp_footer hooks */
		$template = preg_replace( '/<\/head>/', '<?php wp_head() ?></head>', $template );
		$template = preg_replace( '/<\/body>/', '<?php wp_footer() ?></body>', $template );

		return $template;
	}

	function _links_category_dropdown( $current = null ) {
		$c = get_categories( 'title_li=&taxonomy=link_category&hide_empty=0' );
		echo '<option value="none">'. __( 'None', $this->textdomain ) .'</option>';
		if( ! empty( $c ) ) : foreach( $c as $cat ) :
			echo '<option value="'. $cat->cat_ID .'"'. selected( $cat->cat_ID, $current ) .'>'. $cat->cat_name . '</option>';
		endforeach; endif;
	}

	function _pages_dropdown( $current = null ) {
		$pages = get_pages( 'title_li=' );
		echo '<option value="none">'. __( 'None', $this->textdomain ) .'</option>';
		if( ! empty( $pages ) ) : foreach( $pages as $page ) :
			echo '<option value="'. $page->ID .'"'. selected( $page->ID, $current ) .'>'. $page->post_title . '</option>';
		endforeach; endif;
	}
}
$blogfa_templater = new Blogfa_Templater;