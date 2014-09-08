<?php

class TestMultiPostThumbnails extends WP_UnitTestCase {


	function provider_test_register() {

		return array(

			array( true, true, true, 1, 0, 1, true ),
			array( true, true, false, 1, 0, 1, true ),

			array( false, true, false, 1, 1, 1, true ),


			array( false, false, false, 0, 0, 0, false, null, null, null )

		);

	}

	/**
	 * @dataProvider provider_test_register
	 */
	function test_register( $current_theme_supports, $add_theme_support, $version_compare, $current_theme_supports_expects, $add_theme_support_expects, $version_compare_expects, $assertFilters, $label = 'foo', $id = 'bar', $post_type = 'post' ) {

		$mpt = $this->getMockBuilder( 'MultiPostThumbnails' )
			->disableOriginalConstructor()
			->setMethods( array( 'version_compare', 'current_theme_supports', 'trigger_error', 'add_theme_support' ) )
			->getMock();

		$mpt->expects( $this->exactly( $current_theme_supports_expects ) )
			->method( 'current_theme_supports' )
			->will( $this->returnValue( $current_theme_supports ) );

		$mpt->expects( $this->exactly( $add_theme_support_expects ) )
			->method( 'add_theme_support' )
			->will( $this->returnValue( $add_theme_support ) );

		$mpt->expects( $this->exactly( $version_compare_expects ) )
			->method( 'version_compare' )
			->will( $this->returnValue( $version_compare ) );

		if ( ! $id || ! $label ) {

			$mpt->expects( $this->once() )
				->method( 'trigger_error' );

		}


		$mpt->register( array( 'label' => $label, 'id' => $id ) );

		if ( $assertFilters ) {


			$this->assertEquals( has_action( 'add_meta_boxes', array( $mpt, 'add_metabox' ) ), 10 );
			if ( $version_compare ) {
				$this->assertEquals( has_filter( 'attachment_fields_to_edit', array( $mpt, 'add_attachment_field' ) ), 20 );
			}
			$this->assertEquals( has_filter( 'admin_enqueue_scripts', array( $mpt, 'enqueue_admin_scripts' ) ), 10 );
			$this->assertEquals( has_filter( 'admin_print_scripts-post.php', array( $mpt, 'admin_header_scripts' ) ), 10 );
			$this->assertEquals( has_filter( 'admin_print_scripts-post-new.php', array( $mpt, 'admin_header_scripts' ) ), 10 );
			$this->assertEquals( has_filter( 'wp_ajax_set-' . $post_type . '-' . $id . '-thumbnail', array( $mpt, 'set_thumbnail' ) ), 10 );
			$this->assertEquals( has_filter( 'delete_attachment', array( $mpt, 'action_delete_attachment' ) ), 10 );
			$this->assertEquals( has_filter( 'is_protected_meta', array( $mpt, 'filter_is_protected_meta' ) ), 20 );


		}


	}

	function test_get_meta_key() {

		$mpt = new MultiPostThumbnails( array( 'label' => 'foo', 'id' => 'bar', 'post_type' => 'post' ) );

		$actual = $mpt->get_meta_key();

		$expected = 'post_bar_thumbnail_id';

		$this->assertEquals( $expected, $actual );

	}


	function test_add_metabox() {

		global $wp_meta_boxes;

		$mpt = new MultiPostThumbnails( array( 'label' => 'foo', 'id' => 'bar', 'post_type' => 'post', 'context' => 'default', 'priority' => 'high' ) );

		$mpt->add_metabox();

		$this->assertArrayHasKey( 'post-bar', $wp_meta_boxes['post']['default']['high'] );

	}

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

	function test_add_attachment_field_post() {

		$post            = $this->factory->post->create_and_get();
		$post_id         = $post->ID;
		$post2 = $this->factory->post->create_and_get( array( 'post_parent' => $post_id ) );
		$_POST = array(1,2,3);
		$id              = 123;
		$post_type       = 'post';

		$mpt = new MultiPostThumbnails( array( 'id' => $id, 'label' => 'thelabel', 'post_type' => $post_type ) );

		$add_attachment_field = $mpt->add_attachment_field( array(), $post2 );

		$this->assertArrayHasKey( $post_type . '-' . $id . '-thumbnail', $add_attachment_field );

	}


}

