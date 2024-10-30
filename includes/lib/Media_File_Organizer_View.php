<?php
/**
 * Viewer
 *
 * @link       http://TheWebDeveloper.io
 * @since      1.0.0
 *
 * @package    Media_File_Organizer
 * @subpackage Media_File_Organizer/Includes/lib
 */

/**
 * Viewer
 *
 * Responsible for Displaying information
 *
 * @package    Media_File_Organizer
 * @subpackage Media_File_Organizer/Includes/lib
 * @author     Sherif Mesallam <SherifMesallam@gmail.com>
 */
class Media_File_Organizer_View {

	public static function render( $view_name, $data ){

		ob_start();

		self::show( $view_name, $data ) ;

		return ob_get_clean() ;
	}

	public static function show( $view_name, $data, $log = false ){

		$view_path = MEDIA_FILE_ORGANIZER_PLUGIN_DIR.'/views/'.$view_name.'-view.php';
		if( file_exists( $view_path ) ){
			extract( $data );
			include  $view_path ;

			if( $log )
				highlight_string("<?php\n\$data =\n" . var_export($data, true) . ";\n?>");
		}
		else
			echo 'Could not find view : '.$view_path ;

	}
}