<?php

if ( ! class_exists( "All_In_Menu_Settings" ) ):


class All_In_Menu_Settings {
		
	protected $table;
	
/*	*
	* boolean $deprecated_php
	*
	**/	
	protected $deprecated_php;
	
	public static function init(){
		
		new self;
	}
/****
	*	CONSTRUCTOR
	*	In this function we firstly create the table to store the menu data
	*	and then we create the administration page
	*/
	public function __construct() {
		
		$this->table = 'md_metas';// '". $wpdb->prefix ."allinmenu';
		
		// Set the serialize variable;
		$this->deprecated_php = version_compare("5.4.0",  phpversion()) >= 0 ? true : false ; 

		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'wp_ajax_allin_save_settings', array( $this, 'save_settings') );
		add_action( 'wp_ajax_allin_search_posts', array( $this, 'search_posts' ) );
		add_action( 'wp_ajax_allin_save_options', array( $this, 'save_options' ) );
		add_action( 'wp_ajax_allin_flush_cache', array( $this, 'flush_cache' ) );
		add_action( 'wp_ajax_md_add_menu', array( $this, 'add_menu') );
		add_action( 'wp_ajax_md_get_menus', array( $this, 'get_menus') );
		add_action( 'wp_ajax_md_add_component', array( $this, 'add_component') );
		add_action( 'wp_ajax_md_delete_component', array( $this, 'delete_component') );
		add_action( 'wp_ajax_md_update_component', array( $this, 'update_component') );
		add_action( 'wp_ajax_md_get_components', array( $this, 'get_components') );
		add_action( 'wp_ajax_md_update_menu', array( $this, 'update_menu') );
		add_action( 'wp_ajax_md_get_items', array( $this, 'get_items') );

	}
	//RENAME TABLE `md_metas` TO `$wpdb->prefix .allinmenu`;
	
/****
	*	ADMINISTRATION PAGE
	*	This page contains all the function to create and edit menus and tabs
	*/
	public function admin_menu () {
		
		add_menu_page( 'All-In-Menu Settings', 'All-In-Menu Settings', 'manage_options', 'all-in-settings', array( $this, 'all_in_settings'), plugins_url( 'images/page_icon.jpg', __FILE__ ) );
	}
	

/****
	*	START RENDER SETTINGS HTML
	*/
	public function all_in_settings () {
		
		//	Load the stored settings
		$html = '<div id="md_settings_tabs">';

		//	The main menu of the setting page		
		$this->settings_side_menu( $html );
		
		//	General Settings for Menu Dashboard
		$this->general_settings( $html );
		
		//	Export the tab for menus management
		$this->manage_menus( $html );
		
		//	Export the tab for tabs (components) management	
		$this->manage_components( $html );
		
		//	The popup editor
		$this->edit_popup( $html );

		//	Add the html indicators ( Loader + Message box );
		$this->html_indicators( $html );
		
		//	The script templates used in the administration page
		$html .= $this->script_templates();
		
		$html .= '</div>';
		//	Echos the HTML from the previous functionality.
		//	This is the UNIQUE echo in the page
		echo $html;
	}
	
/****
	*	MAIN MENU OF THE SETTING PAGE
	*	Contains the main menu of the settings page shown as left sidebar
	*	asychronous altering the interface between the following states:
	*	GENERAL		: Settings about the plugin
	*	MENUS		: Menus management (create, edit, delete)
	*	TABS		: The tabs are the menu components. At least one tab must be created
	*				  in order to connect it one already created menu.
	*/	
	protected function settings_side_menu( &$html ){
		
		$html .= '
		<aside id="md_settings_side_menu"><div class="md_inside">
			<ul>
				<li class="md_settings_li"><a href="#general_settings">General</a></li>
				<li class="md_settings_li"><a href="#manage_menus">Menus</a></li>
				<li class="md_settings_li"><a href="#manage_components">Tabs</a></li>
			  </ul>';
		
		$html .= '<p class="settings-subtitle">Your php version is '. phpversion() .'</p>';

		$html .= '</div></aside>';
	}
	
/****
	*	GENERAL SETTING
	*	This function contains the html for the general settings. These are:
	*	
	*/
	protected function general_settings( &$html ){
		
		//	Get already submitted options
		$settings	= get_option('allin_settings');
		
		$html .= '<section id="general_settings" class="md_settings_tab">
		
			<h2 class="settings-title">General Settings</h2>
			<p class="settings-subtitle">Settings for all menus</p>';
		
		$html .= '<div class="md_settings_inside">';
		
		//	Enable cache checkbox		
		$enabled	= $settings['enable'] == 'true' ? 'checked="checked"' : '';
		$html .= '<div class="all_in_settings_section md_menu_single_field">
				<input type="checkbox" name="all_in_settings[]" id="all_in_settings_enable"  data-prop="enable" '.$enabled.'/>
				<label for="all_in_settings_enable">Enable Cache</label>
			</div>';
		
		//	Expiry cache select option		
		$expiry		= $settings['expiry'];
		$expiry_values	= array(
			'3600'		=> '1 hour',
			'7200'		=> '2 hours',
			'21600'		=> '6 hours',
			'86400'		=> '1 day',
			'172800'	=> '2 days',
			'604800'	=> '1 week',
			'315360000'	=> 'never (until flush cache)',
		);
		$html .= '<div class="all_in_settings_section md_menu_single_field">
			<label for="all_in_settings_expiry">Cache memory expiration after:</label>
			<select name="all_in_settings[]" id="all_in_settings_expiry" data-prop="expiry">';
		foreach ( $expiry_values as $key => $value ){
			$selected = ( $expiry == $key ) ? 'selected="selected"' : '';
			$html .= '<option value="'.$key.'" '.$selected.'>'.$value.'</option>';
		}
		$html .= '</select>
			</div>';
		
		$html .= '<div class="all_in_settings_section">
			<button id="all_in_settings_submit" class="md_button_secondary md_button_light">Save Settings</button>
			<button id="all_in_settings_flush" class="md_button_secondary md_button_dark">Flush Cache</button>
			</div>';

		$html .= '</div>';

		$html .= '</section>';
	}
	
