<?php

add_action( 'after_setup_theme', 'blogfa_template_setup' );

function blogfa_template_setup() {
	global $blogfa_templater;

	add_action( 'widgets_init', 'blogfa_widget_areas' );
	add_action( 'before_blogfa_tag', 'blogfa_above_content_area' );
	add_action( 'after_blogfa_tag', 'blogfa_below_content_area' );

	add_filter( 'the_content', 'auto_append_comments_template' );

	if( 1 == $blogfa_templater->options['custombackgrounds'] ) {
		add_custom_background();
	}
	if( 1 == $blogfa_templater->options['postthumbs'] ) {
		add_theme_support( 'post-thumbnails' );
		add_filter( 'the_content', 'auto_append_post_thumbnail' );
	}

	add_shortcode( 'archive', 'blogfa_shortcode_archive' );
	add_shortcode( 'posts', 'blogfa_shortcode_posts' );
}

function blogfa_widget_areas() {
	global $blogfa_templater;

	register_sidebar( array(
		'id'	=> 'above-content',
		'name'	=> __( 'Above Content', $blogfa_templater->textdomain ),
		'before_widget'	=> '<div id="%1$s" class="widget-container %2$s">',
		'after_widget'	=> '</div>',
		'before_title'	=> '<h3 class="widget-title">',
		'after_title'	=> '</h3>',
	) );
	register_sidebar( array(
		'id'	=> 'below-content',
		'name'	=> __( 'Below Content', $blogfa_templater->textdomain ),
		'before_widget'	=> '<div id="%1$s" class="widget-container %2$s">',
		'after_widget'	=> '</div>',
		'before_title'	=> '<h3 class="widget-title">',
		'after_title'	=> '</h3>',
	) );
}

function blogfa_above_content_area() {
	get_sidebar( 'above-content' );
}

function blogfa_below_content_area() {
	get_sidebar( 'below-content' );
}

function auto_append_comments_template( $content ) {
	if( is_singular() ) {
		ob_start();
		comments_template();
		$content .= ob_get_clean();
	}
	return $content;
}

function auto_append_post_thumbnail( $content ) {
	if( has_post_thumbnail() ) {
		ob_start();
		the_post_thumbnail();
		$content = ob_get_clean() . $content;
	}
	return $content;
}

function blogfa_about_text() {
	global $blogfa_templater;

	echo $blogfa_templater->options['about'];
}

function blogfa_custom_html() {
	global $blogfa_templater;

	echo do_shortcode( $blogfa_templater->options['html'] );
}

function blogfa_post_tags( $template, $separator = ', ' ) {
	global $post;

	$tags = get_the_tags( $post->id );
	$output = array();
	foreach( $tags as $tag ) {
		$str = $template;
		$str = preg_replace( '/<-TagName->/', $tag->name, $str );
		$str = preg_replace( '/<-TagLink->/', get_tag_link( $tag->term_id ), $str );
		$output[] = $str;
	}
	echo join( $separator, $output );
}

function blogfa_blog_categories( $template ) {
	$cats = get_categories("hide_empty=1");
	$output = '';
	foreach( $cats as $cat ) {
		$str = $template;
		$str = preg_replace( '/<-CategoryName->/', $cat->name, $str );
		$str = preg_replace( '/<-CategoryLink->/', get_category_link( $cat->term_id ), $str );
		$output .= $str;
	}
	echo $output;
}

function blogfa_blog_post_categories( $template ) {
	$output = array();
	foreach( get_the_category() as $cat ) {
		$str = $template;
		$str = preg_replace( '/<-CategoryName->/', $cat->name, $str );
		$str = preg_replace( '/<-CategoryLink->/', get_category_link( $cat->term_id ), $str );
		$output[] = $str;
	}
	echo implode( ' - ', $output );
}

