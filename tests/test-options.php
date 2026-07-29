<?php
/**
 * Option storage tests.
 *
 * @package WP-CommentNavi
 */

/**
 * Covers WP_CommentNavi_Options.
 */
class Test_CommentNavi_Options extends WP_UnitTestCase {

	/**
	 * Reset the options between tests.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		delete_option( WP_CommentNavi_Options::OPTION );
	}

	/**
	 * The option key must never change: it is what carries existing installs
	 * across the 2.0.0 upgrade without a migration.
	 *
	 * @return void
	 */
	public function test_option_key_is_unchanged() {
		$this->assertSame( 'commentnavi_options', WP_CommentNavi_Options::OPTION );
	}

	/**
	 * With nothing stored, the defaults are returned.
	 *
	 * @return void
	 */
	public function test_defaults_when_nothing_stored() {
		$this->assertSame( WP_CommentNavi_Options::get_defaults(), WP_CommentNavi_Options::get() );
	}

	/**
	 * The default values are the ones the plugin has always shipped.
	 *
	 * @return void
	 */
	public function test_default_values() {
		$defaults = WP_CommentNavi_Options::get_defaults();

		$this->assertSame( 5, $defaults['num_pages'] );
		$this->assertSame( 1, $defaults['style'] );
		$this->assertSame( 0, $defaults['always_show'] );
		$this->assertSame( 1, $defaults['use_commentnavi_css'] );
		$this->assertSame( '%PAGE_NUMBER%', $defaults['page_text'] );
	}

	/**
	 * Larger page numbers default to off.
	 *
	 * This is a deliberate divergence from WP-PageNavi, which defaults the same
	 * setting to 3 because it has always had the feature. WP-CommentNavi gains it
	 * in 2.0.0, and defaulting it on would add links to the comment navigation of
	 * every site that upgrades -- a visible change nobody asked for. Pinned here
	 * so it cannot drift back by being copied from the sibling plugin.
	 *
	 * @return void
	 */
	public function test_larger_page_numbers_are_off_by_default() {
		$this->assertSame( 0, WP_CommentNavi_Options::get_defaults()['num_larger_page_numbers'] );
	}

	/**
	 * A row written by an older version, missing the keys added since, still
	 * yields a complete set of options.
	 *
	 * @return void
	 */
	public function test_partial_row_is_merged_over_the_defaults() {
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

	/**
	 * A single key can be read on its own, and an unknown one is null.
	 *
	 * @return void
	 */
	public function test_reading_a_single_key() {
		$this->assertSame( 5, WP_CommentNavi_Options::get( 'num_pages' ) );
		$this->assertNull( WP_CommentNavi_Options::get( 'no_such_key' ) );
	}

	/**
	 * A corrupt option row does not take the plugin down with it.
	 *
	 * @return void
	 */
	public function test_non_array_row_falls_back_to_defaults() {
		update_option( WP_CommentNavi_Options::OPTION, 'not an array' );

		$this->assertSame( WP_CommentNavi_Options::get_defaults(), WP_CommentNavi_Options::get() );
	}

	/**
	 * An inline SVG arrow survives the filter.
	 *
	 * Note that wp_kses_post() deletes an SVG rather than cleaning it, which
	 * empties the link text -- and a link with empty text is dropped altogether,
	 * so the whole previous or next link disappears rather than just its icon.
	 *
	 * @return void
	 */
	public function test_kses_keeps_an_inline_svg() {
		$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>';

		$filtered = WP_CommentNavi_Options::kses( $svg );

		$this->assertStringContainsString( '<svg', $filtered );
		$this->assertStringContainsString( '<path', $filtered );
		$this->assertStringContainsString( 'd="M15 18l-6-6 6-6"', $filtered );
	}

	/**
	 * Script tags and event handlers do not survive it.
	 *
	 * @return void
	 */
	public function test_kses_strips_scripts_and_handlers() {
		$this->assertStringNotContainsString( '<script', WP_CommentNavi_Options::kses( '<script>alert(1)</script>' ) );
		$this->assertStringNotContainsString( 'onload', WP_CommentNavi_Options::kses( '<svg onload="alert(1)"></svg>' ) );
	}

	/**
	 * A javascript: URL in xlink:href is filtered.
	 *
	 * Note that wp_kses() only protocol-filters the attribute names in
	 * wp_kses_uri_attributes(), and xlink:href is not one of them by default. The
	 * list is widened for the duration of the call precisely so that this payload
	 * is caught rather than waved through.
	 *
	 * @return void
	 */
	public function test_kses_filters_the_xlink_href_protocol() {
		$filtered = WP_CommentNavi_Options::kses( '<svg><use xlink:href="javascript:alert(1)"></use></svg>' );

		$this->assertStringNotContainsString( 'javascript:', $filtered );
	}

	/**
	 * Widening the URI attribute list is scoped to the one call.
	 *
	 * @return void
	 */
	public function test_kses_does_not_leave_the_uri_list_widened() {
		WP_CommentNavi_Options::kses( '<svg></svg>' );

		$this->assertNotContains( 'xlink:href', wp_kses_uri_attributes() );
	}

	/**
	 * An array where a text option belongs yields an empty string rather than a
	 * PHP 8 array-to-string notice.
	 *
	 * @return void
	 */
	public function test_kses_rejects_a_non_scalar() {
		$this->assertSame( '', WP_CommentNavi_Options::kses( array( 'a', 'b' ) ) );
	}
}
