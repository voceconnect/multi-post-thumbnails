<?php


class TestMultiPostThumbnails extends Voce_WP_UnitTestCase {

	private $errors;

	public function setUp() {

		parent::setUp();
		$this->errors = array();
		set_error_handler( array( $this, 'errorHandler' ) );


	}

	/**
	 * If these tests are being run on Travis CI, verify that the version of
	 * WordPress installed is the version that we requested.
	 *
	 * @requires PHP 5.3
	 */
	function test_wp_version() {

		if ( !getenv( 'TRAVIS' ) )
			$this->markTestSkipped( 'Test skipped since Travis CI was not detected.' );

		$requested_version = getenv( 'WP_VERSION' );

		// The "latest" version requires special handling.
		if ( 'latest' === $requested_version ) {

			$file = file_get_contents( ABSPATH . WPINC . '/version.php' );
			preg_match( '#\$wp_version = \'([^\']+)\';#', $file, $matches );
			$requested_version = $matches[1];

		}

		$this->assertEquals( get_bloginfo( 'version' ), $requested_version );

	}

	/**
	 * Ensure that the plugin has been installed and activated.
	 */
	function test_plugin_activated() {

		$this->assertTrue( is_plugin_active( 'multi-post-thumbnails/multi-post-thumbnails.php' ) );

	}

	public function errorHandler( $errno, $errstr, $errfile, $errline, $errcontext ) {

		$this->errors[] = compact(
			'errno',
			'errstr',
			'errfile',
			'errline',
			'errcontext'
		);

	}

	public function assertError( $errstr, $errno = E_USER_NOTICE ) {

		foreach ( $this->errors as $error ) {

			if ( ( $errstr === $error['errstr'] ) && ( $errno === $error['errno'] ) ) {

				return;

			}

		}

		$fail_message = sprintf( 'Error with level %s and message "%s" not found in %s', $errno, $errstr, var_export( $this->errors, true ) );

		$this->fail( $fail_message );

	}

	function provider_test_register() {

		/**
		 * Arguments for register() test:
		 * - array $register_args - passed in to register() call
		 * - array $expected_func_calls - functions we expect to be called
		 * - bool $theme_has_thumbnail_support - whether or not post-thumbnails support has been added
		 */
		return array(
			/**
			 * All required args are specified
			 */
			array(
				array(
					'id'    => 'thumbnail-id',
					'label' => 'Thumbnail Label'
				),
				array(
					'attach_hooks',
				),
				true
			),
			/**
			 * Missing required "id" arg
			 */
			array(
				array(
					'label' => 'Thumbnail Label'
				),
				array(
					'trigger_registration_error',
				),
				false
			),
			/**
			 * Missing required "label" arg
			 */
			array(
				array(
					'id' => 'thumbnail-id'
				),
				array(
					'trigger_registration_error',
				),
				false
			)
		);

	}

	/**
	 * @dataProvider provider_test_register
	 * @covers MultiPostThumbnails::register
	 */
	function test_register( $register_args, $expected_func_calls, $theme_has_thumbnail_support ) {

		// clean up side effects from other data sets
		// @TODO determine where to put this logic, or if it's even necessary
		remove_theme_support( MultiPostThumbnails::THEME_SUPPORT );

		$mpt = $this->getMockBuilder( 'MultiPostThumbnails' )
				->disableOriginalConstructor()
				->setMethods( $expected_func_calls )
				->getMock();

		foreach ( $expected_func_calls as $expected_func ) {

			$mpt->expects( $this->once() )
				->method( $expected_func );

		}

		$mpt->register( $register_args );

		$this->assertEquals( $theme_has_thumbnail_support, current_theme_supports( MultiPostThumbnails::THEME_SUPPORT ) );

	}

	/**
	 * @covers MultiPostThumbnails::trigger_registration_error
	 */
	function test_trigger_registration_error() {

		$error_message_method = 'get_register_required_field_error_message';
		$error_message        = 'Error';

		$mpt = $this->getMockBuilder( 'MultiPostThumbnails' )
				->disableOriginalConstructor()
				->setMethods( array( $error_message_method ) )
				->getMock();

		$mpt->expects( $this->once() )
			->method( $error_message_method )
			->will( $this->returnValue( $error_message ) );

		$mpt->trigger_registration_error();

		$this->assertError( $error_message );

	}