function blogfa_get_archives( $template ) {
	global $wpdb, $wp_locale;

	$defaults = array(
		'type' => 'monthly', 'limit' => '',
		'format' => 'html', 'before' => '',
		'after' => '', 'show_post_count' => false,
		'echo' => 1
	);

	extract( $defaults, EXTR_SKIP );

	if ( '' == $type )
		$type = 'monthly';

	if ( '' != $limit ) {
		$limit = absint($limit);
		$limit = ' LIMIT '.$limit;
	}

	// this is what will separate dates on weekly archive links
	$archive_week_separator = '&#8211;';

	// over-ride general date format ? 0 = no: use the date format set in Options, 1 = yes: over-ride
	$archive_date_format_over_ride = 0;

	// options for daily archive (only if you over-ride the general date format)
	$archive_day_date_format = 'Y/m/d';

	// options for weekly archive (only if you over-ride the general date format)
	$archive_week_start_date_format = 'Y/m/d';
	$archive_week_end_date_format	= 'Y/m/d';

	if ( !$archive_date_format_over_ride ) {
		$archive_day_date_format = get_option('date_format');
		$archive_week_start_date_format = get_option('date_format');
		$archive_week_end_date_format = get_option('date_format');
	}

	//filters
	$where = apply_filters( 'getarchives_where', "WHERE post_type = 'post' AND post_status = 'publish'", $r );
	$join = apply_filters( 'getarchives_join', '', $r );

	$output = '';

	if ( 'monthly' == $type ) {
		$query = "SELECT YEAR(post_date) AS `year`, MONTH(post_date) AS `month`, count(ID) as posts FROM $wpdb->posts $join $where GROUP BY YEAR(post_date), MONTH(post_date) ORDER BY post_date DESC $limit";
		$key = md5($query);
		$cache = wp_cache_get( 'wp_get_archives' , 'general');
		if ( !isset( $cache[ $key ] ) ) {
			$arcresults = $wpdb->get_results($query);
			$cache[ $key ] = $arcresults;
			wp_cache_set( 'wp_get_archives', $cache, 'general' );
		} else {
			$arcresults = $cache[ $key ];
		}
		if ( $arcresults ) {
			$afterafter = $after;
			foreach ( (array) $arcresults as $arcresult ) {
				$url = get_month_link( $arcresult->year, $arcresult->month );
				/* translators: 1: month name, 2: 4-digit year */
				$text = sprintf(__('%1$s %2$d'), $wp_locale->get_month($arcresult->month), $arcresult->year);
				if ( $show_post_count )
					$after = '&nbsp;('.$arcresult->posts.')' . $afterafter;
				$output .= blogfa_get_archives_link($url, $text, $template);
			}
		}
	} elseif ('yearly' == $type) {
		$query = "SELECT YEAR(post_date) AS `year`, count(ID) as posts FROM $wpdb->posts $join $where GROUP BY YEAR(post_date) ORDER BY post_date DESC $limit";
		$key = md5($query);
		$cache = wp_cache_get( 'wp_get_archives' , 'general');
		if ( !isset( $cache[ $key ] ) ) {
			$arcresults = $wpdb->get_results($query);
			$cache[ $key ] = $arcresults;
			wp_cache_set( 'wp_get_archives', $cache, 'general' );
		} else {
			$arcresults = $cache[ $key ];
		}
		if ($arcresults) {
			$afterafter = $after;
			foreach ( (array) $arcresults as $arcresult) {
				$url = get_year_link($arcresult->year);
				$text = sprintf('%d', $arcresult->year);
				if ($show_post_count)
					$after = '&nbsp;('.$arcresult->posts.')' . $afterafter;
				$output .= blogfa_get_archives_link($url, $text, $template);
			}
		}
	} elseif ( 'daily' == $type ) {
		$query = "SELECT YEAR(post_date) AS `year`, MONTH(post_date) AS `month`, DAYOFMONTH(post_date) AS `dayofmonth`, count(ID) as posts FROM $wpdb->posts $join $where GROUP BY YEAR(post_date), MONTH(post_date), DAYOFMONTH(post_date) ORDER BY post_date DESC $limit";
		$key = md5($query);
		$cache = wp_cache_get( 'wp_get_archives' , 'general');
		if ( !isset( $cache[ $key ] ) ) {
			$arcresults = $wpdb->get_results($query);
			$cache[ $key ] = $arcresults;
			wp_cache_set( 'wp_get_archives', $cache, 'general' );
		} else {
			$arcresults = $cache[ $key ];
		}
		if ( $arcresults ) {
			$afterafter = $after;
			foreach ( (array) $arcresults as $arcresult ) {
				$url	= get_day_link($arcresult->year, $arcresult->month, $arcresult->dayofmonth);
				$date = sprintf('%1$d-%2$02d-%3$02d 00:00:00', $arcresult->year, $arcresult->month, $arcresult->dayofmonth);
				$text = mysql2date($archive_day_date_format, $date);
				if ($show_post_count)
					$after = '&nbsp;('.$arcresult->posts.')'.$afterafter;
				$output .= blogfa_get_archives_link($url, $text, $template);
			}
		}
	} elseif ( 'weekly' == $type ) {
		$week = _wp_mysql_week( '`post_date`' );
		$query = "SELECT DISTINCT $week AS `week`, YEAR( `post_date` ) AS `yr`, DATE_FORMAT( `post_date`, '%Y-%m-%d' ) AS `yyyymmdd`, count( `ID` ) AS `posts` FROM `$wpdb->posts` $join $where GROUP BY $week, YEAR( `post_date` ) ORDER BY `post_date` DESC $limit";
		$key = md5($query);
		$cache = wp_cache_get( 'wp_get_archives' , 'general');
		if ( !isset( $cache[ $key ] ) ) {
			$arcresults = $wpdb->get_results($query);
			$cache[ $key ] = $arcresults;
			wp_cache_set( 'wp_get_archives', $cache, 'general' );
		} else {
			$arcresults = $cache[ $key ];
		}
		$arc_w_last = '';
		$afterafter = $after;
		if ( $arcresults ) {
				foreach ( (array) $arcresults as $arcresult ) {
					if ( $arcresult->week != $arc_w_last ) {
						$arc_year = $arcresult->yr;
						$arc_w_last = $arcresult->week;
						$arc_week = get_weekstartend($arcresult->yyyymmdd, get_option('start_of_week'));
						$arc_week_start = date_i18n($archive_week_start_date_format, $arc_week['start']);
						$arc_week_end = date_i18n($archive_week_end_date_format, $arc_week['end']);
						$url  = sprintf('%1$s/%2$s%3$sm%4$s%5$s%6$sw%7$s%8$d', home_url(), '', '?', '=', $arc_year, '&amp;', '=', $arcresult->week);
						$text = $arc_week_start . $archive_week_separator . $arc_week_end;
						if ($show_post_count)
							$after = '&nbsp;('.$arcresult->posts.')'.$afterafter;
						$output .= blogfa_get_archives_link($url, $text, $template);
					}
				}
		}
	} elseif ( ( 'postbypost' == $type ) || ('alpha' == $type) ) {
		$orderby = ('alpha' == $type) ? 'post_title ASC ' : 'post_date DESC ';
		$query = "SELECT * FROM $wpdb->posts $join $where ORDER BY $orderby $limit";
		$key = md5($query);
		$cache = wp_cache_get( 'wp_get_archives' , 'general');
		if ( !isset( $cache[ $key ] ) ) {
			$arcresults = $wpdb->get_results($query);
			$cache[ $key ] = $arcresults;
			wp_cache_set( 'wp_get_archives', $cache, 'general' );
		} else {
			$arcresults = $cache[ $key ];
		}
		if ( $arcresults ) {
			foreach ( (array) $arcresults as $arcresult ) {
				if ( $arcresult->post_date != '0000-00-00 00:00:00' ) {
					$url  = get_permalink( $arcresult );
					if ( $arcresult->post_title )
						$text = strip_tags( apply_filters( 'the_title', $arcresult->post_title, $arcresult->ID ) );
					else
						$text = $arcresult->ID;
					$output .= blogfa_get_archives_link($url, $text, $template);
				}
			}
		}
	}
	if ( $echo )
		echo $output;
	else
		return $output;
}

