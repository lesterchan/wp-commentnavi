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
 * The plugin's only admin surface is its settings, so it takes a single page
 * under Settings rather than a top-level menu, and every field is registered
 * rather than hand-written into a form table.
 *
 * Replaces the hand-rolled form in commentnavi-options.php, which posted to
 * admin.php?page=wp-commentnavi/commentnavi-options.php, handled $_POST inline
 * and added a layer of slashes on every save.
 */
class WP_CommentNavi_Settings {

	/**
	 * Settings group passed to register_setting() and settings_fields().
	 *
	 * The group is named after the settings row it writes, so there is one
	 * spelling to remember rather than two.
	 *
	 * @var string
	 */
	const GROUP = 'wp_commentnavi_options';

	/**
	 * The settings page slug.
	 *
	 * Before 2.0.0 the menu was registered with the plugin file as its slug, in
	 * the legacy 'wp-commentnavi/commentnavi-options.php' form, which put the
	 * plugin's directory name into the admin URL. A site that installed the
	 * plugin under any other directory name got a settings page it could not
	 * reach.
	 *
	 * @var string
	 */
	const PAGE = 'wp-commentnavi';

	/**
	 * The capability required to see and save the settings.
	 *
	 * @var string
	 */
	const CAPABILITY = 'manage_options';

	/**
	 * The section holding the text of each navigation element.
	 *
	 * @var string
	 */
	const SECTION_TEXT = 'wp_commentnavi_text';

	/**
	 * The section holding how the navigation is presented.
	 *
	 * The id is not spelled wp_commentnavi_options, which would read as the
	 * settings row of the same name and is a different thing entirely.
	 *
	 * @var string
	 */
	const SECTION_DISPLAY = 'wp_commentnavi_display';

