<?php
/**
 * The base class every test in this plugin extends.
 *
 * @package WP-CommentNavi
 */

/**
 * Puts every test back on an unconfigured install, and sets up a singular
 * request with a known comment count so the template tag has something to
 * paginate.
 *
 * The tag reads $wp_query->comment_count and $wp_query->max_num_comment_pages,
 * which core only populates inside comments_template(). Rather than render a
 * whole theme, the harness puts a real singular query in place with go_to() --
 * which is what get_comments_pagenum_link(), previous_comments_link() and
 * next_comments_link() need in order to build URLs at all -- and then assigns
 * those two properties directly, exactly as comments_template() would.
 */
class WP_CommentNavi_TestCase extends WP_UnitTestCase {

	/**
	 * The post the navigation is rendered against.
	 *
	 * @var int
	 */
	protected $post_id;

	/**
	 * Create the post every test paginates.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		$this->post_id = self::factory()->post->create(
			array(
				'post_title'  => 'CommentNavi test post',
				'post_status' => 'publish',
			)
		);

		update_option( 'page_comments', 1 );
		update_option( 'comments_per_page', 10 );
		update_option( 'default_comments_page', 'newest' );

		delete_option( WP_CommentNavi_Options::OPTION );
		delete_option( WP_CommentNavi_Options::VERSION );
		delete_option( WP_CommentNavi_Options::LEGACY_OPTION );
	}

	/**
	 * Absolute path to a file in the plugin directory.
	 *
	 * @param string $relative Path relative to the plugin root.
	 * @return string
	 */
	protected function plugin_path( $relative = '' ) {
		return rtrim( dirname( __DIR__ ) . '/' . ltrim( $relative, '/' ), '/' );
	}

	/**
	 * The contents of a file in the plugin directory.
	 *
	 * @param string $relative Path relative to the plugin root.
	 * @return string
	 */
	protected function plugin_file_contents( $relative ) {
		return (string) file_get_contents( $this->plugin_path( $relative ) );
	}

	/**
	 * The option values the plugin has shipped with since 1.00.
	 *
	 * Written out in full because the procedural version of the plugin merged no
	 * defaults: it read whatever was in the row and warned on anything missing.
	 * The keys added in 2.0.0 are included too -- the old code simply ignores
	 * them, which is what lets the same test run against both versions.
	 *
	 * @return array
	 */
	protected function default_options() {
		return array(
			'pages_text'                   => 'Page %CURRENT_PAGE% of %TOTAL_PAGES%',
			'current_text'                 => '%PAGE_NUMBER%',
			'page_text'                    => '%PAGE_NUMBER%',
			'first_text'                   => '&laquo; First',
			'last_text'                    => 'Last &raquo;',
			'next_text'                    => '&raquo;',
			'prev_text'                    => '&laquo;',
			'dotright_text'                => '...',
			'dotleft_text'                 => '...',
			'style'                        => 1,
			'num_pages'                    => 5,
			'always_show'                  => 0,
			// Added in 2.0.0. Zero here keeps the page-number window identical to
			// what the pre-2.0.0 code produced, so the window assertions below are
			// a like-for-like comparison across the rewrite. The larger page
			// numbers get their own tests.
			'num_larger_page_numbers'      => 0,
			'larger_page_numbers_multiple' => 10,
			'use_commentnavi_css'          => 1,
		);
	}

	/**
	 * Write the option row, overriding individual keys.
	 *
	 * @param array $overrides Values to change.
	 * @return void
	 */
	protected function set_options( array $overrides = array() ) {
		update_option( WP_CommentNavi_Options::OPTION, array_merge( $this->default_options(), $overrides ) );
	}