function blogfa_get_archives_link( $url, $text, $template ) {
	$str = preg_replace( '/<-ArchiveTitle->/', $text, $template );
	$str = preg_replace( '/<-ArchiveLink->/', $url, $str );
	return $str;
}

if ( ! function_exists( 'twentyeleven_comment' ) ) :
/**
 * Template for comments and pingbacks.
 *
 * To override this walker in a child theme without modifying the comments template
 * simply create your own twentyeleven_comment(), and that function will be used instead.
 *
 * Used as a callback by wp_list_comments() for displaying the comments.
 *
 * @since Twenty Eleven 1.0
 */
function twentyeleven_comment( $comment, $args, $depth ) {
	global $blogfa_templater;

	$GLOBALS['comment'] = $comment;
	switch ( $comment->comment_type ) :
		case 'pingback' :
		case 'trackback' :
	?>
	<li class="post pingback">
		<p><?php _e( 'Pingback:', $blogfa_templater->textdomain ); ?> <?php comment_author_link(); ?><?php edit_comment_link( __( 'Edit', $blogfa_templater->textdomain ), '<span class="edit-link">', '</span>' ); ?></p>
	<?php
			break;
		default :
	?>
	<li <?php comment_class(); ?> id="li-comment-<?php comment_ID(); ?>">
		<article id="comment-<?php comment_ID(); ?>" class="comment">
			<footer class="comment-meta">
				<div class="comment-author vcard">
					<?php
						$avatar_size = 68;
						if ( '0' != $comment->comment_parent )
							$avatar_size = 39;

						echo get_avatar( $comment, $avatar_size );

						/* translators: 1: comment author, 2: date and time */
						printf( __( '%1$s on %2$s <span class="says">said:</span>', $blogfa_templater->textdomain ),
							sprintf( '<span class="fn">%s</span>', get_comment_author_link() ),
							sprintf( '<a href="%1$s"><time pubdate datetime="%2$s">%3$s</time></a>',
								esc_url( get_comment_link( $comment->comment_ID ) ),
								get_comment_time( 'c' ),
								/* translators: 1: date, 2: time */
								sprintf( __( '%1$s at %2$s', $blogfa_templater->textdomain ), get_comment_date(), get_comment_time() )
							)
						);
					?>

					<?php edit_comment_link( __( 'Edit', $blogfa_templater->textdomain ), '<span class="edit-link">', '</span>' ); ?>
				</div><!-- .comment-author .vcard -->

				<?php if ( $comment->comment_approved == '0' ) : ?>
					<em class="comment-awaiting-moderation"><?php _e( 'Your comment is awaiting moderation.', $blogfa_templater->textdomain ); ?></em>
					<br />
				<?php endif; ?>

			</footer>

			<div class="comment-content"><?php comment_text(); ?></div>

			<div class="reply">
				<?php comment_reply_link( array_merge( $args, array( 'reply_text' => __( 'Reply <span>&darr;</span>', $blogfa_templater->textdomain ), 'depth' => $depth, 'max_depth' => $args['max_depth'] ) ) ); ?>
			</div><!-- .reply -->
		</article><!-- #comment-## -->

	<?php
			break;
	endswitch;
}
endif; // ends check for twentyeleven_comment()

