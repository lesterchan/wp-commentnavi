<?php
/**
 * Plugin Name: WP-CommentNavi E2E theme shim
 * Description: Calls wp_commentnavi() where a theme would, so the browser suite has something to look at. Loaded only in the wp-env tests environment.
 *
 * WP-CommentNavi is a template tag. It hooks nothing into the front end by
 * design: a theme calls wp_commentnavi() in place of the_comments_navigation(),
 * and if no theme calls it the plugin renders nothing at all -- correctly.
 *
 * That makes the theme the integration point, and a bundled theme does not call
 * it. So this file plays the part of a theme that does. Without it every
 * front-end assertion in the suite would be checking that an empty page is
 * still empty, and would pass whatever the plugin did.
 *
 * It is a fixture, not a shipped file: it lives under tests/ and is mapped into
 * wp-content/mu-plugins for the tests environment only, by .wp-env.json.
 *
 * @package WP-CommentNavi
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * Web requests only.
 *
 * wp-env maps this directory into the tests environment, and PHPUnit runs in
 * that same environment -- so without this guard the fixture below is loaded by
 * the unit suite as well, and a filter forcing comment paging on made
 * test_paging_disabled_renders_nothing fail while the plugin was behaving
 * perfectly. A browser fixture has no business being visible to a test that is
 * not driving a browser.
 */
if ( 'cli' === PHP_SAPI ) {
	return;
}

/**
 * Print the navigation between the comment list and the comment form.
 *
 * The comment_form_before hook fires inside comment_form(), which a classic
 * theme calls after wp_list_comments() -- so this lands exactly where a theme's
 * own the_comments_navigation() call sits.
 *
 * @return void
 */
function wp_commentnavi_e2e_after_list() {
	echo '<div id="wp-commentnavi-e2e">';
	wp_commentnavi();
	echo '</div>';

	echo '<div id="wp-commentnavi-e2e-dropdown">';
	wp_commentnavi_dropdown();
	echo '</div>';
}
add_action( 'comment_form_before', 'wp_commentnavi_e2e_after_list' );

/**
 * Switch WordPress comment paging on for the tests environment.
 *
 * This is the fixture the whole suite rests on, and it is easy to get wrong:
 * comment paging is OFF in a default install, so a post with a thousand comments
 * still has exactly one page of them and WP-CommentNavi is correct to render
 * nothing. A suite that missed this would assert that an empty page is still
 * empty and pass with the plugin deactivated.
 *
 * Filters rather than update_option(): these three are not in core's REST
 * settings allowlist, so the suite cannot set them over the API -- it tried, and
 * silently changed nothing at all. A filter also cannot be left behind by a test
 * that fails halfway.
 *
 * @return void
 */
function wp_commentnavi_e2e_comment_paging() {
	add_filter(
		'pre_option_page_comments',
		static function () {
			return '1';
		}
	);

	add_filter(
		'pre_option_comments_per_page',
		static function () {
			// Five, matching the demo site's Settings -> Discussion. Smaller
			// pages mean more of them for the same fixture, and the number of
			// pages is the only thing this plugin is about.
			return '5';
		}
	);

	add_filter(
		'pre_option_default_comments_page',
		static function () {
			return 'oldest';
		}
	);
}
add_action( 'init', 'wp_commentnavi_e2e_comment_paging' );

/**
 * A route the suite calls to put the settings back.
 *
 * Deleting the row rather than writing the defaults into it: the plugin merges
 * whatever is stored over its own defaults on read, so "absent" is the true
 * starting state, and a copy of the defaults kept in a test file is a second
 * place holding one fact -- which is how a test ends up asserting last year's
 * defaults.
 *
 * The plugin's own option is not exposed through the REST API, correctly: it is
 * a settings row for an admin screen, not content. This route exists only in the
 * tests environment, and requires the same capability the settings screen does.
 *
 * @return void
 */
function wp_commentnavi_e2e_register_reset_route() {
	register_rest_route(
		'wp-commentnavi-e2e/v1',
		'/reset',
		array(
			'methods'             => 'POST',
			'callback'            => static function () {
				delete_option( 'wp_commentnavi_options' );

				return array( 'reset' => true );
			},
			'permission_callback' => static function () {
				return current_user_can( 'manage_options' );
			},
		)
	);
}
add_action( 'rest_api_init', 'wp_commentnavi_e2e_register_reset_route' );