	/**
	 * @covers MultiPostThumbnails::attach_hooks
	 */
	function test_attach_hooks_wp_35() {

		$GLOBALS['wp_version'] = '3.5';

		$mpt = new MultiPostThumbnails( array(
			'label'     => 'Foo',
			'id'        => 'foo',
			'post_type' => 'post'
		) );

		$this->assertEquals( 10, has_action( 'add_meta_boxes', array( $mpt, 'add_metabox' ) ) );
		$this->assertEquals( 10, has_action( 'admin_print_scripts-post.php', array( $mpt, 'admin_header_scripts' ) ) );
		$this->assertEquals( 10, has_action( 'admin_print_scripts-post-new.php', array( $mpt, 'admin_header_scripts' ) ) );
		$this->assertEquals( 10, has_action( 'wp_ajax_set-post-foo-thumbnail', array( $mpt, 'set_thumbnail' ) ) );
		$this->assertEquals( 10, has_action( 'delete_attachment', array( $mpt, 'action_delete_attachment' ) ) );
		$this->assertEquals( 20, has_filter( 'is_protected_meta', array( $mpt, 'filter_is_protected_meta' ) ) );

		// WP 3.5 and above shouldn't get the attachment_fields_to_edit filter
		$this->assertFalse( has_filter( 'attachment_fields_to_edit', array( $mpt, 'add_attachment_field' ) ) );

	}

	/**
	 * @covers MultiPostThumbnails::attach_hooks
	 */
	function test_attach_hooks_pre_wp_35() {

		$GLOBALS['wp_version'] = '3.4.2';

		$mpt = new MultiPostThumbnails( array(
			'label'     => 'Foo',
			'id'        => 'foo',
			'post_type' => 'post'
		) );

		// WP 3.4.x and below should get the attachment_fields_to_edit filter
		$this->assertEquals( 20, has_filter( 'attachment_fields_to_edit', array( $mpt, 'add_attachment_field' ) ) );

	}

	/**
	 * @covers MultiPostThumbnails::get_meta_key
	 */
	function test_get_meta_key() {

		// create an MPT instance, manually construct what the meta key should be for that instance
		$mpt = new MultiPostThumbnails( array( 'label' => 'foo', 'id' => 'bar', 'post_type' => 'post' ) );


		$expected = 'post_bar_thumbnail_id';

		// get the meta key
		$actual = $mpt->get_meta_key();

		$this->assertEquals( $expected, $actual );

	}

	/**
	 * @covers MultiPostThumbnails::add_metabox
	 */
	function test_add_metabox() {

		global $wp_meta_boxes;

		$mpt = new MultiPostThumbnails( array( 'label' => 'foo', 'id' => 'bar', 'post_type' => 'post', 'context' => 'default', 'priority' => 'high' ) );

		$mpt->add_metabox();

		$this->assertArrayHasKey( 'post-bar', $wp_meta_boxes['post']['default']['high'] );

	}

	/**
	 * @covers MultiPostThumbnails::thumbnail_meta_box
	 */
	function test_thumbnail_meta_box() {

		$thumbnail_id = 'barfoo';
		$meta_key = 'fozbar';
		$post = $this->factory->post->create_and_get();
		$GLOBALS['post'] = $post;

		//manually set the value
		update_post_meta( $post->ID, $meta_key, $thumbnail_id );

		$mpt = $this->getMockBuilder( 'MultiPostThumbnails' )
			->disableOriginalConstructor()
			->setMethods( array( 'get_meta_key', 'post_thumbnail_html' ) )
			->getMock();

		$mpt->expects( $this->once() )
			->method( 'get_meta_key' )
			->will( $this->returnvalue( $meta_key ) );

		$mpt->expects( $this->once() )
			->method( 'post_thumbnail_html')
			->with ( $this->equalTo( $thumbnail_id ) );

		$mpt->thumbnail_meta_box();

	}

	/**
	 * Feed the post_id in to the $_GET superglobal
	 *
	 * @covers MultiPostThumbnails::add_attachment_field
	 */
	function test_add_attachment_field_post_set_in_get() {

		$post  = $this->factory->post->create_and_get();
		$post_id = $post->ID;
		$post_type = $post->post_type;

		$_GET['post_id'] = $post_id;
		$id = 'foo';

		$mpt = new MultiPostThumbnails( array(
			'label'     => 'Bar',
			'id'        => $id,
			'post_type' => $post_type
		) );

		$field = $mpt->add_attachment_field( array(), $post );
		$this->arrayHasKey( sprintf( '%s-%s-thumbnail', $post_type, $id ), $field );

	}

