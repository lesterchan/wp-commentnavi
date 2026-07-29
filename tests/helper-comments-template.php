<?php
/**
 * An intentionally empty comments template, used as a fixture.
 *
 * The bundled test theme ships no comments.php, so comments_template() falls
 * back to wp-includes/theme-compat/comments.php -- and that file opens by
 * calling _deprecated_file(), which the WordPress test suite turns into a
 * failure unless it is declared.
 *
 * That notice is a fact about the test theme rather than about this plugin, and
 * declaring it would tie the tests to whichever theme the suite happens to run
 * under. WP_CommentNavi_Integration_Test points the 'comments_template' filter here
 * instead. Core checks file_exists() on the filtered path first, so this file is
 * included in place of the deprecated fallback.
 *
 * Nothing needs to be rendered: by the time core resolves a template it has
 * already run the comment query and put comment_count and max_num_comment_pages
 * on the query, which is the whole reason those tests call comments_template().
 *
 * @package WP-CommentNavi
 */

defined( 'ABSPATH' ) || exit;
