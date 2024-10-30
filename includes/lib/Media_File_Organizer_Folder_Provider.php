<?php
/**
 * Folder relocation provider
 *
 * @link       http://TheWebDeveloper.io
 * @since      1.0.0
 *
 * @package    Media_File_Organizer
 * @subpackage Media_File_Organizer/includes/lib
 */

/**
 * Folder relocation provider
 *
 * Contains the implementation of moving/renaming a folder
 *
 * @package    Media_File_Organizer
 * @subpackage Media_File_Organizer/includes/lib
 * @author     Sherif Mesallam <SherifMesallam@gmail.com>
 */
class Media_File_Organizer_Folder_Provider extends Media_File_Organizer_Item_Provider implements Media_File_Organizer_Item {

	public function __construct(Media_File_Organizer_Path $old_path, Media_File_Organizer_Path $new_path ) {
		$this->old_path = $old_path ;
		$this->new_path = $new_path ;
	}

	/**
	 * Attempts to relocate the folder
	 *
	 * @param null $history_id
	 * history id to access the item entry in the  history array ( saved in wp_options ) and add file movements
	 *
	 * @return bool
	 *
	 * @since 1.0.0
	 */
	public function relocate( $history_id = null ){
		parent::relocate( $history_id );
		try{
			if( ! $this->validate() )
				return $this->fail('Media_File_Organizer_Path validation failed') ;

			// Renaming and moving a folder is the same process
			return $this->move() ;
		}
		catch( Exception $e ){
			return $this->fail( $e->getMessage() );
		}
	}

	/**
	 * Moves or renames a folder
	 *
	 * Renames the folder path
	 * Finds all attachment posts of files inside this folder and updates them
	 * Finds all the posts where the updated attachment posts were referenced in and update their content
	 *
	 * @return bool
	 *
	 * @since 1.0.0
	 */
	public function move() {

		/**
		 * Move the system folder by renaming its path
		 * uploads/public_images/cars/hondas/... becomes uploads/private/images/hondas/
		 */
		// Keep History
		$this->log( 'Begin moving folder ');
		$folder_moved = $this->rename_path( $this->old_path, $this->new_path );

		if( ! $folder_moved ){
			return $this->fail(__(' Couldn\'t rename ', 'media-file-organizer' ).$this->old_path->get_system_path(). __(' to ','media-file-organizer'). $this->new_path->get_system_path() );
		}

		$this->log('folder moved');
		/**
		 * Any attachments that had the folder path ( for example uploads/public_images/cars/hondas/ )
		 * in their attached file needs to be changed to the new path
		 *
		 * Check for all attachments posts that have the old path ( for example uploads/public_images/cars/hondas/ ) in
		 * their _wp_attached_file meta value and get the ids
		 */
		$attachments = $this->get_attachment_posts() ;

		if( ! $this->start_db_changes() ){
			return $this->fail( __('Couldn\'t start DB transaction ', 'media-file-organizer' ) ) ;
		}
		$this->log(' DB Started ');
		foreach ( $attachments as $post ){
			/**
			 * Update the _wp_attached_file meta_value with the new one
			 * wp_get_attachment_metadata for the id
			 * change the file in the attachment_metadata
			 * wp_update_attachment_metadata
			 */

			if( ! $this->update_attachment_post( $post[ 'ID' ] ) ){
				return $this->fail( __(' Couldn\'t update attachment post ', 'media-file-organizer' ).$post[ 'ID' ] );
			}
			else{
				$this->history['db']['meta'][] = $post[ 'ID' ];
			}
		}

		// Update posts that have files inside that folder referenced in their content
		$this->update_posts_content() ;

		if( ! $this->commit_db_changes() )
			return $this->fail( __('Couldn\'t COMMIT DB ', 'media-file-organizer' ) );

		$this->log(' DB Committed ');

		return true ;
	}

	/**
	 * Checks if old and new paths are valid for relocation
	 *
	 * @return bool
	 *
	 * @since 1.0.0
	 */
	public function validate(){

		// If nothing needs to be done
		if( $this->old_path->get_relative_path() == $this->new_path->get_relative_path() ){
			$this->log_error( __( 'Origin is the same as destination', 'media-file-organizer' ) );
			return false ;
		}

		// Make sure the new path doesn't already exist
		if( $this->new_path->exists() ){
			$this->log_error( __( 'new path exists', 'media-file-organizer' ) );
			return false ;
		}

		// Make sure the new path is not a child of the old path
		// uplodas/images/category/ can't be moved into uplodas/images/category/subfolder/
		if( $this->old_path->is_parent( $this->new_path )  ){
			$this->log_error(__('Can\'t relocate a parent directory into one of its children ', 'media-file-organizer' ) ) ;
			return false ;
		}

		// Make sure the new path exists until the last folder
		// if we are moving to uplodas/images/category/subfolder/ uplodas/images/category/ should be a dir
		/**
		 * @var Media_File_Organizer_Path $parent
		 */
		$parent = $this->new_path->get_parent();
		if( ! $parent->is_folder() ){
			$this->log_error( __('New path is invalid', 'media-file-organizer' ) .$parent->get_system_path().__(' doesn\'t exist' , 'media-file-organizer' ) );
			return false ;
		}

		return true ;
	}