/****
	*	MENUS MANAGEMENT
	*	This function has three main parts.
	*	-	Add new menu
	*	- 	Available tabs (components)
	*	- 	Menus List
	*/
	protected function manage_menus( &$html ){
		
		
		$html .= '<section id="manage_menus" class="md_settings_tab">';
		
		$html .= '<h2 class="settings-title">Menus</h2>
			<p class="settings-subtitle">Create and manipulate menus</p>';
			
		$html .= '<div class="md_settings_inside">';
		
		$html .= '<section id="md_menus_controls">
				<input type="text" name="md_menu_name" placeholder="enter name..." />
				<button id="md_menu_add" class="md_button_main md_button_base">
					&#10010; Add New Menu</button>
			</section>
			
			<section id="md_menus_available_comp">
			</section>
			
			<section id="md_menus_list" class="widefat">
			<section>';
		
		$html .= '</div>';
		
		$html .= '</section>';
	}
	
/****
	*	TABS (COMPONENTS) MANAGEMENT
	*	The tabs (components) are parts of a menu. One menu can have one or more tabs
	*	and a tab can be part of more than one menu. One menu is a unordered list (<uk>)
	*	and a tab is one list item (<li>). There are several tab types that the user can
	*	create and this can be done in the following interface.
	*/
	protected function manage_components( &$html ){

		$html .= '<section id="manage_components" class="md_settings_tab">
			<h2 class="settings-title">Tabs (Components)</h2>
			<p class="settings-subtitle">Create Tabs for your menus</p>';
		
		$html .= '<div class="md_settings_inside">';
		
		$html .= '<div id="md_component_buttons">
		<select id="md_component_select" name="md_component_select">
			<option value="link">Link</option>
			<option value="category">Category</option>
			<option value="post_tag">Tag</option>
			<option value="post">Post</option>
			<option value="social">Social Link</option>
			<option value="youtube">Youtube</option>
			<option value="custom">Custom</option>
		</select>
		<button id="md_component_add" class="md_button_main md_button_base">&#10010; Add New Tab</button>
		</div>';
		
		//	The container hosting each component submission form
		$html .= '<div id="md_component_form"></div>';
		
		//	Create the placeholders
		$html .= '<div id="md_component_custom">';
		
		$html .= $this->placeholder_toolbar();
		
		$html .= '<div class="md_placeholder_main">
			</div>
			
			<div id="md_custom_bin"><div class="dropzone"></div></div>
		</div>';
		
		//	Show the already submitted components
		$html .= '<div id="md_components_list"></div>';
		
		$html .= '</div>'; // END OF div.md_settings_inside
		
		$html .= '</section>';
	}
	
	
/****
	*	POPUP EDITOR HTML
	*	This function returns the html for the editor popup editor
	*	Editor popup is an empty div with a submit button filled with
	*	the appropriate form template (and values) for editing
	*/
	protected function edit_popup( &$html ){
		
		$html .= '<section id="md_popup_editor" class="closed">
			<header class="dark_header">Edit values<span class="md_popup_close">&#10005;</span></header>
			<div id="md_popup_inputs" data-tpl="">
			</div>
			<div id="md_popup_controls">
			<button id="md_popup_submit" class="md_button_secondary md_button_light">Submit</button>
			</div>
		</section>';
	}
	
/****
	*	HTML INDICATORS
	*	The function ontains and export two empty divs.
	*	The first one is the md_loader div which is an full page overlay visible
	*	when ajax requests are in progress and the second is the message indicator is
	*	a position absolute div, visible when a message to the user must be shown.
	*/
	protected function html_indicators( &$html ){
		
		$html .= '<div id="md_loader"></div>
			<div id="md_message_indicator"></div>';
	}
	
	
/****
	*	RETURNS THE TERMS OF A TAXONOMY
	*	This function returns an array from the get_terms() wordpress function
	*/
	protected function get_taxonomy( $type ){

		$taxonomies = array( 
			$type,
		);
		
		$args = array(
			'orderby'           => 'name', 
			'order'             => 'ASC',
			'hide_empty'        => false, 
			'exclude'           => array(), 
			'exclude_tree'      => array(), 
			'include'           => array(),
			'number'            => '', 
			'fields'            => 'all', 
			'slug'              => '',
			'parent'            => 0,
			'hierarchical'      => true, 
			'child_of'          => '',//$atts['parent'], 
			'get'               => '', 
			'name__like'        => '',
			'description__like' => '',
			'pad_counts'        => false, 
			'offset'            => '', 
			'search'            => '', 
			'cache_domain'      => 'core'
		);		
		$items = get_terms( $taxonomies, $args );

		return $items;
	}
	
/****
	*	THE TOOLBAR IN THE CUSTOM MENU TAB SECTION
	*	This function contains the html with the tools available in the creation
	*	of the custom menu tab (Tabs Section).
	*/
	protected function placeholder_toolbar(){
		
		$html = '<section class="md_placeholder_toolbar">
			<section id="md_placeholder_tools" class="push_right">
				<aside class="md_tool md_button_secondary md_button_cararra" data-space="2" data-type="header" data-tpl="md_tool_header">
					<span class="header">Header</span>
					<input type="hidden" class="md_tool_value" />
					<div class="md_tool_content">
					</div>
				</aside>
				<aside class="md_tool md_button_secondary md_button_cararra"  data-space="3" data-type="link" data-tpl="md_tool_link">
					<span class="header">Link</span>
					<input type="hidden" class="md_tool_value" />
					<div class="md_tool_content">
					</div>
				</aside>
				<aside class="md_tool md_button_secondary md_button_cararra"  data-space="3" data-type="paragraph" data-tpl="md_tool_paragraph">
					<span class="header">Paragraph</span>
					<input type="hidden" class="md_tool_value" />
					<div class="md_tool_content">
					</div>
				</aside>
				<aside class="md_tool md_button_secondary md_button_cararra"  data-space="8" data-type="image" data-tpl="md_tool_image">
					<span class="header">Image</span>
					<input type="hidden" class="md_tool_value" />
					<div class="md_tool_content">
					</div>
				</aside>
				<aside class="md_tool md_button_secondary md_button_cararra"  data-space="10" data-type="map" data-tpl="md_tool_map">
					<span class="header">Map</span>
					<input type="hidden" class="md_tool_value" />
					<div class="md_tool_content">
					</div>
				</aside>
				<aside class="md_tool md_button_secondary md_button_cararra"  data-space="10" data-type="youtube" data-tpl="md_tool_youtube">
					<span class="header">Youtube</span>
					<input type="hidden" class="md_tool_value" />
					<div class="md_tool_content">
					</div>
				</aside>
				<aside class="md_tool md_button_secondary md_button_cararra"  data-space="5" data-type="html" data-tpl="md_tool_html">
					<span class="header">HTML</span>
					<input type="hidden" class="md_tool_value" />
					<div class="md_tool_content">
					</div>
				</aside>
			</section>
			<section id="md_placeholder_ctrls" >
				<input type="text" class="md_input_secondary" name="md_custom_tab_name" data-need="required" />
				<button class="add-component md_button_secondary md_button_light" data-type="custom"></button>
				<button name="add_placeholder" class="add_placeholder md_button_secondary md_button_dark">Add Column</button>
			</section>
			';
		$html .= '</section>';
		
		return $html;
	}
	

