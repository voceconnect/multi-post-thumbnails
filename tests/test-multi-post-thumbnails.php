<?php


class TestMultiPostThumbnails extends WP_UnitTestCase {

	private $errors;

	public function setUp() {

		parent::setUp();

		$this->errors = array();

		set_error_handler( array( $this, 'errorHandler' ) );

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
	 * @covers MultiPostThumbnails::get_meta_key
	 */
	function test_get_meta_key() {

		$mpt = new MultiPostThumbnails( array( 'label' => 'foo', 'id' => 'bar', 'post_type' => 'post' ) );

		$actual = $mpt->get_meta_key();

		$expected = 'post_bar_thumbnail_id';

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

		$post_id         = 123;
		$GLOBALS['post'] = (object) array( 'ID' => $post_id );

		$mpt = $this->getMockBuilder( 'MultiPostThumbnails' )
			->disableOriginalConstructor()
			->setMethods( array( 'post_thumbnail_html', 'get_post_meta', 'get_meta_key' ) )
			->getMock();

		$mpt->expects( $this->once() )
			->method( 'get_meta_key' )
			->will( $this->returnValue( 'metaKey' ) );


		$mpt->expects( $this->once() )
			->method( 'get_post_meta' )
			->with( $this->equalTo( $post_id ), $this->equalTo( 'metaKey' ), true )
			->will( $this->returnValue( 'thumbnailid' ) );

		$mpt->expects( $this->once() )
			->method( 'post_thumbnail_html' );

		$mpt->thumbnail_meta_box();


	}

	/**
	 * @covers MultiPostThumbnails::add_attachment_field
	 */
	function test_add_attachment_field_none() {

		$mpt = $this->getMockBuilder( 'MultiPostThumbnails' )
			->disableOriginalConstructor()
			->setMethods( array( 'wp_parse_args', 'wp_create_nonce', 'get_post' ) )
			->getMock();

		$mpt->expects( $this->never() )
			->method( 'wp_parse_args' );

		$mpt->expects( $this->never() )
			->method( 'wp_create_nonce' );

		$mpt->expects( $this->never() )
			->method( 'get_post' );

		$actual   = $mpt->add_attachment_field( 'foo', 'bar' );
		$expected = 'foo';

		$this->assertEquals( $expected, $actual );


	}

	/**
	 * @covers MultiPostThumbnails::add_attachment_field
	 */
	function test_add_attachment_field_get_calling_post_id_null() {

		$_GET['post_id'] = 123;

		$mpt = $this->getMockBuilder( 'MultiPostThumbnails' )
			->disableOriginalConstructor()
			->setMethods( array( 'wp_parse_args', 'wp_create_nonce', 'get_post' ) )
			->getMock();

		$mpt->expects( $this->never() )
			->method( 'wp_parse_args' );

		$mpt->expects( $this->never() )
			->method( 'wp_create_nonce' );

		$mpt->expects( $this->once() )
			->method( 'get_post' )
			->will( $this->returnValue( null ) );

		$actual   = $mpt->add_attachment_field( 'foo', 'bar' );
		$expected = 'foo';

		$this->assertEquals( $expected, $actual );

	}

	/**
	 * @covers MultiPostThumbnails::add_attachment_field
	 */
	function test_add_attachment_field_get_unsupported_post_type() {

		$_GET['post_id'] = 123;

		$mpt = $this->getMockBuilder( 'MultiPostThumbnails' )
			->disableOriginalConstructor()
			->setMethods( array( 'wp_parse_args', 'wp_create_nonce', 'get_post' ) )
			->getMock();

		$mpt->expects( $this->never() )
			->method( 'wp_parse_args' );

		$mpt->expects( $this->never() )
			->method( 'wp_create_nonce' );

		$mpt->expects( $this->once() )
			->method( 'get_post' )
			->will( $this->returnValue( (object) array( 'post_type' => 'not a known post type' ) ) );

		$actual   = $mpt->add_attachment_field( 'foo', 'bar' );
		$expected = 'foo';

		$this->assertEquals( $expected, $actual );


	}

	/**
	 * @covers MultiPostThumbnails::add_attachment_field
	 */
	function test_add_attachment_field_get_unequal_context() {

		$post            = $this->factory->post->create_and_get();
		$post_id         = $post->ID;
		$_GET['post_id'] = $post_id;
		$id              = 123;
		$post_type       = 'post';

		$mpt = new MultiPostThumbnails( array( 'id' => $id, 'label' => 'thelabel', 'post_type' => $post_type ) );

		$add_attachment_field = $mpt->add_attachment_field( array(), $post );

		$this->assertArrayHasKey( $post_type . '-' . $id . '-thumbnail', $add_attachment_field );


	}

	/**
	 * @covers MultiPostThumbnails::add_attachment_field
	 */
	function test_add_attachment_field_get() {

		$post            = $this->factory->post->create_and_get();
		$post_id         = $post->ID;
		$_GET['post_id'] = $post_id;
		$id              = 123;
		$post_type       = 'post';

		$mpt = new MultiPostThumbnails( array( 'id' => $id, 'label' => 'thelabel', 'post_type' => $post_type ) );

		$add_attachment_field = $mpt->add_attachment_field( array(), $post );

		$this->assertArrayHasKey( $post_type . '-' . $id . '-thumbnail', $add_attachment_field );


	}

	/**
	 * @covers MultiPostThumbnails::add_attachment_field
	 */
	function test_add_attachment_field_post() {

		$post      = $this->factory->post->create_and_get();
		$post_id   = $post->ID;
		$post2     = $this->factory->post->create_and_get( array( 'post_parent' => $post_id ) );
		$_POST     = array( 1, 2, 3 );
		$id        = 123;
		$post_type = 'post';

		$mpt = new MultiPostThumbnails( array( 'id' => $id, 'label' => 'thelabel', 'post_type' => $post_type ) );

		$add_attachment_field = $mpt->add_attachment_field( array(), $post2 );

		$this->assertArrayHasKey( $post_type . '-' . $id . '-thumbnail', $add_attachment_field );

	}

	/**
	 * @covers MultiPostThumbnails::enqueue_admin_scripts
	 */
	function test_enqueue_admin_scripts_wp_version_34() {

		$mpt = $this->getMockBuilder( 'MultiPostThumbnails' )
			->disableOriginalConstructor()
			->setMethods( array( 'version_compare' ) )
			->getMock();

		$mpt->expects( $this->once() )
			->method( 'version_compare' )
			->will( $this->returnValue( true ) );

		$mpt->enqueue_admin_scripts( 'post-new.php' );

		$this->assertTrue( wp_script_is( 'thickbox' ) );
		$this->assertTrue( wp_script_is( 'mpt-featured-image' ) );

	}

	/**
	 * @covers MultiPostThumbnails::enqueue_admin_scripts
	 */
	function test_enqueue_admin_scripts_wp_version_40() {

		$mpt = $this->getMockBuilder( 'MultiPostThumbnails' )
			->disableOriginalConstructor()
			->setMethods( array( 'version_compare' ) )
			->getMock();

		$mpt->expects( $this->once() )
			->method( 'version_compare' )
			->will( $this->returnValue( false ) );

		$mpt->enqueue_admin_scripts( 'post-new.php' );

		$this->assertTrue( wp_script_is( 'mpt-featured-image' ) );
		$this->assertTrue( wp_script_is( 'mpt-featured-image-modal' ) );
		$this->assertTrue( wp_script_is( 'media-editor' ) );

	}


	/**
	 * @covers MultiPostThumbnails::enqueue_admin_scripts
	 */
	function test_enqueue_admin_scripts_not_in_hook_array() {

		$mpt = $this->getMockBuilder( 'MultiPostThumbnails' )
			->disableOriginalConstructor()
			->setMethods( array( 'version_compare' ) )
			->getMock();

		$mpt->expects( $this->never() )
			->method( 'version_compare' );

		$mpt->enqueue_admin_scripts( 'NOTINARRAY' );

	}

	/**
	 * @covers MultiPostThumbnails::admin_header_scripts
	 */
	function test_admin_header_scripts() {

		$post            = $this->factory->post->create_and_get();
		$post->ID        = 10000;
		$GLOBALS['post'] = $post;
		$mpt             = new MultiPostThumbnails;
		ob_start();
		$mpt->admin_header_scripts();
		$output = ob_get_clean();
		$this->assertEquals( '<script>var post_id = 10000;</script>', $output );

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

		$wpdb->query( $wpdb->prepare( "INSERT INTO $wpdb->postmeta ( meta_key, meta_value ) values ( '%s', %d )", $mpt->get_meta_key(), $post_id ) );

		$result = $wpdb->get_results( sprintf( "SELECT * FROM  $wpdb->postmeta WHERE meta_key = '%s' AND meta_value = %d", $mpt->get_meta_key(), $post_id ) );
		$this->assertEquals( 1, count( $result ) );
		$mpt->action_delete_attachment( $post_id );
		$result = $wpdb->get_results( sprintf( "SELECT * FROM  $wpdb->postmeta WHERE meta_key = '%s' AND meta_value = %d", $mpt->get_meta_key(), $post_id ) );
		$this->assertEquals( 0, count( $result ) );

	}

	/**
	 * @covers MultiPostThumbnails::filter_is_protected_meta
	 */
	function test_filter_is_protected_meta_filter_returns_true() {

		$mpt = $this->getMockBuilder( 'MultiPostThumbnails' )
			->disableOriginalConstructor()
			->setMethods( array( 'apply_filters' ) )
			->getMock();

		$mpt->expects( $this->once() )
			->method( 'apply_filters' )
			->with( $this->equalTo( 'mpt_unprotect_meta' ), $this->equalTo( false ) )
			->will( $this->returnValue( true ) );

		$expected = 'foo';
		$actual   = $mpt->filter_is_protected_meta( $expected, 'bar' );

		$this->assertEquals( $expected, $actual );


	}

	/**
	 * @covers MultiPostThumbnails::filter_is_protected_meta
	 */
	function test_filter_is_protected_meta_filter_meta_key_equals() {

		$meta_key = 'bar';

		$mpt = $this->getMockBuilder( 'MultiPostThumbnails' )
			->disableOriginalConstructor()
			->setMethods( array( 'apply_filters', 'get_meta_key' ) )
			->getMock();

		$mpt->expects( $this->once() )
			->method( 'apply_filters' )
			->with( $this->equalTo( 'mpt_unprotect_meta' ), $this->equalTo( false ) )
			->will( $this->returnValue( false ) );

		$mpt->expects( $this->once() )
			->method( 'get_meta_key' )
			->will( $this->returnValue( $meta_key ) );

		$expected = 'foo';
		$actual   = $mpt->filter_is_protected_meta( $expected, $meta_key );

		$this->assertTrue( $actual );


	}

	/**
	 * @covers MultiPostThumbnails::filter_is_protected_meta
	 */
	function test_filter_is_protected_meta_filter_meta_key_not_equals() {

		$meta_key = 'bar';

		$mpt = $this->getMockBuilder( 'MultiPostThumbnails' )
			->disableOriginalConstructor()
			->setMethods( array( 'apply_filters', 'get_meta_key' ) )
			->getMock();

		$mpt->expects( $this->once() )
			->method( 'apply_filters' )
			->with( $this->equalTo( 'mpt_unprotect_meta' ), $this->equalTo( false ) )
			->will( $this->returnValue( false ) );

		$mpt->expects( $this->once() )
			->method( 'get_meta_key' )
			->will( $this->returnValue( 'not meta key' ) );

		$expected = 'foo';
		$actual   = $mpt->filter_is_protected_meta( $expected, $meta_key );

		$this->assertEquals( $actual, $expected );


	}

	/**
	 * @covers MultiPostThumbnails::has_post_thumbnail
	 */
	function test_has_post_thumbnail() {

		$post     = $this->factory->post->create_and_get( array( 'post_type' => 'post' ) );
		$id       = 'bar';
		$expected = 'foz';

		update_post_meta( $post->ID, 'post_' . $id . '_thumbnail_id', $expected );

		$GLOBALS['post'] = $post;
		$actual          = MultiPostThumbnails::has_post_thumbnail( 'post', $id );

		$this->assertEquals( $actual, $expected );

	}

	/**
	 * @covers MultiPostThumbnails::has_post_thumbnail
	 */
	function test_has_post_thumbnail_no_post_id() {

		$actual = MultiPostThumbnails::has_post_thumbnail( 'post', 'foz', false );
		$this->assertFalse( $actual );

	}

	/**
	 * @covers MultiPostThumbnails::get_the_post_thumbnail
	 */
	function test_get_the_post_thumbnail_set_meta() {

		$thumbnail_id = 'foobar';

		$post          = $this->factory->post->create_and_get( array( 'post_type' => 'post' ) );
		$attachment_id = $this->factory->attachment->create_object( 'foo.jpg', $post->ID, array(
			'post_mime_type' => 'image/jpeg',
			'post_type'      => 'attachment'
		) );

		MultiPostThumbnails::set_meta( $post->ID, 'post', $thumbnail_id, $attachment_id );

		$actual = MultiPostThumbnails::get_the_post_thumbnail( 'post', $thumbnail_id, $post->ID, 'post-thumbnail', '', true );

		$image_link = wp_get_attachment_image( $attachment_id, 'post-thumbnail', false, '' );
		$url        = wp_get_attachment_url( $attachment_id );
		$expected   = sprintf( '<a href="%s">%s</a>', $url, $image_link );

		$this->assertEquals( $actual, $expected );


	}

	/**
	 * @covers MultiPostThumbnails::get_the_post_thumbnail
	 */
	function test_get_the_post_thumbnail_unset_meta() {

		$thumbnail_id = 'foobar';

		$post     = $this->factory->post->create_and_get( array( 'post_type' => 'post' ) );
		$actual   = MultiPostThumbnails::get_the_post_thumbnail( 'post', $thumbnail_id, $post->ID, 'post-thumbnail', '', true );
		$expected = null;
		$this->assertEquals( $actual, $expected );

	}

	/**
	 * @covers MultiPostThumbnails::the_post_thumbnail
	 */
	function test_the_post_thumbnail() {

		$thumbnail_id = 'foobar';

		$post = $this->factory->post->create_and_get( array( 'post_type' => 'post' ) );


		$expected = MultiPostThumbnails::get_the_post_thumbnail( 'post', $thumbnail_id, $post->ID, 'post-thumbnail', '', true );

		ob_start();
		MultiPostThumbnails::the_post_thumbnail( 'post', $thumbnail_id, $post->ID, $post->ID, 'post-thumbnail', '', true );
		$actual = ob_get_clean();

		$this->assertEquals( $actual, $expected );


	}

	/**
	 * @covers MultiPostThumbnails::get_post_thumbnail_id
	 */

	public function test_get_post_thumbnail_id(){

		$post_type = 'page';
		$thumbnail_id = 'foo';
		$thumbnail_post_id = 'bar';
		$post_id = $this->factory->post->create( array( 'post_type' => $post_type ) );
		MultiPostThumbnails::set_meta( $post_id, $post_type, $thumbnail_id, $thumbnail_post_id);

		$actual = MultiPostThumbnails::get_post_thumbnail_id( $post_type, $thumbnail_id, $post_id );

		$this->assertEquals( $thumbnail_post_id, $actual );

	}

	/**
	 * @covers MultiPostThumbnails::get_post_thumbnail_url
	 */
	public function test_get_post_thumbnail_url() {

		$post            = $this->factory->post->create_and_get();
		$GLOBALS['post'] = $post;
		$thumbnail_id    = 'foobar';
		$file_name       = 'foo.jpg';

		$attachment_id = $this->factory->attachment->create_object( $file_name, $post->ID, array(
			'post_mime_type' => 'image/jpeg',
			'post_type'      => 'attachment'
		) );

		MultiPostThumbnails::set_meta( $post->ID, 'post', $thumbnail_id, $attachment_id );

		$upload_dir_raw = wp_upload_dir();
		$upload_url     = str_replace( $upload_dir_raw['subdir'], '', $upload_dir_raw['url'] ); //strip out the month/date from URL

		$expected = $upload_url . '/' . $file_name;


		$actual = MultiPostThumbnails::get_post_thumbnail_url( 'post', $thumbnail_id );

		$this->assertEquals( $actual, $expected );


	}

	/**
	 * @covers MultiPostThumbnails::get_post_thumbnail_url
	 */
	public function test_get_post_thumbnail_url_size() {

		$post            = $this->factory->post->create_and_get();
		$GLOBALS['post'] = $post;
		$thumbnail_id    = 'foobar';
		$file_name       = 'foo.jpg';

		$post          = $this->factory->post->create_and_get( array( 'post_type' => 'post' ) );
		$attachment_id = $this->factory->attachment->create_object( $file_name, $post->ID, array(
			'post_mime_type' => 'image/jpeg',
			'post_type'      => 'attachment'
		) );

		MultiPostThumbnails::set_meta( $post->ID, 'post', $thumbnail_id, $attachment_id );

		$upload_dir_raw = wp_upload_dir();
		$upload_url     = str_replace( $upload_dir_raw['subdir'], '', $upload_dir_raw['url'] ); //strip out the month/date from URL

		$expected = $upload_url . '/' . $file_name;

		$actual = MultiPostThumbnails::get_post_thumbnail_url( 'post', $thumbnail_id, $post->ID, 'size' );

		$this->assertEquals( $actual, $expected );


	}

	/**
	 * @covers MultiPostThumbnails::get_post_thumbnail_url
	 */
	public function test_get_post_thumbnail_url_size_no_attachment() {

		$post            = $this->factory->post->create_and_get();
		$GLOBALS['post'] = $post;
		$thumbnail_id    = 'foobar';

		$post          = $this->factory->post->create_and_get( array( 'post_type' => 'post' ) );
		$attachment_id = null;

		MultiPostThumbnails::set_meta( $post->ID, 'post', $thumbnail_id, $attachment_id );

		$upload_dir_raw = wp_upload_dir();
		$upload_url     = str_replace( $upload_dir_raw['subdir'], '', $upload_dir_raw['url'] ); //strip out the month/date from URL

		$expected = '';

		$actual = MultiPostThumbnails::get_post_thumbnail_url( 'post', $thumbnail_id, $post->ID, 'size' );
		$this->assertEquals( $actual, $expected );

	}

	/**
	 * @covers MultiPostThumbnails::set_thumbnail
	 */
	public function test_set_thumbnail_current_user_cannot() {

		$post_id          = $this->factory->post->create();
		$_POST['post_id'] = $post_id;

		$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );

		$mpt = $this->getMockBuilder( 'MultiPostThumbnails' )
			->disableOriginalConstructor()
			->setMethods( array( 'mpt_die', 'check_ajax_referer', 'set_meta', 'post_thumbnail_html', 'get_meta_key' ) )
			->getMock();

		$mpt->expects( $this->once() )
			->method( 'mpt_die' )
			->with( $this->equalTo( '-1' ) );

		$mpt->expects( $this->never() )
			->method( 'check_ajax_referer' );

		$mpt->expects( $this->never() )
			->method( 'set_meta' );

		$mpt->expects( $this->never() )
			->method( 'get_meta_key' );

		$mpt->expects( $this->never() )
			->method( 'post_thumbnail_html' );


		$mpt->set_thumbnail();

	}

