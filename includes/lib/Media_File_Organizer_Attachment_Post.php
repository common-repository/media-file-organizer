<?php
/**
 * Contains attachment posts specific operations
 *
 * @link       http://TheWebDeveloper.io
 * @since      1.0.0
 *
 * @package    Media_File_Organizer
 * @subpackage Media_File_Organizer/includes/lib
 */

/**
 * Contains attachment posts specific operations
 *
 * Gets all the files related to an attachment ( secondary sizes and backup files )
 * Updates an attachment post meta and file
 *
 * @package    Media_File_Organizer
 * @subpackage Media_File_Organizer/includes/lib
 * @author     Sherif Mesallam <SherifMesallam@gmail.com>
 */
class Media_File_Organizer_Attachment_Post {
	/**
	 * Attachment Post ID
	 *
	 * @var integer
	 */
	private $post_id ;

	/**
	 * Attachment main file path on the uploads directory
	 *
	 * @var Media_File_Organizer_Path|string
	 */
	private $attached_file_path = '' ;

	/**
	 * Meta data unserialized
	 *
	 * @var array
	 *
	 */
	private $meta ;

	/**
	 * Meta sizes unserialized
	 *
	 * @var array
	 */
	private $meta_sizes = null;

	/**
	 * Backup sizes unserialized
	 *
	 * @var array
	 */
	private $backup_sizes = null;

	/**
	 * All files ( sizes and backup ) related to the main attachment file
	 *
	 * @var array
	 */
	private $related_file_names = null;

	public function __construct($post_id, Media_File_Organizer_Path $attached_file_path = null  ) {
		$this->post_id = $post_id ;
		$this->attached_file_path = $attached_file_path ;
	}


	public function get_id(){
		return $this->post_id ;
	}

	/**
	 * Gets the backup sizes meta data
	 *
	 * @return array
	 */
	private function get_backup_sizes(){

		if( is_null( $this->post_id ) )
			return [] ;

		if( is_null( $this->backup_sizes ) && ! is_null( $this->post_id ) ){

			$backup_sizes = get_post_meta( $this->post_id , '_wp_attachment_backup_sizes', true );

			if( is_array( $backup_sizes ) )
				$this->backup_sizes = $backup_sizes ;
			else
				$this->backup_sizes = [] ;
		}

		return $this->backup_sizes ;
	}

	/**
	 * Gets the attachment meta data
	 *
	 * @return array|null
	 */
	private function get_meta_sizes(){

		if( is_null( $this->post_id ) )
			return [] ;

		if( is_null( $this->meta_sizes ) && ! is_null( $this->post_id ) ){
			$this->meta = wp_get_attachment_metadata( $this->post_id  );
			$this->meta_sizes = isset( $this->meta[ 'sizes' ] ) ? $this->meta[ 'sizes' ] : [] ;
		}

		return $this->meta_sizes ;
	}

	/**
	 * Creates an array that has all the file names of related files ( sizes and backup )
	 *
	 * @return array
	 */
	public function get_related_file_names(){

		// If not loaded before, load them
		if( is_null( $this->related_file_names ) ){

			// Meta sizes
			$meta_sizes = $this->get_meta_sizes() ;
			foreach ( $meta_sizes as $size => $details ){
				// If it is not the main file or not included before
				// because sometimes files are duplicated in meta
				if( $this->attached_file_path->get_base() != $details[ 'file' ] && ! in_array( $details[ 'file' ], $this->related_file_names ) && ! empty( $details[ 'file' ] ) )
					$this->related_file_names[] = $details[ 'file' ];
			}

			// Backup files
			$backup_sizes = $this->get_backup_sizes();
			foreach ( $backup_sizes as $size => $details ){
				// If it is not the main file or not included before
				// because sometimes files are duplicated in meta
				if( $this->attached_file_path->get_base() != $details[ 'file' ] && ! in_array( $details[ 'file' ], $this->related_file_names ) && ! empty( $details[ 'file' ] ) )
					$this->related_file_names[] = $details[ 'file' ];
			}
		}

		return $this->related_file_names ;
	}

	/**
	 * Updates an attachment post
	 *
	 * @param Media_File_Organizer_Path $new_path
	 *
	 * @return bool|int
	 */
	public function update(Media_File_Organizer_Path $new_path ){
		global $wpdb ;
		// Update _wp_attached_file meta value
		$query = "UPDATE $wpdb->postmeta set meta_value = '%s' 
				  WHERE post_id = %d AND meta_key = %s
				 ";
		$params = [ $new_path->get_relative_path(), $this->post_id, '_wp_attached_file' ];

		$attached_file_updated = $wpdb->query( $wpdb->prepare( $query, $params ) ) ;

		if( $attached_file_updated === false ) {
			Media_File_Organizer_Helper::debug( 'Attachment post error :'.$wpdb->last_error );
			Media_File_Organizer_Helper::debug( 'Attachment post error query :'.$wpdb->last_query );
			return false ;
		}

		// Update meta
		$is_rename = $this->attached_file_path->get_base() != $new_path->get_base()  ;
		// If it is a rename, secondary size file names need to be changed
		if( $is_rename ){
			$sizes = $this->get_meta_sizes() ;
			foreach ( $sizes as $size => $details ){
				$new_name = $this->get_new_name( $details[ 'file' ], $new_path  );
				$this->meta[ 'sizes' ][ $size ][ 'file' ] = $new_name ;
			}
			// Backup files that had the old name should be changed as well
			$backup_sizes = $this->get_backup_sizes() ;
			foreach ($backup_sizes as $size => $details ){
				$new_name = $this->get_new_name( $details[ 'file' ], $new_path  );
				$this->backup_sizes[ $size ][ 'file' ] = $new_name ;
			}
		}

		// Update the main file value and the sizes
		$this->meta[ 'file' ] = $new_path->get_relative_path() ;
		$meta_updated = wp_update_attachment_metadata( $this->post_id, $this->meta );

		// If it is a rename and backup names exists , update them
		// otherwise just return if meta was updated
		if( $is_rename && ! empty ( $this->backup_sizes ) )
			$backup_updated = update_post_meta( $this->post_id, '_wp_attachment_backup_sizes', $this->backup_sizes ) ;
		else
			return $meta_updated ;

		// were both updated successfully ?
		return $meta_updated && $backup_updated ;
	}

	/**
	 * Generates a secondary size file new name
	 *
	 * image.jpg renamed to new_image.png results in image-150x150.jpg becoming new_image-150x150.png
	 *
	 * @param string $old_name
	 * @param Media_File_Organizer_Path $new_path
	 *
	 * @return mixed|string
	 */
	public function get_new_name($old_name, Media_File_Organizer_Path $new_path ){
		#TODO : needs a more elegant and precise solution
		$old_name_without_extension =  substr( $old_name, 0 , ( strrpos( $old_name, '.' ) ) );

		$base_name_without_extension = $this->attached_file_path->get_base( false );
		$new_name_without_extension = $new_path->get_base( false );

		$new_name = str_replace( $base_name_without_extension, $new_name_without_extension, $old_name_without_extension );
		$new_name = $new_name . $new_path->get_extension( true );

		return $new_name ;
	}
}