	/**
	 * Finds all attachment posts of files inside old path
	 *
	 * @return array
	 *
	 * @since 1.0.0
	 */
	private function get_attachment_posts( ){

		global $wpdb ;
		// We need attachment posts where the path exist in the beginning of _wp_attached_file value
		$query = "SELECT posts.ID, metas.meta_value as file_path
				  FROM $wpdb->posts posts
				  LEFT JOIN $wpdb->postmeta metas on posts.ID = metas.post_id AND metas.meta_key = '_wp_attached_file'
				  WHERE posts.post_type = 'attachment' AND metas.meta_value LIKE %s
				 ";
		$params = $wpdb->esc_like( $this->old_path->get_relative_path() ).'/%%';

		$posts_results = $wpdb->get_results( $wpdb->prepare( $query, $params ) , ARRAY_A );
		if( is_null( $posts_results ) )
			return [];

		return $posts_results ;
	}

	/**
	 * Updates an attachment post meta data
	 *
	 * @param $post_id
	 *
	 * @return bool
	 *
	 * @since 1.0.0
	 *
	 * TODO should use the attachment post class for this ?
	 */
	private function update_attachment_post( $post_id ){

		global $wpdb ;

		// Update _wp_attached_file, replace only the changed part of the path
		$query = "UPDATE $wpdb->postmeta set meta_value = REPLACE(meta_value, %s, %s) 
				  WHERE post_id = %d AND meta_key = %s
				 ";
		$params = [
			$this->old_path->get_relative_path(),
			$this->new_path->get_relative_path(),
			$post_id, '_wp_attached_file'
		] ;

		$updated = $wpdb->query( $wpdb->prepare( $query, $params ) ) ;

		if( $updated === false ){
			Media_File_Organizer_Helper::debug( 'Attached file update error ( POST ID - '.$post_id.' ) : '. $wpdb->last_error );
			Media_File_Organizer_Helper::debug(  'Attached file update error query: '. $wpdb->last_query ) ;
			return false ;
		}

		// Update attachments meta data
		$meta = wp_get_attachment_metadata( $post_id ) ;

		$old_file_path = $meta[ 'file' ];
		$new_file_path = str_replace( $this->old_path, $this->new_path, $old_file_path );
		$meta[ 'file' ] = $new_file_path ;

		$meta_updated = wp_update_attachment_metadata( $post_id, $meta );

		if( ! $meta_updated ){
			Media_File_Organizer_Helper::debug( 'Meta update error ( POST ID - '.$post_id.' )  '.var_export( $meta, true ) );
		}

		return true ;
	}

	/**
	 * Updates posts content where the files inside the old path folder were referenced
	 *
	 * @return bool
	 */
	private function update_posts_content(){

		global $wpdb ;

		$query = "UPDATE $wpdb->posts set post_content = replace( post_content, %s, %s )
         		  WHERE post_content like %s ";
		$params = [ $this->old_path->get_url(), $this->new_path->get_url() , '%%'.$wpdb->esc_like( $this->old_path->get_url() ).'%%' ] ;

		$updated = $wpdb->query( $wpdb->prepare( $query, $params ) ) ;

		if( $updated === false ){
			Media_File_Organizer_Helper::debug( 'Content Update error : ' . $wpdb->last_error ) ;
			Media_File_Organizer_Helper::debug( 'Content Update error SQL: ' . $wpdb->last_query );
			return false ;
		}
		else
			$this->history['db']['content'] = $updated.' posts updated' ;

	}

	/**
	 * Experimental
	 */
	private function update_posts_content_log(){

		global $wpdb ;

		$query = " SELECT ID from $wpdb->posts WHERE post_content like %s ";
		$params = [ '%%'.$wpdb->esc_like( $this->old_path->get_url() ).'%%' ];
		$results = $wpdb->get_results( $wpdb->prepare( $query, $params ), ARRAY_A );

		foreach ($results as $result ){

			$query = "UPDATE $wpdb->posts set post_content = replace( post_content, %s, %s )
         		  WHERE ID = %d ";
			$params = [ $this->old_path->get_url(), $this->new_path->get_url(), $result[ 'ID' ] ] ;

			$updated = $wpdb->query( $wpdb->prepare( $query, $params ) ) ;

			if( $updated === false ){
				Media_File_Organizer_Helper::debug(  $wpdb->last_error );
				//return false ;
			}
			else
				$this->history['db']['content'][] = $result[ 'ID' ];
		}

	}

}