	/**
	 * @covers MultiPostThumbnails::set_thumbnail
	 */
	public function test_set_thumbnail_current_user_can_thumbnail_negative_1() {

		$post_id               = $this->factory->post->create();
		$_POST['post_id']      = $post_id;
		$_POST['thumbnail_id'] = '-1';
		$id                    = null;
		$post_type             = null;

		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		$mpt = $this->getMockBuilder( 'MultiPostThumbnails' )
			->disableOriginalConstructor()
			->setMethods( array( 'check_ajax_referer', 'set_meta', 'get_meta_key' ) )
			->getMock();


		$mpt->expects( $this->once() )
			->method( 'get_meta_key' );

		$mpt->expects( $this->once() )
			->method( 'check_ajax_referer' );

		$mpt->expects( $this->never() )
			->method( 'set_meta' );


		$result = $mpt->set_thumbnail();


		$document                     = new DOMDocument;
		$document->preserveWhiteSpace = false;
		$document->loadHTML( $result );
		$xpath      = new DOMXPath ( $document );
		$anchor_tag = $xpath->query( "//a[@id='set-" . $post_type . "-" . $id . "-thumbnail']" );
		$this->assertEquals( 1, $anchor_tag->length );


	}

	/**
	 * @covers MultiPostThumbnails::set_thumbnail
	 */
	public function test_set_thumbnail_current_user_can_thumbnail_not_a_post() {

		$post_id               = $this->factory->post->create();
		$_POST['post_id']      = $post_id;
		$_POST['thumbnail_id'] = 'notapostid';

		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		$mpt = $this->getMockBuilder( 'MultiPostThumbnails' )
			->disableOriginalConstructor()
			->setMethods( array( 'mpt_die', 'check_ajax_referer', 'set_meta', 'post_thumbnail_html', 'get_meta_key' ) )
			->getMock();

		$mpt->expects( $this->once() )
			->method( 'mpt_die' )
			->with( $this->equalTo( '0' ) );

		$mpt->expects( $this->once() )
			->method( 'check_ajax_referer' );

		$mpt->expects( $this->never() )
			->method( 'set_meta' );

		$mpt->expects( $this->never() )
			->method( 'get_meta_key' );

		$mpt->expects( $this->never() )
			->method( 'post_thumbnail_html' );


		$mpt->set_thumbnail();


	}

