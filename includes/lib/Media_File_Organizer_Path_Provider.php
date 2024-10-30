<?php
/**
 * Media_File_Organizer_Path Operations
 *
 * @link       http://TheWebDeveloper.io
 * @since      1.0.0
 *
 * @package    Media_File_Organizer
 * @subpackage Media_File_Organizer/Includes/lib
 */

/**
 * Media_File_Organizer_Path Operations
 *
 * Responsible for path validation, sanitation, creation and retrieving information.
 *
 * @package    Media_File_Organizer
 * @subpackage Media_File_Organizer/Includes/lib
 * @author     Sherif Mesallam <SherifMesallam@gmail.com>
 */
class Media_File_Organizer_Path_Provider implements Media_File_Organizer_Path {

	/**
	 * Media_File_Organizer_Path that is relative to the Uploads dir
	 *
	 * @var string $relative_path
	 */
	private $relative_path ;

	/**
	 * Uploads path appended to relative path
	 *
	 * @var string $uploads_path
	 */
	private $uploads_path ;

	/**
	 * Full system path for file system operations
	 *
	 * @var string $system_path
	 */
	private $system_path ;

	/**
	 * Media_File_Organizer_Path URL for DB Operations where path is being referenced as a URL
	 *
	 * @var string $url
	 */
	private $url ;

	/**
	 * Is this a new path that is still not created
	 *
	 * @var bool new
	 */
	private $new;

	/**
	 * If this path is related to an attachment post, this is its ID
	 *
	 * @var integer $attachment_post_id
	 */
	private $attachment_post_id;

	/**
	 *
	 * @var Media_File_Organizer_Attachment_Post
	 */
	private $attachment_post;

	/**
	 * Media_File_Organizer_Path_Provider constructor.
	 *
	 * @param $relative_path
	 * @param bool $new
	 *
	 * @throws Exception
	 */
	public function __construct( $relative_path, $should_be_new = false ) {
		$this->relative_path = trim( $relative_path, " \t\n\r\0\x0B/" ) ;
		$this->new = ! $this->exists() ;

		if( $should_be_new && ! $this->new )
			throw new Exception(' Media_File_Organizer_Path'. $this->relative_path.' Already exists' );
	}

	/**
	 * @return string
	 */
	public function get_relative_path() {
		return $this->relative_path ;
	}

	/**
	 * Checks if this is the uploads directory root
	 *
	 * @return bool
	 */
	public function is_root(){
		return $this->relative_path == '' || $this->relative_path == '/' ;
	}

	/**
	 * @return string
	 */
	public function get_uploads_path(){
		$dir =  wp_upload_dir();
		if( is_null( $this->uploads_path ) ) {
			// If full path already starts with the uploads dir, it is in full
			if( strpos( $this->relative_path, trailingslashit( $dir[ 'basedir' ] ) ) === 0 )
				$this->uploads_path = $this->relative_path ;
			else
				$this->uploads_path = trailingslashit( $dir[ 'basedir' ] ) . $this->relative_path ;
		}

		return $this->uploads_path ;

	}

	/**
	 * Returns the file path according to OS
	 *
	 * TODO MAKE SURE ABOUT OTHER SYSTEMS
	 * @return string
	 */
	public function get_system_path() {

		if( is_null( $this->system_path ) ){
			// If win os, there is a possibility of mixed separators (c:\www\dir/dir/file)
			if ( Media_File_Organizer_Helper::is_win_os() ){
				$this->system_path = str_replace( '/', '\\', $this->get_uploads_path() ) ;
			}
			else{
				$this->system_path = $this->get_uploads_path() ;
			}
		}

		return $this->system_path ;
	}

	/**
	 * Adds a level to current path
	 *
	 * @param $level
	 *
	 * @return Media_File_Organizer_Path|Media_File_Organizer_Path_Provider
	 * @throws Exception
	 */
	public function append( $level ){
		return new Media_File_Organizer_Path_Provider( $this->relative_path . '/' . $level ) ;
	}

	/**
	 * Gets current path parent
	 *
	 * @return Media_File_Organizer_Path|Media_File_Organizer_Path_Provider
	 * @throws Exception
	 */
	public function get_parent() {
		$parts = explode( '/', $this->relative_path );
		array_pop( $parts );

		$parent = new Media_File_Organizer_Path_Provider( implode('/', $parts ) ) ;

		return $parent ;
	}

	/**
	 * Returns last level of path
	 *
	 * @param bool $with_extension
	 *
	 * @return string
	 */
	public function get_base( $with_extension = true ) {
		$base_name =  basename( $this->relative_path ) ;

		if( ! $with_extension ){
			$ar = explode('.', $base_name );
			$base_name = $ar[0] ;
		}

		return $base_name ;
	}

	/**
	 * Gets the path URL according to WP base url option
	 *
	 * @return string
	 */
	public function get_url() {
		$uploads_dir = wp_upload_dir();
		$this->url = trailingslashit( $uploads_dir[ 'baseurl' ] ) . $this->get_relative_path() ;

		return $this->url ;
	}

