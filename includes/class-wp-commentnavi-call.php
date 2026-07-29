<?php
/**
 * A single invocation of the template tag.
 *
 * @package WP-CommentNavi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Resolves pagination figures and builds the individual page links for one call
 * to wp_commentnavi().
 */
class WP_CommentNavi_Call {

	/**
	 * The parsed arguments for this call.
	 *
	 * @var array
	 */
	protected $args;

	/**
	 * Constructor.
	 *
	 * @param array $args Parsed wp_commentnavi() arguments.
	 */
	public function __construct( $args ) {
		$this->args = $args;
	}

	/**
	 * Read an argument as a property.
	 *
	 * @param string $key Argument name.
	 * @return mixed Null when the argument was not supplied.
	 */
	public function __get( $key ) {
		return isset( $this->args[ $key ] ) ? $this->args[ $key ] : null;
	}

	/**
	 * Work out how many comment pages there are and which one is current.
	 *
	 * Both figures come from properties that core only populates inside
	 * comments_template(), which is why the template tag is documented as
	 * belonging in comments.php and produces nothing anywhere else.
	 *
	 * max() guards the zero case: max_num_comment_pages is 0 on a post with no
	 * comments, and before 2.0.0 that zero reached the label as "Page 1 of 0".
	 *
	 * @return array List of current page and total pages.
	 */
	public function get_pagination_args() {
		$query = $this->query;

		$paged       = max( 1, absint( get_query_var( 'cpage' ) ) );
		$total_pages = max( 1, absint( isset( $query->max_num_comment_pages ) ? $query->max_num_comment_pages : 0 ) );

		return array( (int) $paged, (int) $total_pages );
	}

	/**
	 * Build one page link.
	 *
	 * @param int    $page     Page number to link to.
	 * @param string $raw_text Link text, possibly containing a token.
	 * @param array  $attr     Attributes for the anchor.
	 * @param string $format   The token in $raw_text to replace with the page number.
	 * @return string The anchor markup, or an empty string when there is no text.
	 */
	public function get_single( $page, $raw_text, $attr, $format = '%PAGE_NUMBER%' ) {
		if ( empty( $raw_text ) ) {
			return '';
		}

		$text = str_replace( $format, number_format_i18n( $page ), $raw_text );

		$attr['href'] = $this->get_url( $page );

		return self::anchor( $attr, $text );
	}

	/**
	 * Get the URL for a comment page number.
	 *
	 * The $max_page argument of get_comments_pagenum_link() is deliberately not
	 * passed, matching every previous version of this plugin. Passing it changes
	 * which page counts as the default view when default_comments_page is
	 * 'newest', which would rewrite the URL of the last comment page on every
	 * existing site.
	 *
	 * @param int $page Page number.
	 * @return string
	 */
	public function get_url( $page ) {
		return get_comments_pagenum_link( $page );
	}

	/**
	 * Assemble an anchor tag.
	 *
	 * Attributes are emitted in the order given. A false value omits the attribute
	 * entirely and a true value repeats the attribute name as its value.
	 *
	 * Escaping happens here, at the sink, and it is the reason this method exists:
	 * before 2.0.0 the title attribute was built by string concatenation with no
	 * escaping at all, so a double quote in a navigation text option closed the
	 * attribute and everything after it became markup.
	 *
	 * @param array  $attr Attribute name/value pairs.
	 * @param string $text Already-filtered link text.
	 * @return string
	 */
	protected static function anchor( array $attr, $text ) {
		$out = '<a';

		foreach ( $attr as $key => $value ) {
			if ( false === $value ) {
				continue;
			}

			if ( true === $value ) {
				$value = $key;
			}

			$value = ( 'href' === $key ) ? esc_url( $value ) : esc_attr( $value );

			$out .= ' ' . $key . '="' . $value . '"';
		}

		return $out . '>' . $text . '</a>';
	}
}
