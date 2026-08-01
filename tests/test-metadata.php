<?php
/**
 * The release invariants, asserted from the source.
 *
 * Everything §7.2 asks of all nineteen plugins now lives in
 * Plugin_Metadata_TestCase. This file holds what only WP-CommentNavi can say:
 * the version it ships, its class prefix, the breaks its Upgrade Notice has to
 * cover, and the one row the shared uninstall test cannot see.
 *
 * @package WP-CommentNavi
 */

/**
 * WP-CommentNavi's half of the shared metadata contract.
 */
class WP_CommentNavi_Metadata_Test extends Plugin_Metadata_TestCase {

	/**
	 * The version this release ships.
	 *
	 * @return string
	 */
	protected function expected_version() {
		return '2.0.0';
	}

	/**
	 * The prefix every class the plugin declares carries.
	 *
	 * @return string
	 */
	protected function class_prefix() {
		return 'WP_CommentNavi';
	}

	/**
	 * What a site owner updating from the released 1.12.2 would notice.
	 *
	 * One removed template tag that fatals a theme calling it, the query
	 * variable behind it, the settings screen's new address, the option rows
	 * both under their old and new names, and the stylesheet a theme may have
	 * overridden under the old file name.
	 *
	 * @return string[]
	 */
	protected function upgrade_notice_subjects() {
		return array(
			'WordPress 6.8',
			'PHP 8.2',
			'wp_commentnavi_all_comments_link()',
			'comment-all',
			'options-general.php?page=wp-commentnavi',
			'`commentnavi_options`',
			'`wp_commentnavi_options`',
			'`wp_commentnavi_version`',
			'commentnavi-css.css',
			'css/wp-commentnavi.css',
			'--wp-commentnavi-border-color',
		);
	}

	/**
	 * Seed the rows uninstall has to remove, the pre-2.0.0 one included.
	 *
	 * @return void
	 */
	protected function seed_option_rows() {
		WP_CommentNavi_Options::update( WP_CommentNavi_Options::get_defaults() );
		WP_CommentNavi_Options::maybe_upgrade();
		update_option( WP_CommentNavi_Options::LEGACY_OPTION, array( 'style' => 1 ) );
	}

	/**
	 * Write the wp_commentnavi_version marker row.
	 *
	 * @return void
	 */
	protected function write_version_row() {
		WP_CommentNavi_Options::maybe_upgrade();
	}

	/**
	 * Round-trip the settings sanitiser.
	 *
	 * @param array $input What the settings form is pretending to have posted.
	 * @return array
	 */
	protected function sanitize_settings( array $input ) {
		return (array) WP_CommentNavi_Options::sanitize( $input );
	}

	/**
	 * Real settings beside the poison, so the sanitiser actually runs.
	 *
	 * @return array
	 */
	protected function settings_fixture() {
		return array(
			'num_pages' => '4',
			'prev_text' => '&laquo;',
		);
	}

	/**
	 * Register the front-end stylesheet.
	 *
	 * It is only enqueued when the plugin's own CSS is switched on, so the
	 * defaults have to be written before the shared RTL test has a handle to
	 * look at. There is no script to register: this plugin ships none.
	 *
	 * @return void
	 */
	protected function register_plugin_assets() {
		WP_CommentNavi_Options::update( WP_CommentNavi_Options::get_defaults() );

		WP_CommentNavi_Core::stylesheets();
	}

	/**
	 * The pre-2.0.0 settings row goes too, and no LIKE would find it.
	 *
	 * The legacy name does not begin with the plugin's own option prefix, so
	 * the shared uninstall test -- which walks wp_options for wp_commentnavi_%
	 * -- cannot see it. Deleting it is the whole reason the uninstaller names
	 * three rows rather than two.
	 */
	public function test_uninstall_removes_the_pre_2_0_0_settings_row() {
		update_option( WP_CommentNavi_Options::LEGACY_OPTION, array( 'style' => 1 ) );

		$this->assertNotFalse(
			get_option( WP_CommentNavi_Options::LEGACY_OPTION ),
			'There should be a legacy row to remove, or this proves nothing.'
		);

		$this->run_uninstall();

		wp_cache_flush();

		$this->assertFalse(
			get_option( WP_CommentNavi_Options::LEGACY_OPTION ),
			'Uninstalling left the pre-2.0.0 commentnavi_options row behind.'
		);
	}
}
