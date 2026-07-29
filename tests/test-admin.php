<?php
/**
 * Settings screen tests.
 *
 * @package WP-CommentNavi
 */

/**
 * Covers WP_CommentNavi_Settings.
 */
class WP_CommentNavi_Settings_Test extends WP_CommentNavi_TestCase {

	/**
	 * Render one registered settings field and return its markup.
	 *
	 * The callback is looked up the same way register_settings() registers it,
	 * so a field renamed in one place and not the other fails here.
	 *
	 * @param string $name Option key.
	 * @return string
	 */
	protected function render_field( $name ) {
		$method = 'field_' . $name;

		ob_start();
		WP_CommentNavi_Settings::$method();
		return ob_get_clean();
	}

	public function test_dotleft_field_shows_dotleft_not_dotright() {
		// The pre-2.0.0 screen rendered this field from dotright_text. Both boxes
		// therefore displayed the same value, and editing the left one saved whatever
		// the right one happened to contain, so the two settings could never be given
		// different values through the UI at all.

		WP_CommentNavi_Options::update(
			array_merge(
				WP_CommentNavi_Options::get_defaults(),
				array(
					'dotleft_text'  => 'LEFTDOTS',
					'dotright_text' => 'RIGHTDOTS',
				)
			)
		);

		$left  = $this->render_field( 'dotleft_text' );
		$right = $this->render_field( 'dotright_text' );

		$this->assertStringContainsString( 'value="LEFTDOTS"', $left );
		$this->assertStringNotContainsString( 'RIGHTDOTS', $left );
		$this->assertStringContainsString( 'value="RIGHTDOTS"', $right );
	}

	public function test_apostrophe_is_stored_as_typed() {
		// The pre-2.0.0 handler ran addslashes() over the slashes WordPress already
		// adds to $_POST, then stripped one layer back off for display only. The front
		// end stripped nothing, so every save added a visible backslash to the site.

		$clean = WP_CommentNavi_Options::sanitize( array( 'first_text' => "O'Brien &laquo; First" ) );

		$this->assertSame( "O'Brien &laquo; First", $clean['first_text'] );
		$this->assertStringNotContainsString( '\\', $clean['first_text'] );
	}

	public function test_saving_twice_is_stable() {
		$once  = WP_CommentNavi_Options::sanitize( array( 'prev_text' => "it's &laquo;" ) );
		$twice = WP_CommentNavi_Options::sanitize( $once );

		$this->assertSame( $once['prev_text'], $twice['prev_text'] );
	}

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

	public function test_partial_submission_falls_back_to_the_defaults() {
		// The sanitiser is a function from what the form posted to what gets stored
		// and reads nothing back out of the database, which is what stops a version
		// marker or any other row's value being dragged in behind it. The settings
		// form posts every field, so a partial submission only ever arrives from a
		// hand-crafted request.

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

	public function test_scripts_are_stripped_on_save() {
		$clean = WP_CommentNavi_Options::sanitize( array( 'pages_text' => 'Page <script>alert(1)</script>' ) );

		$this->assertStringNotContainsString( '<script', $clean['pages_text'] );
	}

	public function test_array_posted_into_a_text_setting() {
		$clean = WP_CommentNavi_Options::sanitize( array( 'prev_text' => array( 'x' ) ) );

		$this->assertSame( '', $clean['prev_text'] );
	}

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

		$style = $this->render_field( 'style' );
		$this->assertMatchesRegularExpression( '/value="2"\s+selected/', $style );

		$always = $this->render_field( 'always_show' );
		$this->assertMatchesRegularExpression( '/value="1"\s+checked/', $always );
	}

	public function test_text_field_escapes_its_value() {
		WP_CommentNavi_Options::update(
			array_merge(
				WP_CommentNavi_Options::get_defaults(),
				array( 'first_text' => 'x" onfocus="XSSPROBE' )
			)
		);

		$html = $this->render_field( 'first_text' );

		$doc = new DOMDocument();
		$use = libxml_use_internal_errors( true );
		$doc->loadHTML( '<?xml encoding="utf-8" ?><div>' . $html . '</div>' );
		libxml_clear_errors();
		libxml_use_internal_errors( $use );

		$xpath = new DOMXPath( $doc );
		$this->assertSame( 0, $xpath->query( '//*[@onfocus]' )->length );
	}

