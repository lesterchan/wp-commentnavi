<?php
/**
 * End-to-end tests against real comments.
 *
 * Every other rendering test assigns $wp_query->comment_count and
 * max_num_comment_pages directly, because that is what comments_template()
 * leaves behind and it keeps those tests fast and precise. The cost is that
 * they would all still pass if the assumption behind them were wrong.
 *
 * These tests insert real comments, set the real comment paging options and let
 * core's own comments_template() compute the figures, so the plugin is driven
 * the way a theme drives it.
 *
 * @package WP-CommentNavi
 */

/**
 * Covers wp_commentnavi() over a real paginated comment thread.
 */
class Test_CommentNavi_Integration extends CommentNavi_TestCase {

	/**
	 * Create a post carrying a given number of approved comments.
	 *
	 * @param int $count How many comments to attach.
	 * @return int Post ID.
	 */
	protected function post_with_comments( $count ) {
		$post_id = self::factory()->post->create(
			array(
				'post_title'  => 'Integration thread',
				'post_status' => 'publish',
			)
		);

		for ( $i = 1; $i <= $count; $i++ ) {
			self::factory()->comment->create(
				array(
					'comment_post_ID'  => $post_id,
					'comment_content'  => 'Comment number ' . $i,
					'comment_approved' => 1,
				)
			);
		}

		return $post_id;
	}

	/**
	 * Run a real singular request and let core paginate the comments.
	 *
	 * comments_template() is what populates comment_count and
	 * max_num_comment_pages. Its own output is discarded -- the theme's
	 * comments.php is not what is under test here, the figures it leaves on the
	 * query are.
	 *
	 * @param int $post_id Post to display.
	 * @param int $cpage   Comment page to request.
	 * @return string The markup wp_commentnavi() prints.
	 */
	protected function render_real( $post_id, $cpage ) {
		$this->go_to( add_query_arg( 'cpage', $cpage, get_permalink( $post_id ) ) );

		global $wp_query, $withcomments;
		$withcomments = true;

		$this->assertTrue( is_singular(), 'The request did not resolve to a singular view.' );

		$wp_query->the_post();

		/*
		 * The bundled test theme ships no comments.php, so core would fall back to
		 * wp-includes/theme-compat/comments.php -- and that file opens by calling
		 * _deprecated_file(), which the WP test suite turns into a failure unless
		 * declared. The notice describes the test theme, not this plugin, so the
		 * filter substitutes an empty fixture. Core checks file_exists() on the
		 * filtered path before reaching the fallback, so the deprecated file is
		 * never reached.
		 *
		 * Which template is included does not matter here. By the time core
		 * resolves one it has already run the comment query and put comment_count
		 * and max_num_comment_pages on the query, which is the only reason these
		 * tests call comments_template() at all.
		 */
		$fixture = static function () {
			return __DIR__ . '/fixture-comments-template.php';
		};
		add_filter( 'comments_template', $fixture );

		ob_start();
		comments_template();
		ob_end_clean();

		remove_filter( 'comments_template', $fixture );

		ob_start();
		wp_commentnavi();
		$out = ob_get_clean();

		wp_reset_postdata();

		return $out;
	}

	/**
	 * Core really does report three pages for 25 comments at 10 per page, and the
	 * plugin renders a link for each.
	 *
	 * @return void
	 */
	public function test_real_thread_paginates() {
		update_option( 'page_comments', 1 );
		update_option( 'comments_per_page', 10 );
		$this->set_options();

		$post_id = $this->post_with_comments( 25 );
		$html    = $this->render_real( $post_id, 1 );

		global $wp_query;
		$this->assertSame( 3, (int) $wp_query->max_num_comment_pages, 'Core did not paginate the thread as expected.' );

		$this->assertSame( array( 1, 2, 3 ), $this->page_numbers( $html ) );
		$this->assertSame( array( '1' ), $this->current_labels( $html ) );
	}

	/**
	 * Asking for the second page marks the second page current.
	 *
	 * @return void
	 */
	public function test_second_page_of_a_real_thread() {
		update_option( 'page_comments', 1 );
		update_option( 'comments_per_page', 10 );
		$this->set_options();

		$post_id = $this->post_with_comments( 25 );
		$html    = $this->render_real( $post_id, 2 );

		$this->assertSame( array( 1, 2, 3 ), $this->page_numbers( $html ) );
		$this->assertSame( array( '2' ), $this->current_labels( $html ) );
		$this->assertStringContainsString( 'Page 2 of 3', wp_strip_all_tags( $html ) );
	}

	/**
	 * The links core builds for a real thread actually point at comment pages of
	 * that post.
	 *
	 * @return void
	 */
	public function test_real_links_point_at_this_post() {
		update_option( 'page_comments', 1 );
		update_option( 'comments_per_page', 10 );
		$this->set_options();

		$post_id = $this->post_with_comments( 25 );
		$html    = $this->render_real( $post_id, 2 );

		$found = 0;
		foreach ( $this->nodes( $html ) as $node ) {
			if ( 'a' !== $node['tag'] || ! ctype_digit( $node['text'] ) ) {
				continue;
			}

			$this->assertStringContainsString( 'cpage=' . $node['text'], $node['href'] );
			$this->assertStringContainsString( (string) $post_id, $node['href'] );
			++$found;
		}

		$this->assertGreaterThan( 0, $found, 'No numbered links were rendered.' );
	}

	/**
	 * A thread short enough to fit on one page renders no navigation.
	 *
	 * @return void
	 */
	public function test_short_real_thread_renders_nothing() {
		update_option( 'page_comments', 1 );
		update_option( 'comments_per_page', 10 );
		$this->set_options();

		$post_id = $this->post_with_comments( 3 );
		$html    = $this->render_real( $post_id, 1 );

		$this->assertSame( '', trim( $html ) );
	}

	/**
	 * With comment paging turned off in Settings -> Discussion there is only ever
	 * one page, so nothing renders however many comments there are.
	 *
	 * @return void
	 */
	public function test_paging_disabled_renders_nothing() {
		update_option( 'page_comments', 0 );
		$this->set_options();

		$post_id = $this->post_with_comments( 25 );
		$html    = $this->render_real( $post_id, 1 );

		$this->assertSame( '', trim( $html ) );
	}

	/**
	 * The stylesheet is enqueued during a real front end request.
	 *
	 * @return void
	 */
	public function test_stylesheet_enqueued_on_a_real_request() {
		$this->set_options( array( 'use_commentnavi_css' => 1 ) );

		$post_id = $this->post_with_comments( 25 );
		$this->go_to( get_permalink( $post_id ) );

		do_action( 'wp_enqueue_scripts' );

		$this->assertTrue( wp_style_is( 'wp-commentnavi', 'enqueued' ) );
	}
}
