<?php


namespace Outlandish\OowpBundle\Helpers;

/**
 * Static utility class
 */
class Util {
	/**
	 * Translates a camel case string into a string with underscores (e.g. firstName -> first_name)
	 * @param    string   $str    String in camel case format
	 * @return    string            $str Translated into underscore format
	 */
	static function fromCamelCase($str)
	{
		$str[0] = strtolower($str[0]);
		$func   = create_function('$c', 'return "_" . strtolower($c[1]);');
		return preg_replace_callback('/([A-Z])/', $func, $str);
	}

	/**
	 * Translates a string with underscores into camel case (e.g. first_name -> firstName)
	 * @param    string   $str                     String in underscore format
	 * @param    bool     $capitalise_first_char   If true, capitalise the first char in $str
	 * @return   string                              $str translated into camel caps
	 */
	static function toCamelCase($str, $capitalise_first_char = false)
	{
		if ($capitalise_first_char) {
			$str[0] = strtoupper($str[0]);
		}
		$func = create_function('$c', 'return strtoupper($c[1]);');
		return preg_replace_callback('/_([a-z])/', $func, $str);
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
			foreach ($array as $a=>$b) {
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
			foreach ($array as $a=>$b) {
				if ($a == $beforeKey) {
					$output[$key] = $value;
				}
				$output[$a] = $b;
			}
		} else {
			$output[$key] = $value;
			foreach ($array as $a=>$b) {
				$output[$a] = $b;
			}
		}
		return $output;
	}

}