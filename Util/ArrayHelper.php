<?php


namespace Outlandish\OowpBundle\Util;


class ArrayHelper {
	public $array = array();

	function __construct($array = array()) {
		$this->array = $array;
	}

	function insertBefore($beforeKey, $key, $value) {
		$this->array = self::arrayInsertBefore($this->array, $beforeKey, $key, $value);
	}

	function insertAfter($afterKey, $key, $value) {
		$this->array = self::arrayInsertAfter($this->array, $afterKey, $key, $value);
	}

	/**
	 * Inserts the (key, value) pair into the array, after the given key. If the given key is not found,
	 * it is inserted at the end
	 * @param $array
	 * @param $afterKey
	 * @param $key
	 * @param $value
	 * @return array
	 */
	static function arrayInsertAfter($array, $afterKey, $key, $value) {
		if (array_key_exists($afterKey, $array)) {
			$output = array();
			foreach ($array as $a => $b) {
				$output[$a] = $b;
				if ($a == $afterKey) {
					$output[$key] = $value;
				}
			}
			return $output;
		} else {
			$array[$key] = $value;
			return $array;
		}
	}

	/**
	 * Inserts the (key, value) pair into the array, before the given key. If the given key is not found,
	 * it is inserted at the beginning
	 * @param $array
	 * @param $beforeKey
	 * @param $key
	 * @param $value
	 * @return array
	 */
	static function arrayInsertBefore($array, $beforeKey, $key, $value) {
		$output = array();
		if (array_key_exists($beforeKey, $array)) {
			foreach ($array as $a => $b) {
				if ($a == $beforeKey) {
					$output[$key] = $value;
				}
				$output[$a] = $b;
			}
		} else {
			$output[$key] = $value;
			foreach ($array as $a => $b) {
				$output[$a] = $b;
			}
		}
		return $output;
	}
}
