<?php
/**
 * Option storage tests.
 *
 * @package WP-CommentNavi
 */

/**
 * Covers WP_CommentNavi_Options.
 */
class WP_CommentNavi_Options_Test extends WP_CommentNavi_TestCase {

	public function test_option_rows_carry_the_plugin_prefix() {
		$this->assertSame( 'wp_commentnavi_options', WP_CommentNavi_Options::OPTION );
		$this->assertSame( 'wp_commentnavi_version', WP_CommentNavi_Options::VERSION );
		$this->assertSame( 'commentnavi_options', WP_CommentNavi_Options::LEGACY_OPTION );
	}

	public function test_the_upgrade_moves_the_legacy_row() {
		update_option(
			WP_CommentNavi_Options::LEGACY_OPTION,
			array(
				'num_pages' => 9,
				'style'     => 2,
			)
		);

		WP_CommentNavi_Options::maybe_upgrade();

		$this->assertFalse(
			get_option( WP_CommentNavi_Options::LEGACY_OPTION ),
			'the pre-2.0.0 settings row survived the upgrade.'
		);
		$this->assertSame( 9, WP_CommentNavi_Options::get( 'num_pages' ), 'the legacy settings were not carried over.' );
		$this->assertSame( 2, WP_CommentNavi_Options::get( 'style' ), 'the legacy settings were not carried over.' );
	}

	public function test_the_upgrade_does_not_overwrite_the_new_row() {
		// A settings row already in the new name is never overwritten by a stale
		// legacy row that was left behind.

		WP_CommentNavi_Options::update(
			array_merge( WP_CommentNavi_Options::get_defaults(), array( 'num_pages' => 7 ) )
		);
		update_option( WP_CommentNavi_Options::LEGACY_OPTION, array( 'num_pages' => 9 ) );

		WP_CommentNavi_Options::maybe_upgrade();

		$this->assertSame( 7, WP_CommentNavi_Options::get( 'num_pages' ) );
		$this->assertFalse( get_option( WP_CommentNavi_Options::LEGACY_OPTION ) );
	}

	public function test_the_upgrade_is_idempotent() {
		update_option( WP_CommentNavi_Options::LEGACY_OPTION, array( 'num_pages' => 9 ) );

		WP_CommentNavi_Options::maybe_upgrade();
		$first = WP_CommentNavi_Options::get();

		WP_CommentNavi_Options::maybe_upgrade();

		$this->assertSame( $first, WP_CommentNavi_Options::get() );
	}

	public function test_the_upgrade_resanitises_the_settings() {
		// The upgrade re-sanitises what it finds, so markup an older and laxer
		// release stored is cleaned without anyone visiting the settings screen.

		update_option(
			WP_CommentNavi_Options::LEGACY_OPTION,
			array( 'pages_text' => 'Page <script>alert(1)</script>' )
		);

		WP_CommentNavi_Options::maybe_upgrade();

		$this->assertStringNotContainsString( '<script', WP_CommentNavi_Options::get( 'pages_text' ) );
	}

	public function test_the_upgrade_stamps_the_version_markers() {
		WP_CommentNavi_Options::maybe_upgrade();

		$this->assertSame(
			array(
				'plugin' => WP_COMMENTNAVI_VERSION,
				'db'     => WP_COMMENTNAVI_DB_VERSION,
			),
			get_option( WP_CommentNavi_Options::VERSION )
		);
		$this->assertArrayNotHasKey( 'plugin', WP_CommentNavi_Options::get() );
		$this->assertArrayNotHasKey( 'db', WP_CommentNavi_Options::get() );
	}

	public function test_version_markers_default_to_empty_strings() {
		$this->assertSame(
			array(
				'plugin' => '',
				'db'     => '',
			),
			WP_CommentNavi_Options::get_versions()
		);
	}

	public function test_a_corrupt_version_row_reads_as_empty() {
		update_option( WP_CommentNavi_Options::VERSION, 'not an array' );

		$this->assertSame(
			array(
				'plugin' => '',
				'db'     => '',
			),
			WP_CommentNavi_Options::get_versions()
		);
	}

