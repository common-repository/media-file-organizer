<?php
/**
 * Media_File_Organizer_Path exploration
 *
 * @link       http://TheWebDeveloper.io
 * @since      1.0.0
 *
 * @package    Media_File_Organizer
 * @subpackage Media_File_Organizer/includes/lib
 */

/**
 * Media_File_Organizer_Path exploration
 *
 * Responsible for exploring a path and rendering its contents
 *
 * @package    Media_File_Organizer
 * @subpackage Media_File_Organizer/includes/lib
 * @author     Sherif Mesallam <SherifMesallam@gmail.com>
 */
class Media_File_Organizer_Panel_Provider  {


	/**
	 * Details contents of a given path
	 *
	 * Combines files related ( secondary sizes and backup files ) into one file item
	 *
	 * @return array
	 */
	public static function detail(Media_File_Organizer_Path $path ){

		$list = [ 'files' =>[], 'folders'=>[], 'dir' => $path->get_relative_path() ] ;

		$items = scandir( $path->get_system_path() );

		$items = is_array( $items ) ? array_diff( $items , ['.', '..'] ) : false ;

		if( !$items || count( $items ) <= 0 )
			return $list ;

		// Separate folders from files

		$folders = [] ;
		$files = [] ;
		$sizes = [] ;

		foreach ( $items as $item ){

			/**
			 * @var $item_path Media_File_Organizer_Path
			 */
			$item_path = $path->append( $item ) ;

			if( $item_path->is_folder() ){
				$folders[] = self::prepare_item( $item, 'folder') ;
			} elseif( $item_path->is_file() ){
				$files[] = $item ;
			}else{
				#FIXME -> DEBUGGER
			}

		}


		$add_separator = $path->is_root() ? '' : '/' ;
		// Check for attachments in $path
		// Any attachment post has a meta _wp_attached_file that contains the path of the file
		// Using regex '^path[^/]+$' against _wp_attached_file values, only attachments in $path will be retrieved
		global $wpdb ;
		$query = "SELECT posts.ID, posts.post_mime_type, metas.meta_value as file_path, metas2.meta_value as file_data, metas3.meta_value as backup_data
				  FROM $wpdb->posts posts
				  LEFT JOIN $wpdb->postmeta metas on posts.ID = metas.post_id AND metas.meta_key = '_wp_attached_file'
				  LEFT JOIN $wpdb->postmeta metas2 on posts.ID = metas2.post_id AND metas2.meta_key = '_wp_attachment_metadata'
				  LEFT JOIN $wpdb->postmeta metas3 on posts.ID = metas3.post_id AND metas3.meta_key = '_wp_attachment_backup_sizes'
				  WHERE post_type = 'attachment' AND metas.meta_value REGEXP %s
				  ORDER BY metas.meta_value
				 ";
		$params = '^'.$path->get_relative_path().$add_separator.'[^/]+$' ;


		$results = $wpdb->get_results( $wpdb->prepare( $query, $params ), ARRAY_A );



		$file_thumbs = [] ;
		foreach ( $results as $result ){
			$file_name = basename( $result[ 'file_path' ] );
			$file_data = unserialize( $result[ 'file_data' ] );
			$backup_data = unserialize( $result[ 'backup_data' ] );
			//Media_File_Organizer_Helper::debug( $backup_data, true ) ;
			// The final list will not show the different versions of the same file
			// image.png , image-350x350.png should only return image.png
			// generate different size versions of the same file so we can remove them from $files array later
			if( isset( $file_data[ 'sizes' ] ) && is_array( $file_data[ 'sizes' ] ) ){
				foreach ( $file_data[ 'sizes' ] as $size => $details ){
					if( $details[ 'file' ] != $file_name ){
						$sizes[] = $details[ 'file' ] ;
					}
				}
			}

			if( is_array( $backup_data ) ) {
				foreach ( $backup_data as $size => $details ) {
					if( $details[ 'file' ] != $file_name )
						$sizes[] = $details[ 'file' ] ;
				}
			}

			if( ! isset( $file_thumbs[ $file_name ] ) )
				$file_thumbs[ $file_name ] = wp_get_attachment_image_url( $result[ 'ID' ] );

		}

		// Removes sizes
		$files = array_diff( $files, $sizes ) ;

		$file_items = []  ;
		foreach ($files as $file ){
			$file_items[] = [
				'type' => 'file',
				'name' => $file,
				'thumb' => ( isset( $file_thumbs[ $file ] ) && ! empty( $file_thumbs[ $file ] ) )  ? $file_thumbs[ $file ] : self::get_thumb( $path->append( $file ) )
			];
		}

		$list = [
			'folders' => $folders ,
			'files' => $file_items ,
			'dir' => $path->get_relative_path()
		];

		return $list ;
	}

	/**
	 * Prepares an item with more details
	 *
	 * TODO Needs implementing !
	 *
	 * @param $name
	 * @param $type
	 *
	 * @return array
	 */
	private static function prepare_item( $name, $type ){
		$item = [] ;
		$item[ 'type' ] = $type ;
		$item[ 'name' ] = $name ;

		return $item ;
	}

	public function refresh() {
		// TODO: Implement refresh() method with CACHE
	}

	/**
	 * Returns the thumbnail image of an item
	 *
	 * @param Media_File_Organizer_Path $file
	 *
	 * @return string
	 */
	public static function get_thumb(Media_File_Organizer_Path $file ){
		if( Media_File_Organizer_Helper::isimage( $file->get_base() ) )
			return $file->get_url(); // TODO Return thumb size, return no-thumb if url doesn't exist
		elseif (Media_File_Organizer_Helper::isaudio( $file->get_base() )){
			return MEDIA_FILE_ORGANIZER_PLUGIN_URL.'images/audio.png';
		}
		elseif( Media_File_Organizer_Helper::isvideo( $file->get_base())){
			return MEDIA_FILE_ORGANIZER_PLUGIN_URL.'images/video.png';
		}

		return MEDIA_FILE_ORGANIZER_PLUGIN_URL.'images/file.png' ;
	}
}