	/**
	 * @covers MultiPostThumbnails::set_thumbnail
	 */
	public function test_set_thumbnail_is_a_post() {

		$post_type     = 'post';
		$post_id       = $this->factory->post->create( array( 'post_type' => $post_type ) );
		$attachment_id = $this->factory->attachment->create_object( 'foo.jpg', $post_id, array(
			'post_mime_type' => 'image/jpeg',
			'post_type'      => 'attachment'
		) );

		$id                    = 'barfoo';
		$_POST['post_id']      = $post_id;
		$_POST['thumbnail_id'] = $attachment_id;


		$mpt = $this->getMockBuilder( 'MultiPostThumbnails' )
			->disableOriginalConstructor()
			->setMethods( array( 'check_ajax_referer', 'get_meta_key' ) )
			->getMock();

		$mpt->register( array( 'label' => 'foobar', 'id' => $id ) );


		$mpt->expects( $this->once() )
			->method( 'check_ajax_referer' );

		$mpt->expects( $this->never() )
			->method( 'get_meta_key' );


		$result = $mpt->set_thumbnail();


		$document                     = new DOMDocument;
		$document->preserveWhiteSpace = false;
		$document->loadHTML( $result );
		$xpath      = new DOMXPath ( $document );
		$anchor_tag = $xpath->query( "//a[@id='set-" . $post_type . "-" . $id . "-thumbnail']" );
		$this->assertEquals( 1, $anchor_tag->length );

	}

