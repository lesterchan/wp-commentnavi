<?php
/**
 * The settings screen.
 *
 * @package WP-CommentNavi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Builds Settings -> CommentNavi with the WordPress Settings API.
 *
 * Replaces the hand-rolled form in commentnavi-options.php, which posted to
 * admin.php?page=wp-commentnavi/commentnavi-options.php, handled $_POST inline
 * and added a layer of slashes on every save.
 */
class CommentNavi_Admin {

	/**
	 * Settings group passed to register_setting() and settings_fields().
	 *
	 * @var string
	 */
	const OPTION_GROUP = 'commentnavi_options_group';

	/**
	 * The page slug.
	 *
	 * Before 2.0.0 the menu was registered with the plugin file as its slug, in
	 * the legacy 'wp-commentnavi/commentnavi-options.php' form, which put the
	 * plugin's directory name into the admin URL. A site that installed the
	 * plugin under any other directory name got a settings page it could not
	 * reach.
	 *
	 * @var string
	 */
	const PAGE_SLUG = 'commentnavi';

	/**
	 * Hook the admin screen into WordPress.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( WP_COMMENTNAVI_MAIN_FILE ), array( __CLASS__, 'action_links' ) );
	}

	/**
	 * Add the settings page under the Settings menu.
	 *
	 * @return void
	 */
	public static function add_page() {
		add_options_page(
			__( 'CommentNavi Settings', 'wp-commentnavi' ),
			__( 'CommentNavi', 'wp-commentnavi' ),
			'manage_options',
			self::PAGE_SLUG,
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Add a Settings link to the plugin's row on the Plugins screen.
	 *
	 * @param array $links Existing action links.
	 * @return array
	 */
	public static function action_links( $links ) {
		if ( ! is_array( $links ) ) {
			$links = array();
		}

		array_unshift(
			$links,
			'<a href="' . esc_url( admin_url( 'options-general.php?page=' . self::PAGE_SLUG ) ) . '">' . esc_html__( 'Settings', 'wp-commentnavi' ) . '</a>'
		);

		return $links;
	}

	/**
	 * Register the setting, its sections and its fields.
	 *
	 * @return void
	 */
	public static function register_settings() {
		register_setting(
			self::OPTION_GROUP,
			CommentNavi_Options::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
			)
		);

		add_settings_section(
			'commentnavi_text',
			__( 'Comment Navigation Text', 'wp-commentnavi' ),
			array( __CLASS__, 'text_section_intro' ),
			self::PAGE_SLUG
		);

		add_settings_section(
			'commentnavi_options',
			__( 'Comment Navigation Options', 'wp-commentnavi' ),
			'__return_false',
			self::PAGE_SLUG
		);

		foreach ( self::text_fields() as $name => $field ) {
			add_settings_field(
				$name,
				$field['title'],
				array( __CLASS__, 'render_text_field' ),
				self::PAGE_SLUG,
				'commentnavi_text',
				array(
					'label_for' => 'commentnavi-' . $name,
					'name'      => $name,
					'class_'    => isset( $field['class'] ) ? $field['class'] : '',
					'tokens'    => isset( $field['tokens'] ) ? $field['tokens'] : array(),
				)
			);
		}

		foreach ( self::option_fields() as $name => $field ) {
			add_settings_field(
				$name,
				$field['title'],
				array( __CLASS__, 'render_' . $field['type'] . '_field' ),
				self::PAGE_SLUG,
				'commentnavi_options',
				array(
					'label_for' => 'commentnavi-' . $name,
					'name'      => $name,
					'choices'   => isset( $field['choices'] ) ? $field['choices'] : array(),
					'notes'     => isset( $field['notes'] ) ? $field['notes'] : array(),
					'class_'    => isset( $field['class'] ) ? $field['class'] : '',
				)
			);
		}
	}

	/**
	 * The nine text fields, in the order the screen has always shown them.
	 *
	 * The %TOKEN% placeholders are listed separately from the translated label so
	 * they never end up inside a translatable string, where a formatting pass
	 * would rewrite them into numbered printf placeholders.
	 *
	 * @return array
	 */
	protected static function text_fields() {
		return array(
			'pages_text'    => array(
				'title'  => __( 'Text For Number Of Pages', 'wp-commentnavi' ),
				'class'  => 'regular-text',
				'tokens' => array(
					'%CURRENT_PAGE%' => __( 'The current page number.', 'wp-commentnavi' ),
					'%TOTAL_PAGES%'  => __( 'The total number of pages.', 'wp-commentnavi' ),
				),
			),
			'current_text'  => array(
				'title'  => __( 'Text For Current Page', 'wp-commentnavi' ),
				'tokens' => array(
					'%PAGE_NUMBER%' => __( 'The page number.', 'wp-commentnavi' ),
				),
			),
			'page_text'     => array(
				'title'  => __( 'Text For Page', 'wp-commentnavi' ),
				'tokens' => array(
					'%PAGE_NUMBER%' => __( 'The page number.', 'wp-commentnavi' ),
				),
			),
			'first_text'    => array(
				'title'  => __( 'Text For First Page', 'wp-commentnavi' ),
				'tokens' => array(
					'%TOTAL_PAGES%' => __( 'The total number of pages.', 'wp-commentnavi' ),
				),
			),
			'last_text'     => array(
				'title'  => __( 'Text For Last Page', 'wp-commentnavi' ),
				'tokens' => array(
					'%TOTAL_PAGES%' => __( 'The total number of pages.', 'wp-commentnavi' ),
				),
			),
			'prev_text'     => array(
				'title' => __( 'Text For Previous Page', 'wp-commentnavi' ),
			),
			'next_text'     => array(
				'title' => __( 'Text For Next Page', 'wp-commentnavi' ),
			),
			'dotleft_text'  => array(
				'title' => __( 'Text For Previous ...', 'wp-commentnavi' ),
			),
			'dotright_text' => array(
				'title' => __( 'Text For Next ...', 'wp-commentnavi' ),
			),
		);
	}

	/**
	 * The non-text fields, in the order the screen has always shown them.
	 *
	 * @return array
	 */
	protected static function option_fields() {
		$yes_no = array(
			1 => __( 'Yes', 'wp-commentnavi' ),
			0 => __( 'No', 'wp-commentnavi' ),
		);

		return array(
			'use_commentnavi_css'          => array(
				'title'   => __( 'Use commentnavi-css.css', 'wp-commentnavi' ),
				'type'    => 'radio',
				'choices' => $yes_no,
			),
			'style'                        => array(
				'title'   => __( 'Comment Navigation Style', 'wp-commentnavi' ),
				'type'    => 'select',
				'choices' => array(
					1 => __( 'Normal', 'wp-commentnavi' ),
					2 => __( 'Drop Down List', 'wp-commentnavi' ),
				),
			),
			'always_show'                  => array(
				'title'   => __( 'Always Show Comment Navigation?', 'wp-commentnavi' ),
				'type'    => 'radio',
				'choices' => $yes_no,
				'notes'   => array( __( 'Show navigation even if there\'s only one page of comments.', 'wp-commentnavi' ) ),
			),
			'num_pages'                    => array(
				'title' => __( 'Number Of Pages To Show?', 'wp-commentnavi' ),
				'type'  => 'number',
				'class' => 'small-text',
			),
			'num_larger_page_numbers'      => array(
				'title' => __( 'Number Of Larger Page Numbers To Show', 'wp-commentnavi' ),
				'type'  => 'number',
				'class' => 'small-text',
				'notes' => array(
					__( 'Larger page numbers are in addition to the normal page numbers. They are useful when a post has many pages of comments.', 'wp-commentnavi' ),
					__( 'For example, WP-CommentNavi will display: Pages 1, 2, 3, 4, 5, 10, 20, 30, 40, 50.', 'wp-commentnavi' ),
					__( 'Enter 0 to disable. New in 2.0.0, and off by default so that upgrading does not change what the navigation looks like.', 'wp-commentnavi' ),
				),
			),
			'larger_page_numbers_multiple' => array(
				'title' => __( 'Show Larger Page Numbers In Multiples Of', 'wp-commentnavi' ),
				'type'  => 'number',
				'class' => 'small-text',
				'notes' => array( __( 'For example, if multiple is 5, it will show: 5, 10, 15, 20, 25', 'wp-commentnavi' ) ),
			),
		);
	}

	/**
	 * Intro copy for the text section.
	 *
	 * @return void
	 */
	public static function text_section_intro() {
		echo '<p>' . esc_html__( 'Leaving a field blank will hide that part of the navigation.', 'wp-commentnavi' ) . '</p>';
	}

	/**
	 * Build the name attribute for a field.
	 *
	 * @param string $name Option key.
	 * @return string
	 */
	protected static function field_name( $name ) {
		return CommentNavi_Options::OPTION_NAME . '[' . $name . ']';
	}

	/**
	 * Print the notes shown beneath a field.
	 *
	 * @param array $notes Lines of help text.
	 * @return void
	 */
	protected static function render_notes( $notes ) {
		foreach ( $notes as $note ) {
			echo '<br />' . esc_html( $note );
		}
	}

	/**
	 * Render a text field.
	 *
	 * The value is read by its own key. The pre-2.0.0 screen rendered the
	 * "Text For Previous ..." field from dotright_text, so editing it silently
	 * saved whatever was in the "Text For Next ..." box and the two settings
	 * could never be given different values through the UI.
	 *
	 * @param array $args Field arguments.
	 * @return void
	 */
	public static function render_text_field( $args ) {
		$value = CommentNavi_Options::get( $args['name'] );
		$class = $args['class_'] ? $args['class_'] : 'regular-text';

		printf(
			'<input type="text" id="%1$s" name="%2$s" value="%3$s" class="%4$s" />',
			esc_attr( $args['label_for'] ),
			esc_attr( self::field_name( $args['name'] ) ),
			esc_attr( $value ),
			esc_attr( $class )
		);

		foreach ( $args['tokens'] as $token => $description ) {
			echo '<br /><code>' . esc_html( $token ) . '</code> &mdash; ' . esc_html( $description );
		}
	}

	/**
	 * Render a number field.
	 *
	 * @param array $args Field arguments.
	 * @return void
	 */
	public static function render_number_field( $args ) {
		$value = CommentNavi_Options::get( $args['name'] );

		printf(
			'<input type="number" min="0" step="1" id="%1$s" name="%2$s" value="%3$s" class="%4$s" />',
			esc_attr( $args['label_for'] ),
			esc_attr( self::field_name( $args['name'] ) ),
			esc_attr( $value ),
			esc_attr( $args['class_'] ? $args['class_'] : 'small-text' )
		);

		self::render_notes( $args['notes'] );
	}

	/**
	 * Render a pair of radio buttons.
	 *
	 * @param array $args Field arguments.
	 * @return void
	 */
	public static function render_radio_field( $args ) {
		$value = (int) CommentNavi_Options::get( $args['name'] );

		echo '<fieldset>';
		foreach ( $args['choices'] as $choice => $label ) {
			printf(
				'<label><input type="radio" name="%1$s" value="%2$s"%3$s /> %4$s</label> ',
				esc_attr( self::field_name( $args['name'] ) ),
				esc_attr( $choice ),
				checked( $value, (int) $choice, false ),
				esc_html( $label )
			);
		}
		echo '</fieldset>';

		self::render_notes( $args['notes'] );
	}

	/**
	 * Render a select box.
	 *
	 * @param array $args Field arguments.
	 * @return void
	 */
	public static function render_select_field( $args ) {
		$value = (int) CommentNavi_Options::get( $args['name'] );

		printf(
			'<select id="%1$s" name="%2$s">',
			esc_attr( $args['label_for'] ),
			esc_attr( self::field_name( $args['name'] ) )
		);
		foreach ( $args['choices'] as $choice => $label ) {
			printf(
				'<option value="%1$s"%2$s>%3$s</option>',
				esc_attr( $choice ),
				selected( $value, (int) $choice, false ),
				esc_html( $label )
			);
		}
		echo '</select>';

		self::render_notes( $args['notes'] );
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wp-commentnavi' ) );
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'CommentNavi Settings', 'wp-commentnavi' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( self::OPTION_GROUP );
				do_settings_sections( self::PAGE_SLUG );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Validate and clean the submitted settings.
	 *
	 * Values missing from the submission fall back to what is already stored, so
	 * a partial POST never wipes a setting.
	 *
	 * Note what is absent: the pre-2.0.0 handler wrapped every value in
	 * addslashes() on top of the slashes WordPress already adds to $_POST, then
	 * stripped one layer back off for display. The front end stripped nothing, so
	 * an apostrophe grew a backslash on the site itself with every save. The
	 * Settings API hands this callback unslashed data, so it is stored as typed.
	 *
	 * @param mixed $input Raw submitted values.
	 * @return array
	 */
	public static function sanitize( $input ) {
		$options = wp_parse_args( is_array( $input ) ? $input : array(), CommentNavi_Options::get() );

		// Keep only keys the plugin actually defines. Without this a hand-crafted
		// post to options.php would have its extra keys stored in the option row
		// forever.
		$options = array_intersect_key( $options, CommentNavi_Options::get_defaults() );

		foreach ( CommentNavi_Options::int_keys() as $key ) {
			$value           = isset( $options[ $key ] ) && is_scalar( $options[ $key ] ) ? $options[ $key ] : 0;
			$options[ $key ] = absint( $value );
		}

		foreach ( CommentNavi_Options::bool_keys() as $key ) {
			$value           = isset( $options[ $key ] ) && is_scalar( $options[ $key ] ) ? $options[ $key ] : 0;
			$options[ $key ] = intval( $value );
		}

		// The same allow-list the renderer uses, so an SVG arrow typed into the
		// settings screen survives exactly as one passed through the 'options'
		// argument does.
		foreach ( CommentNavi_Options::text_keys() as $key ) {
			$options[ $key ] = CommentNavi_Options::kses( isset( $options[ $key ] ) ? $options[ $key ] : '' );
		}

		return $options;
	}
}