	public function test_defaults_when_nothing_stored() {
		$this->assertSame( WP_CommentNavi_Options::get_defaults(), WP_CommentNavi_Options::get() );
	}

	public function test_default_values() {
		$defaults = WP_CommentNavi_Options::get_defaults();

		$this->assertSame( 5, $defaults['num_pages'] );
		$this->assertSame( 1, $defaults['style'] );
		$this->assertSame( 0, $defaults['always_show'] );
		$this->assertSame( 1, $defaults['use_commentnavi_css'] );
		$this->assertSame( '%PAGE_NUMBER%', $defaults['page_text'] );
	}

	public function test_larger_page_numbers_are_off_by_default() {
		// This is a deliberate divergence from WP-PageNavi, which defaults the same
		// setting to 3 because it has always had the feature. WP-CommentNavi gains it
		// in 2.0.0, and defaulting it on would add links to the comment navigation of
		// every site that upgrades -- a visible change nobody asked for. Pinned here
		// so it cannot drift back by being copied from the sibling plugin.

		$this->assertSame( 0, WP_CommentNavi_Options::get_defaults()['num_larger_page_numbers'] );
	}

	public function test_partial_row_is_merged_over_the_defaults() {
		// A row written by an older version, missing the keys added since, still
		// yields a complete set of options.

		update_option(
			WP_CommentNavi_Options::OPTION,
			array(
				'num_pages' => 9,
				'style'     => 2,
			)
		);

		$options = WP_CommentNavi_Options::get();

		$this->assertSame( 9, $options['num_pages'] );
		$this->assertSame( 2, $options['style'] );
		$this->assertSame( WP_CommentNavi_Options::get_defaults()['page_text'], $options['page_text'] );
		$this->assertArrayHasKey( 'use_commentnavi_css', $options );
	}

	public function test_reading_a_single_key() {
		$this->assertSame( 5, WP_CommentNavi_Options::get( 'num_pages' ) );
		$this->assertNull( WP_CommentNavi_Options::get( 'no_such_key' ) );
	}

	public function test_non_array_row_falls_back_to_defaults() {
		update_option( WP_CommentNavi_Options::OPTION, 'not an array' );

		$this->assertSame( WP_CommentNavi_Options::get_defaults(), WP_CommentNavi_Options::get() );
	}

	public function test_kses_keeps_an_inline_svg() {
		// Note that wp_kses_post() deletes an SVG rather than cleaning it, which
		// empties the link text -- and a link with empty text is dropped altogether,
		// so the whole previous or next link disappears rather than just its icon.

		$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>';

		$filtered = WP_CommentNavi_Options::kses( $svg );

		$this->assertStringContainsString( '<svg', $filtered );
		$this->assertStringContainsString( '<path', $filtered );
		$this->assertStringContainsString( 'd="M15 18l-6-6 6-6"', $filtered );
	}

	public function test_kses_strips_scripts_and_handlers() {
		$this->assertStringNotContainsString( '<script', WP_CommentNavi_Options::kses( '<script>alert(1)</script>' ) );
		$this->assertStringNotContainsString( 'onload', WP_CommentNavi_Options::kses( '<svg onload="alert(1)"></svg>' ) );
	}

	public function test_kses_filters_the_xlink_href_protocol() {
		// Note that wp_kses() only protocol-filters the attribute names in
		// wp_kses_uri_attributes(), and xlink:href is not one of them by default. The
		// list is widened for the duration of the call precisely so that this payload
		// is caught rather than waved through.

		$filtered = WP_CommentNavi_Options::kses( '<svg><use xlink:href="javascript:alert(1)"></use></svg>' );

		$this->assertStringNotContainsString( 'javascript:', $filtered );
	}

	public function test_kses_does_not_leave_the_uri_list_widened() {
		WP_CommentNavi_Options::kses( '<svg></svg>' );

		$this->assertNotContains( 'xlink:href', wp_kses_uri_attributes() );
	}

	public function test_kses_rejects_a_non_scalar() {
		// An array where a text option belongs yields an empty string rather than a
		// PHP 8 array-to-string notice.

		$this->assertSame( '', WP_CommentNavi_Options::kses( array( 'a', 'b' ) ) );
	}
}