	/**
	 * Feed an array into $_POST to test async-upload
	 *
	 * @covers MultiPostThumbnails::add_attachment_field
	 */
	function test_add_attachment_field_post_superglobal_set() {

		$post_parent  = $this->factory->post->create_and_get();
		$post  = $this->factory->post->create_and_get( array('post_parent' => $post_parent->ID ) );
		$post_type = $post->post_type;
		$_POST = array(1,2,3);
		$id = 'foo';

		$mpt = new MultiPostThumbnails( array(
			'label'     => 'Bar',
			'id'        => $id,
			'post_type' => $post_type
		) );

		$field = $mpt->add_attachment_field( array(), $post );
		$this->arrayHasKey( sprintf( '%s-%s-thumbnail', $post_type, $id ), $field );

	}

	/**
	 * @covers MultiPostThumbnails::add_attachment_field
	 */
	function test_add_attachment_field_post_type_mismatch() {

		$id = 'foo';
		$post  = $this->factory->post->create_and_get();
		$post_id = $post->ID;
		$_GET['post_id'] = $post_id;
		$mpt = new MultiPostThumbnails( array(
			'label'     => 'Bar',
			'id'        => $id,
			'post_type' => 'notpost'
		) );

		$field = $mpt->add_attachment_field( array(), $post );
		$this->assertEquals( array(), $field );

	}

	/**
	 * @covers MultiPostThumbnails::add_attachment_field
	 */
	function test_add_attachment_field_no_post_parent() {

		$post  = $this->factory->post->create_and_get();
		$post_type = $post->post_type;
		$_POST = array(1,2,3);
		$id = 'foo';

		$mpt = new MultiPostThumbnails( array(
			'label'     => 'Bar',
			'id'        => $id,
			'post_type' => $post_type
		) );

		$field = $mpt->add_attachment_field( array(), $post );
		$this->assertEquals( array(), $field );

	}


	/**
	 * Arguments for enqueue_admin_scripts() test:
	 * - string $wp_version - Version of WordPress
	 * - array $scripts_expected - scripts that are expected to be enqueued
	 * - array $scripts_not_expected - scripts NOT expected to be enqueued
	 */
	function provider_test_enqueue_admin_scripts(){

		return array(
			/* WP version 3.4 and expected scripts */
			array( '3.4', 'post-new.php', array( 'thickbox', 'mpt-featured-image' ), array( 'mpt-featured-image-modal', 'media-editor' )  ),
			/* WP version 4.0 and expected scripts */
			array( '4.0', 'post-new.php', array( 'mpt-featured-image-modal', 'media-editor', 'mpt-featured-image' ),  array( 'thickbox' )  ),
		);

	}


	/**
	 * @dataProvider provider_test_enqueue_admin_scripts
	 * @covers MultiPostThumbnails::enqueue_admin_scripts
	 */
	function test_enqueue_admin_scripts( $version, $hook, $scripts_expected, $scripts_not_expected ) {

		$GLOBALS['wp_version'] = $version;

		$mpt = new MultiPostThumbnails();
		$mpt->enqueue_admin_scripts( $hook );
		foreach( $scripts_expected as $script ) {

			$this->assertTrue( wp_script_is( $script, 'enqueued' ) );

		}
		foreach( $scripts_not_expected as $script ) {

			$this->assertFalse( wp_script_is( $script, 'enqueued' ) );

		}

	}

	/**
	 * @covers MultiPostThumbnails::admin_header_scripts
	 */
	function test_admin_header_scripts() {

		$post            = $this->factory->post->create_and_get();
		$GLOBALS['post'] = $post;
		$mpt             = new MultiPostThumbnails;
		ob_start();
		$mpt->admin_header_scripts();
		$output = ob_get_clean();
		$this->assertEquals( sprintf( '<script>var post_id = %s;</script>', $post->ID ) , $output );

	}

