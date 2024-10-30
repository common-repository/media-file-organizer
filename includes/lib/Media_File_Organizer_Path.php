<?php
interface Media_File_Organizer_Path {
	public function get_relative_path() ;
	public function get_system_path() ;
	public function get_uploads_path() ;
	public function get_url()  ;

	/**
	 * @return Media_File_Organizer_Path
	 */
	public function get_parent() ;
	public function get_base( $with_extension = true ) ;
	public function get_extension( $with_dot = false );
	public function is_file();
	public function is_valid_file_name();
	public function is_folder();

	/**
	 * @param Media_File_Organizer_Path $possible_child
	 *
	 * @return bool
	 */
	public function is_parent(Media_File_Organizer_Path $possible_child );
	public function exists() ;
	public function has_attachment_post();
	public function get_attachment_post_id();

	/**
	 * @return Media_File_Organizer_Attachment_Post
	 */
	public function get_attachment_post();

	/**
	 * @param $level
	 *
	 * @return Media_File_Organizer_Path
	 */
	public function append( $level ) ;
	public function __toString();
}