<?php
class Magehack_Elasticsearch_Helper_Inflector
{
	
	/**
	 * Convert string in format 'StringString' to 'string_string'
	 *
	 * @param  string $string  string to underscore
	 * @return string $string  underscored string
	 */
	public function underscore($string) {
		return str_replace(' ', '_', strtolower ( preg_replace ( '~(?<=\\w)([A-Z])~', '_$1', $string ) ));
	}
	
	/**
	 * Convert a word in to the format for a class name. Converts 'class_name' to 'ClassName'
	 *
	 * @param string  $word  Word to classify
	 * @return string $word  Classified word
	 */
	public function classify($word) {
		static $cache = array ();
		
		if (! isset ( $cache [$word] )) {
			$word = preg_replace ( '/[$]/', '', $word );
			$classify = preg_replace_callback ( '~(_?)([-_])([\w])~', array (get_class($this), "classifyCallback" ), ucfirst ( strtolower ( $word ) ) );
			$cache [$word] = $classify;
		}
		return $cache [$word];
	}
	
	/**
	 * Callback function to classify a classname properly.
	 *
	 * @param  array  $matches  An array of matches from a pcre_replace call
	 * @return string $string   A string with matches 1 and mathces 3 in upper case.
	 */
	public static function classifyCallback($matches) {
		return $matches [1] . strtoupper ( $matches [3] );
	}
	
	/**
	 * Replaces underscores with spaces to string given.
	 * 
	 * @param string $str
	 * @return string 
	 */
	public function humanize($str) {
		
		$str = trim ( strtolower ( $str ) );
		$str = str_replace ( '_', ' ', $str );
		return ucwords($str);
	}
}

/* End of file */

