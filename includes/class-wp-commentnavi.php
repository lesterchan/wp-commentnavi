<?php
/**
 * Plugin bootstrap.
 *
 * @package WP-CommentNavi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Loads the plugin's components and hands each of them their hooks.
 *
 * Everything the plugin does hangs off this one entry point, so the main file
 * carries nothing but the header, the constants and a single call.
 */
class WP_CommentNavi {

	/**
	 * Load the components and wire them up.
	 *
	 * @return void
	 */
	public static function init() {
		require_once WP_COMMENTNAVI_DIR . 'includes/class-wp-commentnavi-options.php';
		require_once WP_COMMENTNAVI_DIR . 'includes/class-wp-commentnavi-call.php';
		require_once WP_COMMENTNAVI_DIR . 'includes/class-wp-commentnavi-core.php';
		require_once WP_COMMENTNAVI_DIR . 'includes/template-tags.php';

		// Must be registered at file-load time, which is when this runs.
		register_activation_hook( WP_COMMENTNAVI_MAIN_FILE, array( __CLASS__, 'activate' ) );

		WP_CommentNavi_Core::init();

		if ( is_admin() ) {
			require_once WP_COMMENTNAVI_DIR . 'includes/class-wp-commentnavi-settings.php';

			WP_CommentNavi_Settings::init();
		}
	}

	/**
	 * Activation: run the upgrade routine so the rows are in their current shape.
	 *
	 * That folds in the pre-2.0.0 settings row and stamps the version markers. The
	 * plugin works correctly with no settings row at all, because the options are
	 * merged over the defaults on every read, so this is about carrying an existing
	 * install forward rather than about seeding a new one.
	 *
	 * The network branch replaces a wp_get_sites() call. That function has been
	 * deprecated since WordPress 4.6 and returns only the first 100 sites, so
	 * network activation silently skipped the rest. 'number' => 0 lifts
	 * WP_Site_Query's default cap of 100, and restore_current_blog() runs inside
	 * the loop because switch_to_blog() pushes onto a stack.
	 *
	 * @param bool $network_wide Whether the plugin is being activated network-wide.
	 * @return void
	 */
	public static function activate( $network_wide = false ) {
		if ( is_multisite() && $network_wide ) {
			$site_ids = get_sites(
				array(
					'fields' => 'ids',
					'number' => 0,
				)
			);

			foreach ( $site_ids as $site_id ) {
				switch_to_blog( (int) $site_id );
				WP_CommentNavi_Options::maybe_upgrade();
				restore_current_blog();
			}

			return;
		}

		WP_CommentNavi_Options::maybe_upgrade();
	}
}