	/**
	 * Render the navigation for a given pagination state.
	 *
	 * @param array $args {
	 *     Optional. The pagination state to render.
	 *
	 *     @type int    $cpage         Current comment page. Default 1.
	 *     @type int    $max_pages     Total comment pages. Default 10.
	 *     @type int    $comment_count Comments on this page. Default 10.
	 *     @type int    $per_page      Comments per page. Default 10.
	 *     @type string $before        Markup before the wrapper.
	 *     @type string $after         Markup after the wrapper.
	 * }
	 * @return string The printed markup.
	 */
	protected function render( array $args = array() ) {
		$args = array_merge(
			array(
				'cpage'         => 1,
				'max_pages'     => 10,
				'comment_count' => 10,
				'per_page'      => 10,
				'before'        => '',
				'after'         => '',
			),
			$args
		);

		$this->go_to( add_query_arg( 'cpage', $args['cpage'], get_permalink( $this->post_id ) ) );

		global $wp_query;
		$wp_query->comment_count         = $args['comment_count'];
		$wp_query->max_num_comment_pages = $args['max_pages'];
		set_query_var( 'comments_per_page', $args['per_page'] );
		set_query_var( 'cpage', $args['cpage'] );

		ob_start();
		wp_commentnavi( $args['before'], $args['after'] );
		return ob_get_clean();
	}

	/**
	 * Parse rendered markup into a flat list of elements.
	 *
	 * Comparing parsed nodes rather than raw strings keeps the assertions
	 * independent of attribute order and quoting style, both of which the 2.0.0
	 * rewrite changes deliberately.
	 *
	 * @param string $html Rendered markup.
	 * @return array List of arrays with tag, class, text and href keys.
	 */
	protected function nodes( $html ) {
		if ( '' === trim( $html ) ) {
			return array();
		}

		$doc = new DOMDocument();
		$use = libxml_use_internal_errors( true );
		$doc->loadHTML( '<?xml encoding="utf-8" ?><div id="cn-root">' . $html . '</div>' );
		libxml_clear_errors();
		libxml_use_internal_errors( $use );

		$xpath = new DOMXPath( $doc );
		$out   = array();

		// name() and string() are asked of XPath rather than read off the node as
		// ->tagName and ->textContent, because DOM spells its properties in camel
		// case and the coding standard requires snake_case for every property read.
		foreach ( $xpath->query( '//a | //span | //option | //select' ) as $el ) {
			$out[] = array(
				'tag'   => (string) $xpath->evaluate( 'name(.)', $el ),
				'class' => $el->getAttribute( 'class' ),
				'text'  => trim( (string) $xpath->evaluate( 'string(.)', $el ) ),
				'href'  => $el->hasAttribute( 'href' ) ? $el->getAttribute( 'href' ) : $el->getAttribute( 'value' ),
				'title' => $el->getAttribute( 'title' ),
			);
		}

		return $out;
	}

	/**
	 * The numeric labels the navigation shows, in document order.
	 *
	 * Every non-numeric label -- the "Page x of y" text, the first/last links, the
	 * arrows and the ellipses -- is skipped, so this is exactly the window of page
	 * numbers the paging algorithm chose.
	 *
	 * @param string $html Rendered markup.
	 * @return array List of integers.
	 */
	protected function page_numbers( $html ) {
		$numbers = array();

		foreach ( $this->nodes( $html ) as $node ) {
			if ( 'select' === $node['tag'] ) {
				continue;
			}
			if ( '' !== $node['text'] && ctype_digit( $node['text'] ) ) {
				$numbers[] = (int) $node['text'];
			}
		}

		return $numbers;
	}

	/**
	 * The label of the element marked as the current page.
	 *
	 * Matched on the class name, which both versions set to "current".
	 *
	 * @param string $html Rendered markup.
	 * @return array List of labels; more than one entry is a bug.
	 */
	protected function current_labels( $html ) {
		$found = array();

		foreach ( $this->nodes( $html ) as $node ) {
			if ( in_array( 'current', preg_split( '/\s+/', $node['class'] ), true ) ) {
				$found[] = $node['text'];
			}
		}

		return $found;
	}
}
