<?php
/**
 * Tests for the capabilities added in 2.0.0.
 *
 * @package WP-CommentNavi
 */

/**
 * Covers larger page numbers, the class-name filters, the stylesheet toggle and
 * the array argument form of the template tag.
 */
class WP_CommentNavi_Features_Test extends WP_CommentNavi_TestCase {

	public function test_larger_page_numbers_after_the_window() {
		$this->set_options(
			array(
				'num_larger_page_numbers'      => 3,
				'larger_page_numbers_multiple' => 10,
			)
		);

		$html = $this->render(
			array(
				'cpage'     => 1,
				'max_pages' => 100,
			)
		);

		$this->assertSame( array( 1, 2, 3, 4, 5, 10, 20, 30 ), $this->page_numbers( $html ) );
	}

	public function test_smaller_page_numbers_before_the_window() {
		$this->set_options(
			array(
				'num_larger_page_numbers'      => 3,
				'larger_page_numbers_multiple' => 10,
			)
		);

		$html = $this->render(
			array(
				'cpage'     => 100,
				'max_pages' => 100,
			)
		);

		$this->assertSame( array( 10, 20, 30, 96, 97, 98, 99, 100 ), $this->page_numbers( $html ) );
	}

	public function test_larger_page_numbers_absent_by_default() {
		// With the default of 0, no larger page numbers appear at all -- which is
		// what keeps an upgraded site's navigation looking the way it did.

		$this->set_options( array( 'num_larger_page_numbers' => 0 ) );

		$html = $this->render(
			array(
				'cpage'     => 1,
				'max_pages' => 100,
			)
		);

		$this->assertSame( array( 1, 2, 3, 4, 5 ), $this->page_numbers( $html ) );
	}

	public function test_larger_page_numbers_multiple() {
		$this->set_options(
			array(
				'num_larger_page_numbers'      => 2,
				'larger_page_numbers_multiple' => 25,
			)
		);

		$html = $this->render(
			array(
				'cpage'     => 1,
				'max_pages' => 100,
			)
		);

		$this->assertSame( array( 1, 2, 3, 4, 5, 25, 50 ), $this->page_numbers( $html ) );
	}

	public function test_class_name_filters() {
		$this->set_options();

		$replace = static function () {
			return 'cn-custom-current';
		};
		add_filter( 'wp_commentnavi_class_current', $replace );

		$html = $this->render(
			array(
				'cpage'     => 3,
				'max_pages' => 10,
			)
		);

		remove_filter( 'wp_commentnavi_class_current', $replace );

		$this->assertStringContainsString( 'cn-custom-current', $html );
	}

	public function test_output_filter() {
		$this->set_options();

		$replace = static function () {
			return 'REPLACED';
		};
		add_filter( 'wp_commentnavi', $replace );

		$html = $this->render( array( 'max_pages' => 10 ) );

		remove_filter( 'wp_commentnavi', $replace );

		$this->assertSame( 'REPLACED', trim( $html ) );
	}

	public function test_stylesheet_toggle() {
		$this->set_options( array( 'use_commentnavi_css' => 1 ) );
		WP_CommentNavi_Core::stylesheets();
		$this->assertTrue( wp_style_is( 'wp-commentnavi', 'enqueued' ), 'With the stylesheet setting on, the stylesheet is enqueued.' );

		wp_dequeue_style( 'wp-commentnavi' );
		wp_deregister_style( 'wp-commentnavi' );

		$this->set_options( array( 'use_commentnavi_css' => 0 ) );
		WP_CommentNavi_Core::stylesheets();
		$this->assertFalse( wp_style_is( 'wp-commentnavi', 'enqueued' ), 'With the stylesheet setting off, nothing is enqueued.' );
	}

	public function test_stylesheet_url_is_not_hardcoded() {
		// The stylesheet URL is built from the main plugin file, not a hardcoded
		// directory name, so the plugin works under any directory name.

		$this->set_options( array( 'use_commentnavi_css' => 1 ) );
		WP_CommentNavi_Core::stylesheets();

		$styles = wp_styles();
		$src    = $styles->registered['wp-commentnavi']->src;

		$this->assertStringContainsString( 'css/wp-commentnavi.css', $src );
		$this->assertSame( WP_COMMENTNAVI_URL . 'css/wp-commentnavi.css', $src );
	}

	public function test_array_argument_form() {
		// The template tag accepts an array of arguments as well as the positional
		// form every previous release used.

		$this->set_options();

		$this->go_to( get_permalink( $this->post_id ) );

		global $wp_query;
		$wp_query->comment_count         = 10;
		$wp_query->max_num_comment_pages = 10;
		set_query_var( 'comments_per_page', 10 );
		set_query_var( 'cpage', 1 );

		$markup = wp_commentnavi(
			array(
				'echo'          => false,
				'wrapper_class' => 'my-nav',
				'wrapper_tag'   => 'nav',
			)
		);

		$this->assertIsString( $markup, 'The array argument form returns markup rather than echoing it.' );
		$this->assertStringContainsString( 'my-nav', $markup );
		$this->assertStringStartsWith( '<nav', trim( $markup ) );
	}

	public function test_per_call_option_override() {
		$this->set_options( array( 'num_pages' => 5 ) );

		$this->go_to( get_permalink( $this->post_id ) );

		global $wp_query;
		$wp_query->comment_count         = 10;
		$wp_query->max_num_comment_pages = 20;
		set_query_var( 'comments_per_page', 10 );
		set_query_var( 'cpage', 1 );

		$markup = wp_commentnavi(
			array(
				'echo'    => false,
				'options' => array( 'num_pages' => 3 ),
			)
		);

		$this->assertSame( array( 1, 2, 3 ), $this->page_numbers( $markup ) );
		$this->assertSame( 5, WP_CommentNavi_Options::get( 'num_pages' ) );
	}

	public function test_view_all_comments_feature_is_removed() {
		// It hooked pre_get_posts with no is_main_query() or is_singular() guard and
		// forced comments_per_page to 9999, so any visitor could trigger an unbounded
		// comment query on any URL by appending ?comment-all=1. Removing the template
		// tag is a breaking change for themes that call it, which is why it is pinned
		// here rather than left to be quietly reintroduced.

		$this->assertFalse( function_exists( 'wp_commentnavi_all_comments_link' ), 'The withdrawn all-comments helper is gone, not merely undocumented.' );

		$this->go_to( add_query_arg( 'comment-all', 1, get_permalink( $this->post_id ) ) );

		$this->assertNotContains( 'comment-all', $GLOBALS['wp']->public_query_vars );
	}

	public function test_wrapper_tag_is_escaped() {
		$this->set_options();

		$this->go_to( get_permalink( $this->post_id ) );

		global $wp_query;
		$wp_query->comment_count         = 10;
		$wp_query->max_num_comment_pages = 10;
		set_query_var( 'comments_per_page', 10 );
		set_query_var( 'cpage', 1 );

		$markup = wp_commentnavi(
			array(
				'echo'        => false,
				'wrapper_tag' => 'div onclick="XSSPROBE"',
			)
		);

		$doc = new DOMDocument();
		$use = libxml_use_internal_errors( true );
		$doc->loadHTML( '<?xml encoding="utf-8" ?><div id="cn-root">' . $markup . '</div>' );
		libxml_clear_errors();
		libxml_use_internal_errors( $use );

		$xpath = new DOMXPath( $doc );
		$this->assertSame( 0, $xpath->query( '//*[@onclick]' )->length );
	}
}
