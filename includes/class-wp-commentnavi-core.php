<?php
/**
 * Front end rendering and asset loading.
 *
 * @package WP-CommentNavi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Builds the comment navigation markup and enqueues the stylesheet.
 */
class WP_CommentNavi_Core {

	/**
	 * Hook the plugin into WordPress.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'stylesheets' ) );
	}

	/**
	 * Enqueue the stylesheet, letting the theme override it with its own copy.
	 *
	 * Before 2.0.0 the override was looked for in TEMPLATEPATH — the parent theme
	 * — but enqueued from get_stylesheet_directory_uri(), the child theme. Under a
	 * child theme those are different directories, so a parent-theme override
	 * produced a URL with nothing behind it and a child-theme override was never
	 * found at all. Each candidate is now tested and enqueued from the same place.
	 *
	 * The file the theme is asked for is named after the plugin slug, matching the
	 * one the plugin now ships in css/. Before 2.0.0 both were 'commentnavi-css.css'.
	 *
	 * @return void
	 */
	public static function stylesheets() {
		if ( ! WP_CommentNavi_Options::get( 'use_commentnavi_css' ) ) {
			return;
		}

		if ( file_exists( get_stylesheet_directory() . '/wp-commentnavi.css' ) ) {
			$css_file = get_stylesheet_directory_uri() . '/wp-commentnavi.css';
		} elseif ( file_exists( get_template_directory() . '/wp-commentnavi.css' ) ) {
			$css_file = get_template_directory_uri() . '/wp-commentnavi.css';
		} else {
			$css_file = WP_COMMENTNAVI_URL . 'css/wp-commentnavi.css';
		}

		wp_enqueue_style( WP_COMMENTNAVI_SLUG, $css_file, array(), WP_COMMENTNAVI_VERSION );
	}