	/**
	 * @covers MultiPostThumbnails::set_thumbnail
	 */
	public function test_set_thumbnail_is_a_post_thumbnail_html_returns_null() {

		$post_type     = 'post';
		$post_id       = $this->factory->post->create( array( 'post_type' => $post_type ) );
		$attachment_id = $this->factory->attachment->create_object( 'foo.jpg', $post_id, array(
			'post_mime_type' => 'image/jpeg',
			'post_type'      => 'attachment'
		) );

		$id                    = 'barfoo';
		$_POST['post_id']      = $post_id;
		$_POST['thumbnail_id'] = $attachment_id;


		$mpt = $this->getMockBuilder( 'MultiPostThumbnails' )
			->disableOriginalConstructor()
			->setMethods( array( 'mpt_die', 'check_ajax_referer', 'get_meta_key', 'wp_get_attachment_image' ) )
			->getMock();

		$mpt->register( array( 'label' => 'foobar', 'id' => $id ) );


		$mpt->expects( $this->once() )
			->method( 'check_ajax_referer' );

		$mpt->expects( $this->never() )
			->method( 'get_meta_key' );

		$mpt->expects( $this->once() )
			->method( 'wp_get_attachment_image' )
			->will( $this->returnValue( null ) );

		$mpt->expects( $this->once() )
			->method( 'mpt_die' )
			->with( $this->equalTo( '0' ) );


		$mpt->set_thumbnail();

	}


	/**
	 * @covers MultiPostThumbnails::set_meta
	 */
	public function test_set_meta() {

		$post_type = 'page';
		$thumbnail_id = 'foo';
		$thumbnail_post_id = 'bar';
		$post_id = $this->factory->post->create( array( 'post_type' => $post_type ) );
		MultiPostThumbnails::set_meta( $post_id, $post_type, $thumbnail_id, $thumbnail_post_id);

		$actual = get_post_meta( $post_id, "{$post_type}_{$thumbnail_id}_thumbnail_id", true );

		$this->assertEquals( $thumbnail_post_id, $actual );

	}


}