	public function test_page_slug_is_not_a_file_path() {
		// The settings page is registered under Settings with a slug that does not
		// contain the plugin's directory name.

		$this->assertSame( WP_COMMENTNAVI_SLUG, WP_CommentNavi_Settings::PAGE );
		$this->assertStringNotContainsString( '/', WP_CommentNavi_Settings::PAGE );
		$this->assertStringNotContainsString( '.php', WP_CommentNavi_Settings::PAGE );
	}

	public function test_capability_defaults_to_manage_options() {
		$this->assertSame( 'manage_options', WP_CommentNavi_Settings::CAPABILITY );
		$this->assertSame( 'manage_options', WP_CommentNavi_Settings::capability() );
	}

	public function test_capability_filter_is_honoured() {
		// Every capability check goes through one filter, which is handed the context
		// it is being asked about.

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

	public function test_action_links() {
		$links = WP_CommentNavi_Settings::action_links( array( '<a href="#">Deactivate</a>' ) );

		$this->assertCount( 2, $links );
		$this->assertStringContainsString( 'options-general.php?page=' . WP_COMMENTNAVI_SLUG, $links[0] );
	}

	public function test_every_setting_has_a_registered_field() {
		// Every field the screen registers has a callback to render it and a section
		// to render it in, and every setting the plugin stores has a field.

		$fields   = WP_CommentNavi_Settings::fields();
		$sections = array( WP_CommentNavi_Settings::SECTION_TEXT, WP_CommentNavi_Settings::SECTION_DISPLAY );

		// Compared as sets, not in order: fields() is in the order the screen has
		// always shown the settings in, which is a deliberate choice, and
		// get_defaults() has no order worth coupling it to.
		$expected = array_keys( WP_CommentNavi_Options::get_defaults() );
		$actual   = array_keys( $fields );
		sort( $expected );
		sort( $actual );

		$this->assertSame(
			$expected,
			$actual,
			'the settings screen and the option defaults disagree about which settings exist.'
		);

		foreach ( $fields as $name => $field ) {
			$this->assertTrue(
				method_exists( 'WP_CommentNavi_Settings', 'field_' . $name ),
				$name . ' is registered but has no field_' . $name . '() callback.'
			);
			$this->assertContains( $field['section'], $sections, $name . ' is in a section that is never registered.' );
			$this->assertNotEmpty( $field['title'], $name . ' has no title.' );
		}
	}

	public function test_section_constants_are_prefixed() {
		// The section ids are prefixed with the plugin, and the display section is
		// not spelled the same as the settings row.

		$this->assertSame( 'wp_commentnavi_text', WP_CommentNavi_Settings::SECTION_TEXT );
		$this->assertSame( 'wp_commentnavi_display', WP_CommentNavi_Settings::SECTION_DISPLAY );
		$this->assertNotSame( WP_CommentNavi_Options::OPTION, WP_CommentNavi_Settings::SECTION_DISPLAY );
	}

	public function test_fields_post_into_the_settings_array() {
		// Every field posts into the settings array and carries the id its label
		// points at.

		foreach ( array_keys( WP_CommentNavi_Settings::fields() ) as $name ) {
			$html = $this->render_field( $name );

			$this->assertStringContainsString(
				'name="' . WP_CommentNavi_Options::OPTION . '[' . $name . ']"',
				$html,
				$name . ' does not post into the ' . WP_CommentNavi_Options::OPTION . ' array.'
			);
			$this->assertStringContainsString(
				'id="' . WP_CommentNavi_Settings::PAGE . '-' . $name . '"',
				$html,
				$name . ' does not carry the id its label_for points at.'
			);
		}
	}

	public function test_the_screen_hand_writes_no_table_and_no_inline_attributes() {
		// §4.2 allows zero hand-written <table class="form-table">, and §4.4 forbids
		// inline style, width, valign and align attributes anywhere in the markup.

		$source = file_get_contents( dirname( __DIR__ ) . '/includes/class-wp-commentnavi-settings.php' );

		$this->assertStringNotContainsString( 'form-table', $source, 'do_settings_sections() emits the form table.' );
		$this->assertStringNotContainsString( '<table', $source, 'the settings screen hand-writes a table.' );

		foreach ( array( 'style=', 'width=', 'valign=', 'align=' ) as $attribute ) {
			$this->assertStringNotContainsString(
				$attribute,
				$source,
				'the settings screen uses an inline ' . rtrim( $attribute, '=' ) . ' attribute.'
			);
		}
	}
}
