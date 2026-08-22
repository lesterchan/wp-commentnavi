<?php
/**
 * Escaping and robustness tests.
 *
 * Unlike test-render.php, the assertions here FAIL against the procedural
 * plugin as it stood at 1.12.2. That is the point of them: they were written
 * against bugs that actually existed, and they are what proves the suite can
 * detect a regression rather than merely agreeing with whatever the code does.
 *
 * @package WP-CommentNavi
 */

/**
 * Covers output escaping and missing-option handling in wp_commentnavi().
 */
class WP_CommentNavi_Escaping_Test extends WP_CommentNavi_TestCase {

	/**
	 * Assert that no element in the markup carries an inline event handler.
	 *
	 * A string search is not good enough here: correctly escaped output still
	 * contains the literal text `onmouseover=` inside an attribute value, as
	 * `title="x&quot; onmouseover=&quot;..."`. Only parsing tells the difference
	 * between that and a real attribute, which is exactly the difference between
	 * safe and exploitable.
	 *
	 * @param string $html Rendered markup.
	 * @return void
	 */
	protected function assertNoEventHandlers( $html ) {
		$doc = new DOMDocument();
		$use = libxml_use_internal_errors( true );
		$doc->loadHTML( '<?xml encoding="utf-8" ?><div id="cn-root">' . $html . '</div>' );
		libxml_clear_errors();
		libxml_use_internal_errors( $use );

		$xpath = new DOMXPath( $doc );
		$found = array();

		// name() rather than ->tagName: DOM spells its properties in camel case and
		// the coding standard requires snake_case for every property read.
		foreach ( $xpath->query( '//*[@onmouseover or @onclick or @onerror or @onload or @onfocus]' ) as $el ) {
			$found[] = (string) $xpath->evaluate( 'name(.)', $el );
		}

		$this->assertSame(
			array(),
			$found,
			'A navigation text option broke out of its attribute and became an event handler.'
		);
	}

	public function test_quote_in_first_text_cannot_break_out_of_the_attribute() {
		// Before 2.0.0 the first-page link was built as
		// title="'.$first_page_text.'" with no escaping at all, and the value reaches
		// that sink straight from the option row. wp_filter_post_kses(), which the
		// settings screen applied on save, does not touch a bare quote in text
		// content, so it was no defence.

		$this->set_options( array( 'first_text' => 'x" onmouseover="XSSPROBE' ) );

		$html = $this->render(
			array(
				'cpage'     => 10,
				'max_pages' => 20,
			)
		);

		$this->assertStringContainsString( 'XSSPROBE', $html, 'The first link did not render, so this test proves nothing.' );
		$this->assertNoEventHandlers( $html );
	}

	public function test_quote_in_last_text_cannot_break_out_of_the_attribute() {
		$this->set_options( array( 'last_text' => 'x" onmouseover="XSSPROBE' ) );

		$html = $this->render(
			array(
				'cpage'     => 1,
				'max_pages' => 20,
			)
		);

		$this->assertStringContainsString( 'XSSPROBE', $html, 'The last link did not render, so this test proves nothing.' );
		$this->assertNoEventHandlers( $html );
	}

	public function test_quote_in_page_text_cannot_break_out_of_the_attribute() {
		$this->set_options( array( 'page_text' => '%PAGE_NUMBER%" onmouseover="XSSPROBE' ) );

		$html = $this->render(
			array(
				'cpage'     => 5,
				'max_pages' => 20,
			)
		);

		$this->assertStringContainsString( 'XSSPROBE', $html, 'No numbered link rendered, so this test proves nothing.' );
		$this->assertNoEventHandlers( $html );
	}

	public function test_script_tag_in_an_option_is_stripped_on_output() {
		// The settings screen filters on save, but an option written by WP-CLI, a
		// migration or another plugin never passes through it, so the renderer has to
		// filter on read as well.

		$this->set_options( array( 'pages_text' => 'Page <script>alert(1)</script>' ) );

		$html = $this->render( array( 'max_pages' => 10 ) );

		$this->assertStringNotContainsString( '<script', strtolower( $html ), 'A script stored in an option is stripped on output as well as on save.' );
	}

	public function test_partial_option_row_renders_without_notices() {
		// This is the shape of the row on any site that last saved its settings under
		// an older version. Before 2.0.0 nothing merged defaults, so every absent key
		// was read straight out of the array and raised a notice on the front end.

		update_option(
			WP_CommentNavi_Options::OPTION,
			array(
				'pages_text' => 'Page %CURRENT_PAGE% of %TOTAL_PAGES%',
				'style'      => 1,
			)
		);

		$html = $this->render(
			array(
				'cpage'     => 2,
				'max_pages' => 10,
			)
		);

		$this->assertStringContainsString( 'Page 2 of 10', wp_strip_all_tags( $html ), 'A partial row renders, taking the defaults for what is missing.' );
		$this->assertNotEmpty( $this->page_numbers( $html ), 'The page-number window collapsed when num_pages was absent.' );
	}

	public function test_missing_option_row_falls_back_to_defaults() {
		delete_option( WP_CommentNavi_Options::OPTION );

		$html = $this->render(
			array(
				'cpage'     => 1,
				'max_pages' => 10,
			)
		);

		$this->assertSame( array( 1, 2, 3, 4, 5 ), $this->page_numbers( $html ), 'A missing row falls back to the defaults rather than rendering nothing.' );
	}

	public function test_always_show_with_no_pages_reports_one_page() {
		// The max_num_comment_pages value is 0 on a post with no comments. The old
		// arithmetic carried that straight into the label and printed "Page 1 of 0".

		$this->set_options( array( 'always_show' => 1 ) );

		$html = $this->render(
			array(
				'cpage'         => 1,
				'max_pages'     => 0,
				'comment_count' => 0,
			)
		);

		$this->assertStringContainsString( 'Page 1 of 1', wp_strip_all_tags( $html ), 'With always_show and no pages, the label reports one page.' );
		$this->assertStringNotContainsString( 'of 0', wp_strip_all_tags( $html ), 'It never reports zero, which would read as a broken thread.' );
	}
}
