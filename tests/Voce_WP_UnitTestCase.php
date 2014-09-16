<?php

class Voce_WP_UnitTestCase extends WP_UnitTestCase {

	protected $exit_called              = false;

	function setUp() {

		parent::setUp();

		if ( function_exists( 'set_exit_overload' ) ) {

			set_exit_overload( array( $this, 'exit_overload' ) );

		}

	}

	function tearDown() {

		parent::tearDown();

		$this->exit_called       = false;

		if ( function_exists( 'unset_exit_overload' ) ) {

			unset_exit_overload();

		}

	}

	function exit_overload() {

		$this->exit_called = true;

	}

	function exit_called() {

		return $this->exit_called;

	}

}