/****
	*	CREATE A SELECT HTML TAG
	*	The function accepts three parameters and creates a select html tag:
	*	The first one is an array containing the total <option> tag that the final
	*	<select> will have available. The second is the name attribute of the 
	*	select tag and the third (optional) is an array with the data-values attributes
	*	of the select tag. The following parameters for example:
	*	$terms	= array( 'example' );
	*	$data	= array(
	*		'need'	=> 'required',
	*		'id'	=> 27,
	*	);
	*	create_term_select_tag( $terms, 'the_selected_name', $data )
	*	will create the following select tag:
	*	<select name="the_selected_name" data-need="required" data-id="27">
	*		<option value="example">example</option>
	*	</select>
	*/
	protected function create_term_select_tag( $terms, $name, $data = array() ){
		
		foreach ( $data as $k => &$d ){
			$d = 'data-'.$k.'="'.$d.'"';
		}
		
		$html = '<select name="'.$name.'[]" '.implode(' ', $data ).'>';
			$html .= '<option value="" selected="selected">-- No select --</option>';
		foreach ( $terms as $term ){
			$html .= '<option value="'.$term->term_id.'">'.$term->name.'</option>';
		}		
		$html .= '</select><br />';
		
		return $html;

	}
	

/****
	*	CREATE AN INPUT HTML TAG
	*	The function accepts three parameters and creates an input html tag:
	*	The first one is the type of input produced input tag (e.x. hidden). The second
	*	is the name attribute of the tag and the third the data-values attributes.
	*	The following parameters for example:
	*	$type	= 'text';
	*	$data	= array(
	*		'need'	=> 'required',
	*		'id'	=> 27,
	*	);
	*	create_input_tag( $type, 'the_selected_name', $data )
	*	will create the following input tag:
	*	<input type="text" name="the_selected_name" data-need="required" data-id="27"/>
	*/
	protected function create_input_tag( $type, $name, $data = array(), $label = '', $class = '', $placeholder = '' ){
		
		//	Looping the $data array converting values to data-key="value" format
		foreach ( $data as $k => &$d ){
			$d = 'data-'.$k.'="'.$d.'"';
		}
		//	Create a unique id for the input
		$id = $type .'_'. $name .'_'. rand( 1, 99999999);
		//	Generate the html with the input tag
		$html = '<input id="'. $id .'" type="'.$type.'" name="'.$name.'[]" '.implode(' ', $data ).' class="'.$class.'" placeholder="'.$placeholder.'"/>';
		//	If label is set then add the <label> tag
		if ( $label ){
			$html .= '<label for="'.$id.'">'.$label.'</label>';
		}
		$html .= '<br />';
		
		return $html;
	}
	
	protected function create_textarea_tag( $name, $data = array() ){
		
		//	Looping the $data array converting values to data-key="value" format
		foreach ( $data as $k => &$d ){
			$d = 'data-'.$k.'="'.$d.'"';
		} 
		//	Create a unique id for the input
		$id = $name .'_'. rand( 1, 99999999);

		$html = '<textarea id="'.$id.'" name="'.$name.'" '.implode(' ', $data ).' rows="4" cols="50"></textarea>';
		
		$html .= '<br />';

		return $html;
	}
	

/****	-------------------------------------------------------------
		AJAX RESPONSES
		Every ajax response returns with wp_send_json($response) all the
		asynchonous data contained in the response object. The response->error
		is always initializing with the value 0. If any error occurs the
		$response->error value becomes 1 and a $response->msg is set with 
		the appropriate value so we can manage the javascript more easily
		-------------------------------------------------------------*/
	public function save_settings(){

		$response = new stdClass();
		$response->error = 0;

		//	Collecting $_POST data
		if ( isset( $_POST ) && is_array( $_POST )){
			foreach ( $_POST as $key => $value ){
				$response->{$key} = $value;
			}
		}
		
		update_option( 'allin_settings', $response->settings );
		
		
		wp_send_json($response);
	}
	