	/**
	 * @covers MultiPostThumbnails::action_delete_attachment
	 */
	function test_action_delete_attachment() {

		$post_id  = $this->factory->post->create();
		$meta_key = 'foobar';

		$mpt = $this->getMockBuilder( 'MultiPostThumbnails' )
			->disableOriginalConstructor()
			->setMethods( array( 'get_meta_key' ) )
			->getMock();

		$mpt->expects( $this->exactly( 4 ) )
			->method( 'get_meta_key' )
			->will( $this->returnValue( $meta_key ) );

		global $wpdb;

		// insert an arbitrary attachment

		$wpdb->query( $wpdb->prepare( "INSERT INTO $wpdb->postmeta ( meta_key, meta_value ) values ( '%s', %d )", $mpt->get_meta_key(), $post_id ) );

		// check that the attachment exists
		$result = $wpdb->get_results( sprintf( "SELECT * FROM  $wpdb->postmeta WHERE meta_key = '%s' AND meta_value = %d", $mpt->get_meta_key(), $post_id ) );
		$this->assertEquals( 1, count( $result ) );

		//execute MultiPostThumbnails::action_delete_attachment
		$mpt->action_delete_attachment( $post_id );

		// check that the attachment no longer exists
		$result = $wpdb->get_results( sprintf( "SELECT * FROM  $wpdb->postmeta WHERE meta_key = '%s' AND meta_value = %d", $mpt->get_meta_key(), $post_id ) );
		$this->assertEquals( 0, count( $result ) );

	}

	/**
	 * @covers MultiPostThumbnails::filter_is_protected_meta
	 */
	function test_filter_is_protected_meta_meta_key_equals() {

		$mpt = $this->getMockBuilder( 'MultiPostThumbnails' )
			->disableOriginalConstructor()
			->setMethods( array( 'get_meta_key' ) )
			->getMock();

		$mpt->expects( $this->once() )
			->method( 'get_meta_key' )
			->will( $this->returnValue( 'meta_key' ) );

		$actual = $mpt->filter_is_protected_meta( 'foo', 'meta_key' );
		$this->assertTrue( $actual );

	}

	/**
	 * @covers MultiPostThumbnails::filter_is_protected_meta
	 */
	function test_filter_is_protected_meta_meta_key_unequal() {

		$mpt = $this->getMockBuilder( 'MultiPostThumbnails' )
			->disableOriginalConstructor()
			->setMethods( array( 'get_meta_key' ) )
			->getMock();

		$mpt->expects( $this->once() )
			->method( 'get_meta_key' )
			->will( $this->returnValue( 'NOT_META_KEY' ) );


		// pass foo as the first argument, and expect it to be returned by MultiPostThumbnails::filter_is_protected_meta
		$expected = 'foo';

		$actual = $mpt->filter_is_protected_meta( $expected, 'meta_key' );
		$this->assertEquals( $actual, $expected );

	}

	/**
	 * @covers MultiPostThumbnails::filter_is_protected_meta
	 */
	function test_filter_is_protected_meta_filter() {

		// add filter to return true
		add_filter( 'mpt_unprotect_meta', '__return_true' );
		$mpt = new MultiPostThumbnails();

		// pass foo as the first argument, and expect it to be returned by MultiPostThumbnails::filter_is_protected_meta
		$expected = 'foo';
		$actual = $mpt->filter_is_protected_meta( $expected, 'meta_key' );
		$this->assertEquals( $expected, $actual );

	}

	/**
	 * @covers MultiPostThumbnails::has_post_thumbnail
	 */
	function test_has_post_thumbnail() {

		$post     = $this->factory->post->create_and_get( array( 'post_type' => 'post' ) );
		$id       = 'bar';

		//build the expected meta key and set a value to test
		$expected = 'foz';

		// set the meta
		MultiPostThumbnails::set_meta( $post->ID, $post->post_type, $id, $expected);


		$GLOBALS['post'] = $post;
		$actual          = MultiPostThumbnails::has_post_thumbnail( 'post', $id );

		$this->assertEquals( $actual, $expected );

	}

	/**
	 * @covers MultiPostThumbnails::has_post_thumbnail
	 */
	function test_has_post_thumbnail_no_post_id() {

		// if no post is set, it should return false
		$actual = MultiPostThumbnails::has_post_thumbnail( 'post', 'foz', false );
		$this->assertFalse( $actual );

	}

