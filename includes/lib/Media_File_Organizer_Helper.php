<?php
/**
 * Media_File_Organizer_Helper Class
 *
 * @link       http://TheWebDeveloper.io
 * @since      1.0.0
 *
 * @package    Media_File_Organizer
 * @subpackage Media_File_Organizer/includes/lib
 */

/**
 * Media_File_Organizer_Helper Class
 *
 * Contains helper functions
 *
 * @package    Media_File_Organizer
 * @subpackage Media_File_Organizer/includes/lib
 * @author     Sherif Mesallam <SherifMesallam@gmail.com>
 */

class Media_File_Organizer_Helper
{

	/**
	 * If logging option is enabled or not
	 *
	 * @var bool
	 */
	public static $logging_enabled = null ;


	/**
	 * Checks if we are running on a win os
	 *
	 * @return bool
	 */
	public static function is_win_os(){
		$os = strtolower( php_uname('s') );
		return strpos( $os, 'win' ) !== false  ;
	}


	/**
	 * Sanitizes a path and makes sure it is secure
	 *  only absolute paths are allowed, ../.. are not allowed
	 *
	 * @param $path
	 * @param bool $sanitize_name whether to use wp sanitization for the file name or not
	 *
	 * @return string
	 */
	public static function clean_path( $path, $sanitize_name = false  ){
		#FIXME Should we throw an exception if the original path is not clean ?

		$path = str_replace(array('/', '\\'), '/', $path );
		$parts = array_filter(explode('/', $path), 'strlen');
		$absolutes = array();
		foreach ($parts as $part) {

			if ('.' == $part || '..' == $part){
				continue;
			} else {
				$absolutes[] = $sanitize_name ? sanitize_file_name( $part ) : $part ;
			}

		}

		return implode('/', $absolutes);
	}

	/**
	 * Checks if a key exist in $_POST and returns the value
	 *
	 * @param $key
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public static function get_post_data( $key ){
		if( isset( $_POST[ $key ] ) )
			return $_POST[ $key ] ;
		else
			throw New Exception( 'Invalid input' );
	}

	/**
	 * Gets the path values for relocation requests
	 *
	 * @param $key
	 *
	 * @return string
	 * @throws Exception
	 */
	public static function get_clean_path( $key, $sanitize = false  ){
		return self::clean_path( self::get_post_data( $key ), $sanitize ) ;
	}

	/**
	 * Checks if a filename is for an image
	 *
	 * @param $fname
	 *
	 * @return bool
	 */
	public static function isimage ($fname ) {
		return preg_match('/\.(jpg|jpeg|gif|png|bmp|tif|tiff|dng|pef|cr2)$/i', $fname);
	}

	/**
	 * Checks if a filename is for an audio
	 *
	 * @param $fname
	 *
	 * @return bool
	 */
	public static function isaudio ($fname ) {
		return preg_match('/\.(wav|mp3|m3u|wma|ra|ram|aac|flac|ogg|opus)$/i', $fname);
	}

	/**
	 * Checks if a filename is for a video
	 *
	 * @param $fname
	 *
	 * @return bool
	 */
	public static function isvideo ($fname ) {
		return preg_match('/\.(mp4|wma|avi|flv|ogv|divx|mov|3gp)$/i', $fname);
	}

	/**
	 * Displays debugging information either on screen for development or in the log
	 *
	 * @param $data
	 * @param bool $stop_execution
	 * @param string $output
	 */
	public static function debug( $data, $stop_execution = false, $output = 'log' ){

		if( ! self::is_logging_enabled() )
			return ;

		if( $output == 'screen' ){
			highlight_string("<?php\n\$data =\n" . var_export($data, true) . ";\n?>");
			echo "<br>";
		}
		elseif ($output == 'log' ){
			try{
				//file_put_contents(media_file_organizer_LOG_PATH, PHP_EOL.'===================='.PHP_EOL, FILE_APPEND );
				$log_file_path = MEDIA_FILE_ORGANIZER_PLUGIN_DIR.'debug.log';
				file_put_contents($log_file_path, date('Y-m-d H:i:s' ).' : '.var_export( $data, true ).PHP_EOL, FILE_APPEND);
			}
			catch (Exception $e ){
				//TODO Tell user logging is not working
			}
		}

		if( $stop_execution )
			exit();
	}

	/**
	 * Checks if the logging option is enabled
	 *
	 * TODO Should use the get option function that is already in admin class, or move that function to the helper
	 *
	 * @return bool
	 */
	public static function is_logging_enabled(){
		if ( is_null( self::$logging_enabled ) ){
			$options = get_option('media_file_organizer_general', [] );
			self::$logging_enabled = isset( $options[ 'logging' ] ) ? $options[ 'logging' ] : true ;
		}

		return self::$logging_enabled ;
	}
}