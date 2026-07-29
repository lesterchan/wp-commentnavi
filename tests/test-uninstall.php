<?php
/**
 * Uninstaller tests.
 *
 * @package WP-CommentNavi
 */

/**
 * Covers uninstall.php.
 */
class WP_CommentNavi_Uninstall_Test extends WP_CommentNavi_TestCase {

	/**
	 * Every PHP file the plugin ships, for the source-level assertions below.
	 *
	 * @return array
	 */
	protected function plugin_php_files() {
		$root  = dirname( __DIR__ );
		$files = glob( $root . '/*.php' );

		if ( is_dir( $root . '/includes' ) ) {
			$files = array_merge( $files, glob( $root . '/includes/*.php' ) );
		}

		return $files;
	}

	public function test_uninstall_deletes_every_row() {
		// Running the uninstaller removes all three of the rows the plugin can leave
		// behind: the settings, the version markers and the pre-2.0.0 settings row.

		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			define( 'WP_UNINSTALL_PLUGIN', 'wp-commentnavi/wp-commentnavi.php' );
		}

		// require_once: the suite runs in one process, and a second require would
		// redeclare wp_commentnavi_uninstall_site() and fatal. The rows are written
		// after it, so the deletion under test is the explicit call below rather
		// than whatever the file did on the way in.
		require_once dirname( __DIR__ ) . '/uninstall.php';

		WP_CommentNavi_Options::update( array( 'style' => 1 ) );
		WP_CommentNavi_Options::maybe_upgrade();
		update_option( WP_CommentNavi_Options::LEGACY_OPTION, array( 'style' => 1 ) );

		$this->assertIsArray( get_option( WP_CommentNavi_Options::OPTION ) );
		$this->assertIsArray( get_option( WP_CommentNavi_Options::VERSION ) );

		wp_commentnavi_uninstall_site();

		$this->assertFalse( get_option( WP_CommentNavi_Options::OPTION ) );
		$this->assertFalse( get_option( WP_CommentNavi_Options::VERSION ) );
		$this->assertFalse( get_option( WP_CommentNavi_Options::LEGACY_OPTION ) );
	}

	public function test_no_call_to_the_removed_wp_get_sites() {
		// Calling it does not degrade gracefully -- it is a fatal error, so before
		// 2.0.0 both network activation and multisite uninstall died outright on any
		// supported WordPress. A single-site suite cannot reach that code path, so
		// this is asserted against the source instead.

		foreach ( $this->plugin_php_files() as $file ) {
			$this->assertStringNotContainsString(
				'wp_get_sites',
				$this->code_without_comments( $file ),
				basename( $file ) . ' calls wp_get_sites(), which WordPress removed in 5.1.'
			);
		}
	}

	/**
	 * Return a file's source with all comments removed.
	 *
	 * Searching raw source for a legacy symbol gets a false positive from every
	 * comment that explains why the symbol is gone -- which is exactly the kind of
	 * comment worth keeping. Stripping comments first means the assertion sees
	 * only code, so the history can be written down without tripping it.
	 *
	 * @param string $file Absolute path to a PHP file.
	 * @return string
	 */
	protected function code_without_comments( $file ) {
		$code = '';

		foreach ( token_get_all( file_get_contents( $file ) ) as $token ) {
			if ( is_array( $token ) && in_array( $token[0], array( T_COMMENT, T_DOC_COMMENT ), true ) ) {
				continue;
			}

			$code .= is_array( $token ) ? $token[1] : $token;
		}

		return $code;
	}

	public function test_multisite_uninstall_asks_for_every_site() {
		// WP_Site_Query defaults 'number' to 100, so a network larger than that would
		// silently leave the option behind on every site past the hundredth. This is
		// asserted against the source rather than by building a 101-site network,
		// which the single-site suite cannot do; the assertion exists so the argument
		// cannot be dropped again without a failure.

		$source = file_get_contents( dirname( __DIR__ ) . '/uninstall.php' );

		$this->assertMatchesRegularExpression(
			"/'number'\s*=>\s*0/",
			$source,
			"uninstall.php must pass 'number' => 0 to get_sites(), or it stops at 100 sites."
		);
	}

	public function test_uninstall_restores_the_blog_inside_the_loop() {
		// Calling switch_to_blog() pushes onto a stack. Restoring once after the loop
		// instead of once per iteration leaves the stack wound up by every site but
		// the last, which leaks the switched state into whatever runs next.
		//
		// Counting the two calls is not enough to catch this -- the broken version has
		// one of each too, just in the wrong places -- so the foreach body is pulled
		// out with the tokenizer and both calls have to be found inside it.

		$body = $this->foreach_body( dirname( __DIR__ ) . '/uninstall.php' );

		$this->assertNotNull( $body, 'uninstall.php has no foreach loop over the network sites.' );
		$this->assertStringContainsString( 'switch_to_blog', $body );
		$this->assertStringContainsString(
			'restore_current_blog',
			$body,
			'restore_current_blog() must be called inside the site loop, once per switch_to_blog().'
		);
	}

	/**
	 * Return the source of the first foreach body in a file.
	 *
	 * Brace matching is done over the token stream rather than with a regular
	 * expression, so a nested block inside the loop cannot end the match early.
	 *
	 * @param string $file Absolute path to a PHP file.
	 * @return string|null The loop body, or null when there is no foreach.
	 */
	protected function foreach_body( $file ) {
		$tokens = token_get_all( file_get_contents( $file ) );
		$count  = count( $tokens );

		for ( $i = 0; $i < $count; $i++ ) {
			if ( ! is_array( $tokens[ $i ] ) || T_FOREACH !== $tokens[ $i ][0] ) {
				continue;
			}

			// Walk to the brace that opens the loop body.
			for ( $j = $i; $j < $count && '{' !== $tokens[ $j ]; $j++ ) {
				continue;
			}

			$depth = 0;
			$body  = '';

			for ( ; $j < $count; $j++ ) {
				$text = is_array( $tokens[ $j ] ) ? $tokens[ $j ][1] : $tokens[ $j ];

				if ( '{' === $tokens[ $j ] ) {
					++$depth;
				} elseif ( '}' === $tokens[ $j ] ) {
					--$depth;
					if ( 0 === $depth ) {
						return $body;
					}
				}

				$body .= $text;
			}
		}

		return null;
	}
}