	/**
	 * @covers MultiPostThumbnails::the_post_thumbnail
	 */
	function test_the_post_thumbnail() {

		// test that MultiPostThumbnails::the_post_thumbnail echos the post thumbnail

		$filename = 'foo.jpg';
		$upload_array = wp_upload_dir();
		$upload_base_url = $upload_array['baseurl'];

		$post     = $this->factory->post->create_and_get( array( 'post_type' => 'post' ) );
		$attachment_id = $this->factory->attachment->create_object( $filename, $post->ID, array(
			'post_mime_type' => 'image/jpeg',
			'post_type'      => 'attachment'
		) );


		$post_type = $post->post_type;
		$id = 'foobar';

		MultiPostThumbnails::set_meta( $post->ID, $post_type, $id, $attachment_id);

		$mpt = $this->getMockBuilder( 'MultiPostThumbnails' )
			->disableOriginalConstructor()
			->getMock();

		$mpt->register(array('post_type' => 'post', 'id' => $id), false );

		ob_start();
		MultiPostThumbnails::the_post_thumbnail( $post_type, $id, $post->ID);
		$output = ob_get_clean();

		$document                     = new DOMDocument;
		$document->preserveWhiteSpace = false;
		$document->loadHTML( $output );
		$xpath      = new DOMXPath ( $document );
		$anchor_tag = $xpath->query( "//img[@src='" . $upload_base_url . '/' . $filename . "']" );
		$this->assertEquals( 1, $anchor_tag->length );

	}

	/**
	 * @covers MultiPostThumbnails::get_the_post_thumbnail
	 */
	function test_get_the_post_thumbnail() {


		// test that MultiPostThumbnails::get_the_post_thumbnail returns the post thumbnail

		$filename = 'foo.jpg';
		$upload_directory_array = wp_upload_dir();
		$upload_directory = $upload_directory_array['baseurl'];

		$post     = $this->factory->post->create_and_get( array( 'post_type' => 'post' ) );
		$attachment_id = $this->factory->attachment->create_object( $filename, $post->ID, array(
			'post_mime_type' => 'image/jpeg',
			'post_type'      => 'attachment'
		) );


		$post_type = $post->post_type;
		$id = 'foobar';

		MultiPostThumbnails::set_meta( $post->ID, $post_type, $id, $attachment_id);

		$mpt = $this->getMockBuilder( 'MultiPostThumbnails' )
			->disableOriginalConstructor()
			->getMock();

		$mpt->register(array('post_type' => 'post', 'id' => $id), false );

		$output = MultiPostThumbnails::get_the_post_thumbnail( $post_type, $id, $post->ID);

		$document                     = new DOMDocument;
		$document->preserveWhiteSpace = false;
		$document->loadHTML( $output );
		$xpath      = new DOMXPath ( $document );
		$anchor_tag = $xpath->query( "//img[@src='" . $upload_directory . '/' . $filename . "']" );
		$this->assertEquals( 1, $anchor_tag->length );

	}

	/**
	 * @covers MultiPostThumbnails::get_post_thumbnail
	 */
	function test_get_post_thumbnail(){

		// test that MultiPostThumbnails::get_post_thumbnail returns the post thumbnail

		$filename = 'foo.jpg';
		$upload_directory_array = wp_upload_dir();
		$upload_directory = $upload_directory_array['baseurl'];

		$post     = $this->factory->post->create_and_get( array( 'post_type' => 'post' ) );
		$attachment_id = $this->factory->attachment->create_object( $filename, $post->ID, array(
			'post_mime_type' => 'image/jpeg',
			'post_type'      => 'attachment'
		) );
		$id = 'foo';

		$mpt = new MultiPostThumbnails( array(
			'label'     => 'Foo',
			'id'        => $id,
			'post_type' => 'post'
		) );

		$post_type = $post->post_type;


		MultiPostThumbnails::set_meta( $post->ID, $post_type, $id, $attachment_id);

		$actual = $mpt->get_post_thumbnail( $post->ID, 'post-thumbnail', '', false );
		$document                     = new DOMDocument;
		$document->preserveWhiteSpace = false;
		$document->loadHTML( $actual );
		$xpath      = new DOMXPath ( $document );
		$anchor_tag = $xpath->query( "//img[@src='" . $upload_directory . '/' . $filename . "']" );
		$this->assertEquals( 1, $anchor_tag->length );


	}

