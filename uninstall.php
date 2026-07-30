<?php
/**
 * Uninstaller: removes everything the plugin stored.
 *
 * @package WP-CommentNavi
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * Delete the plugin's options for the current site.
 *
 * @return void
 */
function wp_commentnavi_uninstall_site() {
	delete_option( 'wp_commentnavi_options' );
	delete_option( 'wp_commentnavi_version' );

	// The settings row was called commentnavi_options up to 1.12.2. The upgrade
	// routine deletes it, so this only catches an install that never reached
	// wp-admin between updating and being removed.
	delete_option( 'commentnavi_options' );
}

if ( is_multisite() ) {
	// Three separate bugs lived in this block before 2.0.0.
	//
	// wp_get_sites() has been deprecated since WordPress 4.6 and returns only
	// the first 100 sites, so multisite uninstall was silently partial.
	//
	// 'number' => 0 is required: WP_Site_Query defaults it to 100, so without it
	// a network larger than that keeps the option on every site past the
	// hundredth, and uninstall still reports success.
	//
	// restore_current_blog() has to run inside the loop. switch_to_blog() pushes
	// onto a stack, so restoring once after the loop left it wound up by every
	// site but the last.
	$site_ids = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);

	foreach ( $site_ids as $site_id ) {
		switch_to_blog( (int) $site_id );
		wp_commentnavi_uninstall_site();
		restore_current_blog();
	}
} else {
	wp_commentnavi_uninstall_site();
}