/****
	*	SEARCH FOR POST BY STRING
	*	The function collects the search string from $.ajax data object
	*	and makes a WP_Query with s=[search_string] parameter
	*/
	public function search_posts(){
		
		$response = new stdClass();
		$response->error = 0;
		
		//	Collecting $_GET data
		if ( isset( $_GET ) && is_array( $_GET )){
			foreach ( $_GET as $key => $value ){
				$response->{$key} = $value;
			}
		}
		//	Making the database search
		$posts	= new WP_Query( 's='. $response->s );
		
		//	Check if no posts returned and create the appropriate message
		if ( $posts->post_count == 0 ){
			$response->error++; // Increment the error
			$response->msg = 'No posts returned'; // Set response message
			wp_send_json($response);
		}
		else{
			$response->posts = $posts->posts;
			wp_send_json($response);
		}
	}
	
	public function save_options(){
		
		$response = new stdClass();
		$response->error = 0;
		
		//	Collecting $_POST data
		if ( isset( $_POST ) && is_array( $_POST )){
			foreach ( $_POST as $key => $value ){
				$response->{$key} = $value;
			}
		}
		
		if ( ! update_option( 'menudash_values', $response->values ) ){
			$response->error++; // Increment the error
			$response->msg = 'No posts returned'; // Set response message
		}
		wp_send_json($response);
	}
	
	public function add_menu(){
		
		global $wpdb;
		
		$response = new stdClass();
		$response->error = 0;
		
		if ( isset( $_POST ) && is_array( $_POST )){
			foreach ( $_POST as $key => $value ){
				
				$response->{$key} = $value;
			}
		}
		
		//	The default empty values json is the following
		$jsonValues = '{"left":[],"center":[],"right":[],"color":"light","sticky":"","max_width":""}';
		
		$query = "INSERT INTO ". $this->table ." ( md_ref, md_name, md_values, md_type ) 
				VALUES ( '0', '".$response->name."', '".$jsonValues."', '0')";
		
		$response->result = $wpdb->query( $query );
		
		if ( $response->result == 0 ){
			$response->error++;
		}
		
		wp_send_json($response);
	}

	public function update_menu(){
		
		global $wpdb;
		
		$response = new stdClass();
		$response->error = 0;
		
		if ( isset( $_POST ) && is_array( $_POST )){
			foreach ( $_POST as $key => $value ){
				
				$response->{$key} = $value;
			}
		}
		
		$query = "UPDATE ". $this->table ." 
				SET md_name = '".strip_tags($response->name)."',
					md_values = '".$response->values."'
				WHERE md_id = ".$response->menu_id;
		
		$response->result = $wpdb->query( $query );
		
		wp_send_json($response);
	}

	public function flush_cache(){

		$response = new stdClass();
		$response->error = 0;

		if ( isset( $_POST ) && is_array( $_POST )){
			foreach ( $_POST as $key => $value ){
				
				$response->{$key} = $value;
			}
		}

		$upload_dir = wp_upload_dir();
				
		$response->files = glob( $upload_dir['basedir'].'/allincache/*' ); // get all file names
		
		foreach($response->files as $file){ // iterate files
			if(is_file($file)){	unlink($file); }// delete file
		}
		
		wp_send_json($response);
	}
	
	
	public function get_menus(){
		
		global $wpdb;
		
		$response = new stdClass();
		$response->error = 0;
		
		if ( isset( $_GET ) && is_array( $_GET )){
			foreach ( $_GET as $key => $value ){
				
				$response->{$key} = $value;
			}
		}
		
		$query = "SELECT * FROM ". $this->table ." WHERE md_type = '0' ORDER BY md_id DESC";
		
		$response->items = $wpdb->get_results( $query );
		
		wp_send_json($response);
	}
	
	public function serialize_menu( &$value ){
		
		global $wpdb;
		
		$sections = json_decode( $value->md_values );
		
		$align = array( 'left', 'center', 'right' );
		
		foreach ( $align as $a ){
			
			foreach ( $sections->{$a} as $menu ){
				
				$query = "SELECT * FROM ". $this->table ." WHERE md_id = '".$menu."'";
				
				$results = $wpdb->get_results( $query );
				
				$results = array_shift( $results );
				
				var_dump( $results );
			}
			
			//$value->md_values = 
		}
				
		
	}

/****	-------------------------------------------------------------
		AJAX response for save the menu tabs (components)
		-------------------------------------------------------------*/
	public function add_component(){
		
		global $wpdb;
		
		$response = new stdClass();
		$response->error = 0;
		
		if ( isset( $_POST ) && is_array( $_POST )){
			foreach ( $_POST as $key => $value ){
				
				$response->{$key} = $value;
			}
		}
		
		//	Checking the PHP version in order to use the JSON_UNESCAPED_UNICODE
		//	Must be greater than 5.4
		if ( version_compare(PHP_VERSION, '5.4') >= 0 ){
			$values = json_encode( $response->values, JSON_UNESCAPED_UNICODE );
		}
		else{
			$values = json_encode( $response->values );
		}
	
		//	Ready to submit the new values to the db
		//	Check first if we must update or insert the row		
		if ( empty( $response->menu_id ) ){
			$query = "INSERT INTO ". $this->table ." ( md_ref, md_name, md_values, md_type ) 
				VALUES ( '0', '".$response->name."', '".$values."', '1')";
		}
		else{
			$query = "UPDATE ". $this->table ." SET 
				md_name = '".$response->name."', 
				md_values = '".$values."'  
				WHERE md_id = '".$response->menu_id."'";
		}
		
		$response->result = $wpdb->query( $query );
		
		wp_send_json($response);
	}

	public function delete_component(){
		
		global $wpdb;
		
		$response = new stdClass();
		$response->error = 0;
		
		if ( isset( $_POST ) && is_array( $_POST )){
			foreach ( $_POST as $key => $value ){
				
				$response->{$key} = $value;
			}
		}
		
		$query = "DELETE FROM ". $this->table ." 
				WHERE md_id = ".$response->menu_id;
		
		$response->result = $wpdb->query( $query );
		
		if ( $response->result == 0 ){
			$response->error++;
		}
		
		wp_send_json($response);
	}


	public function update_component(){
		
		global $wpdb;
		
		$response = new stdClass();
		$response->error = 0;
		
		if ( isset( $_POST ) && is_array( $_POST )){
			foreach ( $_POST as $key => $value ){
				
				$response->{$key} = $value;
			}
		}
		
		$query = "UPDATE ". $this->table ." 
				SET md_values = '".$response->values."' 
				WHERE md_id = ".$response->menu_id;
		
		$response->result = $wpdb->query( $query );
		
		/*if ( $response->result == 0 ){
			$response->error++;
		}*/
		
		wp_send_json($response);
	}

	public function get_components(){
		
		global $wpdb;
		
		$response = new stdClass();
		$response->error = 0;
		
		if ( isset( $_GET ) && is_array( $_GET )){
			foreach ( $_GET as $key => $value ){
				
				$response->{$key} = $value;
			}
		}
		
		$query = "SELECT * FROM ". $this->table ." WHERE md_type = 1 ORDER BY md_id DESC";
		
		$response->items = $wpdb->get_results( $query );
		
		foreach ( $response->items as &$value ){
			if ( !empty($value->md_values)){
				$value->md_values = json_decode( $value->md_values );
			}
		}
		
		wp_send_json($response);
	}
	
