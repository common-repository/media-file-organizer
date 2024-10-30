<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://TheWebDeveloper.io
 * @since      1.0.0
 *
 * @package    Media_File_Organizer
 * @subpackage Media_File_Organizer/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Media_File_Organizer
 * @subpackage Media_File_Organizer/admin
 * @author     Sherif Mesallam <SherifMesallam@gmail.com>
 */
class Media_File_Organizer_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * options holder .
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $options    holds the plugin options so we load it only one time
	 */
	private $options;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Load plugin options array and return a specific field's value
	 *
	 * @param string $name
	 * @param string $setting
	 * @param bool $allow_empty_string
	 * @param null $default
	 *
	 * @return mixed|null
	 *
	 * @since    1.0.0
	 */
	private function get_option( $name, $setting,  $default = null, $allow_empty_string = true ){

		if( is_null( $this->options ) || ! array_key_exists( $setting, $this->options )){
			$this->options[ $setting ] =  get_option( 'media_file_organizer_'.$setting, [] );
		}

		if( isset( $this->options[ $setting ][ $name ] ) )
		{
			if( ! $allow_empty_string && $this->options[ $setting ][ $name ] == '' )
				return $default ;

			return $this->options[ $setting ][ $name ];
		}

		return $default ;
	}

	/**
     * Returns which roles does the current logged in user have that are among the available roles
     *
	 * @return bool|string
     *
	 * @since    1.0.0
	 */
	private function get_current_user_available_role(){

		$role = false ;

		$current_user = wp_get_current_user();
		if ( !($current_user instanceof WP_User) )
			return false ;
        // Get available roles from options, get user roles, intersect both roles arrays to make sure any exist
		$user_roles = $current_user->roles;
		$accepted_roles = array_keys($this->get_option('roles', 'general', ['administrator' => 'on'], false ));
		$available_roles = array_intersect( $user_roles, $accepted_roles );
        // If any roles exist return the first one ( doesn't matter which one as long as one exists )
		if( count( $available_roles ) > 0 )
			$role = array_shift( $available_roles ) ;

		return $role ;

	}

	/**
     * If current user has permission to use the plugin
     *
	 * @return bool
     *
	 * @since    1.0.0
	 */
	private function has_permissions(){
		return $this->get_current_user_available_role() !== false ;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

	    // Main plugin css
		wp_register_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/media-file-organizer-admin.css', array(), $this->version, 'all' );
		// Context menu css
		wp_register_style( $this->plugin_name.'-contextmenu', plugin_dir_url( __FILE__ ) . 'css/jquery.contextMenu.min.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
	    // Requester class
		wp_register_script( $this->plugin_name.'-requester', plugin_dir_url( __FILE__ ) . 'js/requester.js', array( 'jquery' ), time(), true );
        // Progress class
		wp_register_script( $this->plugin_name.'-progress', plugin_dir_url( __FILE__ ) . 'js/progress.js', array( 'jquery' ), time(), true );
        // Context menu
		wp_register_script( $this->plugin_name.'-contextmenu', plugin_dir_url( __FILE__ ) . 'js/jquery.contextMenu.min.js', array( 'jquery' ), $this->version, true );
		// Panel Class
		wp_register_script( $this->plugin_name.'-panel', plugin_dir_url( __FILE__ ) . 'js/panel.js', array( 'jquery', $this->plugin_name.'-contextmenu' ), time(), true );
        // App Class
		wp_register_script( $this->plugin_name.'-app', plugin_dir_url( __FILE__ ) . 'js/app.js', array( 'jquery', 'jquery-ui-core', 'jquery-ui-widget', $this->plugin_name.'-panel',$this->plugin_name.'-requester', $this->plugin_name.'-progress' ), time(), true );
        // Main JS file to that initializes the app
		wp_register_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/media-file-organizer-admin.js', array( 'jquery' , $this->plugin_name.'-app' ), time(), true );
		// Options page
		wp_register_script( $this->plugin_name.'-options', plugin_dir_url( __FILE__ ) . 'js/options.js', array( 'jquery' ), time(), true );

        // Localize with urls and nonce
		wp_localize_script($this->plugin_name.'-requester', 'ajax_obj', [
			'url' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('media_file_organizer_ajax_request'),
		] );
		wp_localize_script($this->plugin_name.'-options', 'ajax_obj', [
			'url' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('media_file_organizer_options_ajax_request'),
		] );
        // Localize needed options like batch size
		$batch_size = $this->get_option('batch_size','general',50,false );
		wp_localize_script( $this->plugin_name.'-app', 'options',[
		    'batch_size' =>  $batch_size
        ] );

	}

    /**
     * Adds plugin menu links
     *
     * @since 1.0.0
     */
	public function add_menus(){

	    // Make sure any links should be displayed to current user
        // Get available user roles
		$user_role = $this->get_current_user_available_role() ;
		if( $user_role === false )
			return ;
        // Main Plugin screen under the media library menu
		$page_title = __( 'Media File Organizer', 'media-file-organizer' );
		$menu_title = __ ( 'Media File Organizer', 'media-file-organizer' );
		$menu_slug = 'media-file-organizer-relocation';
		add_submenu_page( 'upload.php', $page_title, $menu_title, $user_role, $menu_slug, [ $this, 'relocation_page_ui' ]);

		// Settings screen under the settings menu
        // Only appears to admin
		$page_title = __( 'Media File Organizer Settings', 'media-file-organizer' );
		$menu_title = __ ( 'Media File Organizer', 'media-file-organizer' );
		$menu_slug = 'media-file-organizer-relocation-settings';
		add_submenu_page( 'options-general.php', $page_title, $menu_title, 'manage_options', $menu_slug, [&$this,'render_general_settings_page']);
    }

	/**
	 * Registers Plugin's settings
     *
     * @since 1.0.0
	 */
	public function register_settings()
	{
		// Register general settings option
		register_setting( $this->plugin_name.'_general', 'media_file_organizer_general');

		// register general settings section
		add_settings_section( 'media_file_organizer_general_settings', __( 'Media File Organizer', 'media-file-organizer' ),
			[ &$this, 'general_settings_section_cb' ], $this->plugin_name.'_general' );

		// register batch size
		add_settings_field( 'media_file_organizer_batch_size', __( 'Batch Size', 'media-file-organizer'),
			[ &$this, 'batch_size_cb' ], $this->plugin_name."_general", 'media_file_organizer_general_settings' );

		// register roles
		add_settings_field( 'media_file_organizer_roles', __( 'Allowed Roles', 'media-file-organizer'),
			[ &$this, 'roles_cb' ], $this->plugin_name."_general", 'media_file_organizer_general_settings' );

		// register enable log
		add_settings_field( 'media_file_organizer_logging', __( 'Enable Logging', 'media-file-organizer'),
			[ &$this, 'logging_cb' ], $this->plugin_name."_general", 'media_file_organizer_general_settings' );
		
	}

	/**
	 * Outputs generatl setttings
	 */
	public function general_settings_section_cb(){
        echo '<h3>'.__('General Settings', 'media-file-organizer' ).'</h3>' ;
	}

	/**
	 * Outputs the batch size settings field
     *
     * @since 1.0.0
	 */
	public function batch_size_cb(){
		$batch_size = $this->get_option('batch_size','general', 50, false);
		?>
		<fieldset><legend class="screen-reader-text"><span><?php _e('Batch Size', $this->plugin_name) ?></span></legend>
			<label for="media_file_organizer_general[batch_size]"><input type="text" name="media_file_organizer_general[batch_size]" value="<?php echo $batch_size; ?>" /> <?php _e('Items', 'media-file-organizer' )?></label>
			<p class="description"><?php _e('How many items to process per request when relocating more than one item<', 'media-file-organizer' ) ;?>/p>
		</fieldset>
		<?php
	}

	/**
	 * Outputs the roles checkboxes field
     *
     * @since 1.0.0
	 */
	public function roles_cb(){
		$wp_roles = get_editable_roles();
		$selected_roles = $this->get_option('roles','general', ['administrator' => 'on'], false);
		foreach ( $wp_roles as $role => $details ){
			$current_val = isset( $selected_roles[ $role ] ) ? $role : '' ;
		?>
			<input type="checkbox" name="media_file_organizer_general[roles][<?php echo  $role ?>]" <?php checked($role, $current_val) ?> > <?php echo $details[ 'name' ] ?><br>
		<?php
		}
		echo '<p class="description">'.__('Select the roles allowed to use media file organizer.', $this->plugin_name).'</p>';

	}

	/**
	 * Outputs enable logging field
	 *
	 * @since 1.0.0
	 */
	public function logging_cb(){
		$logging_enabled  = $this->get_option('logging','general', true );
?>
            <input value="1" type="checkbox" name="media_file_organizer_general[logging]" <?php checked( $logging_enabled )?>>
            <a target="_blank" href="<?php echo MEDIA_FILE_ORGANIZER_PLUGIN_URL.'debug.log' ?>"> <?php _e('Download log', 'media-file-organizer' )?></a> |
            <a href="#" class="clear_log"> <?php _e('Clear log', 'media-file-organizer' )?></a>
<?php
		echo '<p class="description">'.__('Check to enable logging relocation information to file',  'media-file-organizer' ).'</p>';
		echo '<p class="description">'.__('By time the file could grow very big so it is advised to clear it every once in a while',  'media-file-organizer' ).'</p>';


	}

	/**
	 * Outputs the contents of the general settings page
	 *
	 * @since    1.0.0
	 */
	public function render_general_settings_page()
	{
	    // Enqueue the options js file
	    wp_enqueue_script( $this->plugin_name.'-options' );
		?>
		<div class='wrap'>
			<form action="options.php" method="post" class="bne-form">
				<?php
				// output security fields for the registered setting
				settings_fields( $this->plugin_name.'_general' );
				// output setting sections and their fields
				do_settings_sections( $this->plugin_name."_general" );
				// output save settings button
				submit_button( __( 'Save Settings', 'media-file-organizer' ) );
				?>
			</form>
		</div>
		<?php
	}

	/**
     * Displays the main plugin page
     *
	 * @throws Exception
     *
     * @since 1.0.0
	 */
    public function relocation_page_ui(){

        // Enqueue needed scripts & styles
        wp_enqueue_style( $this->plugin_name);
        wp_enqueue_style( $this->plugin_name.'-contextmenu' );
	    wp_enqueue_script($this->plugin_name.'-requester');
	    wp_enqueue_script( $this->plugin_name.'-contextmenu');
	    wp_enqueue_script( $this->plugin_name.'-panel');
	    wp_enqueue_script( $this->plugin_name.'-app');
	    wp_enqueue_script( $this->plugin_name);

	    // Get a list of files in the root path
		$list = Media_File_Organizer_Panel_Provider::detail( new Media_File_Organizer_Path_Provider('' ) );

        // Render both left and right panels with the same list
		$right_panel = Media_File_Organizer_View::render('panel', ['id' => 'right_panel', 'list'=> $list ] );
		$left_panel = Media_File_Organizer_View::render('panel', ['id' => 'left_panel', 'list'=> $list ] );
        // Show the main view
		Media_File_Organizer_View::show('main', compact('right_panel', 'left_panel', 'list') );
    }

	/**
	 * Handles incoming AJAX requests, forwards each one to its responsible function and returns the JSON response
     *
     * @since 1.0.0
	 */
    public function handle_ajax_requests(){

        // Check nonce and permission
	    if( check_ajax_referer( 'media_file_organizer_ajax_request', 'nonce', false ) === false || $this->has_permissions() === false )
	    	wp_send_json([
	    		'status' => false,
			    'data' => __('Invalid request, please refresh the page', 'media-file-organizer' )
		    ]);

	    // Increase time limit for big files and long DB queries
	    set_time_limit( 3600 ); #TODO can't count on this

	    // Get the request details and decide the corresponding handler function
		$operation = $_POST[ 'operation' ] ;
		$handler = 'handle_'.$operation.'_request' ;

	    // For benchmarking
	    $memory = memory_get_peak_usage()  ;
	    $start_time = microtime( true );
	    Media_File_Organizer_Helper::debug( '=================');
	    Media_File_Organizer_Helper::debug( [ 'operation' => $operation, 'memory' => $memory, 'start' => $start_time ] );
		// Try handling the request
		try{
			$response = $this->$handler();
		}
		catch( Exception $e ){
			$response = [
				'status' => false,
				'data' => $e->getMessage()
			];

			// Log what happened
            Media_File_Organizer_Helper::debug( [ 'operation_error' => $e->getMessage() ] );
		}

		// Benchmark the operation
		$memory = memory_get_peak_usage() - $memory ;
		$end_time = microtime( true ) ;
		Media_File_Organizer_Helper::debug( [ 'operation' => $operation, 'memory_used' => $memory, 'time' => $end_time - $start_time ] );
	    Media_File_Organizer_Helper::debug( '=================');
		// Send back the response
		wp_send_json( $response ) ;
    }

	/**
     * Handles a list dir
     * Lists the contents of a dir and renders its markup
     *
	 * @return array
	 * @throws Exception
     *
     * @since 1.0.0
	 */
    private function handle_list_request(){
        // Get & clean path from request
		$clean_dir = Media_File_Organizer_Helper::get_clean_path( 'dir' );
		// list contents
		$path = new Media_File_Organizer_Path_Provider( $clean_dir ) ;
		$list = Media_File_Organizer_Panel_Provider::detail( $path ) ;
		// Build markup
		$markup = Media_File_Organizer_View::render( 'list', [ 'list' => $list ] ) ;

		return [
			'data' => $markup,
			'status' => true,
		];
    }

	/**
	 * Handles a rename request
	 * Renames an item and returns the results
	 *
	 * @return array
	 * @throws Exception
	 *
	 * @since 1.0.0
	 */
    private function handle_rename_request(){
        // Get old and new item paths
		$old = Media_File_Organizer_Helper::get_clean_path( 'old' );
		$new = Media_File_Organizer_Helper::get_clean_path( 'new' );

	    $old_path  = new Media_File_Organizer_Path_Provider( $old ) ;
	    $new_path  = new Media_File_Organizer_Path_Provider( $new ) ;

	    // Create an item and relocate it
	    $item = Media_File_Organizer_Item_Factory::create( $old_path, $new_path );
	    if( $this->relocate_item( $item ) === true )	{
	        // On successful relocation tell the log
	        return [
	            'data' => $item->get_new_path()->get_system_path(),
			    'status' => true
		    ];
	    } else {
	        // On failure, explain the reasons
	        return [
	            'data' => $item->explain( true ),
			    'status' => false
		    ];
	    }

    }

	/**
     * Handles a move request
     * Moves a number of items and returns data about successful and failed items
     *
	 * @return array
	 * @throws Exception
     *
     * @since 1.0.0
	 */
    private function handle_move_request(){
	    // Items to move
		$items = Media_File_Organizer_Helper::get_post_data( 'items' );
		// Destination
		$destination = Media_File_Organizer_Helper::get_post_data( 'to' );
		$destination_path = new Media_File_Organizer_Path_Provider( $destination ) ;
        // To save results
		$failed_items = [];
		$successful_items = [] ;
        // Loop through items and try relocating each one on its own
        // A failed item should not stop the whole batch
		foreach ( $items as $item_string ){
			try{
				$old_path = new Media_File_Organizer_Path_Provider( $item_string );
				/**
				 * Media_File_Organizer_Item new path is the destination path + old path last part
				 * Media_File_Organizer_Item 2019/01 moving to 2019/test becomes 2019/test/01
				 */
				$new_path = $destination_path->append( $old_path->get_base() );
				// Create an item and relocate
				$item = Media_File_Organizer_Item_Factory::create( $old_path, $new_path );
				if( $this->relocate_item( $item ) ){
				    // On success return the new path ( just in case it is needed for UI, it is not currently )
					$successful_items[] = $item->get_new_path()->get_system_path() ;
                }
				else{
				    // On failure return the failed path and the reason for UI
					$failed_items[] = [ 'Media_File_Organizer_Path' => $item->get_old_path()->get_system_path(), 'reason'=> $item->explain( true ) ];
                }
			}
			catch ( Exception $e ){
			    // If failed for an exception ( like a failure in system file or db ) return reason
                #TODO This smells like bad design
				$failed_items[] = [ 'Media_File_Organizer_Path' => $item->get_old_path()->get_system_path(), 'reason'=> $e->getMessage() ];
			}
		}

		//  Finally return all the results
        #TODO status is always true ? at least it should be false in case all failed
		return [
			'status' => true,
			'data' => [
				'failed' => $failed_items,
				'successful' => $successful_items
			]
		];
    }

	/**
	 * Handles creating a new dir request
	 *
	 * @return array
	 * @throws Exception
	 *
	 * @since 1.0.0
	 */
    public function handle_new_dir_request(){
        // Get the new dir
		$new_dir = Media_File_Organizer_Helper::get_clean_path( 'new_dir', true );
        // it should be new
		$path = new Media_File_Organizer_Path_Provider( $new_dir, true );

		#TODO This validation  shouldn't be implemented here
	    #TODO Can't we encapsulate returning responses somewhere and call it ?

		if( ! $path->is_valid_file_name() )
			return [
				'data' => __('Invalid dir name : ', 'media-file-organizer' ).$new_dir ,
				'status' => false
			];

		if( $path->exists() )
			return [
				'data' => $path->get_system_path(). __(' already exists', 'media-file-organizer') ,
				'status' => false
			];

		$result = $path->create()  ; #TODO : Media_File_Organizer_Path should not be responsible for creating anything
		if( $result === true ){
			return [
				'data' => 'created', #TODO : can we return more meaningful data ?
				'status' => true,
			];
		}
		else{
			return [
				'data' => __('Unable to create directory ', 'media-file-organizer').$path->get_system_path(). ' : ' . $result,
				'status' => false
			];
		}

    }

	/**
     * Tries to relocate an item and rolls it back on failure
     * Adds an item to relocation history and removes it on success
     *
	 * @param Media_File_Organizer_Item $item
	 *
	 * @return bool
	 * @throws Exception
	 */
    private function relocate_item(Media_File_Organizer_Item $item ){
        // Adds the item to relocation history ( stored in options )
        $history_id = $this->add_item_to_history( $item ) ;
        if( false === $history_id )
            throw new Exception(__('Can not add item to process history', 'media-file-organizer' ) );
        // Tries to relocate
	    if( $item->relocate( $history_id ) ){
	    	// Media_File_Organizer_Item was relocated successfully
	        $item->tell( true );
            // Remove from relocation history
	    	$this->remove_item_from_history( $history_id );
		    return true ;
	    }
	    else{
	        // Something wrong happened , try to rollback any changes made to the filesystem or DB
		    if( $item->roll_back() ){
		        // Media_File_Organizer_Item rolled back successfully, remove from history
		        $this->remove_item_from_history( $history_id );
				return false ;
		    }
		    else{
		        // Media_File_Organizer_Item failed and changes can not be rolled back
		    	Media_File_Organizer_Helper::debug(' !! CAN NOT ROLL BACK ITEM !!');
		    	Media_File_Organizer_Helper::debug( $item->explain( true ) );
			    throw new Exception(__('An unexpected error occurred while relocating item and rolling back files failed, please check log file for last operation to make sure file integrity is intact', 'media-file-organizer') );
		    }
	    }
    }

	/**
     * Adds an item to relocating history
     *
     * In case an item made any changes to the file system or DB and couldn't complete the whole
     * relocation process successfully for example for a memory limit error, then we have all the changes
     * made so we can at least reverse them manually
     *
	 * @param Media_File_Organizer_Item $item
	 *
	 * @return string
	 */
    private function add_item_to_history(Media_File_Organizer_Item $item ){
	    $items_history =  get_option('media_file_organizer_item_relocation_history',[] );
	    $id = md5( microtime() ) ;
	    #TODO : ALdo add DB or is mysql rolling back automatically enough ?
	    $items_history[ $id ] = [
		    'old' => $item->get_old_path()->get_system_path(),
		    'new' => $item->get_new_path()->get_system_path(),
            'file_movements' => []
	    ];

	    if( update_option('media_file_organizer_item_relocation_history', $items_history ) )
	        return $id ;
    }

	/**
     * Removes an item from relocating history
     *
	 * @param $id
     *
     * @since 1.0.0
	 */
    private function remove_item_from_history( $id ){
	    $items_history =  get_option('media_file_organizer_item_relocation_history',[] );
	    Media_File_Organizer_Helper::debug( $items_history ) ;
	    if( isset( $items_history[ $id ] ) ){
		    unset( $items_history[ $id ] ) ;
		    update_option( 'media_file_organizer_item_relocation_history', $items_history ) ;
        }
    }

	/**
	 * Handles an ajax request from the plugins options page
     * Currently there is only one request which is for clearing the log file
     *
     * @since 1.0.0
	 */
    public function handle_options_ajax_requests(){
        check_ajax_referer('media_file_organizer_options_ajax_request', 'nonce');
        $log_file_path = MEDIA_FILE_ORGANIZER_PLUGIN_DIR.'debug.log';
        if( file_exists($log_file_path ) && unlink( $log_file_path ) )
            wp_send_json_success();

            wp_send_json_error();
    }
}
