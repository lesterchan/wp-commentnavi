<?php
/**
 * Settings screen tests.
 *
 * @package WP-CommentNavi
 */

/**
 * Covers CommentNavi_Admin.
 */
class Test_CommentNavi_Admin extends WP_UnitTestCase {

	/**
	 * Reset the options between tests.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		delete_option( CommentNavi_Options::OPTION_NAME );
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
		CommentNavi_Admin::$method( $args );
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
		CommentNavi_Options::update(
			array_merge(
				CommentNavi_Options::get_defaults(),
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
		$clean = CommentNavi_Admin::sanitize( array( 'first_text' => "O'Brien &laquo; First" ) );

		$this->assertSame( "O'Brien &laquo; First", $clean['first_text'] );
		$this->assertStringNotContainsString( '\\', $clean['first_text'] );
	}

	/**
	 * Saving the same value twice does not accumulate anything.
	 *
	 * @return void
	 */
	public function test_saving_twice_is_stable() {
		$once  = CommentNavi_Admin::sanitize( array( 'prev_text' => "it's &laquo;" ) );
		$twice = CommentNavi_Admin::sanitize( $once );

		$this->assertSame( $once['prev_text'], $twice['prev_text'] );
	}

	/**
	 * Keys the plugin does not define are dropped rather than stored forever.
	 *
	 * @return void
	 */
	public function test_unknown_keys_are_discarded() {
		$clean = CommentNavi_Admin::sanitize(
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
	 * A partial submission leaves the settings it did not mention alone.
	 *
	 * @return void
	 */
	public function test_partial_submission_preserves_other_settings() {
		CommentNavi_Options::update(
			array_merge( CommentNavi_Options::get_defaults(), array( 'num_pages' => 11 ) )
		);

		$clean = CommentNavi_Admin::sanitize( array( 'style' => 2 ) );

		$this->assertSame( 2, $clean['style'] );
		$this->assertSame( 11, $clean['num_pages'] );
	}

	/**
	 * Numeric settings are cast, and a negative number cannot be stored.
	 *
	 * @return void
	 */
	public function test_integer_settings_are_cast() {
		$clean = CommentNavi_Admin::sanitize(
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
		$clean = CommentNavi_Admin::sanitize( array( 'pages_text' => 'Page <script>alert(1)</script>' ) );

		$this->assertStringNotContainsString( '<script', $clean['pages_text'] );
	}

	/**
	 * An array posted where a text setting belongs does not raise a notice.
	 *
	 * @return void
	 */
	public function test_array_posted_into_a_text_setting() {
		$clean = CommentNavi_Admin::sanitize( array( 'prev_text' => array( 'x' ) ) );

		$this->assertSame( '', $clean['prev_text'] );
	}

	/**
	 * The select and radio renderers mark the stored value as chosen.
	 *
	 * @return void
	 */
	public function test_current_values_are_preselected() {
		CommentNavi_Options::update(
			array_merge(
				CommentNavi_Options::get_defaults(),
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
		CommentNavi_Options::update(
			array_merge(
				CommentNavi_Options::get_defaults(),
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
		$this->assertSame( 'commentnavi', CommentNavi_Admin::PAGE_SLUG );
		$this->assertStringNotContainsString( '/', CommentNavi_Admin::PAGE_SLUG );
		$this->assertStringNotContainsString( '.php', CommentNavi_Admin::PAGE_SLUG );
	}

	/**
	 * A Settings link is added to the plugin's row on the Plugins screen.
	 *
	 * @return void
	 */
	public function test_action_links() {
		$links = CommentNavi_Admin::action_links( array( '<a href="#">Deactivate</a>' ) );

		$this->assertCount( 2, $links );
		$this->assertStringContainsString( 'options-general.php?page=commentnavi', $links[0] );
	}
}
