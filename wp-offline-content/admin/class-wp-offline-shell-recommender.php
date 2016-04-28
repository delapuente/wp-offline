<?php
/**
 * Includes the class holding the file recommender logic
 *
 * @package OfflineContent
 */

/**
 * Represents the recommender.
 *
 * It suggests the files of a WordPress installation that should be cached to
 * increase the performance.
 */
class Offline_Shell_Recommender {
	/**
	 * The singleton instance.
	 *
	 * @var Offline_Shell_Recommender
	 */
	private static $instance;

	/**
	 * Initializes the recommender shared instance.
	 *
	 * @returns Offline_Shell_Recommender
	 */
	public static function init() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Decides if there is a minified version of the file.
	 *
	 * @param string   $file_name path to the file.
	 * @param string[] $all_files list of paths.
	 * @returns bool true if there exist a minified version of the file.
	 */
	public static function has_min_file( $file_name, $all_files ) {
		$exploded_path = explode( '/', $file_name );
		$immediate_name = array_pop( $exploded_path );
		$split_name = explode( '.', $immediate_name );
		$ext = array_pop( $split_name );
		$name_without_extension = implode( '/', $split_name );
		$regex = '/'.preg_quote( $name_without_extension, '/' ).'[-|.](min|compressed).'.$ext.'/';

		return count( preg_grep( $regex, $all_files ) );
	}

	/**
	 * Checks if a file name match one of any regular expressions.
	 *
	 * @param string   $file_name the file name to check.
	 * @param string[] $regexes array of regular expressions.
	 * @returns bool true if the file name matches any of the regular expressions.
	 */
	public static function matches_any_regex( $file_name, $regexes = array() ) {
		foreach ( $regexes as $regex ) {
			if ( preg_match( $regex, $file_name ) ) {
				return true;
			}
		}

		return false;
	}
}

?>
