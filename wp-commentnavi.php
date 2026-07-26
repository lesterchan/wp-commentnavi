<?php
/*
Plugin Name: WP-CommentNavi
Plugin URI: https://lesterchan.net/portfolio/programming/php/
Description: Adds a more advanced paging navigation for your comments to your WordPress 2.7 and above blog.
Version: 1.12.2
Author: Lester 'GaMerZ' Chan
Author URI: https://lesterchan.net
Text Domain: wp-commentnavi
*/

/*
    Copyright 2023  Lester Chan  (email : lesterchan@gmail.com)

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
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/


### Create Text Domain For Translations
add_action( 'plugins_loaded', 'commentnavi_textdomain' );
function commentnavi_textdomain() {
	load_plugin_textdomain( 'wp-commentnavi', false, dirname( plugin_basename( __FILE__ ) ) );
}


### Function: Comment Navigation Option Menu
add_action('admin_menu', 'commentnavi_menu');
function commentnavi_menu() {
	if (function_exists('add_options_page')) {
		add_options_page(__('CommentNavi', 'wp-commentnavi'), __('CommentNavi', 'wp-commentnavi'), 'manage_options', 'wp-commentnavi/commentnavi-options.php') ;
	}
}


### Function: Enqueue CommentNavi Stylesheets
add_action('wp_enqueue_scripts', 'commentnavi_stylesheets');
function commentnavi_stylesheets() {
	// The override was looked up in TEMPLATEPATH -- the parent theme -- but
	// enqueued from get_stylesheet_directory_uri(), the child theme. Under a child
	// theme those are different directories, so a parent-theme override loaded a
	// URL with nothing behind it and a child-theme override was never found at
	// all. Each candidate is now tested and enqueued from the same place.
	if(file_exists(get_stylesheet_directory().'/commentnavi-css.css')) {
		$commentnavi_css = get_stylesheet_directory_uri().'/commentnavi-css.css';
	} elseif(file_exists(get_template_directory().'/commentnavi-css.css')) {
		$commentnavi_css = get_template_directory_uri().'/commentnavi-css.css';
	} else {
		// Was plugins_url('wp-commentnavi/commentnavi-css.css'), which 404s for
		// anyone who installed the plugin under a different directory name.
		$commentnavi_css = plugins_url('commentnavi-css.css', __FILE__);
	}
	wp_enqueue_style('wp-commentnavi', $commentnavi_css, array(), '1.10', 'all');
}


### Function: Default Option Values
function commentnavi_default_options() {
	return array(
		'pages_text'    => __('Page %CURRENT_PAGE% of %TOTAL_PAGES%','wp-commentnavi'),
		'current_text'  => '%PAGE_NUMBER%',
		'page_text'     => '%PAGE_NUMBER%',
		'first_text'    => __('&laquo; First','wp-commentnavi'),
		'last_text'     => __('Last &raquo;','wp-commentnavi'),
		'next_text'     => __('&raquo;','wp-commentnavi'),
		'prev_text'     => __('&laquo;','wp-commentnavi'),
		'dotright_text' => __('...','wp-commentnavi'),
		'dotleft_text'  => __('...','wp-commentnavi'),
		'style'         => 1,
		'num_pages'     => 5,
		'always_show'   => 0,
	);
}


### Function: Option Keys Rendered As HTML
function commentnavi_text_keys() {
	return array('pages_text', 'current_text', 'page_text', 'first_text', 'last_text', 'next_text', 'prev_text', 'dotright_text', 'dotleft_text');
}


### Function: Read Options, Merged Over The Defaults
function commentnavi_get_options() {
	// Nothing merged defaults before, so a row written by an older version -- or
	// one where a key had simply never been saved -- was read straight out of the
	// array and raised a notice on the front end for every missing key.
	$commentnavi_options = get_option('commentnavi_options', array());
	$commentnavi_options = wp_parse_args(is_array($commentnavi_options) ? $commentnavi_options : array(), commentnavi_default_options());

	// The text options are printed as HTML. They are filtered on save, but an
	// option written by WP-CLI, a migration or another plugin never passes through
	// the settings screen, so they are filtered on read as well.
	foreach(commentnavi_text_keys() as $commentnavi_key) {
		$commentnavi_options[$commentnavi_key] = is_scalar($commentnavi_options[$commentnavi_key]) ? wp_kses_post((string) $commentnavi_options[$commentnavi_key]) : '';
	}

	return $commentnavi_options;
}


### Function: CommentNavi Public Variables
add_filter('query_vars', 'commentnavi_variables');
function commentnavi_variables($public_query_vars) {
	$public_query_vars[] = 'comment-all';
	return $public_query_vars;
}


### Function: Display All Comments
add_action('pre_get_posts', 'commentnavi_allcomments');
function commentnavi_allcomments() {
	if(intval(get_query_var('comment-all')) == 1) {
		set_query_var('comments_per_page', 9999);
	}
}


### Function: Display All Comment Link
function wp_commentnavi_all_comments_link($text = 'View all comments', $display = true) {
	global $post;
	$post_permalink = get_permalink();
	if(strpos($post_permalink, '?') !== false) {
		$post_permalink = "$post_permalink&amp;comment-all=1";
	} else {
		$post_permalink = "$post_permalink?comment-all=1";
	}
	if($display) {
		echo '<a href="'.$post_permalink.'" class="wp-commentnavi-all-comments-link" title="'.$text.'">'.$text.'</a>';
	} else {
		return $post_permalink;
	}
}


### Function: Comment Navigation: Boxed Style Paging
function wp_commentnavi($before = '', $after = '') {
	global $wp_query;
	$paged = intval(get_query_var('cpage'));
	$commentnavi_options = commentnavi_get_options();
	// max(1, ...) because max_num_comment_pages is 0 on a post with no comments,
	// and the 0 used to be carried straight into the label as "Page 1 of 0".
	$max_page = max(1, intval($wp_query->max_num_comment_pages));
	if(empty($paged) || $paged == 0) {
		$paged = 1;
	}
	$pages_to_show = intval($commentnavi_options['num_pages']);
	$pages_to_show_minus_1 = $pages_to_show-1;
	$half_page_start = floor($pages_to_show_minus_1/2);
	$half_page_end = ceil($pages_to_show_minus_1/2);
	$start_page = $paged - $half_page_start;
	if($start_page <= 0) {
		$start_page = 1;
	}
	$end_page = $paged + $half_page_end;
	if(($end_page - $start_page) != $pages_to_show_minus_1) {
		$end_page = $start_page + $pages_to_show_minus_1;
	}
	if($end_page > $max_page) {
		$start_page = $max_page - $pages_to_show_minus_1;
		$end_page = $max_page;
	}
	if($start_page <= 0) {
		$start_page = 1;
	}
	if($max_page > 1 || intval($commentnavi_options['always_show']) == 1) {
		$pages_text = str_replace("%CURRENT_PAGE%", number_format_i18n($paged), $commentnavi_options['pages_text']);
		$pages_text = str_replace("%TOTAL_PAGES%", number_format_i18n($max_page), $pages_text);
		echo $before.'<div class="wp-commentnavi">'."\n";
		switch(intval($commentnavi_options['style'])) {
			case 1:
				if(!empty($pages_text)) {
					echo '<span class="pages">'.$pages_text.'</span>';
				}
				if ($start_page >= 2 && $pages_to_show < $max_page) {
					$first_page_text = str_replace("%TOTAL_PAGES%", number_format_i18n($max_page), $commentnavi_options['first_text']);
					// The title attribute took the option value raw. A double quote in
					// it closed the attribute and anything after it became markup, so
					// the settings screen was an XSS sink for anyone who could reach it.
					echo '<a href="'.esc_url(get_comments_pagenum_link()).'" class="first" title="'.esc_attr(wp_strip_all_tags($first_page_text)).'">'.$first_page_text.'</a>';
					if(!empty($commentnavi_options['dotleft_text'])) {
						echo '<span class="extend">'.$commentnavi_options['dotleft_text'].'</span>';
					}
				}
				previous_comments_link($commentnavi_options['prev_text']);
				for($i = $start_page; $i  <= $end_page; $i++) {
					if($i == $paged) {
						$current_page_text = str_replace("%PAGE_NUMBER%", number_format_i18n($i), $commentnavi_options['current_text']);
						echo '<span class="current">'.$current_page_text.'</span>';
					} else {
						$page_text = str_replace("%PAGE_NUMBER%", number_format_i18n($i), $commentnavi_options['page_text']);
						echo '<a href="'.esc_url(get_comments_pagenum_link($i)).'" class="page" title="'.esc_attr(wp_strip_all_tags($page_text)).'">'.$page_text.'</a>';
					}
				}
				next_comments_link($commentnavi_options['next_text'], $max_page);
				if ($end_page < $max_page) {
					if(!empty($commentnavi_options['dotright_text'])) {
						echo '<span class="extend">'.$commentnavi_options['dotright_text'].'</span>';
					}
					$last_page_text = str_replace("%TOTAL_PAGES%", number_format_i18n($max_page), $commentnavi_options['last_text']);
					echo '<a href="'.esc_url(get_comments_pagenum_link($max_page)).'" class="last" title="'.esc_attr(wp_strip_all_tags($last_page_text)).'">'.$last_page_text.'</a>';
				}
				break;
			// Was "case 2;" -- a semicolon, not a colon. PHP has always accepted it
			// as a synonym, but it is deprecated as of 8.5 and due for removal.
			case 2:
				echo '<form action="'.admin_url('admin.php?page='.plugin_basename(__FILE__)).'" method="get">'."\n";
				echo '<select size="1" onchange="document.location.href = this.options[this.selectedIndex].value;">'."\n";
				for($i = 1; $i  <= $max_page; $i++) {
					$page_num = $i;
					if($page_num == 1) {
						$page_num = 0;
					}
					if($i == $paged) {
						$current_page_text = str_replace("%PAGE_NUMBER%", number_format_i18n($i), $commentnavi_options['current_text']);
						echo '<option value="'.esc_url(get_comments_pagenum_link($page_num)).'" selected="selected" class="current">'.$current_page_text."</option>\n";
					} else {
						$page_text = str_replace("%PAGE_NUMBER%", number_format_i18n($i), $commentnavi_options['page_text']);
						echo '<option value="'.esc_url(get_comments_pagenum_link($page_num)).'">'.$page_text."</option>\n";
					}
				}
				echo "</select>\n";
				echo "</form>\n";
				break;
		}
		echo '</div>'.$after."\n";
	}
}


### Function: Comment Navigation: Drop Down Menu (Deprecated)
function wp_commentnavi_dropdown() {
	wp_commentnavi();
}


### Function: Activate Plugin
register_activation_hook( __FILE__, 'commentnavi_activation' );
function commentnavi_activation( $network_wide ) {
	if ( is_multisite() && $network_wide ) {
		// wp_get_sites() was removed in WordPress 5.1, so network activation was a
		// fatal error on every supported version. 'number' => 0 lifts
		// WP_Site_Query's default cap of 100, which would otherwise skip every site
		// past the hundredth, and restore_current_blog() belongs inside the loop
		// because switch_to_blog() pushes onto a stack.
		$site_ids = get_sites( array( 'fields' => 'ids', 'number' => 0 ) );

		foreach ( $site_ids as $site_id ) {
			switch_to_blog( (int) $site_id );
			commentnavi_activate();
			restore_current_blog();
		}
	} else {
		commentnavi_activate();
	}
}

function commentnavi_activate() {
	// The third argument used to be the string 'CommentNavi Options'. That
	// parameter is add_option()'s deprecated $deprecated, not a description, and
	// passing anything non-empty raises _deprecated_argument().
	add_option( 'commentnavi_options', commentnavi_default_options() );
}