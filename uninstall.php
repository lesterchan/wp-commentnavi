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
	// 'number' => 0 lifts WP_Site_Query's default cap of 100, which would
	// otherwise skip every site past the hundredth while reporting success.
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