	/**
	 * If the path is a file
	 *
	 * @return bool
	 */
	public function is_file() {
		return is_file( $this->get_system_path() ) ;
	}

	/**
	 * If the path is of a valid name according to OS
	 * TODO -> IMPLEMENT & SHOULD IT BE HERE ??
	 *
	 * @return bool
	 */
	public function is_valid_file_name(){
		$chars = "/\0";   // default is Linux-like OS
		$class = '[:cntrl:]';
		if (PHP_OS == 'Darwin') { // MacOS
			$chars = ':';
		} elseif (preg_match('/^win/i', PHP_OS)) { // Windows
			$chars = '\/<>:"\'|?*';
		}
		$regex = '/[' . preg_quote($chars, '/') . $class . ']/';

		if(  preg_match( $regex, $this->get_base( true )  ) )
			return false   ;

		return true  ;
	}

	/**
	 * Checks if the path is a folder
	 *
	 * @return bool
	 */
	public function is_folder() {
		return is_dir( $this->get_system_path() ) ;
	}

	/**
	 * Checks if the path exists !
	 *
	 * @return bool
	 */
	public function exists() {
		return file_exists( $this->get_system_path() ) ;
	}

	public function __toString() {
		return $this->get_relative_path() ;
	}

	/**
	 * Removes illegal characters
	 * TODO WHAT TO DO IF IT IS NOT VALID !!?
	 */
	private function sanitize(){

		$parts = explode( '/', $this->relative_path );
		$clean = [] ;

		foreach ( $parts as $part ){
			$sanitized = sanitize_file_name( $part ) ;
			if( ! empty( $sanitized ) ){
				$clean[] = $sanitized ;
			}
		}

		$this->relative_path = realpath( implode( '/', $clean ) ) ;

		if( $this->relative_path == '/' )
			$this->relative_path = '' ;
	}

	/**
	 * If a file return its extension
	 *
	 * @param bool $with_dot
	 *
	 * @return string
	 */
	public function get_extension( $with_dot = false ){
		$base = $this->get_base();;
		$ar = explode( '.', $base );
		if( is_array( $ar ) && count( $ar ) > 0 ){
			$ext = array_pop( $ar );
		}

		$ext_string = empty( $ext ) ? '' : '.' . $ext ;

		if( $with_dot )
			return $ext_string ;

		return $ext ;
	}

	/**
	 * Created the path in the file system
	 *
	 * @return bool|string
	 */
	public function create(){
		if( $this->new ){
			try {
				if( @mkdir( $this->get_system_path() ) ){
					$this->new = false ;
					return true ;
				}
			}
			catch(Exception $e){
				return $e->getMessage() ;
			}
		}

		return false ;
	}

	/**
	 * Checks if this path has an attachment post related to it in WP
	 *
	 * @return bool
	 */
	public function has_attachment_post(){

		if( is_null( $this->attachment_post_id ) )
			$this->get_attachment_post_id();

		if( $this->attachment_post_id !== false )
			return true ;

		return false ;
	}

	/**
	 * Get the attachment post related to this path
	 *
	 * @return Media_File_Organizer_Attachment_Post|bool
	 */
	public function get_attachment_post(){

		if( ! $this->has_attachment_post() )
			return false  ;

		if( is_null( $this->attachment_post ) ){
			$this->attachment_post = new Media_File_Organizer_Attachment_Post( $this->attachment_post_id, $this );
		}

		return $this->attachment_post ;
	}

	/**
	 * @return bool|int
	 */
	public function get_attachment_post_id(){

		if( is_null( $this->attachment_post_id ) ){

			if( $this->new || $this->is_folder() ) {
				$this->attachment_post_id = false ;
				return false ;
			}

			global $wpdb ;

			$query = "SELECT posts.ID, metas.meta_value as file_path
				  FROM $wpdb->posts posts
				  LEFT JOIN $wpdb->postmeta metas on posts.ID = metas.post_id AND metas.meta_key = '_wp_attached_file'
				  WHERE posts.post_type = 'attachment' AND metas.meta_value = %s
				 ";
			$params = $this->get_relative_path() ;

			#TODO Could there be more than one record ?
			$result = $wpdb->get_row( $wpdb->prepare( $query, $params ) , ARRAY_A );

			if( is_array( $result ) && !empty ( $result[ 'ID' ] ) ){
				$this->attachment_post_id = $result[ 'ID' ];
			}
			else
				$this->attachment_post_id = false ;
		}

		return $this->attachment_post_id ;
	}

	/**
	 * Checks if this path is parent of given path
	 *
	 * @param Media_File_Organizer_Path $possible_child
	 *
	 * @return bool
	 */
	public function is_parent( Media_File_Organizer_Path $possible_child ){
		$my_levels = $this->get_levels();
		$possible_child_levels = $possible_child->get_levels();

		if( $my_levels === array_slice( $possible_child_levels, 0, count( $my_levels ) ) )
		    return true ;

		return false ;
	}

	/**
	 * Get all path levels
	 *
	 * @return array
	 */
	public function get_levels(){
		$sep = Media_File_Organizer_Helper::is_win_os() ? '\\' : '/' ;
		return explode($sep, $this->get_system_path() );
	}



}