	/**
	 * @covers MultiPostThumbnails::get_post_thumbnail
	 */
	function test_get_post_thumbnail_echo_link_to_original(){

		// test that MultiPostThumbnails::get_post_thumbnail echos the post thumbnail

		$filename = 'foo.jpg';
		$upload_directory_array = wp_upload_dir();
		$upload_directory = $upload_directory_array['baseurl'];

		$post     = $this->factory->post->create_and_get( array( 'post_type' => 'post' ) );
		$attachment_id = $this->factory->attachment->create_object( $filename, $post->ID, array(
			'post_mime_type' => 'image/jpeg',
			'post_type'      => 'attachment'
		) );
		$id = 'foo';

		$mpt = new MultiPostThumbnails( array(
			'label'     => 'Foo',
			'id'        => $id,
			'post_type' => 'post'
		) );

		$post_type = $post->post_type;


		MultiPostThumbnails::set_meta( $post->ID, $post_type, $id, $attachment_id);

		ob_start();
		$mpt->get_post_thumbnail( $post->ID, 'post-thumbnail', '', true, true );
		$output = ob_get_clean();
		$document                     = new DOMDocument;
		$document->preserveWhiteSpace = false;
		$document->loadHTML( $output );
		$xpath      = new DOMXPath ( $document );
		$anchor_tag = $xpath->query( "//a[@href='" . $upload_directory . '/' . $filename . "']" );
		$this->assertEquals( 1, $anchor_tag->length );


	}

	/**
	 * @covers MultiPostThumbnails::get_post_thumbnail
	 */
	function test_get_post_thumbnail_no_post_thumbnail(){

		// test that MultiPostThumbnails::get_post_thumbnail returns an empty string when no post thumbnail exists

		$post     = $this->factory->post->create_and_get( array( 'post_type' => 'post' ) );
		$id = 'foo';

		$mpt = new MultiPostThumbnails( array(
			'label'     => 'Foo',
			'id'        => $id,
			'post_type' => 'post'
		) );

		ob_start();
		$mpt->get_post_thumbnail( $post->ID, 'post-thumbnail', '', true, true );
		$output = ob_get_clean();
		$this->assertEquals('', $output);

	}

	/**
	 * @covers MultiPostThumbnails::get_post_thumbnail_id
	 */

	function test_get_post_thumbnail_id(){

		// test that the proper URL is returned when calling MultiPostThumbnails::get_post_thumbnail_url

		$filename = 'foo.jpg';
		$post     = $this->factory->post->create_and_get( array( 'post_type' => 'post' ) );
		$attachment_id = $this->factory->attachment->create_object( $filename, $post->ID, array(
			'post_mime_type' => 'image/jpeg',
			'post_type'      => 'attachment'
		) );


		$post_type = $post->post_type;
		$id = 'foobar';

		MultiPostThumbnails::set_meta( $post->ID, $post_type, $id, $attachment_id);
		$thumbnail_id = MultiPostThumbnails::get_post_thumbnail_id( $post_type, $id, $post->ID );
		$this->assertEquals( $attachment_id, $thumbnail_id );

	}

	/**
	 * @covers MultiPostThumbnails::get_post_thumbnail_url
	 */
	function test_get_post_thumbnail_url(){

		// test that the proper URL is returned when calling MultiPostThumbnails::get_post_thumbnail_url

		$filename = 'foo.jpg';
		$upload_array = wp_upload_dir();
		$upload_base_url = $upload_array['baseurl'];

		$post     = $this->factory->post->create_and_get( array( 'post_type' => 'post' ) );
		$GLOBALS['post'] = $post;
		$attachment_id = $this->factory->attachment->create_object( $filename, $post->ID, array(
			'post_mime_type' => 'image/jpeg',
			'post_type'      => 'attachment'
		) );


		$post_type = $post->post_type;
		$id = 'foobar';

		$expected = $upload_base_url . '/' . $filename;

		MultiPostThumbnails::set_meta( $post->ID, $post_type, $id, $attachment_id);
		$actual = MultiPostThumbnails::get_post_thumbnail_url( $post_type, $id );

		$this->assertEquals( $expected, $actual );

	}


	/**
	 * @covers MultiPostThumbnails::get_post_thumbnail_url
	 */
	function test_get_post_thumbnail_url_with_size(){

		// test that the proper URL is returned when calling MultiPostThumbnails::get_post_thumbnail_url

		$filename = 'foo.jpg';
		$upload_array = wp_upload_dir();
		$upload_base_url = $upload_array['baseurl'];

		$post     = $this->factory->post->create_and_get( array( 'post_type' => 'post' ) );
		$GLOBALS['post'] = $post;
		$attachment_id = $this->factory->attachment->create_object( $filename, $post->ID, array(
			'post_mime_type' => 'image/jpeg',
			'post_type'      => 'attachment'
		) );


		$post_type = $post->post_type;
		$id = 'foobar';

		$expected = $upload_base_url . '/' . $filename;

		MultiPostThumbnails::set_meta( $post->ID, $post_type, $id, $attachment_id);
		$actual = MultiPostThumbnails::get_post_thumbnail_url( $post_type, $id, 0, 'post-thumbnail' );

		$this->assertEquals( $expected, $actual );

	}


