<?php
/**
 * Media_File_Organizer_Item Provider Factory
 *
 * @link       http://TheWebDeveloper.io
 * @since      1.0.0
 *
 * @package    Media_File_Organizer
 * @subpackage Media_File_Organizer/includes/lib
 */

/**
 * Media_File_Organizer_Item Provider Factory
 *
 * Creates an item for relocation
 *
 * @package    Media_File_Organizer
 * @subpackage Media_File_Organizer/includes/lib
 * @author     Sherif Mesallam <SherifMesallam@gmail.com>
 */
class Media_File_Organizer_Item_Factory {

	/**
	 * Creates an item for relocation
	 *
	 * @param Media_File_Organizer_Path $old_path
	 * @param Media_File_Organizer_Path $new_path
	 *
	 * @return Media_File_Organizer_Item
	 * @throws Exception
	 */
	public static function create(Media_File_Organizer_Path $old_path, Media_File_Organizer_Path $new_path ){
		if( ! $old_path->exists() )
			throw new Exception( $old_path->get_system_path().' doesn\'t exist !');

		if( $old_path->is_folder() )
			return new Media_File_Organizer_Folder_Provider( $old_path, $new_path );
		elseif ( $old_path->is_file() )
			return new Media_File_Organizer_File_Provider( $old_path, $new_path );
		else
			throw new Exception( __('Unknown Media_File_Organizer_Item', 'media-file-organizer' ) );
	}
}