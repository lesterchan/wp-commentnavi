<?php
/**
 * Public template tags.
 *
 * These are the plugin's API. Their names and signatures are relied on by
 * themes and must not change.
 *
 * @package WP-CommentNavi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Template tag: comment navigation.
 *
 * Belongs in the theme's comments.php. It reads figures that core only
 * populates inside comments_template(), so it produces nothing anywhere else.
 *
 * Accepts either an array of arguments or, for backwards compatibility, up to
 * three positional arguments: $before, $after, $options. Every release before
 * 2.0.0 took the positional form, so that is what existing themes pass.
 *
 * @param array|string $args {
 *     Optional. Arguments, or the 'before' string in the positional form.
 *
 *     @type string $before        Markup to place before the navigation.
 *     @type string $after         Markup to place after the navigation.
 *     @type string $wrapper_tag   Tag name for the wrapper element. Default 'div'.
 *     @type string $wrapper_class Class for the wrapper element. Default 'wp-commentnavi'.
 *     @type array  $options       Overrides for the settings saved in WP-Admin.
 *     @type object $query         Query to paginate. Default the main query.
 *     @type bool   $echo          Whether to print the markup. Default true.
 * }
 * @return string|void The markup when 'echo' is false, otherwise nothing.
 */
function wp_commentnavi( $args = array() ) {
	if ( ! is_array( $args ) ) {
		$argv = func_get_args();

		$args = array();
		foreach ( array( 'before', 'after', 'options' ) as $i => $key ) {
			$args[ $key ] = isset( $argv[ $i ] ) ? $argv[ $i ] : '';
		}
	}

	return WP_CommentNavi_Core::render( $args );
}

/**
 * Template tag: drop down menu.
 *
 * @deprecated 1.10 Use wp_commentnavi() with the 'style' option set to 2.
 *
 * @return void
 */
function wp_commentnavi_dropdown() {
	wp_commentnavi();
}
