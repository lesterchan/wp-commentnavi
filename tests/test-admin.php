<?php
/**
 * Settings screen tests.
 *
 * @package WP-CommentNavi
 */

/**
 * Covers WP_CommentNavi_Settings.
 */
class Test_CommentNavi_Admin extends WP_UnitTestCase {

	/**
	 * Reset the options between tests.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		delete_option( WP_CommentNavi_Options::OPTION );
		delete_option( WP_CommentNavi_Options::VERSION );
		delete_option( WP_CommentNavi_Options::LEGACY_OPTION );
	}

	/**
	 * Render one settings field and return its markup.
	 *
	 * @param string $method Renderer method name.
	 * @param string $name   Option key.
	 * @param array  $extra  Extra field arguments.
	 * @return string
	 */
	protected function render_field( $method, $name, array $extra = array() ) {
		$args = array_merge(
			array(
				'label_for' => 'commentnavi-' . $name,
				'name'      => $name,
				'class_'    => '',
				'tokens'    => array(),
				'choices'   => array(),
				'notes'     => array(),
			),
			$extra
		);

		ob_start();
		WP_CommentNavi_Settings::$method( $args );
		return ob_get_clean();
	}

	/**
	 * The "Text For Previous ..." field shows dotleft_text.
	 *
	 * The pre-2.0.0 screen rendered this field from dotright_text. Both boxes
	 * therefore displayed the same value, and editing the left one saved whatever
	 * the right one happened to contain, so the two settings could never be given
	 * different values through the UI at all.
	 *
	 * @return void
	 */
	public function test_dotleft_field_shows_dotleft_not_dotright() {
		WP_CommentNavi_Options::update(
			array_merge(
				WP_CommentNavi_Options::get_defaults(),
				array(
					'dotleft_text'  => 'LEFTDOTS',
					'dotright_text' => 'RIGHTDOTS',
				)
			)
		);

		$left  = $this->render_field( 'render_text_field', 'dotleft_text' );
		$right = $this->render_field( 'render_text_field', 'dotright_text' );

		$this->assertStringContainsString( 'value="LEFTDOTS"', $left );
		$this->assertStringNotContainsString( 'RIGHTDOTS', $left );
		$this->assertStringContainsString( 'value="RIGHTDOTS"', $right );
	}

	/**
	 * An apostrophe survives a save without growing a backslash.
	 *
	 * The pre-2.0.0 handler ran addslashes() over the slashes WordPress already
	 * adds to $_POST, then stripped one layer back off for display only. The front
	 * end stripped nothing, so every save added a visible backslash to the site.
	 *
	 * @return void
	 */
	public function test_apostrophe_is_stored_as_typed() {
		$clean = WP_CommentNavi_Options::sanitize( array( 'first_text' => "O'Brien &laquo; First" ) );

		$this->assertSame( "O'Brien &laquo; First", $clean['first_text'] );
		$this->assertStringNotContainsString( '\\', $clean['first_text'] );
	}

	/**
	 * Saving the same value twice does not accumulate anything.
	 *
	 * @return void
	 */
	public function test_saving_twice_is_stable() {
		$once  = WP_CommentNavi_Options::sanitize( array( 'prev_text' => "it's &laquo;" ) );
		$twice = WP_CommentNavi_Options::sanitize( $once );

		$this->assertSame( $once['prev_text'], $twice['prev_text'] );
	}

	/**
	 * Keys the plugin does not define are dropped rather than stored forever.
	 *
	 * @return void
	 */
	public function test_unknown_keys_are_discarded() {
		$clean = WP_CommentNavi_Options::sanitize(
			array(
				'num_pages'   => 4,
				'evil_key'    => 'payload',
				'another_one' => array( 'nested' => true ),
			)
		);

		$this->assertSame( 4, $clean['num_pages'] );
		$this->assertArrayNotHasKey( 'evil_key', $clean );
		$this->assertArrayNotHasKey( 'another_one', $clean );
	}

	/**
	 * A partial submission fills the settings it did not mention from the
	 * defaults, not from what happens to be stored.
	 *
	 * The sanitiser is a function from what the form posted to what gets stored
	 * and reads nothing back out of the database, which is what stops a version
	 * marker or any other row's value being dragged in behind it. The settings
	 * form posts every field, so a partial submission only ever arrives from a
	 * hand-crafted request.
	 *
	 * @return void
	 */
	public function test_partial_submission_falls_back_to_the_defaults() {
		WP_CommentNavi_Options::update(
			array_merge( WP_CommentNavi_Options::get_defaults(), array( 'num_pages' => 11 ) )
		);

		$clean = WP_CommentNavi_Options::sanitize( array( 'style' => 2 ) );

		$this->assertSame( 2, $clean['style'] );
		$this->assertSame(
			WP_CommentNavi_Options::get_defaults()['num_pages'],
			$clean['num_pages'],
			'the sanitiser reached back into the stored row instead of the defaults.'
		);
	}

