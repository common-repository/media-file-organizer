<?php
/**
 * File relocation provider
 *
 * @link       http://TheWebDeveloper.io
 * @since      1.0.0
 *
 * @package    Media_File_Organizer
 * @subpackage Media_File_Organizer/includes/lib
 */

/**
 * File relocation provider
 *
 * Contains the implementation of moving/renaming a file
 *
 * @package    Media_File_Organizer
 * @subpackage Media_File_Organizer/includes/lib
 * @author     Sherif Mesallam <SherifMesallam@gmail.com>
 */
class Media_File_Organizer_File_Provider extends Media_File_Organizer_Item_Provider implements Media_File_Organizer_Item {

	/**
	 * Media_File_Organizer_File_Provider constructor.
	 *
	 * @param Media_File_Organizer_Path $old_path the old path of the file 2019/02/image.jpg
	 * @param Media_File_Organizer_Path $new_path the new path of the file 2019/02/new_image.jpg or 2019/01/image.jpg
	 */
	public function __construct(Media_File_Organizer_Path $old_path, Media_File_Organizer_Path $new_path ) {
		$this->old_path = $old_path ;
		$this->new_path = $new_path ;
	}

	/**
	 * Attempts to relocate the file
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
			// Validate paths
			if( ! $this->validate() )
				return $this->fail( __('Media_File_Organizer_Path validation failed', 'media-file-organizer' ) );

			// Decide if it is a rename or a move
			if( $this->old_path->get_base() == $this->new_path->get_base() ) {
				return $this->move();
			}
			else {
				return $this->rename();
			}
		}
		catch (Exception $e){
			return $this->fail( $e->getMessage() );
		}

	}

	/**
	 * Moves a file
	 *
	 * Renames the file path and all its related files' paths
	 * Updates the attachment post and all posts that the file was referenced in
	 *
	 * @return bool
	 *
	 * @since 1.0.0
	 */
	public function move() {

		// System files that will be moved
		$files_to_move = [
			['old' => $this->old_path, 'new' => $this->new_path ]
		];

		// If attachment post found, there are other files to be moved
		if( $this->old_path->has_attachment_post() ){
			/**
			 * Parent folder of old path
			 *
			 * @var $old_parent Media_File_Organizer_Path
			 */
			$old_parent = $this->old_path->get_parent();

			/**
			 * Parent folder of new path
			 *
			 * @var $new_parent Media_File_Organizer_Path
			 */
			$new_parent = $this->new_path->get_parent();

			$related_file_names = $this->old_path->get_attachment_post()->get_related_file_names();

			foreach ($related_file_names as $file_name ){

				if( empty( $file_name ) )
					continue ;

				$files_to_move[] = [
					'old' => $old_parent->append( $file_name ),
					'new' => $new_parent->append( $file_name )
				];
			}
		}

		// Start Moving Files
		$this->log( 'start moving files ');
		foreach ( $files_to_move as $file ){

			$file_moved = $this->rename_path( $file[ 'old' ], $file[ 'new' ] ) ;

			if( ! $file_moved ){
				return $this->fail( __('Could not move file ', 'media-file-organizer' ). $file[ 'old' ] . __(' to ', 'media-file-organizer' ) . $file[ 'new' ] ) ;
			}

		}
		$this->log( ' files moved ');
		// Start updating DB
		if( ! $this->start_db_changes() )
			return $this->fail( __('Couldn\'t start DB transaction', 'media-file-organizer' ) ) ;

		$this->log(' DB Started ');

		// Update attachment post if exists
		if( $this->old_path->has_attachment_post() ){

			$attachment = $this->old_path->get_attachment_post() ;

			if( $attachment->update( $this->new_path ) === false ){
				return $this->fail( __('Couldn\'t update attachment post ', 'media-file-organizer' ).$attachment->get_id() );
			}
			else{
				$this->history['db']['meta'][] = $attachment->get_id() ;
			}

		}

		// Update posts that have that file in their content
		$this->update_posts_content();

		if( ! $this->commit_db_changes() )
			return $this->fail( __( 'Couldn\'t COMMIT DB ', 'media-file-organizer' ) );

		$this->log(' DB Committed ');

		return true ;

	}

