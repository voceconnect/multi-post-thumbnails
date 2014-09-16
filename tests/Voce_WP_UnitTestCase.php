<?php

class Voce_WP_UnitTestCase extends WP_UnitTestCase {

	protected $exit_called              = false;

	function setUp() {

		parent::setUp();

		$this->backupGlobal( 'wp_scripts' );

		if ( function_exists( 'set_exit_overload' ) ) {

			set_exit_overload( array( $this, 'exit_overload' ) );

		}

	}

	function tearDown() {

		parent::tearDown();

		$this->restoreGlobal( 'wp_script' );

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

	function backupGlobal( $global ) {
		if ( isset( $GLOBALS[$global] ) ) {
			$this->backupGlobals[$global] = $GLOBALS[$global];
		} else {
			$this->variablesUnset[$global] = true;
		}
	}

	function restoreGlobal( $global ) {
		if ( isset( $this->backupGlobals[$global] ) ) {
			$GLOBALS[$global] = $this->backupGlobals[$global];
		} elseif ( isset( $this->variablesUnset[ $global ] ) ) {
			unset($GLOBALS[ $global ]);
		}
	}

}

