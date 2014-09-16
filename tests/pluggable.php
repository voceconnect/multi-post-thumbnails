<?php
/**
 * Replace some "pluggable" WordPress functions so they are testable
 */


function check_ajax_referer(){

	return true;

}