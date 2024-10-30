<?php
/**
 * Abstract Media_File_Organizer_Item relocation provider
 *
 * @link       http://TheWebDeveloper.io
 * @since      1.0.0
 *
 * @package    Media_File_Organizer
 * @subpackage Media_File_Organizer/includes/lib
 */

/**
 * Abstract Media_File_Organizer_Item relocation provider
 *
 * Contains the functions needed while relocating any item
 *
 * @package    Media_File_Organizer
 * @subpackage Media_File_Organizer/includes/lib
 * @author     Sherif Mesallam <SherifMesallam@gmail.com>
 */
Abstract class Media_File_Organizer_Item_Provider implements Media_File_Organizer_Item {
	/**
	 * Saves any changes made
	 *
	 * @var array
	 */
	protected $history = [ 'files' => [], 'db' => [ 'meta' => [], 'content' => [] ] ] ;

	/**
	 * If db transaction started
	 *
	 * @var bool
	 */
	protected $db_started = false ;

	/**
	 * Relocation log and errors
	 *
	 * @var array
	 */
	protected $log = [ 'info' => [], 'errors' => [] ] ;

	/**
	 * Old file path
	 *
	 * @var Media_File_Organizer_Path
	 */
	protected $old_path;

	/**
	 * New file path
	 *
	 * @var Media_File_Organizer_Path
	 */
	protected $new_path;

	/**
	 * Media_File_Organizer_Item history id
	 *
	 * @var integer
	 */
	protected $history_id ;

	/**
	 * Relocates an Media_File_Organizer_Item
	 *
	 * @param null $history_id relocation process key in history array ( saved in wp options )
	 *
	 * @return bool
	 */
	public function relocate( $history_id = null ){
		$this->history_id = $history_id ;
	}

	/**
	 * @return Media_File_Organizer_Path
	 */
	public function get_old_path(){
		return $this->old_path ;
	}

	/**
	 * @return Media_File_Organizer_Path
	 */
	public function get_new_path(){
		return $this->new_path;
	}

	/**
	 * Start DB transaction
	 *
	 * @return bool
	 */
	protected function start_db_changes(){

		if( ! $this->db_started && $this->execute_db_query( 'START TRANSACTION' ) !== false ){
			$this->db_started = true ;
			return true ;
		}
		else{
			$this->log_error( 'can not start transaction ');
			return false ;
		}
	}

	/**
	 * Commit DB Transaction
	 *
	 * @return bool
	 */
	protected function commit_db_changes(){

		if( $this->db_started ){
			if( $this->execute_db_query( 'COMMIT' ) !== false ){
				$this->db_started = false ;
				return true ;
			}
			else
				return false ;
		}
		else{
			$this->log( 'No DB Transaction to commit ' );
		}

		return true ;
	}

	/**
	 * Rollback DB
	 *
	 * @return bool
	 */
	protected function rollback_db(){

		if( $this->db_started ){
			if( $this->execute_db_query( 'ROLLBACK' ) !== false ){
				$this->db_started = false ;
				return true ;
			}
			else
				return false ;
		}
		else{
			$this->log( 'No DB Transaction to rollback ' );
		}

		return true ;

	}

	/**
	 * Roll Back any changes made ( db & files )
	 *
	 * @return bool
	 */
	public function roll_back() {
		$files_rolled_back = $this->rollback_files() ;
		$db_rolled_back = $this->rollback_db() ;

		return $files_rolled_back && $db_rolled_back ;
	}

	/**
	 * Executes transactions queries
	 *
	 * TODO Use this to execute all queries
	 *
	 * @param $query
	 * @param array $params
	 *
	 * @return bool|false|int
	 */
	public function execute_db_query( $query, $params = [] ){
		global $wpdb;
		try{
			$query = empty( $params ) ? $query : $wpdb->prepare( $query, $params );
			$result = $wpdb->query( $query );
			return $result ;
		}
		catch ( Exception $e ){
			$this->log_error( $e->getMessage() );
			return false ;
		}
	}

	/**
	 * Renames an items path
	 *
	 * @param Media_File_Organizer_Path $old
	 * @param Media_File_Organizer_Path $new
	 *
	 * @return bool
	 */
	public function rename_path(Media_File_Organizer_Path $old, Media_File_Organizer_Path $new){
		// Some times attachment files are present in DB but not in file system, consider it done.
		if( ! $old->exists() || $old->get_system_path() == $new->get_system_path() )
			return true ;
		$this->save_files_history( $old, $new, 1 );
		try{
			if( @rename( $old->get_system_path(), $new->get_system_path() ) === true ){
				// Make sure the file was actually moved because rename acts weirdly sometimes
				if( $new->exists() && ! $old->exists() ){
					$this->history['files'][] = [
						'old' => $old ,
						'new' => $new
					] ;
					$this->save_files_history( $old, $new, 2 );
					return true;
				} #FIXME what if new exists and old still exists ??!
				else{
					// For some reason the file wasn't renamed, can not continue

					return false ;
				}
			}

			return false;
		}
		catch ( Exception $e ){
			$this->log_error( $e->getMessage() );
			return false ;
		}
	}


	/**
	 * Roll back file changes
	 * TODO need to double check the file before returning true
	 * @return bool
	 */
	protected function rollback_files(){
		$moved_files = $this->history['files'];

		if( count( $moved_files ) <= 0 )
			return true ;

		foreach ( $moved_files as $file ){
			try{
				$this->save_files_history( $file['old'], $file['new'], 3 );
				if( @rename( $file['new']->get_system_path(), $file['old']->get_system_path() ) === true ){
					$renamed[] = $file[ 'old' ];
					$this->save_files_history( $file['old'], $file['new'], 4 );

				}
			}
			catch (Exception $e){
				$this->log_error( $e->getMessage() );
			}
		}

		return count( $renamed ) == count( $moved_files );
	}


	/**
	 * Returns the latest error or all errors happened during the relocation process
	 *
	 * @param bool $full
	 *
	 * @return mixed|string
	 */
	public function explain( $full = false ){

		if( ! $full ){
			$last_error = array_pop( $this->log['errors'] ) ;
			return $last_error ;
		}
		else{
			$errors = '' ;
			foreach ($this->log['errors'] as $error ){
				$errors .= $error . PHP_EOL;
			}
			return $errors;
		}

	}

	/**
	 * Returns process information
	 *
	 * @param bool $log
	 *
	 * @return array
	 */
	public function tell( $log = false ){
		$data = [ 'log' => $this->log, 'history' => $this->history ] ;
		if( $log )
			Media_File_Organizer_Helper::debug( $data );

		return $data ;
	}

	/**
	 * Logs an error and returns false
	 *
	 * @param string $message
	 *
	 * @return false
	 */
	public function fail( $message = 'Failed !' ){
		$this->log_error( $message ) ;
		return false ;
	}

	/**
	 * Saves information to process log
	 *
	 * @param $information
	 * @param bool $verbose
	 */
	public function log( $information, $verbose = false  ){
		$this->log['info'][] = $information ;
		if( $verbose ){
			Media_File_Organizer_Helper::debug( $information ) ;
		}

	}

	/**
	 * Logs an error to the process log
	 *
	 * @param $error
	 */
	public function log_error( $error ){
		$this->log[ 'errors' ][] = $error ;
	}

	/**
	 * Saves a file movement to the item relocation history
	 *
	 * @param Media_File_Organizer_Path $old
	 * @param Media_File_Organizer_Path $new
	 * @param $status
	 *
	 * @return bool
	 */
	public function save_files_history(Media_File_Organizer_Path $old, Media_File_Organizer_Path $new, $status ){
		$items_history =  get_option('media_file_organizer_item_relocation_history', [] );

		if( ! isset( $items_history[ $this->history_id ]  ) )
			return false ;


			$items_history[ $this->history_id ][ 'file_movements' ][] = [
				'old' => $old->get_system_path(),
				'new' => $new->get_system_path(),
				'status' => $status
			];


		update_option( 'media_file_organizer_item_relocation_history', $items_history );
	}

}