function blogfa_comment_link() {
	global $blogfa_templater;

	comments_popup_link( __( "Leave a comment", $blogfa_templater->textdomain ), __( '1 Comment', $blogfa_templater->textdomain ), __( '%s Comments', $blogfa_templater->textdomain ), 'comments-link', __( 'Comments are closed.', $blogfa_templater->textdomain ) );
}

function blogfa_shortcode_archive( $attr ) {
	/*
	* Make sure certain we have boolean values instead of strings when needed
	*/
	if ( $attr['show_post_count'] )
		$attr['show_post_count'] = shortcode_string_to_bool( $attr['show_post_count'] );

	if ( $attr['limit'] )
		$attr['limit'] = (int)$attr['limit'];

	$attr['echo'] = false;

	if( function_exists( 'wp_get_jarchives' ) ) {
		ob_start();
		wp_get_jarchives( "type={$attr['type']}&show_post_count={$attr['show_post_count']}" );
		$output = ob_get_clean();
	} else {
		$output = wp_get_archives( $attr );
	}
	return '<ul class="archive">' . $output . '</ul>';
}

function blogfa_shortcode_posts() {
	$query = new WP_Query('posts_per_page=-1');
	if( $query->have_posts() ) :
		$output = '<ul class="posts-list">';
		while( $query->have_posts() ) : $query->the_post();
			$output .= '<li><a href="' . get_permalink() . '">' . get_the_title() . '</a></li>';
		endwhile;
		$output .= '</ul>';
		return $output;
	endif;
	wp_reset_postdata();
	return "";
}

function blogfa_archive_link() {
	global $blogfa_templater;

	if( isset( $blogfa_templater->options['archive_page'] ) ) {
		echo get_permalink( $blogfa_templater->options['archive_page'] );
	} else {
		echo home_url();
	}
}

function blogfa_posts_link() {
	global $blogfa_templater;

	echo 'href="' . get_permalink( $blogfa_templater->options['posts_page'] ) . '"';
}

function blogfa_blog_tags( $template ) {
	$tags = get_tags( "hide_empty=1" );
	$output = '';
	foreach( $tags as $tag ) {
		$str = $template;
		$str = preg_replace( '/<-TagName->/', $tag->name, $str );
		$str = preg_replace( '/<-TagLink->/', get_tag_link( $tag->term_id ), $str );
		$str = preg_replace( '/<-TagCount->/', $tag->count, $str );
		$output .= $str;
	}
	echo $output;
}