	/**
	 * Render the comment navigation.
	 *
	 * @param array $args Arguments as accepted by wp_commentnavi().
	 * @return string|void The markup when 'echo' is false, otherwise nothing.
	 */
	public static function render( $args ) {
		$args = wp_parse_args(
			$args,
			array(
				'before'        => '',
				'after'         => '',
				'wrapper_tag'   => 'div',
				'wrapper_class' => 'wp-commentnavi',
				'options'       => array(),
				'query'         => $GLOBALS['wp_query'],
				'echo'          => true,
			)
		);

		$before        = $args['before'];
		$after         = $args['after'];
		$wrapper_tag   = $args['wrapper_tag'];
		$wrapper_class = $args['wrapper_class'];
		$echo          = $args['echo'];

		$options = wp_parse_args( is_array( $args['options'] ) ? $args['options'] : array(), WP_CommentNavi_Options::get() );

		// The text options are output as HTML, so they are filtered here as well as
		// on save, to cover values set through the 'options' argument or written to
		// the option row directly by WP-CLI, a migration or another plugin.
		foreach ( WP_CommentNavi_Options::text_keys() as $key ) {
			if ( isset( $options[ $key ] ) ) {
				$options[ $key ] = WP_CommentNavi_Options::kses( $options[ $key ] );
			}
		}

		$instance = new WP_CommentNavi_Call( $args );

		list( $paged, $total_pages ) = $instance->get_pagination_args();

		if ( 1 === $total_pages && ! $options['always_show'] ) {
			return;
		}

		// floor()/ceil() return floats, so every derived page number is cast back to
		// int. Without this the strict comparisons below would never match.
		$pages_to_show         = absint( $options['num_pages'] );
		$larger_page_to_show   = absint( $options['num_larger_page_numbers'] );
		$larger_page_multiple  = absint( $options['larger_page_numbers_multiple'] );
		$pages_to_show_minus_1 = $pages_to_show - 1;
		$half_page_start       = (int) floor( $pages_to_show_minus_1 / 2 );
		$half_page_end         = (int) ceil( $pages_to_show_minus_1 / 2 );
		$start_page            = $paged - $half_page_start;

		if ( $start_page <= 0 ) {
			$start_page = 1;
		}

		$end_page = $paged + $half_page_end;

		if ( ( $end_page - $start_page ) !== $pages_to_show_minus_1 ) {
			$end_page = $start_page + $pages_to_show_minus_1;
		}

		if ( $end_page > $total_pages ) {
			$start_page = $total_pages - $pages_to_show_minus_1;
			$end_page   = $total_pages;
		}

		if ( $start_page < 1 ) {
			$start_page = 1;
		}

		/*
		 * One filter per element, so a theme can rename a single class without
		 * having to reproduce the other nine. The names mirror WP-PageNavi's
		 * wp_pagenavi_class_* family element for element, so a theme that styles
		 * both plugins reads the same way twice.
		 */
		$class_names = array(
			/**
			 * Filters the class on the "Page x of y" label.
			 *
			 * @since 2.0.0
			 *
			 * @param string $class_name Class name. Default 'pages'.
			 */
			'pages'                => apply_filters( 'wp_commentnavi_class_pages', 'pages' ),

			/**
			 * Filters the class on the link to the first page of comments.
			 *
			 * @since 2.0.0
			 *
			 * @param string $class_name Class name. Default 'first'.
			 */
			'first'                => apply_filters( 'wp_commentnavi_class_first', 'first' ),

			/**
			 * Filters the class on the link to the previous page of comments.
			 *
			 * @since 2.0.0
			 *
			 * @param string $class_name Class name. Default 'previouscommentslink'.
			 */
			'previouscommentslink' => apply_filters( 'wp_commentnavi_class_previouscommentslink', 'previouscommentslink' ),

			/**
			 * Filters the class on the ellipsis that stands in for a skipped run of pages.
			 *
			 * @since 2.0.0
			 *
			 * @param string $class_name Class name. Default 'extend'.
			 */
			'extend'               => apply_filters( 'wp_commentnavi_class_extend', 'extend' ),

			/**
			 * Filters the class added to page numbers below the current page.
			 *
			 * @since 2.0.0
			 *
			 * @param string $class_name Class name. Default 'smaller'.
			 */
			'smaller'              => apply_filters( 'wp_commentnavi_class_smaller', 'smaller' ),

			/**
			 * Filters the class shared by every numbered page link.
			 *
			 * @since 2.0.0
			 *
			 * @param string $class_name Class name. Default 'page'.
			 */
			'page'                 => apply_filters( 'wp_commentnavi_class_page', 'page' ),

			/**
			 * Filters the class on the page the visitor is reading.
			 *
			 * @since 2.0.0
			 *
			 * @param string $class_name Class name. Default 'current'.
			 */
			'current'              => apply_filters( 'wp_commentnavi_class_current', 'current' ),

			/**
			 * Filters the class added to page numbers above the current page.
			 *
			 * @since 2.0.0
			 *
			 * @param string $class_name Class name. Default 'larger'.
			 */
			'larger'               => apply_filters( 'wp_commentnavi_class_larger', 'larger' ),

			/**
			 * Filters the class on the link to the next page of comments.
			 *
			 * @since 2.0.0
			 *
			 * @param string $class_name Class name. Default 'nextcommentslink'.
			 */
			'nextcommentslink'     => apply_filters( 'wp_commentnavi_class_nextcommentslink', 'nextcommentslink' ),

			/**
			 * Filters the class on the link to the last page of comments.
			 *
			 * @since 2.0.0
			 *
			 * @param string $class_name Class name. Default 'last'.
			 */
			'last'                 => apply_filters( 'wp_commentnavi_class_last', 'last' ),
		);

		$out = '';

		switch ( intval( $options['style'] ) ) {
			case 1:
				$out = self::render_normal( $instance, $options, $class_names, $paged, $total_pages, $start_page, $end_page, $pages_to_show, $half_page_start, $half_page_end, $larger_page_to_show, $larger_page_multiple );
				break;

			case 2:
				$out = self::render_dropdown( $instance, $options, $class_names, $paged, $total_pages );
				break;
		}

		$wrapper_tag = tag_escape( $wrapper_tag );

		$out = $before . '<' . $wrapper_tag . " class='" . esc_attr( $wrapper_class ) . "' role='navigation'>\n" . $out . "\n</" . $wrapper_tag . '>' . $after;

		/**
		 * Filters the complete comment navigation markup.
		 *
		 * @since 2.0.0
		 *
		 * @param string $out  The markup.
		 * @param array  $args The parsed arguments for this call.
		 */
		$out = apply_filters( 'wp_commentnavi', $out, $args );

		if ( ! $echo ) {
			return $out;
		}

		// $out is assembled from escaped fragments above.
		echo $out; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Render the numbered link style.
	 *
	 * @param WP_CommentNavi_Call $instance             The current call.
	 * @param array               $options              Merged options.
	 * @param array               $class_names          Filtered class names.
	 * @param int                 $paged                Current page.
	 * @param int                 $total_pages          Total pages.
	 * @param int                 $start_page           First page in the window.
	 * @param int                 $end_page             Last page in the window.
	 * @param int                 $pages_to_show        Size of the window.
	 * @param int                 $half_page_start      Pages before the current one.
	 * @param int                 $half_page_end        Pages after the current one.
	 * @param int                 $larger_page_to_show  How many larger page numbers to show.
	 * @param int                 $larger_page_multiple Multiple the larger page numbers step by.
	 * @return string
	 */
	protected static function render_normal( $instance, $options, $class_names, $paged, $total_pages, $start_page, $end_page, $pages_to_show, $half_page_start, $half_page_end, $larger_page_to_show, $larger_page_multiple ) {
		$out = '';

		// Text.
		if ( ! empty( $options['pages_text'] ) ) {
			$pages_text = str_replace(
				array( '%CURRENT_PAGE%', '%TOTAL_PAGES%' ),
				array( number_format_i18n( $paged ), number_format_i18n( $total_pages ) ),
				$options['pages_text']
			);
			$out       .= "<span class='" . esc_attr( $class_names['pages'] ) . "'>" . $pages_text . '</span>';
		}

		/*
		 * The four navigation aria-labels below use this plugin's own text domain.
		 * Reading them from core's 'default' domain would have them arrive already
		 * translated everywhere core is, but a plugin may only ship strings in its
		 * own domain: the shared coding standard pins one text domain per plugin,
		 * and borrowing core's makes the strings invisible to this plugin's own
		 * catalogue on translate.wordpress.org.
		 */

		if ( $start_page >= 2 && $pages_to_show < $total_pages ) {
			// First.
			$first_text = str_replace( '%TOTAL_PAGES%', number_format_i18n( $total_pages ), $options['first_text'] );
			$out       .= $instance->get_single(
				1,
				$first_text,
				array(
					'class'      => $class_names['first'],
					'aria-label' => __( 'First page', 'wp-commentnavi' ),
				),
				'%TOTAL_PAGES%'
			);
		}

		// Previous.
		if ( $paged > 1 && ! empty( $options['prev_text'] ) ) {
			$out .= $instance->get_single(
				$paged - 1,
				$options['prev_text'],
				array(
					'class'      => $class_names['previouscommentslink'],
					'rel'        => 'prev',
					'aria-label' => __( 'Previous Page', 'wp-commentnavi' ),
				)
			);
		}

		if ( $start_page >= 2 && $pages_to_show < $total_pages ) {
			if ( ! empty( $options['dotleft_text'] ) ) {
				$out .= "<span class='" . esc_attr( $class_names['extend'] ) . "'>" . $options['dotleft_text'] . '</span>';
			}
		}

		// Smaller pages.
		$larger_pages_array = array();
		if ( $larger_page_multiple ) {
			for ( $i = $larger_page_multiple; $i <= $total_pages; $i += $larger_page_multiple ) {
				$larger_pages_array[] = $i;
			}
		}

		$larger_page_start = 0;
		foreach ( $larger_pages_array as $larger_page ) {
			if ( $larger_page < ( $start_page - $half_page_start ) && $larger_page_start < $larger_page_to_show ) {
				$out .= $instance->get_single(
					$larger_page,
					$options['page_text'],
					array(
						'class' => $class_names['smaller'] . ' ' . $class_names['page'],
						/* translators: %s: page number. */
						'title' => sprintf( __( 'Page %s', 'wp-commentnavi' ), number_format_i18n( $larger_page ) ),
					)
				);
				++$larger_page_start;
			}
		}

		if ( $larger_page_start ) {
			$out .= "<span class='" . esc_attr( $class_names['extend'] ) . "'>" . $options['dotleft_text'] . '</span>';
		}

		// Page numbers.
		$timeline = 'smaller';
		foreach ( range( $start_page, $end_page ) as $i ) {
			if ( $i === $paged && ! empty( $options['current_text'] ) ) {
				$current_page_text = str_replace( '%PAGE_NUMBER%', number_format_i18n( $i ), $options['current_text'] );
				$out              .= "<span aria-current='page' class='" . esc_attr( $class_names['current'] ) . "'>" . $current_page_text . '</span>';
				$timeline          = 'larger';
			} else {
				$out .= $instance->get_single(
					$i,
					$options['page_text'],
					array(
						'class' => $class_names['page'] . ' ' . $class_names[ $timeline ],
						/* translators: %s: page number. */
						'title' => sprintf( __( 'Page %s', 'wp-commentnavi' ), number_format_i18n( $i ) ),
					)
				);
			}
		}

		// Larger pages.
		$larger_page_end = 0;
		$larger_page_out = '';
		foreach ( $larger_pages_array as $larger_page ) {
			if ( $larger_page > ( $end_page + $half_page_end ) && $larger_page_end < $larger_page_to_show ) {
				$larger_page_out .= $instance->get_single(
					$larger_page,
					$options['page_text'],
					array(
						'class' => $class_names['larger'] . ' ' . $class_names['page'],
						/* translators: %s: page number. */
						'title' => sprintf( __( 'Page %s', 'wp-commentnavi' ), number_format_i18n( $larger_page ) ),
					)
				);
				++$larger_page_end;
			}
		}

		if ( $larger_page_out ) {
			$out .= "<span class='" . esc_attr( $class_names['extend'] ) . "'>" . $options['dotright_text'] . '</span>';
		}
		$out .= $larger_page_out;

		if ( $end_page < $total_pages ) {
			if ( ! empty( $options['dotright_text'] ) ) {
				$out .= "<span class='" . esc_attr( $class_names['extend'] ) . "'>" . $options['dotright_text'] . '</span>';
			}
		}

		// Next.
		if ( $paged < $total_pages && ! empty( $options['next_text'] ) ) {
			$out .= $instance->get_single(
				$paged + 1,
				$options['next_text'],
				array(
					'class'      => $class_names['nextcommentslink'],
					'rel'        => 'next',
					'aria-label' => __( 'Next Page', 'wp-commentnavi' ),
				)
			);
		}

		if ( $end_page < $total_pages ) {
			// Last.
			$out .= $instance->get_single(
				$total_pages,
				$options['last_text'],
				array(
					'class'      => $class_names['last'],
					'aria-label' => __( 'Last page', 'wp-commentnavi' ),
				),
				'%TOTAL_PAGES%'
			);
		}

		return $out;
	}

	/**
	 * Render the drop-down list style.
	 *
	 * The form action is empty, where before 2.0.0 it pointed at
	 * admin.php?page=wp-commentnavi/wp-commentnavi.php — an admin URL that has
	 * nothing to do with the front end and 404s for a logged-out visitor. Nothing
	 * is ever submitted: the select navigates on change.
	 *
	 * @param WP_CommentNavi_Call $instance    The current call.
	 * @param array               $options     Merged options.
	 * @param array               $class_names Filtered class names.
	 * @param int                 $paged       Current page.
	 * @param int                 $total_pages Total pages.
	 * @return string
	 */
	protected static function render_dropdown( $instance, $options, $class_names, $paged, $total_pages ) {
		$out  = '<form action="" method="get">' . "\n";
		$out .= '<select size="1" onchange="document.location.href = this.options[this.selectedIndex].value;">' . "\n";

		foreach ( range( 1, $total_pages ) as $i ) {
			$page_num = $i;
			if ( 1 === $page_num ) {
				$page_num = 0;
			}

			if ( $i === $paged ) {
				$current_page_text = str_replace( '%PAGE_NUMBER%', number_format_i18n( $i ), $options['current_text'] );
				$out              .= '<option value="' . esc_url( $instance->get_url( $page_num ) ) . '" selected="selected" class="' . esc_attr( $class_names['current'] ) . '">' . $current_page_text . "</option>\n";
			} else {
				$page_text = str_replace( '%PAGE_NUMBER%', number_format_i18n( $i ), $options['page_text'] );
				$out      .= '<option value="' . esc_url( $instance->get_url( $page_num ) ) . '">' . $page_text . "</option>\n";
			}
		}

		$out .= "</select>\n";
		$out .= "</form>\n";

		return $out;
	}
}
