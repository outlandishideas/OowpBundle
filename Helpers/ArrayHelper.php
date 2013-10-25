<?php


namespace Outlandish\OowpBundle\Helpers;


class ArrayHelper {
	public $array = array();

	function __construct($array = array()) {
		$this->array = $array;
	}

	function insertBefore($beforeKey, $key, $value) {
		$this->array = Util::arrayInsertBefore($this->array, $beforeKey, $key, $value);
	}

	function insertAfter($afterKey, $key, $value) {
		$this->array = Util::arrayInsertAfter($this->array, $afterKey, $key, $value);
	}
}