	/**
	 * @covers MultiPostThumbnails::get_post_thumbnail_url
	 */
	function test_get_post_thumbnail_url_with_size_set_post_set_to_null(){

		$post     = $this->factory->post->create_and_get( array( 'post_type' => 'post' ) );
		$GLOBALS['post'] = null;
		$attachment_id = $this->factory->attachment->create_object( 'foo.jpg', $post->ID, array(
			'post_mime_type' => 'image/jpeg',
			'post_type'      => 'attachment'
		) );

		$post_type = $post->post_type;
		$id = 'foobar';
		$expected = '';

		MultiPostThumbnails::set_meta( $post->ID, $post_type, $id, $attachment_id);
		$actual = MultiPostThumbnails::get_post_thumbnail_url( $post_type, $id, 0, 'post-thumbnail' );

		$this->assertEquals( $expected, $actual );

	}


	/**
	 * @covers MultiPostThumbnails::set_thumbnail
	 */
	function test_set_thumbnail_user_cannot(){

		// test that the post meta does not get changed

		$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );
		$value_to_set_meta_before_set_thumbnail = 'barfoo';
		$id = 'foobar';
		$post = $this->factory->post->create_and_get();
		$_POST['post_id'] = $post->ID;

		$mpt = new MultiPostThumbnails();
		$mpt->register( compact('post_type', 'id' ), false );

		// add a dummy value to test against after running MultiPostThumbnails::set_thumbnail
		MultiPostThumbnails::set_meta( $post->ID, $post->post_type, $id, $value_to_set_meta_before_set_thumbnail );

		$mpt->set_thumbnail();

		$actual = $mpt->get_thumbnail_id ( $post->ID );

		// make sure that the value does not change by MultiPostThumbnails::set_thumbnail
		$this->assertEquals( $actual, $value_to_set_meta_before_set_thumbnail );