/****
	*	GET ITEMS ( MENUS & TABS )
	*	The function returns the submitted menus and tabs in the ajax request
	*	It is used when the page first loads but also when a new menu/tab is created
	*	or deleted
	*/
	public function get_items(){
		
		global $wpdb;
		
		$response = new stdClass();
		$response->error = 0;
		
		//	Collecting $_GET data
		if ( isset( $_GET ) && is_array( $_GET )){
			foreach ( $_GET as $key => $value ){
				
				$response->{$key} = $value;
			}
		}
		
		//	The select query is really simple without any filter
		$query = "SELECT * FROM ". $this->table ." ORDER BY md_id DESC";
		//	Executing the query
		$results = $wpdb->get_results( $query );
		//	Initializing the arrays.
		//	$response->menus 	for menus
		//	$response->tabs 	for menu tabs
		$response->tabs = $response->menus = array();
		//	Start looping the query results
		foreach ( $results as $result ){
			
			$result->md_values = json_decode( $result->md_values );
			
			if ( $result->md_type == 0 ){
				array_push( $response->menus, $result );
			}
			else{
				array_push( $response->tabs, $result );
			}
		}
		
		wp_send_json($response);
	}
	
	
	//	Include all the script templates needed for the administration page
	public function script_templates(){
		
		//	Get categories and tags to use them inside components creation
		$Categories = $this->get_taxonomy('category');
		$Tags		= $this->get_taxonomy('post_tag');
		
	/*	-----------------------------
		MENU ITEMS
		SCRIPT TEMPLATES
		-----------------------------	*/
	/*	The template for the the list of the submitted menus
	 *	Contains all the properties of the menu
	 *	MENU PROPERTIES 
	 *	------------------------------------------
	 *	Menu Name		: Menu name (Only for administrative reasons)
	 *	Items Order		: The alignment of the menu items (left, center, right)
	 *	Colors			: The color theme of the menu
	 *	More Options	: 
	 */
		$html = '<script id="md_menus_lists_tmpl" type="text/x-jQuery-tmpl">
			<aside class="md_menu_item">
			<div class="md_menu_visible">
				<span class="md_span md_span_menu md_flag">${md_name}</span>
				<span class="md_span md_span_menu md_title">[menu_dash id="${md_id}"]</span>
				<i class="md_menu_edit"></i>
			</div>
			<div class="md_menu_hidden"><div class="md_inside">

				<div class="md_menu_input_field">
					<span class="dark_header">Drop menu tabs in the alignment placeholders</span>
					
					<div class="md_menu_table"><div class="md_menu_table_row">
						<div class="md_menu_comp_order" data-align="left"><div class="inner">
						{{if ( md_values && md_values.left ) }}
						{{each md_values.left}}
						{{if menuAdmin.struct.components[this]}}
						<aside class="md_minicomp_item">
							<span class="md_span md_span_sortable">
								${menuAdmin.struct.components[this]}
								<i class="md_minicomp_delete">&#10005;</i>
							</span>
							<input type="hidden" name="menu_tab[]" value="${this}" />
						</aside>
						{{/if}}
						{{/each}}
						{{/if}}
						</div></div>
						<div class="md_menu_comp_order" data-align="center"><div class="inner">
						{{if ( md_values && md_values.center ) }}
						{{each md_values.center}}
						{{if menuAdmin.struct.components[this]}}
						<aside class="md_minicomp_item">
							<span class="md_span md_span_sortable">
								${menuAdmin.struct.components[this]}
								<i class="md_minicomp_delete">&#10005;</i>
							</span>
							<input type="hidden" name="menu_tab[]" value="${this}" />
						</aside>
						{{/if}}
						{{/each}}
						{{/if}}
						</div></div>
						<div class="md_menu_comp_order" data-align="right"><div class="inner">
						{{if ( md_values && md_values.right ) }}
						{{each md_values.right}}
						{{if menuAdmin.struct.components[this]}}
						<aside class="md_minicomp_item">
							<span class="md_span md_span_sortable">
								${menuAdmin.struct.components[this]}
								<i class="md_minicomp_delete">&#10005;</i>
							</span>
							<input type="hidden" name="menu_tab[]" value="${this}" />
						</aside>
						{{/if}}
						{{/each}}
						{{/if}}
						</div></div>
					</div></div>
				</div>
				
				<div class="md_menu_name md_menu_input_field">
					<span class="dark_header">Menu name (only for administrative reasons)</span>
					<input type="text" name="md_menu_settings_name" value="${md_name}" class="widefat"/>
				</div>
				
				<div class="md_menu_settings md_menu_input_field">
					<span class="dark_header">Select menu theme color</span>
					<div class="md_menu_settings_color">
						{{each ["light", "dark", "hot", "sunsetorange", "cool", "green", "nature", "pumice", "desert", "beige", "female", "ming", "edony" ]}}
						<div class="md_menu_color md_menu_${this} {{if md_values.color && md_values.color == this}} active {{/if}}" data-color="${this}">
							<div class="md_menu_color_sub md_menu_color1"></div>
							<div class="md_menu_color_sub md_menu_color2"></div>
							<i class="md_menu_color_check"></i>
						</div>
						{{/each}}			

						<input type="hidden" name="menu_tab[]" value="{{if md_values.color}}${md_values.color}{{/if}}" data-attr="color" />
					</div>
				</div>
				
				<div class="md_menu_input_field">
				  <span class="dark_header">More options</span>
				  <div class="md_inside">
					<div class="md_menu_single_field">
						<label for="more_options_sticky_${md_id}">Sticky</label>
						<input id="more_options_sticky_${md_id}" type="checkbox" name="menu_tab[]" data-attr="sticky" {{if md_values.sticky && md_values.sticky == "checked"}} checked="checked" {{/if}}/>
					</div>
					<div class="md_menu_single_field">
						<label for="more_options_width_${md_id}">Max-Width</label>
						<input id="more_options_width_${md_id}" type="number" min="1" name="menu_tab[]" data-attr="max_width" value="{{if md_values.max_width}}${parseInt(md_values.max_width)}{{/if}}" />px
					</div>
				  </div>
				</div>
				
				<button class="update_menu md_button_secondary md_button_light" data-menu_id="${md_id}">Save Menu</button>
				<button class="delete_menu md_button_secondary md_button_dark" data-menu_id="${md_id}">Delete Menu</button>
				<span class="md_hidden_text_indicator">Are you sure you want to delete this menu? <a href="#" class="md_delete_menu" data-md_id=${md_id}>Yes</a> or <a href="#" class="md_delete_menu_cancel">No</a></span>
			</div></div>
			</aside>
		</script>';

		

		//	List of menu components to add in the menus
		$html .= '<script id="md_components_mini_list_tmpl" type="text/x-jQuery-tmpl">
			{{if md_values && md_values.type && md_values.title}}
			<aside class="md_minicomp_item md_minicomp_item${md_type} md_minicomp_item${md_values.type}">
				<span class="md_span">
					${md_values.title.toUpperCase()}
					<i class="md_minicomp_delete">&#10005;</i>
				</span>
				<input type="hidden" name="menu_tab[]" value="${md_id}" />
			</aside>
			{{/if}}
			</script>';
			

	/*	-----------------------------
		MENU TABS
		SCRIPT TEMPLATES
		-----------------------------	*/
	/*	The template for static simple links
	 *	CATEGORY PROPERTIES
	 *	Title			->	The title of the new menu item
	 *	Link url		->	The url where the link target to.
	 */
		$html .= '<script id="md_component_add_link" type="text/x-jQuery-tmpl">
		
		<section class="md_single_component" data-type="link">';
		$html .= '<section class="md_placeholder_toolbar">
			<button class="add-component md_button_secondary md_button_light " data-type="link" data-menu_id="${md_id}"></button>';
		$html .= '<span>The menu item title</span>';
		$html .= $this->create_input_tag( 'text', 'add-link', array(
				'attr'		=> 'title',
				'need'		=> 'required',
			) );
		$html .= '</section>';
		$html .= '<i>Submit the destination url of the link</i><br />';
		$html .= $this->create_input_tag( 'text', 'add-link', array(
				'attr'		=> 'url',
				'need'		=> 'required',
			) );
		$html .= '<i>Check to open url in a new window</i>';
		$html .= $this->create_input_tag( 'checkbox', 'add-link', array(
				'attr'		=> 'target_blank',
			) );
		$html .= '</section>
			
		</script>';
		
	/*	The template for category component form
	 *	CATEGORY PROPERTIES
	 *	Title			->	The title of the new menu item
	 *	Main Category	->	The category (parent=0) where the posts be load from
	 *	Tag				->	Show category posts of a specific tag
	 *	Small Thumbs	->	Shows the latest posts the selected category in small thumbs
	 */
		$html .= '<script id="md_component_add_category" type="text/x-jQuery-tmpl">
		
		<section class="md_single_component" data-type="category">';
		$html .= '<section class="md_placeholder_toolbar">
			<button class="add-component md_button_secondary md_button_light " data-type="category" data-menu_id="${md_id}"></button>';
		$html .= '<span>The menu item title</span>';
		$html .= $this->create_input_tag( 'text', 'add-category', array(
				'attr'		=> 'title',
				'need'		=> 'required',
			) );
		$html .= '</section>';
		$html .= '<i>Select the Category. You can select only from parent Categories</i><br />';
		$html .= $this->create_term_select_tag( $Categories, 'add-category', array(
				'attr'		=> 'term_id',
				'need'		=> 'required',
			) );
		$html .= '<i>Select one Tag to show posts (optional)</i><br />';
		$html .= $this->create_term_select_tag( $Tags, 'add-category', array(
				'attr'		=> 'from_term_id',
				'dependant'	=> 'from_term_id',
			) );
		$html .= '<i data-dependency="from_term_id">Check this option to show small thumbnails with the latest posts from the selected category</i>';
		$html .= $this->create_input_tag( 'checkbox', 'add-category', array(
				'attr'		=> 'show_small_thumbs',
				'dependency'=> 'from_term_id', 
			) );
		$html .= '</section>
			
		</script>';
		
	/*	The template for tag component form
	 *	TAG PROPERTIES
	 *	Title			->	The title of the new menu item
	 *	Tag				->	Show posts filtered by the selected tag
	 */
		$html .= '<script id="md_component_add_post_tag" type="text/x-jQuery-tmpl">
		
		<section class="md_single_component" data-type="post_tag">';

		$html .= '<section class="md_placeholder_toolbar">
			<button class="add-component md_button_secondary md_button_light " data-type="post_tag" data-menu_id="${md_id}"></button>';
		$html .= '<span>The menu item title</span>';
		$html .= $this->create_input_tag( 'text', 'add-post_tag', array(
				'attr'		=> 'title',
				'need'		=> 'required',
			) );
		$html .= '</section>';

		$html .= '<i>Select the Tag to filter the posts</i><br />';
		$html .= $this->create_term_select_tag( $Tags, 'add-post_tag', array(
				'attr'		=> 'term_id',
				'need'		=> 'required',
			) );
		$html .= '</section>
		
		</script>';

	/*	The template for post component form
	 *	POST PROPERTIES
	 *	Title			->	The title of the new menu item
	 *	Post			->	Select the post to show
	 *	Show Title		->	Show the post title
	 *	Show Excerpt	->	Show the post excerpt
	 */
		$html .= '<script id="md_component_add_post" type="text/x-jQuery-tmpl">
		
		<section class="md_single_component" data-type="post">';

		$html .= '<section class="md_placeholder_toolbar">
			<button class="add-component md_button_secondary md_button_light" data-type="post" data-menu_id="${md_id}"></button>';
		$html .= '<span>The menu item title</span>';
		$html .= $this->create_input_tag( 'text', 'add-post', array(
				'attr'		=> 'title',
				'need'		=> 'required',
			) );
		$html .= '</section>';

		$html .= '<i>Select post</i><br />';
		$html .= '<section class="autocomplete_container">';
		
		$html .=  $this->create_input_tag( 'text', 'add-post', array(
				'attr'		=> 'placeholder',
				'field'		=> 'search-post',
			), null, 'widefat', 'Search Posts' );
		$html .= $this->create_input_tag( 'hidden', 'add-post', array(
				'attr'		=> 'postid',
				'need'		=> 'required',
			) );
		$html .= '<section class="results" style="margin-top:-16px"></section>
			</section>';
		$html .= '<i>Show Post title</i>';
		$html .= $this->create_input_tag( 'checkbox', 'add-post', array(
				'attr'		=> 'show_title',
			) );
		$html .= '<i>Show Post excerpt</i>';
		$html .= $this->create_input_tag( 'checkbox', 'add-post', array(
				'attr'		=> 'show_excerpt',
			) );
		$html .= '</section>
		
		</script>';
		
	/*	The template for social component form
	 *	SOCIAL PROPERTIES
	 *	Social Provider	->	The social provider such as Facebook
	 *	Url				->	The social url target
	 */
		$html .= '<script id="md_component_add_social" type="text/x-jQuery-tmpl">
		
		<section class="md_single_component" data-type="social">';

		$html .= '<section class="md_placeholder_toolbar">
			<button class="add-component md_button_secondary md_button_light " data-type="social" data-menu_id="${md_id}"></button>
		</section>';

		$html .= '<i>Select the social provider</i><br />';
		$html .= '
			<select name="add-social[]" data-attr="title">
				<option value="facebook">Facebook</option>
				<option value="twitter">Twitter</option>
				<option value="google_plus">Google+</option>
				<option value="youtube">Youtube</option>
				<option value="instagram">Instagram</option>
				<option value="tumblr">Tumblr</option>
				<option value="pinterest">Pinterest</option>
				<option value="linkedin">Linkedin</option>
			</select><br />';
			
		$html .= '<i>Enter the destination url</i><br />';
		$html .= $this->create_input_tag( 'text', 'add-social', array(
				'attr'		=> 'link',
				'need'		=> 'required',
			) );
		$html .= '</section>
		
		</script>';
		
	/*	The template for youtube component form
	 *	YOUTUBE PROPERTIES
	 *	Youtube url		->	Enter the youtube url or id
	 *	Autoplay		->	Autoplay the video when the menu opens
	 *	Show controls	->	Show the youtube controls (play, progress bar )
	 */
		$html .= '<script id="md_component_add_youtube" type="text/x-jQuery-tmpl">
		
		<section class="md_single_component" data-type="youtube">';

		$html .= '<section class="md_placeholder_toolbar">
			<button class="add-component md_button_secondary md_button_light " data-type="youtube" data-menu_id="${md_id}"></button>';
		$html .= '<span>The menu item title</span>';
		$html .= $this->create_input_tag( 'text', 'add-youtube', array(
				'attr'		=> 'title',
				'need'		=> 'required',
			) );
		$html .= '</section>';

		$html .= '<i>Enter the youtube video url</i><br />';
		$html .= $this->create_input_tag( 'text', 'add-youtube', array(
				'attr'		=> 'video_url',
				'need'		=> 'required',
			) );
			
		$html .= '<i>Submit a header text for the video</i><br />';
		$html .= $this->create_textarea_tag( 'add-youtube', array(
				'attr'		=> 'header',
			) );
			
		$html .= '<i>Submit a description for the video</i><br />';
		$html .= $this->create_textarea_tag( 'add-youtube', array(
				'attr'		=> 'description',
			) );

		/*$html .= $this->create_input_tag( 'checkbox', 'add-youtube', array(
				'attr'		=> 'autoplay',
			), 'Check to enable autoplay of the video' );*/
			

		$html .= $this->create_input_tag( 'checkbox', 'add-youtube', array(
				'attr'		=> 'show_controls',
			), 'Check to show the video controls' );
		$html .= '</section>
		
		</script>';
		
		
		//	THE TEMPLATE FOR THE MENU TABS LIST
		$html .= '<script id="md_components_list_tmpl" type="text/x-jQuery-tmpl">
			{{if md_values && md_values.type}}
			<aside class="md_component_item md_component_${md_type} md_component_${md_values.type}" data-md_id="${md_id}">
				<span class="md_span md_flag">${md_name.toUpperCase()}</span>
				<span class="md_span md_title">Type: <strong>${md_values.type}</strong></span>
				<i class="md_open_controls" title="Open more options"  data-parent_class=".md_component_item"></i>
				<ul class="md_controls">
					<li class="md_list md_edit_icon" data-type="${md_values.type}" data-md_id="${md_id}" title="Edit Tab">Edit</li>
					<li class="md_list md_delete_icon" data-type="${md_values.type}" data-md_id="${md_id}" data-delete="component" title="Delete Tab">Delete</li>
				</ul>
				<input type="hidden" name="md_component_title"  value="${md_name}" />
				<input type="hidden" name="md_component_values"  value="{{if md_values.values}} ${JSON.stringify(md_values.values)} {{/if}}" />
			</aside>
			{{/if}}
			</script>';
		//	The template for the post autocomplete
		$html .= '<script id="menu-autocomplete-post-tmpl" type="text/x-jQuery-tmpl">
				<aside class="autocomplete-post-item" data-id="${ID}">[ID: ${ID}] - ${post_title}</aside>
		</script>';


	/*	-----------------------------
		MENU TOOLS ( FOR CUSTOM MENU TABS )
		SCRIPT TEMPLATES
		-----------------------------	*/
	/*	The script pattern templates
	 *	------------------------------------------
	 *	id =  md_tool_[TOOL NAME]
	 *
	 *	AVAILABLE MENU TOOLS 
	 *	------------------------------------------
	 *	Header		: An h3 header text
	 *	Link		: A two row text which can be used as link
	 *	Paragraph	: A simple text paragraph
	 *	Image		: Enter an image into custom tab
	 *	Map			: Submit an adress to show it in a map
	 *	Youtube		: A youtube video can be also added in custom tab
	 */
	 
	 //	The placeholder containers (columns) for the tools 
	 	$html .= '<script id="md_tool_single_placeholder" type="text/x-jQuery-tmpl">
			<section class="md_single_placeholder">
				<header class="md_single_header dark_header">Menu Tab Column</header>
				<!--<i class="md_open_controls" data-parent_class=".md_single_placeholder" title="Open more options"></i>-->
				<ul class="md_controls">
					<li class="md_list md_delete_icon" title="Delete Tab" data-delete="tab_column">Delete</li>
				</ul>
				<div class="md_single_inside">
				</div>
				<div class="md_single_controls">
					<i class="md_single_delete_btn"></i>
				</div>
			</section>
			</script>';
			
	//	The template for the  header tool
		$html .= '<script id="md_tool_header" type="text/x-jQuery-tmpl">
			<div class="md_tool_header_input md_tool_edit" data-input="header">
				<input type="hidden" class="md_tool_input" value="header" data-tpl_place="md_tool_type"/>
				<div class="md_tool_icon_container">
				<button name="md_tool_find_icon" class="md_button_secondary md_button_dark" data-to-input="md_tool_header_icon">Icon</button>
				<button name="md_tool_remove_icon" class="md_button_secondary md_button_dark" data-to-input="md_tool_header_icon" style="{{if typeof md_tool_header_icon != "undefined"}}display:none;{{/if}}">Remove</button>
				<img src="${md_tool_header_icon}" class="md_tool_preview" width="36" style="{{if typeof md_tool_header_icon != "undefined"}}display:none;{{/if}}" />
				<input type="hidden" name="md_tool_header_icon" class="md_tool_input" data-type="url" data-tpl_place="md_tool_header_icon" value="${md_tool_header_icon}"/><br />
				</div>
				<i>The header text</i><br />
				<input type="text" class="md_tool_input" value="${md_tool_header_text}" data-type="text" data-tpl_place="md_tool_header_text"/><br />
			</div>
			<div class="md_tool_header_html md_tool_html" data-input="header">
				{{if md_tool_header_icon}}
				<img class="md_tool_header_html_icon" src="${md_tool_header_icon}" width="36" />
				{{/if}}
				<h3 class="md_tool_header_html">${md_tool_header_text}</h3>
			</div>
			</script>';
			
		$html .= '<script id="md_tool_link" type="text/x-jQuery-tmpl">
			<div class="md_tool_link_input md_tool_edit" data-input="link">
				<input type="hidden" class="md_tool_input" value="link" data-tpl_place="md_tool_type"/>
				<i>Select an icon for your link</i><br />
				<div class="md_tool_icon_container">
				<button name="md_tool_find_icon" class="md_button_secondary md_button_dark" data-to-input="md_tool_link_icon">Icon</button>
				<button name="md_tool_remove_icon" class="md_button_secondary md_button_dark" data-to-input="md_tool_link_icon" style="{{if typeof md_tool_link_icon != "undefined"}}display:none;{{/if}}">Remove</button>
				<img src="${md_tool_link_icon}" class="md_tool_preview" width="36" style="{{if typeof md_tool_link_icon != "undefined"}}display:none;{{/if}}" />
				<input type="hidden" name="md_tool_link_icon" class="md_tool_input" data-type="url" data-tpl_place="md_tool_link_icon" value="${md_tool_link_icon}"/><br />
				</div>
				<i>The link to forward</i><br />
				<input type="text" class="md_tool_input" value="${md_tool_link_url}"  data-type="text" data-tpl_place="md_tool_link_url"/><br />
				<i>Insert the primary text</i><br />
				<input type="text" class="md_tool_input" value="${md_tool_link_primary}" data-type="text" data-tpl_place="md_tool_link_primary"/><br />
				<i>Insert the secondary text</i><br />
				<input type="text" class="md_tool_input" value="${md_tool_link_secondary}" data-type="text" data-tpl_place="md_tool_link_secondary"/><br />
			</div>
			<div class="md_tool_link_html md_tool_html" data-input="link">
			<a href="${md_tool_link_url}" class="md_tool_link_html_link">
				{{if md_tool_link_icon}}
				<img class="md_tool_link_html_icon" src="${md_tool_link_icon}" width="36" />
				{{/if}}
				<p class="md_tool_link_html_primary">${md_tool_link_primary}</p>
				<p class="md_tool_link_html_secondary">${md_tool_link_secondary}</p>
			</a>
			</div>
			</script>';
			
		$html .= '<script id="md_tool_paragraph" type="text/x-jQuery-tmpl">
			<div class="md_tool_paragraph_input md_tool_edit" data-input="paragraph">
				<input type="hidden" class="md_tool_input" value="paragraph" data-tpl_place="md_tool_type"/>
				<i>The paragraph text</i><br />
				<textarea class="md_tool_input" data-type="text" data-tpl_place="md_tool_paragraph_text" rows="10" cols="50">${md_tool_paragraph_text}</textarea><br />
			</div>
			<div class="md_tool_paragraph_html md_tool_html" data-input="paragraph">
				<p class="md_tool_paragraph_text">${md_tool_paragraph_text}</p>
			</div>
			</script>';

		$html .= '<script id="md_tool_image" type="text/x-jQuery-tmpl">
			<div class="md_tool_image_input md_tool_edit" data-input="image">
				<input type="hidden" class="md_tool_input" value="image" data-tpl_place="md_tool_type"/>
				<input type="text" class="md_tool_input" value="${md_tool_image_url}" data-type="text" data-tpl_place="md_tool_image_url"/>
			</div>
			<div class="md_tool_image_html md_tool_html" data-input="image">
				<img src="${md_tool_image_url}" width="300" />
			</div>
			</script>';

		//	MAP TOOL
		$html .= '<script id="md_tool_map" type="text/x-jQuery-tmpl">
			<div class="md_tool_map_input md_tool_edit" data-input="map">
				<input type="hidden" class="md_tool_input" value="map" data-tpl_place="md_tool_type"/>
				<i>Submit the address to be shown on map</i><br />
				<input type="text" class="md_tool_input" value="${md_tool_map_address}" data-type="text" data-tpl_place="md_tool_map_address"/><br />
			</div>
			<div class="md_tool_map_html md_tool_html" data-input="map">
				<img src="http://maps.googleapis.com/maps/api/staticmap?center=${md_tool_map_address}&amp;scale=false&amp;size=600x400&amp;maptype=roadmap&amp;format=jpg&amp;visual_refresh=true&markers=size:mid%7Ccolor:red%7Clabel:1%7C${md_tool_map_address}">
			</div>
			</script>';
			
		$html .= '<script id="md_tool_youtube" type="text/x-jQuery-tmpl">
			<div class="md_tool_image_input md_tool_edit" data-input="youtube">
				<input type="hidden" class="md_tool_input" value="youtube" data-tpl_place="md_tool_type"/>
				<i>The video id (e.g. the id is the text in bold: https://youtu.be/<strong>5bRrtyrNc_w</strong>)</i><br />
				<input type="text" class="md_tool_input" value="${md_tool_youtube_url}" data-type="text" data-tpl_place="md_tool_youtube_url"/>
			</div>
			<div class="md_tool_youtube_html md_tool_html" data-input="youtube">
			<iframe width="260" height="150" src="https://www.youtube.com/embed/${md_tool_youtube_url}" frameborder="0" allowfullscreen></iframe>
			</div>
			</script>';

		$html .= '<script id="md_tool_html" type="text/x-jQuery-tmpl">
			<div class="md_tool_html_input md_tool_edit" data-input="html">
				<input type="hidden" class="md_tool_input" value="html" data-tpl_place="md_tool_type"/>
				<i>The html content</i><br />
				<textarea class="md_tool_input" data-type="html" data-tpl_place="md_tool_html_content" cols="50">${md_tool_html_content}</textarea><br />
			</div>
			<div class="md_tool_html md_tool_html" data-input="html">
				<p class="md_tool_html_content">{{html md_tool_html_content}}</p>
			</div>
			</script>';
			
		return $html;
		
	}

}

endif;

All_In_Menu_Settings::init();