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
	Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
*/

// Prevent direct access.
defined( 'ABSPATH' ) || exit;

define( 'WP_COMMENTNAVI_VERSION', '2.0.0' );
define( 'WP_COMMENTNAVI_DB_VERSION', '1' );
define( 'WP_COMMENTNAVI_SLUG', 'wp-commentnavi' );
define( 'WP_COMMENTNAVI_MAIN_FILE', __FILE__ );
define( 'WP_COMMENTNAVI_DIR', plugin_dir_path( __FILE__ ) );
define( 'WP_COMMENTNAVI_URL', plugin_dir_url( __FILE__ ) );

require_once WP_COMMENTNAVI_DIR . 'includes/class-wp-commentnavi.php';

WP_CommentNavi::init();