	/**
	 * Hook the admin screen into WordPress.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );

		// Activation hooks do not fire when a plugin is updated, so the upgrade
		// routine is also run on every admin load.
		add_action( 'admin_init', array( 'WP_CommentNavi_Options', 'maybe_upgrade' ) );

		add_filter( 'plugin_action_links_' . plugin_basename( WP_COMMENTNAVI_MAIN_FILE ), array( __CLASS__, 'action_links' ) );
	}

	/**
	 * The capability required for a given context.
	 *
	 * Every capability check in the plugin goes through here, so a site that
	 * hands the settings to an editor changes one filter rather than hunting for
	 * current_user_can() calls.
	 *
	 * @param string $context What the capability is being checked for.
	 * @return string
	 */
	public static function capability( $context = 'settings' ) {
		/**
		 * Filters the capability required to manage the plugin.
		 *
		 * @since 2.0.0
		 *
		 * @param string $capability The required capability.
		 * @param string $context    What the capability is being checked for.
		 */
		return (string) apply_filters( 'wp_commentnavi_capability', self::CAPABILITY, $context );
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
			self::capability(),
			self::PAGE,
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
			'<a href="' . esc_url( admin_url( 'options-general.php?page=' . self::PAGE ) ) . '">' . esc_html__( 'Settings', 'wp-commentnavi' ) . '</a>'
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
			self::GROUP,
			WP_CommentNavi_Options::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( 'WP_CommentNavi_Options', 'sanitize' ),
			)
		);

		add_settings_section(
			self::SECTION_TEXT,
			__( 'Comment Navigation Text', 'wp-commentnavi' ),
			array( __CLASS__, 'section_text' ),
			self::PAGE
		);

		add_settings_section(
			self::SECTION_DISPLAY,
			__( 'Comment Navigation Options', 'wp-commentnavi' ),
			'__return_false',
			self::PAGE
		);

		foreach ( self::fields() as $name => $field ) {
			add_settings_field(
				$name,
				$field['title'],
				array( __CLASS__, 'field_' . $name ),
				self::PAGE,
				$field['section'],
				array( 'label_for' => self::id( $name ) )
			);
		}
	}

	/**
	 * The field definitions: a title and a section for each registered field, in
	 * the order the screen has always shown them.
	 *
	 * @return array
	 */
	public static function fields() {
		return array(
			'pages_text'                   => array(
				'title'   => __( 'Text For Number Of Pages', 'wp-commentnavi' ),
				'section' => self::SECTION_TEXT,
			),
			'current_text'                 => array(
				'title'   => __( 'Text For Current Page', 'wp-commentnavi' ),
				'section' => self::SECTION_TEXT,
			),
			'page_text'                    => array(
				'title'   => __( 'Text For Page', 'wp-commentnavi' ),
				'section' => self::SECTION_TEXT,
			),
			'first_text'                   => array(
				'title'   => __( 'Text For First Page', 'wp-commentnavi' ),
				'section' => self::SECTION_TEXT,
			),
			'last_text'                    => array(
				'title'   => __( 'Text For Last Page', 'wp-commentnavi' ),
				'section' => self::SECTION_TEXT,
			),
			'prev_text'                    => array(
				'title'   => __( 'Text For Previous Page', 'wp-commentnavi' ),
				'section' => self::SECTION_TEXT,
			),
			'next_text'                    => array(
				'title'   => __( 'Text For Next Page', 'wp-commentnavi' ),
				'section' => self::SECTION_TEXT,
			),
			'dotleft_text'                 => array(
				'title'   => __( 'Text For Previous ...', 'wp-commentnavi' ),
				'section' => self::SECTION_TEXT,
			),
			'dotright_text'                => array(
				'title'   => __( 'Text For Next ...', 'wp-commentnavi' ),
				'section' => self::SECTION_TEXT,
			),
			'use_commentnavi_css'          => array(
				'title'   => __( 'Use wp-commentnavi.css', 'wp-commentnavi' ),
				'section' => self::SECTION_DISPLAY,
			),
			'style'                        => array(
				'title'   => __( 'Comment Navigation Style', 'wp-commentnavi' ),
				'section' => self::SECTION_DISPLAY,
			),
			'always_show'                  => array(
				'title'   => __( 'Always Show Comment Navigation?', 'wp-commentnavi' ),
				'section' => self::SECTION_DISPLAY,
			),
			'num_pages'                    => array(
				'title'   => __( 'Number Of Pages To Show?', 'wp-commentnavi' ),
				'section' => self::SECTION_DISPLAY,
			),
			'num_larger_page_numbers'      => array(
				'title'   => __( 'Number Of Larger Page Numbers To Show', 'wp-commentnavi' ),
				'section' => self::SECTION_DISPLAY,
			),
			'larger_page_numbers_multiple' => array(
				'title'   => __( 'Show Larger Page Numbers In Multiples Of', 'wp-commentnavi' ),
				'section' => self::SECTION_DISPLAY,
			),
		);
	}

	/**
	 * Intro copy for the text section.
	 *
	 * @return void
	 */
	public static function section_text() {
		echo '<p>' . esc_html__( 'Leaving a field blank will hide that part of the navigation.', 'wp-commentnavi' ) . '</p>';
	}

	/**
	 * The "Page x of y" label.
	 *
	 * @return void
	 */
	public static function field_pages_text() {
		self::text( 'pages_text' );
		self::tokens(
			array(
				'%CURRENT_PAGE%' => __( 'The current page number.', 'wp-commentnavi' ),
				'%TOTAL_PAGES%'  => __( 'The total number of pages.', 'wp-commentnavi' ),
			)
		);
	}

	/**
	 * The label on the page the visitor is reading.
	 *
	 * @return void
	 */
	public static function field_current_text() {
		self::text( 'current_text' );
		self::tokens( array( '%PAGE_NUMBER%' => __( 'The page number.', 'wp-commentnavi' ) ) );
	}

	/**
	 * The label on every other numbered page link.
	 *
	 * @return void
	 */
	public static function field_page_text() {
		self::text( 'page_text' );
		self::tokens( array( '%PAGE_NUMBER%' => __( 'The page number.', 'wp-commentnavi' ) ) );
	}

	/**
	 * The label on the link to the first page.
	 *
	 * @return void
	 */
	public static function field_first_text() {
		self::text( 'first_text' );
		self::tokens( array( '%TOTAL_PAGES%' => __( 'The total number of pages.', 'wp-commentnavi' ) ) );
	}

	/**
	 * The label on the link to the last page.
	 *
	 * @return void
	 */
	public static function field_last_text() {
		self::text( 'last_text' );
		self::tokens( array( '%TOTAL_PAGES%' => __( 'The total number of pages.', 'wp-commentnavi' ) ) );
	}

	/**
	 * The label on the link to the previous page.
	 *
	 * @return void
	 */
	public static function field_prev_text() {
		self::text( 'prev_text' );
	}

	/**
	 * The label on the link to the next page.
	 *
	 * @return void
	 */
	public static function field_next_text() {
		self::text( 'next_text' );
	}

	/**
	 * The ellipsis shown before the page-number window.
	 *
	 * The value is read by its own key. The pre-2.0.0 screen rendered this field
	 * from dotright_text, so editing it silently saved whatever was in the "Text
	 * For Next ..." box and the two settings could never be given different
	 * values through the UI.
	 *
	 * @return void
	 */
	public static function field_dotleft_text() {
		self::text( 'dotleft_text' );
	}

	/**
	 * The ellipsis shown after the page-number window.
	 *
	 * @return void
	 */
	public static function field_dotright_text() {
		self::text( 'dotright_text' );
	}

	/**
	 * Whether to enqueue the bundled stylesheet.
	 *
	 * @return void
	 */
	public static function field_use_commentnavi_css() {
		self::radio( 'use_commentnavi_css', self::yes_no() );
	}

	/**
	 * Numbered links or a drop-down list.
	 *
	 * @return void
	 */
	public static function field_style() {
		self::select(
			'style',
			array(
				1 => __( 'Normal', 'wp-commentnavi' ),
				2 => __( 'Drop Down List', 'wp-commentnavi' ),
			)
		);
	}

	/**
	 * Whether to show the navigation on a single-page thread.
	 *
	 * @return void
	 */
	public static function field_always_show() {
		self::radio( 'always_show', self::yes_no() );
		self::notes( array( __( 'Show navigation even if there\'s only one page of comments.', 'wp-commentnavi' ) ) );
	}

	/**
	 * How wide the page-number window is.
	 *
	 * @return void
	 */
	public static function field_num_pages() {
		self::number( 'num_pages' );
	}

	/**
	 * How many larger page numbers to show on each side of the window.
	 *
	 * @return void
	 */
	public static function field_num_larger_page_numbers() {
		self::number( 'num_larger_page_numbers' );
		self::notes(
			array(
				__( 'Larger page numbers are in addition to the normal page numbers. They are useful when a post has many pages of comments.', 'wp-commentnavi' ),
				__( 'For example, WP-CommentNavi will display: Pages 1, 2, 3, 4, 5, 10, 20, 30, 40, 50.', 'wp-commentnavi' ),
				__( 'Enter 0 to disable. New in 2.0.0, and off by default so that upgrading does not change what the navigation looks like.', 'wp-commentnavi' ),
			)
		);
	}

	/**
	 * The step between larger page numbers.
	 *
	 * @return void
	 */
	public static function field_larger_page_numbers_multiple() {
		self::number( 'larger_page_numbers_multiple' );
		self::notes( array( __( 'For example, if multiple is 5, it will show: 5, 10, 15, 20, 25', 'wp-commentnavi' ) ) );
	}

	/**
	 * The choices shared by every yes/no setting.
	 *
	 * @return array
	 */
	protected static function yes_no() {
		return array(
			1 => __( 'Yes', 'wp-commentnavi' ),
			0 => __( 'No', 'wp-commentnavi' ),
		);
	}

	/**
	 * The id attribute for a field, matching the label_for it was registered with.
	 *
	 * @param string $name Option key.
	 * @return string
	 */
	protected static function id( $name ) {
		return self::PAGE . '-' . $name;
	}

	/**
	 * The name attribute for a field, which posts into the settings array.
	 *
	 * @param string $name Option key.
	 * @return string
	 */
	protected static function name( $name ) {
		return WP_CommentNavi_Options::OPTION . '[' . $name . ']';
	}

	/**
	 * Print the notes shown beneath a field.
	 *
	 * @param array $notes Lines of help text.
	 * @return void
	 */
	protected static function notes( array $notes ) {
		foreach ( $notes as $note ) {
			printf( '<p class="description">%s</p>', esc_html( $note ) );
		}
	}

	/**
	 * Print the list of %TOKEN% placeholders a text field understands.
	 *
	 * The placeholders are listed separately from the translated label so they
	 * never end up inside a translatable string, where a formatting pass would
	 * rewrite them into numbered printf placeholders.
	 *
	 * @param array $tokens Token name to description.
	 * @return void
	 */
	protected static function tokens( array $tokens ) {
		foreach ( $tokens as $token => $description ) {
			printf(
				'<p class="description"><code>%1$s</code> &mdash; %2$s</p>',
				esc_html( $token ),
				esc_html( $description )
			);
		}
	}

	/**
	 * Print a single-line text box.
	 *
	 * @param string $name Option key.
	 * @return void
	 */
	protected static function text( $name ) {
		printf(
			'<input type="text" id="%1$s" name="%2$s" value="%3$s" class="regular-text" />',
			esc_attr( self::id( $name ) ),
			esc_attr( self::name( $name ) ),
			esc_attr( (string) WP_CommentNavi_Options::get( $name ) )
		);
	}

	/**
	 * Print a whole-number box.
	 *
	 * @param string $name Option key.
	 * @return void
	 */
	protected static function number( $name ) {
		printf(
			'<input type="number" min="0" step="1" id="%1$s" name="%2$s" value="%3$s" class="small-text" />',
			esc_attr( self::id( $name ) ),
			esc_attr( self::name( $name ) ),
			esc_attr( (string) WP_CommentNavi_Options::get( $name ) )
		);
	}

	/**
	 * Print a set of radio buttons.
	 *
	 * @param string $name    Option key.
	 * @param array  $choices Value to label.
	 * @return void
	 */
	protected static function radio( $name, array $choices ) {
		$value = (int) WP_CommentNavi_Options::get( $name );

		echo '<fieldset>';

		// The first radio carries the id the field was registered with, so the
		// label do_settings_fields() prints has something to point at.
		$first = true;

		foreach ( $choices as $choice => $label ) {
			printf(
				'<label><input type="radio" id="%1$s" name="%2$s" value="%3$s"%4$s /> %5$s</label> ',
				esc_attr( $first ? self::id( $name ) : self::id( $name ) . '-' . $choice ),
				esc_attr( self::name( $name ) ),
				esc_attr( $choice ),
				checked( $value, (int) $choice, false ),
				esc_html( $label )
			);

			$first = false;
		}

		echo '</fieldset>';
	}

	/**
	 * Print a select box.
	 *
	 * @param string $name    Option key.
	 * @param array  $choices Value to label.
	 * @return void
	 */
	protected static function select( $name, array $choices ) {
		$value = (int) WP_CommentNavi_Options::get( $name );

		printf(
			'<select id="%1$s" name="%2$s">',
			esc_attr( self::id( $name ) ),
			esc_attr( self::name( $name ) )
		);

		foreach ( $choices as $choice => $label ) {
			printf(
				'<option value="%1$s"%2$s>%3$s</option>',
				esc_attr( $choice ),
				selected( $value, (int) $choice, false ),
				esc_html( $label )
			);
		}

		echo '</select>';
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public static function render_page() {
		if ( ! current_user_can( self::capability() ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wp-commentnavi' ) );
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'CommentNavi Settings', 'wp-commentnavi' ); ?></h1>
			<?php settings_errors(); ?>
			<form method="post" action="options.php">
				<?php
				settings_fields( self::GROUP );
				do_settings_sections( self::PAGE );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
}
