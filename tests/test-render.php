<?php
/**
 * Rendering tests: the page-number window, the links it builds and the tokens
 * it substitutes.
 *
 * Everything in this file is a golden master. It was written against the
 * procedural plugin as it stood at 1.12.2 and passed there before any of the
 * 2.0.0 work began, so a failure means the rewrite changed what themes see.
 *
 * The assertions deliberately go through parsed nodes rather than raw markup:
 * 2.0.0 changes attribute order, quoting and adds ARIA attributes on purpose,
 * and none of that is behaviour a theme depends on. What a theme depends on is
 * which page numbers appear, in what order, and where they link.
 *
 * @package WP-CommentNavi
 */

/**
 * Covers wp_commentnavi().
 */
class WP_CommentNavi_Render_Test extends WP_CommentNavi_TestCase {

	public function test_window_on_first_page() {
		$this->set_options();

		$html = $this->render(
			array(
				'cpage'     => 1,
				'max_pages' => 10,
			)
		);

		$this->assertSame( array( 1, 2, 3, 4, 5 ), $this->page_numbers( $html ) );
	}

	public function test_window_slides_with_the_current_page() {
		$this->set_options();

		$html = $this->render(
			array(
				'cpage'     => 5,
				'max_pages' => 10,
			)
		);

		$this->assertSame( array( 3, 4, 5, 6, 7 ), $this->page_numbers( $html ) );
	}

	public function test_window_clamps_at_the_last_page() {
		// At the end of the run the window stops rather than running past the last
		// page.

		$this->set_options();

		$html = $this->render(
			array(
				'cpage'     => 10,
				'max_pages' => 10,
			)
		);

		$this->assertSame( array( 6, 7, 8, 9, 10 ), $this->page_numbers( $html ) );
	}

	public function test_window_wider_than_the_run() {
		$this->set_options( array( 'num_pages' => 20 ) );

		$html = $this->render(
			array(
				'cpage'     => 2,
				'max_pages' => 3,
			)
		);

		$this->assertSame( array( 1, 2, 3 ), $this->page_numbers( $html ) );
	}

	public function test_num_pages_setting_is_respected() {
		$this->set_options( array( 'num_pages' => 3 ) );

		$html = $this->render(
			array(
				'cpage'     => 5,
				'max_pages' => 10,
			)
		);

		$this->assertSame( array( 4, 5, 6 ), $this->page_numbers( $html ) );
	}

	public function test_current_page_is_marked_once() {
		// Exactly one element is marked as the current page, and it is the one the
		// request asked for.

		$this->set_options();

		$html = $this->render(
			array(
				'cpage'     => 4,
				'max_pages' => 10,
			)
		);

		$this->assertSame( array( '4' ), $this->current_labels( $html ) );
	}

	public function test_numbered_links_point_at_their_comment_page() {
		$this->set_options();

		$html  = $this->render(
			array(
				'cpage'     => 5,
				'max_pages' => 10,
			)
		);
		$found = 0;

		foreach ( $this->nodes( $html ) as $node ) {
			if ( 'a' !== $node['tag'] || ! ctype_digit( $node['text'] ) ) {
				continue;
			}

			$this->assertStringContainsString(
				'cpage=' . $node['text'],
				$node['href'],
				"The link labelled {$node['text']} does not point at comment page {$node['text']}."
			);
			++$found;
		}

		// Four of the five numbers in the window are links; the fifth is current.
		$this->assertSame( 4, $found );
	}

	public function test_pages_text_tokens_are_substituted() {
		$this->set_options();

		$html = $this->render(
			array(
				'cpage'     => 3,
				'max_pages' => 10,
			)
		);

		$this->assertStringContainsString( 'Page 3 of 10', wp_strip_all_tags( $html ) );
		$this->assertStringNotContainsString( '%CURRENT_PAGE%', $html );
		$this->assertStringNotContainsString( '%TOTAL_PAGES%', $html );
	}

	public function test_page_number_token_is_substituted() {
		$this->set_options(
			array(
				'page_text'    => 'p%PAGE_NUMBER%',
				'current_text' => 'c%PAGE_NUMBER%',
			)
		);

		$html = $this->render(
			array(
				'cpage'     => 2,
				'max_pages' => 10,
			)
		);
		$text = wp_strip_all_tags( $html );

		$this->assertStringContainsString( 'p1', $text );
		$this->assertStringContainsString( 'c2', $text );
		$this->assertStringContainsString( 'p3', $text );
		$this->assertStringNotContainsString( '%PAGE_NUMBER%', $html );
	}