		$this->assertTrue( $this->exit_called );

	}


	/**
	 * @covers MultiPostThumbnails::set_thumbnail
	 */
	function test_set_thumbnail_user_can_thumbnail_id_negative_1(){

		// test that the post meta gets deleted

		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$value_to_set_meta_before_set_thumbnail = 'barfoo';
		$post = $this->factory->post->create_and_get();
		$post_type = $post->post_type;
		$id = 'foobar';
		$_POST['post_id'] = $post->ID;
		$_POST['thumbnail_id'] = '-1';

		wp_set_current_user( $user_id );

		// add a dummy value to test against after running MultiPostThumbnails::set_thumbnail
		MultiPostThumbnails::set_meta( $post->ID, $post->post_type, $id, $value_to_set_meta_before_set_thumbnail );

		$mpt = new MultiPostThumbnails();
		$mpt->register( compact('post_type', 'id' ), false );

		$mpt->set_thumbnail();

		$actual = $mpt->get_thumbnail_id ( $post->ID );

		// make sure that the value does not persist as it should be deleted by MultiPostThumbnails::set_thumbnail
		$this->assertNotEquals( $actual, $value_to_set_meta_before_set_thumbnail );

		$this->assertTrue( $this->exit_called );

	}

	/**
	 * @covers MultiPostThumbnails::set_thumbnail
	 */
	function test_set_thumbnail_user_can(){

		// if the user can set the thumbnail, the meta value should change

		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$value_to_set_meta_before_set_thumbnail = 'foobar';
		$post = $this->factory->post->create_and_get();
		$filename = 'foo.jpg';
		$attachment_id = $this->factory->attachment->create_object( $filename, $post->ID, array(
			'post_mime_type' => 'image/jpeg',
			'post_type'      => 'attachment'
		) );
		$post_type = $post->post_type;
		$id = 'foobar';
		$_POST['post_id'] = $post->ID;
		$_POST['thumbnail_id'] = $attachment_id;

		wp_set_current_user( $user_id );

		// add a dummy value to test against after running MultiPostThumbnails::set_thumbnail
		MultiPostThumbnails::set_meta( $post->ID, $post->post_type, $id, $value_to_set_meta_before_set_thumbnail );

		$mpt = new MultiPostThumbnails();
		$mpt->register( compact('post_type', 'id' ), false );
		$mpt->set_thumbnail();
		$actual = $mpt->get_thumbnail_id ( $post->ID );

		// make sure that the value changes as it should be changed by MultiPostThumbnails::set_thumbnail
		$this->assertNotEquals( $value_to_set_meta_before_set_thumbnail, $actual );

		$this->assertTrue( $this->exit_called );

	}

	/**
	 * @covers MultiPostThumbnails::set_thumbnail
	 */
	function test_set_thumbnail_id_not_post(){

		// if this is not a valid thumbnail_id it should not change the posts meta value for the instance key

		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$value_to_set_meta_before_set_thumbnail = 'foobar';
		$post = $this->factory->post->create_and_get();
		$post_type = $post->post_type;
		$id = 'foobar';
		$_POST['post_id'] = $post->ID;
		$_POST['thumbnail_id'] = 'abcdefg';

		wp_set_current_user( $user_id );

		// add a dummy value to test against after running MultiPostThumbnails::set_thumbnail
		MultiPostThumbnails::set_meta( $post->ID, $post->post_type, $id, $value_to_set_meta_before_set_thumbnail );

		$mpt = new MultiPostThumbnails();

		$mpt->register( compact('post_type', 'id' ), false );
		$mpt->set_thumbnail();
		$actual = $mpt->get_thumbnail_id ( $post->ID );

		// make sure that the value does not change by MultiPostThumbnails::set_thumbnail
		$this->assertEquals( $actual, $value_to_set_meta_before_set_thumbnail );

		$this->assertTrue( $this->exit_called );


	}

	/**
	 * @covers MultiPostThumbnails::set_thumbnail
	 */
	function test_set_thumbnail_id_is_not_image_attachment_attachment(){

		// if this is not a valid image it should not change the posts meta value for the instance key

		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$value_to_set_meta_before_set_thumbnail = 'foobar';
		$post = $this->factory->post->create_and_get();
		$post_type = $post->post_type;
		$id = 'foobar';

		// set this as a 'post' post type (not an image)

		$_POST['post_id'] = $post->ID;

		wp_set_current_user( $user_id );

		// add a dummy value to test against after running MultiPostThumbnails::set_thumbnail
		MultiPostThumbnails::set_meta( $post->ID, $post->post_type, $id, $value_to_set_meta_before_set_thumbnail );

		$mpt = new MultiPostThumbnails();
		$mpt->register( compact('post_type', 'id' ), false );
		$mpt->set_thumbnail();
		$actual = $mpt->get_thumbnail_id ( $post->ID );

		// make sure that the value does not change by MultiPostThumbnails::set_thumbnail
		$this->assertEquals( $actual, $value_to_set_meta_before_set_thumbnail );

		$this->assertTrue( $this->exit_called );


	}


	/**
	 * @covers MultiPostThumbnails::get_thumbnail_id
	 */
	function test_get_thumbnail_id(){

		$post            = $this->factory->post->create_and_get();
		$post_type = $post->post_type;
		$id = 'foobar';
		$value = 'fozbar';
		$mpt = new MultiPostThumbnails();
		$mpt->register( compact('post_type', 'id' ), false );

		// add a dummy value to test against after running MultiPostThumbnails::set_thumbnail
		MultiPostThumbnails::set_meta( $post->ID, $post->post_type, $id, $value );

		// validate that value is what is expected
		$actual = MultiPostThumbnails::get_post_thumbnail_id( $post->post_type, $id, $post->ID );
		$this->assertEquals( $value, $actual );

	}

	/**
	 * @covers MultiPostThumbnails::set_meta
	 */
	function test_set_meta(){

		$post  = $this->factory->post->create_and_get();
		$post_id = $post->ID;
		$post_type = $post->post_type;
		$expected = $this->factory->attachment->create_object( 'foobar.jpg', $post->ID, array(
			'post_mime_type' => 'image/jpeg',
			'post_type'      => 'attachment'
		) );

		$id = 'foobar';

		// add a dummy value to test against after running MultiPostThumbnails::set_thumbnail
		MultiPostThumbnails::set_meta($post_id, $post_type, $id, $expected);

		$mpt = new MultiPostThumbnails();
		$mpt->register( compact('post_type', 'id' ), false );

		//verify that we've set the meta as expected

		$actual = MultiPostThumbnails::get_post_thumbnail_id( $post->post_type, $id, $post->ID );

		$this->assertEquals( $expected, $actual );

	}

}