	/**
	 * Renames a file
	 *
	 * Renames the file path and all its related files' paths
	 * Updates the attachment post and all posts that the file was referenced in
	 *
	 * @return bool
	 *
	 * @since 1.0.0
	 *
	 * TODO A lot of code is repeated from move
	 */
	public function rename() {

		// System files that will be renamed
		$files_to_rename = [
			['old' => $this->old_path, 'new' => $this->new_path ]
		];

		// If attachment post found, there are other files to be moved
		if( $this->old_path->has_attachment_post() ){

			$attachment = $this->old_path->get_attachment_post() ;
			/**
			 * Parent folder of old path
			 * New name will be appended to it to create the new path of secondary files
			 *
			 * @var $old_parent Media_File_Organizer_Path
			 */
			$old_parent = $this->old_path->get_parent();

			$related_file_names = $attachment->get_related_file_names();
			foreach ($related_file_names as $file_name ){

				if( empty( $file_name ) )
					continue ;

				// Get the new name of the secondary file
				$new_name = $attachment->get_new_name( $file_name, $this->new_path );

				if( empty( $new_name ) )
					continue ;

				$files_to_rename[] = [
					'old' => $old_parent->append( $file_name ),
					'new' => $old_parent->append( $new_name )
				];
			}
		}


		// Start Moving
		$this->log( 'start renaming files ');
		foreach ( $files_to_rename as $file ){

			$file_renamed = $this->rename_path( $file[ 'old' ], $file[ 'new' ] ) ;

			if( ! $file_renamed ){
				return $this->fail(__('Could not rename file ', 'media-file-organizer' ). $file[ 'old' ] . __( ' to ', 'media-file-organizer' ) . $file[ 'new' ] ) ;
			}

		}
		$this->log( 'files renamed');
		// Start updating DB
		if( ! $this->start_db_changes() )
			return $this->fail( __( 'Couldn\'t start DB transaction ', 'media-file-organizer' ) );

		$this->log(' DB Started ');

		// Update attachment post if exists
		if( $this->old_path->has_attachment_post() ){

			$attachment = $this->old_path->get_attachment_post() ;

			if( $attachment->update( $this->new_path ) === false ){
				return $this->fail( __('Couldn\'t update attachment post ', 'media-file-organizer' ).$attachment->get_id() );
			}
			else{
				$this->history['db']['meta'][] = $attachment->get_id() ;
			}

		}

		// Update posts that have that file in their content
		$this->update_posts_content();

		if( ! $this->commit_db_changes() )
			return $this->fail( __('Couldn\'t COMMIT DB ', 'media-file-organizer' ) );

		$this->log(' DB Committed ');

		return true ;
	}


	/**
	 * Checks if old and new paths are valid for relocation
	 *
	 * @return bool
	 */
	public function validate(){

		#TODO : if has attachment post, should check destination for all related files ?
		/*if( $this->old_path->has_attachment_post() ){

		}*/

		// If nothing needs to be done
		if( $this->old_path->get_relative_path() == $this->new_path->get_relative_path() ){
			$this->log_error( __('Origin is the same as destination', 'media-file-organizer' ) );
			return false ;
		}

		// Make sure the parent folder exists
		$new_parent = $this->new_path->get_parent();
		if( ! $new_parent->is_folder() ){
			$this->log_error( __('Parent folder doesn\'t exist :', 'media-file-organizer' ) . $new_parent->get_system_path() );
			return false;
		}

		// Make sure the file name doesn't already exist
		if( $this->new_path->exists() ){
			$this->log_error( __('New File name already exist : ', 'media-file-organizer' ). $this->new_path );
			return false ;
		}

		//Make sure it is a valid name
		if(  $this->new_path->is_valid_file_name() === false ){
			$this->log_error( __( 'New File name is invalid : ', 'media-file-organizer' ). basename( $this->new_path ) );
			return false ;
		}

		return true ;
	}

	/**
	 * Updates posts content where the file were referenced
	 *
	 * @return bool
	 */
	private function update_posts_content(){

		// We need to update the files that were successful moved/renamed
		foreach ($this->history[ 'files' ] as $file  ){
			/**
			 * @var $old_path Media_File_Organizer_Path
			 */
			$old_path = $file[ 'old' ];
			/**
			 * @var $new_path Media_File_Organizer_Path
			 */
			$new_path = $file[ 'new' ];

			global $wpdb ;
			$query = "UPDATE $wpdb->posts set post_content = REPLACE(post_content, %s, %s)
         		  WHERE post_content like %s ";

			$params = [ $old_path->get_url(), $new_path->get_url() , '%%'.$wpdb->esc_like( $old_path->get_url() ).'"%%' ] ; // notice the " at the end to make sure file.jpg will not also replace file.jpg.jpg

			$updated = $wpdb->query( $wpdb->prepare( $query, $params ) ) ;

			if( $updated === false ){
				Media_File_Organizer_Helper::debug( 'Update post content error : '.$wpdb->last_error ) ;
				Media_File_Organizer_Helper::debug( 'Update post content query : '.$wpdb->last_query ) ;
			}
		}

		return true ;
	}

}