	public function test_total_pages_token_in_first_and_last_text() {
		$this->set_options(
			array(
				'first_text' => 'First of %TOTAL_PAGES%',
				'last_text'  => 'Last of %TOTAL_PAGES%',
			)
		);

		$html = $this->render(
			array(
				'cpage'     => 10,
				'max_pages' => 20,
			)
		);
		$text = wp_strip_all_tags( $html );

		$this->assertStringContainsString( 'First of 20', $text );
		$this->assertStringContainsString( 'Last of 20', $text );
	}

	public function test_single_page_prints_nothing() {
		$this->set_options();

		$this->assertSame(
			'',
			trim(
				$this->render(
					array(
						'cpage'     => 1,
						'max_pages' => 1,
					)
				)
			)
		);
	}

	public function test_no_comment_pages_prints_nothing() {
		$this->set_options();

		$this->assertSame(
			'',
			trim(
				$this->render(
					array(
						'cpage'         => 1,
						'max_pages'     => 0,
						'comment_count' => 0,
					)
				)
			)
		);
	}

	public function test_always_show_prints_on_a_single_page() {
		$this->set_options( array( 'always_show' => 1 ) );

		$html = $this->render(
			array(
				'cpage'     => 1,
				'max_pages' => 1,
			)
		);

		$this->assertNotSame( '', trim( $html ) );
		$this->assertStringContainsString( 'Page 1 of 1', wp_strip_all_tags( $html ) );
	}

	public function test_wrapper_class_is_unchanged() {
		$this->set_options();

		$html = $this->render( array( 'max_pages' => 10 ) );

		$this->assertMatchesRegularExpression( '/class=[\'"]wp-commentnavi[\'"]/', $html );
	}

	public function test_before_and_after_wrap_the_output() {
		$this->set_options();

		$html = $this->render(
			array(
				'max_pages' => 10,
				'before'    => '<p id="cn-before">',
				'after'     => '</p>',
			)
		);

		$this->assertStringStartsWith( '<p id="cn-before">', trim( $html ) );
		$this->assertStringEndsWith( '</p>', trim( $html ) );
	}

	public function test_empty_pages_text_hides_the_pages_label() {
		$this->set_options( array( 'pages_text' => '' ) );

		$html = $this->render( array( 'max_pages' => 10 ) );

		$this->assertStringNotContainsString( 'Page 1 of', wp_strip_all_tags( $html ) );
		$this->assertSame( array( 1, 2, 3, 4, 5 ), $this->page_numbers( $html ) );
	}

	public function test_ellipsis_sides() {
		// The ellipsis appears on the side that has hidden pages, and not on the side
		// that does not.

		$this->set_options(
			array(
				'dotleft_text'  => 'LEFTDOTS',
				'dotright_text' => 'RIGHTDOTS',
			)
		);

		$first = $this->render(
			array(
				'cpage'     => 1,
				'max_pages' => 20,
			)
		);
		$this->assertStringNotContainsString( 'LEFTDOTS', $first );
		$this->assertStringContainsString( 'RIGHTDOTS', $first );

		$last = $this->render(
			array(
				'cpage'     => 20,
				'max_pages' => 20,
			)
		);
		$this->assertStringContainsString( 'LEFTDOTS', $last );
		$this->assertStringNotContainsString( 'RIGHTDOTS', $last );
	}

	public function test_dropdown_style() {
		// The drop-down style renders a select with one option per page and the
		// current page selected.

		$this->set_options( array( 'style' => 2 ) );

		$html = $this->render(
			array(
				'cpage'     => 3,
				'max_pages' => 8,
			)
		);

		$options = array();
		$selects = 0;

		foreach ( $this->nodes( $html ) as $node ) {
			if ( 'select' === $node['tag'] ) {
				++$selects;
			}
			if ( 'option' === $node['tag'] ) {
				$options[] = $node['text'];
			}
		}

		$this->assertSame( 1, $selects );
		$this->assertSame( array( '1', '2', '3', '4', '5', '6', '7', '8' ), $options );
		$this->assertSame( array( '3' ), $this->current_labels( $html ) );
	}

	public function test_deprecated_dropdown_tag_still_works() {
		$this->set_options();
		$this->go_to( get_permalink( $this->post_id ) );

		global $wp_query;
		$wp_query->comment_count         = 10;
		$wp_query->max_num_comment_pages = 5;
		set_query_var( 'comments_per_page', 10 );
		set_query_var( 'cpage', 1 );

		ob_start();
		wp_commentnavi_dropdown();
		$html = ob_get_clean();

		$this->assertStringContainsString( 'wp-commentnavi', $html );
	}
}