	/**
	 * Numeric settings are cast, and a negative number cannot be stored.
	 *
	 * @return void
	 */
	public function test_integer_settings_are_cast() {
		$clean = WP_CommentNavi_Options::sanitize(
			array(
				'num_pages'               => '7',
				'num_larger_page_numbers' => '-3',
			)
		);

		$this->assertSame( 7, $clean['num_pages'] );
		$this->assertSame( 3, $clean['num_larger_page_numbers'] );
	}

	/**
	 * Markup submitted into a text setting is filtered on save.
	 *
	 * @return void
	 */
	public function test_scripts_are_stripped_on_save() {
		$clean = WP_CommentNavi_Options::sanitize( array( 'pages_text' => 'Page <script>alert(1)</script>' ) );

		$this->assertStringNotContainsString( '<script', $clean['pages_text'] );
	}

	/**
	 * An array posted where a text setting belongs does not raise a notice.
	 *
	 * @return void
	 */
	public function test_array_posted_into_a_text_setting() {
		$clean = WP_CommentNavi_Options::sanitize( array( 'prev_text' => array( 'x' ) ) );

		$this->assertSame( '', $clean['prev_text'] );
	}

	/**
	 * The select and radio renderers mark the stored value as chosen.
	 *
	 * @return void
	 */
	public function test_current_values_are_preselected() {
		WP_CommentNavi_Options::update(
			array_merge(
				WP_CommentNavi_Options::get_defaults(),
				array(
					'style'       => 2,
					'always_show' => 1,
				)
			)
		);

		$style = $this->render_field(
			'render_select_field',
			'style',
			array(
				'choices' => array(
					1 => 'Normal',
					2 => 'Drop-down List',
				),
			)
		);
		$this->assertMatchesRegularExpression( '/value="2"\s+selected/', $style );

		$always = $this->render_field(
			'render_radio_field',
			'always_show',
			array(
				'choices' => array(
					1 => 'Yes',
					0 => 'No',
				),
			)
		);
		$this->assertMatchesRegularExpression( '/value="1"\s+checked/', $always );
	}

	/**
	 * A quote in a stored value cannot break out of the input's value attribute.
	 *
	 * @return void
	 */
	public function test_text_field_escapes_its_value() {
		WP_CommentNavi_Options::update(
			array_merge(
				WP_CommentNavi_Options::get_defaults(),
				array( 'first_text' => 'x" onfocus="XSSPROBE' )
			)
		);

		$html = $this->render_field( 'render_text_field', 'first_text' );

		$doc = new DOMDocument();
		$use = libxml_use_internal_errors( true );
		$doc->loadHTML( '<?xml encoding="utf-8" ?><div>' . $html . '</div>' );
		libxml_clear_errors();
		libxml_use_internal_errors( $use );

		$xpath = new DOMXPath( $doc );
		$this->assertSame( 0, $xpath->query( '//*[@onfocus]' )->length );
	}

	/**
	 * The settings page is registered under Settings with a slug that does not
	 * contain the plugin's directory name.
	 *
	 * @return void
	 */
	public function test_page_slug_is_not_a_file_path() {
		$this->assertSame( WP_COMMENTNAVI_SLUG, WP_CommentNavi_Settings::PAGE );
		$this->assertStringNotContainsString( '/', WP_CommentNavi_Settings::PAGE );
		$this->assertStringNotContainsString( '.php', WP_CommentNavi_Settings::PAGE );
	}

	/**
	 * The settings screen asks for manage_options unless a filter says otherwise.
	 *
	 * @return void
	 */
	public function test_capability_defaults_to_manage_options() {
		$this->assertSame( 'manage_options', WP_CommentNavi_Settings::CAPABILITY );
		$this->assertSame( 'manage_options', WP_CommentNavi_Settings::capability() );
	}

	/**
	 * Every capability check goes through one filter, which is handed the context
	 * it is being asked about.
	 *
	 * @return void
	 */
	public function test_capability_filter_is_honoured() {
		$seen = null;

		$replace = static function ( $capability, $context ) use ( &$seen ) {
			$seen = $context;
			return 'edit_pages';
		};
		add_filter( 'wp_commentnavi_capability', $replace, 10, 2 );

		$capability = WP_CommentNavi_Settings::capability();

		remove_filter( 'wp_commentnavi_capability', $replace, 10 );

		$this->assertSame( 'edit_pages', $capability );
		$this->assertSame( 'settings', $seen, 'the filter was not told which context it was being asked about.' );
	}

	/**
	 * A Settings link is added to the plugin's row on the Plugins screen.
	 *
	 * @return void
	 */
	public function test_action_links() {
		$links = WP_CommentNavi_Settings::action_links( array( '<a href="#">Deactivate</a>' ) );

		$this->assertCount( 2, $links );
		$this->assertStringContainsString( 'options-general.php?page=' . WP_COMMENTNAVI_SLUG, $links[0] );
	}
}
