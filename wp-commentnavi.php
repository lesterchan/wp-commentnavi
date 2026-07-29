<?php
/**
 * Plugin Name: WP-CommentNavi
 * Plugin URI: https://lesterchan.net/portfolio/programming/php/
 * Description: Adds a more advanced paging navigation for your comments to your WordPress blog
 * Version: 2.0.0
 * Requires at least: 6.8
 * Requires PHP: 8.2
 * Author: Lester 'GaMerZ' Chan
 * Author URI: https://lesterchan.net
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-commentnavi
 * Domain Path: /languages
 *
 * @package WP-CommentNavi
 */

/*
	Copyright 2026  Lester Chan  (email : lesterchan@gmail.com)

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

// Prevent direct access.
defined( 'ABSPATH' ) || exit;

// Plugin version.
define( 'WP_COMMENTNAVI_VERSION', '2.0.0' );

// Main plugin file, for resolving paths from the includes.
define( 'WP_COMMENTNAVI_MAIN_FILE', __FILE__ );

require_once __DIR__ . '/includes/class-commentnavi-options.php';
require_once __DIR__ . '/includes/class-commentnavi-call.php';
require_once __DIR__ . '/includes/class-commentnavi-core.php';
require_once __DIR__ . '/includes/template-tags.php';

CommentNavi_Core::init();

if ( is_admin() ) {
	require_once __DIR__ . '/includes/class-commentnavi-admin.php';
	CommentNavi_Admin::init();
}

register_activation_hook( __FILE__, 'wp_commentnavi_activate' );

/**
 * Seed the option row on activation.
 *
 * Calling add_option() leaves an existing row alone, and the options are merged
 * over the defaults on every read anyway, so this is a convenience rather than
 * a requirement — the plugin works correctly with no row at all.
 *
 * The network branch replaces a wp_get_sites() call that WordPress removed in
 * 5.1, which made network activation a fatal error. 'number' => 0 lifts
 * WP_Site_Query's default cap of 100, and restore_current_blog() runs inside
 * the loop because switch_to_blog() pushes onto a stack.
 *
 * @param bool $network_wide Whether the plugin is being activated network-wide.
 * @return void
 */
function wp_commentnavi_activate( $network_wide = false ) {
	if ( is_multisite() && $network_wide ) {
		$site_ids = get_sites(
			array(
				'fields' => 'ids',
				'number' => 0,
			)
		);

		foreach ( $site_ids as $site_id ) {
			switch_to_blog( (int) $site_id );
			add_option( CommentNavi_Options::OPTION_NAME, CommentNavi_Options::get_defaults() );
			restore_current_blog();
		}

		return;
	}

	add_option( CommentNavi_Options::OPTION_NAME, CommentNavi_Options::get_defaults() );
}
