<?php

interface Media_File_Organizer_Item {
	/**
	 * @return bool
	 */
	public function relocate( $history_id = null );
	public function roll_back();

	/**
	 * @return Media_File_Organizer_Path
	 */
	public function get_old_path();
	/**
	 * @return Media_File_Organizer_Path
	 */
	public function get_new_path();
}