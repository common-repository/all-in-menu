<?php
/**
 * Plugin Name: All In Menu
 * Plugin URI: wordpress.org/plugins/all-in-menu/
 * Description: Create dynamic and responsive menus for your wordpress site
 * Version: 1.1.5
 * Author: CookForWeb
 * Author URI: http://www.cookforweb.com
 * Text Domain: allinmenu
 */
 
 // BRANCH FOR HOVER
 
if ( ! defined( 'ABSPATH' ) ) {
    exit; // disable direct access
}

if ( ! class_exists( 'All_In_Menu' ) ) :


/**
 * Register the plugin.
 *
 * Display the administration panel, insert JavaScript etc.
 */
class All_In_Menu {


	public $table;
	
	public $version;
    /**
     * Init
     */	
    public static function init() {

		//	Require the plugin's settings php
		require_once("all-in-menu-settings.php");
		
        $menudash = new self();
    }
	
	/**
	 * Construct function
	 */
	public function __construct(){
		
		$this->add_shortcode();
		$this->table = 'md_metas';// '". $wpdb->prefix ."allinmenu';
		$this->version = '1.1.3';
		
		register_activation_hook(__FILE__, array( $this, 'activate' ));
		//$this->add_scripts();
		//$this->add_styles();
		
		add_action( 'wp_enqueue_scripts', 		array( $this, 'enqueue_scripts' ) );
		//add_action( 'plugins_loaded', 			array( $this, 'init' ), 10 );
		add_action( 'wp_footer', 				array( $this, 'wp_footer')  );
		
		//	Add Settings js
		if ( isset( $_GET['page'] ) && $_GET['page'] == 'all-in-settings' ){
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );		}
		
		add_action( 'wp_ajax_load_panel_category', 		array( $this, 'load_panel_category') );
		add_action( 'wp_ajax_nopriv_load_panel_category',array( $this, 'load_panel_category') );
		add_action( 'wp_ajax_load_panel_post_tag', 		array( $this, 'load_panel_category') );
		add_action( 'wp_ajax_nopriv_load_panel_post_tag',array( $this, 'load_panel_category') );
		add_action( 'wp_ajax_load_panel_post', 			array( $this, 'load_panel_post') );
		add_action( 'wp_ajax_nopriv_load_panel_post',	array( $this, 'load_panel_post') );
		add_action( 'wp_ajax_load_panel_youtube', 		array( $this, 'load_panel_youtube') );
		add_action( 'wp_ajax_nopriv_load_panel_youtube',array( $this, 'load_panel_youtube') );
		add_action( 'wp_ajax_load_panel_custom', 		array( $this, 'load_panel_custom') );
		add_action( 'wp_ajax_nopriv_load_panel_custom',	array( $this, 'load_panel_custom') );
		
		
		add_action( 'after_setup_theme', array( $this, 'theme_setup') );
		
	}
	
/****
	*	Create the table in db to store the menu values
	*	We will create one table to avoid inner joins
	*	
	*/
	public function activate(){

		global $wpdb;
	
		// create the database table
		if( $wpdb->get_var("show tables like '".$this->table ."'") == null) {
			
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			
			$sql = "CREATE TABLE `".$this->table."` (
			  `md_id` bigint(20) NOT NULL AUTO_INCREMENT,
			  `md_ref` bigint(20) NOT NULL,
			  `md_name` varchar(255),
			  `md_values` text,
			  `md_type` tinyint,
			  PRIMARY KEY (`md_id`),
			  UNIQUE KEY `md_id` (`md_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
	 
			dbDelta($sql);


			$sql = "INSERT INTO `".$this->table."` VALUES
			('1', '0', 'Home','{\"type\":\"static\",\"title\":\"Home\",\"values\":{}}','1'),
			('2', '0', 'Search','{\"type\":\"search\",\"title\":\"Search\",\"values\":{}}','1')";

			dbDelta($sql);
		}
	}
	
	public function theme_setup(){
		add_image_size( 'medium_fixed', 300, 300, true ); // 300 pixels wide (and unlimited height)
	}

    /**
     * Add the menu dash shortcode
     */
    public function add_shortcode() {

       add_shortcode( 'menu_dash', array( $this, 'do_shortcode' ) );
    }


    /**
     * Do the shortcode when called in theme
     */
	 public function do_shortcode( $atts ){
		
		$atts = shortcode_atts( array(
			'id'	=> '0',
		), $atts, 'menu_dash' );
		
		$items = $this->get_menu( $atts['id'] );
		
		//	If menu doesn't exists $items will be false so nothing will happen
		if ( $items ){
			$this->export_html( $items );
		}
	 }
	 

    /**
     * Enqueue scripts and styles
     */
	public function enqueue_scripts(){
		 
		wp_enqueue_script( 'all_in_js', plugins_url( 'js/all-in.js', __FILE__ ), array('jquery'), null, true );
		wp_localize_script( 'all_in_js', 'Ajax', array( 'url' => admin_url('admin-ajax.php') ) );
		
		//wp_enqueue_script( 'scrollbarjs', plugins_url('js/jquery.mCustomScrollbar.min.js', __FILE__), array('jquery'), true );
		//wp_enqueue_script( 'mousewheeljs', plugins_url('js/jquery.mousewheel.min.js', __FILE__), array('jquery'), true );

		//jquery.mCustomScrollbar.min
		wp_enqueue_style( 'all_in_css', plugins_url( 'css/all-in.css', __FILE__ ) );
		//wp_enqueue_style( 'scrollbarcss', plugins_url( 'css/jquery.mCustomScrollbar.min.css', __FILE__ ) );
	}
	
    /**
     * Enqueue scripts and styles in admin settings page
     */
	 public function admin_enqueue_scripts(){
		wp_enqueue_script( 'tmpljs', plugins_url( 'js/jquery.tmpl.min.js', __FILE__ ) ,array('jquery'), $this->version, true );
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script( 'jquery-ui-draggable' );
		wp_enqueue_script( 'jquery-ui-droppable' );
		wp_enqueue_script( 'jquery-ui-accordion' );
		wp_enqueue_script( 'jquery-ui-tabs');
		wp_enqueue_script( 'touch_punch', plugins_url( 'js/jquery.ui.touch-punch.min.js', __FILE__ ) ,array('jquery-ui-tabs'), $this->version, true );
		wp_enqueue_media();
		wp_enqueue_style( 'all_in_settings_css', plugins_url( 'css/all-in-settings.css', __FILE__ ), null, $this->version );
		wp_enqueue_script( 'all_in_settings_js', plugins_url( 'js/all-in-settings.js', __FILE__ ), array('jquery'), $this->version, true );
		wp_localize_script( 'all_in_settings_js', 'Ajax', array( 
			'url' => admin_url('admin-ajax.php') 
		) );
	 }	
	 	 

	 /**
	  *	With the wp_footer function we print the all the necessary script templates
	  *	in the footer of every page
	  */
	 public function wp_footer(){
		 
		 //$this->export_html_scripts();
	 }

    /**
     * Get the items of the selected menu
     */
	private function get_items( $atts ){

		$taxonomies = array( 
			$atts['term'],
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
			'parent'            => $atts['parent'],
			'hierarchical'      => true, 
			'child_of'          => 0,//$atts['parent'], 
			'get'               => '', 
			'name__like'        => '',
			'description__like' => '',
			'pad_counts'        => false, 
			'offset'            => '', 
			'search'            => '', 
			'cache_domain'      => 'core'
		);		
		$items = get_terms( $taxonomies, $args );
		//var_dump( $items );
		return $items;
		
	}

	/**
     *	Decode stored values
     */
	public static function decode_values( $items ){
		//	 DECODE VALUES
		if ( is_array( $items ) ){
		  foreach ( $items as &$item ){

			$item = json_decode( $item );
		  }
		}
		
		return $items;
	}


	/**
     *	Constructing the menu html to be rendered
     */
	private function export_html( $items ){
		
		//	Get the stored settings
		$settings	= get_option('allin_settings'); 

		//	Checking if cache exists and is not older than the user defined
		$cache = $this->check_cache( $items->md_id, $settings ); 
		//	If cache is false then we must create a new file
		if ( $cache === false ){
			//	Check if is sticky or not
			$sticky		= ( isset( $items->md_values->sticky ) && intval($items->md_values->sticky) == 'checked' ) ? 'sticky' : ''; 
			//	Get the color theme
			$color		= isset( $items->md_values->color )  ? $items->md_values->color : 'light'; 
	
			$html = '<div id="all_in_wrapper" class="horizontal '.$color.' '.$sticky.' primary-color">';

			$html .= '<div id="all_in_mobile_extend" class="allin_for_mobile">'.__( 'Menu', 'allinmenu').'</div>';
			//	Print the main menu items
			$this->export_html_main( $items, $html );
					
			$html .= '</div>';
			
			if ( $settings['enable'] === 'true' ){
				
				$this->create_cache( $html, $items->md_id );
			}
		}
		else{
			$html = file_get_contents( $cache );
		}
		
		echo $html;
			
	}
	
	/**
	*	Export html <ul> per parent
	*	
	*	$items has all the menu items
	*/
	private function export_html_main( $items, &$html ){

		//	Check if the user has already select a maximum width for the inner menu
		$max_width 	= ( isset( $items->md_values->max_width ) && intval($items->md_values->max_width) > 0 ) ? 'max-width:'.$items->md_values->max_width .'px' : '';
		$height 	= ( isset( $items->md_values->height ) && intval($items->md_values->height) > 0 ) ? 'height:'.$items->md_values->height .'px' : '';

		$html .= '<nav id="md_nav" role="navigation" class="menu-dash primary-color" style="'.$max_width.';'.$height.'"><div class="md_container">';
		
		//	The button for mobile devices
		$html .= '<div id="main-menu-mobile-trigger"></div>';
		
		//	Initialize the two variables for the html
		$desktopHtml = $socialHtml = $searchHtml = '';
		
		$aligns = array( 'left', 'center', 'right' );
		//	Generate the left alignment menu
		foreach ( $aligns as $align ){
			
			if ( isset( $items->md_values->{$align} ) && count( $items->md_values->{$align} ) > 0 ){
				
				$desktopHtml .= '<div id="main-menu-'.$align.'" class="main-menu menu-component">';
				
				$this->export_html_list( $desktopHtml, $socialHtml, $searchHtml, $items->md_values->{$align}, 'main_categories_menu', 'menu-item');
		
				$desktopHtml .= '</div>';
			}
		}
		
		$html .= '<div id="main-menu-mobile-content">
					<ul id="main-menu-mobile-social">'.$socialHtml.'</ul>
					<div id="main-menu-mobile-search">'.$searchHtml.'</div>
				</div>';
		
		$html .= $desktopHtml;
		
		$html .= '</div>';
				
		$html .= '</nav>';
	}
	
	
	/**
	*	Export html panel per main menu item
	*	
	*	$items has all the menu items
	
	private function export_html_panels( $items ){
		
		echo '<div id="menu-dash-container" class="menu-dash-panel-wrapper"><div class="md_container">';
		
		foreach ( $items as $item ){ 
			
			$children = $this->get_items( $item->term_id );
			
			// Start the panel
			echo '<div class="menu-dash-panel">';
			
			echo '<div class="menu-dash-panel-tree">';
			
			//var_dump($children);			
			$this->export_html_list( $children );
			
			echo '</div>';
			
			echo '<div id="menu-dash-panel-'.$item->term_id.'" class="menu-dash-panel-posts">';
			
			echo '</div>';
			
			echo '</div>';
			
		}
		
		echo '</div></div>';
	}*/

	/**
	*	Export html panel per main menu item
	*	
	*	$items has all the menu items
	
	private function export_html_panel( $items, $parent ){
		
			echo '<div class="menu-dash-panel-tree">';
			
			$this->export_html_list( $items, $parent );
			
			echo '</div>';
	}*/
	
	/**
	*	Export html <ul> per parent
	*	
	*	$items has all the menu items
	*/
	private function export_html_list( &$html, &$socialHtml, &$searchHtml, $items, $ul_class = '', $menu_item_class = '', $home = false ){
		
		
		$html .= '<ul class="'.$ul_class.'">';

		$items = All_In_Menu::decode_values( $items );
		
		if ( is_array( $items ) ):
		
		foreach ( $items as $key => $item ){ 
			//	OPEN LI TAG
		  if ( isset( $item->type ) && !empty( $item->type ) ):
			  
			switch ( $item->type ){
			  
			  case 'static':
				$html .= '<li id="menu-item-'.$key.'" class="'. $menu_item_class .' '. $menu_item_class .'-'.$key.' '. $menu_item_class .'-'.$item->type.'">';
				$html .= '<a href="'.get_home_url().'" class="menu-static">'.__( $item->title, 'allinmenu') .'</a>';
				$html .= '</li>';
			  	break;
			  case 'social':
				$html .= '<li id="menu-item-'.$key.'" class="'. $menu_item_class .' '. $menu_item_class .'-'.$key.' '. $menu_item_class .'-'.$item->type.' no-mobile">'; 
				$html .= '<a href="'.$item->values->link.'" class="menu-link menu-social menu-social-'.$item->values->title.'">&nbsp;</a>';
				$html .= '</li>';
				$socialHtml .= '<li id="menu-item-'.$key.'" class="'. $menu_item_class .' '. $menu_item_class .'-'.$key.' '. $menu_item_class .'-'.$item->type.'">'; 
				$socialHtml .= '<a href="#" class="menu-link menu-social menu-social-'.$item->values->title.'">&nbsp;</a>';
				$socialHtml .= '</li>';
			  	break;	
			  case 'link':
			  	$target = $item->values->target_blank == "true" ? "_blank" : "_self";
				$html .= '<li id="menu-item-'.$key.'" class="'. $menu_item_class .' '. $menu_item_class .'-'.$key.' '. $menu_item_class .'-'.$item->type.'">'; 
				$html .= '<a href="'.$item->values->url.'" class="menu-static-link" target="'.$target.'">'.$item->values->title.'</a>';
				$html .= '</li>';
			  	break;	
			  case 'search':
				$html .= '<li id="menu-item-'.$key.'" class="'. $menu_item_class .' '. $menu_item_class .'-'.$key.' '. $menu_item_class .'-'.$item->type.' no-mobile">'; 
				$this->search_form( $html );
				$html .= '</li>';
				$searchHtml .= '<form role="search" method="get" id="md_search_form_mobile" class="md_search_form" action="'.get_site_url().'">
				<div>
					<label class="screen-reader-text" for="s"></label>
					<input class="md_search_input" type="text" value="" name="s" id="s" placeholder="Search...">
					<input type="submit" class="md_search_submit" value="">
				</div>
			</form>';
			  	break;
			  default:
				$html .= '<li id="menu-item-'.$key.'" class="'. $menu_item_class .' '. $menu_item_class .'-'.$key.' '. $menu_item_class .'-'.$item->type.'">';
				$html .= '<a href="#" class="menu-link" data-target="#menu-content-'.$key.'">'.$item->title .'</a>';
				$this->export_html_item( $html, 'menu-content-'.$key , $item );
				$html .= '</li>';
			  	break;
			}
		  endif;	
		}
		endif;		
		
		$html .= '</ul>';
	}
	
	private function export_html_item( &$html, $id, $item ){

		$html .= '<div id="'.$id.'"class="menu-expanded-content secondary-color">';
				
		if ( isset( $item->values ) && !empty( $item ) ){
		
			$html .= call_user_func( array( $this, 'menu_expanded_type_'. $item->type ), $item );
		}
		
		$html .= '</div>';
	}
	
	private function menu_expanded_type_category( $item, $number_primary_res = 4 ){

		$html = '';

		//	Childer categories
		$args = array(
			'hide_empty'        => false, 
			'parent'            => $item->values->term_id,
			'child_of'			=> '',//$response->id,
			'hierarchical'      => true, 
		);
		$terms = get_terms($item->type, $args);
		
		$tpl = '<li class="menu-expandable">
				  <a href="${term_link}"><h3 class="menu-expanded-title" data-termid="${term_id}">${name} (${count})</h3></a>
			  </li>';
		
		if ( count( $terms ) > 0 ){
		
			$html .= '<section class="menu-expanded-sidebar"><ul class="md_list_category">';
		
			foreach ( $terms as $term ){ 
				$html .= strtr( $tpl, array(
					'${term_link}'	=> get_term_link( $term ),
					'${term_id}'	=> $term->term_id,
					'${name}'		=> $term->name,
					'${count}'		=> $term->count,
				));
			}
			
			$html .= '</ul></section>';
		}
		
		$html .= '<section class="menu-expanded-container">';
			
		//	Main Lobby articles
		$args = array(
			//'post_type'		=> 'post',
			'posts_per_page'=> $number_primary_res,
			'post_status'	=> 'publish',
			'meta_query' 	=> array(array('key' => '_thumbnail_id')),
			'tax_query'		=> array(
				array(
					'taxonomy'	=> $item->type,
					'field'		=> 'term_id',
					'terms'		=> $item->values->term_id,
				),
			),
		);
		
		if ( !empty( $item->values->from_term_id )){
			$args['tax_query'][] = array(
					'taxonomy'	=> 'post_tag',
					'field'		=> 'term_id',
					'terms'		=> $item->values->from_term_id,
			);
		}
		
		$posts = new WP_Query( $args ); //var_dump( $posts );
		
		$tpl = '<a href="${permalink}" class="lobby-item primary-color">
					<div class="menu-expanded-item item-scale" title="${post_title}">
						<img src="${image}" alt="" />
						<div class="main-listing-highlight">
						<h4 class="main-listing-header">${post_title}</h4>
						</div>
					</div>
				</a>';
				
		if ( $posts->post_count > 0 ){
				
			$html .= '<div class="menu-expanded-lobby">';
			
			foreach ( $posts->posts as $key => $post ){ 
			
				$row_separator = $key % 4;
			
				if ( $row_separator == 0 ){	$html .= '<div class="md_table_row">'; }
			
				$image = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'medium_fixed' );
				$html .= strtr( $tpl, array(
					'${permalink}'		=> get_permalink( $post->ID ),
					'${image}'			=> current( $image ),
					'${post_title}'		=> $post->post_title,
				));

				if ( $row_separator == 3 ){ $html .= '</div>'; }
						
			}

			$html .= '</div>';
		}
		
		//	Small lobby icons with the latest posts
		if ( $item->values->term_id != 0 && isset( $item->values->show_small_thumbs ) && $item->values->show_small_thumbs == 'true' ){
			
			$args = array(
				//'post_type'		=> 'post',
				'posts_per_page'=> 10,
				'post_status'	=> 'publish',
				'meta_query' 	=> array(array('key' => '_thumbnail_id')),
				'tax_query'		=> array(
					array(
						'taxonomy'	=> $item->type,
						'field'		=> 'term_id',
						'terms'		=> $item->values->term_id,
					),
				),
			);
			
			$posts = new WP_Query( $args );
			$tpl = '<a href="${permalink}" class="lobby-item primary-color">
					<div class="menu-expanded-product-item item-scale">
						<img src="${image}" alt="" title="${post_title}"/>
					</div>
					</a>';
			
			if ( $posts->post_count > 0 ){
				
				$html .= '<h3 class="md_title">Latest posts in the category</h3>';
				
				$html .= '<div class="menu-expanded-small-lobby">';
				
				/*$html .= '<div class="md_table_row">';

				
				$html .= '</div>';*/
				
				$html .= '<div class="md_table_row">';
			
				foreach ( $posts->posts as $post ){
					
					$image = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ),'thumbnail' );
					$html .= strtr( $tpl, array(
						'${permalink}'		=> get_permalink( $post->ID ),
						'${image}'			=> current( $image ),
						'${post_title}'		=> $post->post_title,
					));
				}
				
				$html .= '</div></div>';
			}

		}
		
		$html .= '</section>';
		
		return $html;
	}
	
	private function menu_expanded_type_post_tag( $item ){
		
		return $this->menu_expanded_type_category( $item, 8 );
	}

	private function menu_expanded_type_post( $item ){
		
		//print '<pre>'; var_dump( $item ); print '</pre>';
		$html = '';
		
		//	Get the post and the feature image
		$post	= get_post( $item->values->postid );
		$image 	= wp_get_attachment_image_src(get_post_thumbnail_id($item->values->postid), 'medium_fixed' );
		if ( $image !== false || ( $item->values->show_excerpt == 'true' && !empty($post->post_excerpt) )){
			
			$html .= '<section class="menu-expanded-sidebar">';
			
			$html .= '<figure class="menu-sidebar-post-img">
						  <img src="'. $image[0] .'" />
					  </figure>';
			if ( $item->values->show_excerpt == 'true' ){
			
			$html .=  '<div class="menu-sidebar-post-excerpt">'. $post->post_excerpt .'</div>';
			
			}		
			$html .= '</section>';
		}
		
		$html .= '<section class="menu-expanded-container">';

		if ( $item->values->show_title == 'true' ){
				
			$html .= '<h2 class="lobby-header tertiary-color">'.$post->post_title.'</h2>';
		}
		$html .= '<div class="menu-post-lobby-content">'.apply_filters('the_content', $post->post_content).'</div>';
		
		$html .= '</section>';
		
		return $html;
		
	}

	private function menu_expanded_type_youtube( $item ){
		
		$html = '';
		
		if ( !empty( $item->values->header) || !empty( $item->values->description) ){

			$html .= '<section class="menu-expanded-sidebar">';
			
			if ( !empty( $item->values->header )){
				
				$string =  strlen( $item->values->header ) > 200 ? substr($item->values->header,0,200).'...': $item->values->header;
				$html .= '<p class="menu-expanded-header">'. $string .'</p>';
			}
			if ( !empty( $item->values->description) ){
	
				$string = strlen( $item->values->description ) > 800 ? substr($item->values->description,0,800).'...': $item->values->description;
				$html .= '<p class="menu-expanded-descr">'. $string .'</p>';
			}
				
			$html .= '</section>';
		}
		
		
		$html .= '<section class="menu-expanded-container">';

			$html .= '<iframe class="menu_youtube_video" src="https://www.youtube.com/embed/'.$item->values->video_url.'?rel=0&autoplay=0&showinfo=0&controls='.$item->values->show_controls.'&fs=0" width="853" height="480" frameborder="0" allowfullscreen="allowfullscreen"></iframe>';
		
		$html .= '</section>';
		
		
		return $html;
		
	}
	

	private function menu_expanded_type_custom( $item ){
		
		$html = '';
		
		$total_columns	= count( $item->values ); 
		
		//	Define all the template for custom tab
		$tpl = array(
		  'holder'	=> '<div id="menu_custom_holder_${column}" 
					class="menu-custom-holder menu-custom-holder-${total_columns}">
					<div class="menu_custom_inside">
						${content}
					</div>
				</div>',
		);
		
		
		//	Loop the columns
		foreach ( $item->values as $x => $col){
			
			$col_content = '';
			
			//	Loop the tools
			foreach( $col as $y => $row ){ 
	
				$type = $row->md_tool_type;
				
				//$row = (array)$row;
				
				//var_dump( $row );
				/*$row_values = array_values( $row );
				$row_keys	= array_keys($row);
				
				foreach( $row_keys as &$value ){
					$value = '${'.$value.'}';
				}
				$row = array_combine($row_keys, $row_values); */
				
				//	Add each tool html
				switch ( $type ){
					case 'header':
						$col_content .= '<div class="menu_custom_item menu_custom_item_header">';
						if ( !empty ( $row->md_tool_header_icon )){
						$col_content .= '<img class="md_tool_link_html_icon" src="'.$row->md_tool_header_icon.'" width="36" />';		}
						$col_content .= '<h3 class="md_tool_header_text">'.$row->md_tool_header_text.'</h3>
							</div>';
						break;
					case 'link':
						$col_content .= '<div class="menu_custom_item menu_custom_item_link">
							<a href="'.$row->md_tool_link_url.'" class="md_tool_link_html_link">';
						if ( !empty ( $row->md_tool_link_icon )){
						$col_content .= '<img class="md_tool_link_html_icon" src="'.$row->md_tool_link_icon.'" width="36" />'; } 
						$col_content .= '<p class="md_tool_link_html_primary">'.$row->md_tool_link_primary.'</p>
								<p class="md_tool_link_html_secondary">'.$row->md_tool_link_secondary.'</p>
							</a>
						</div>';
						break;
					case 'paragraph':
						$col_content .= '<div class="menu_custom_item menu_custom_item_paragraph">
							<p class="md_tool_paragraph">'.$row->md_tool_paragraph_text.'</p>
						</div>';
						break;
					case 'image':
						$col_content .= '<div class="menu_custom_item menu_custom_item_image">
							<img src="'.$row->md_tool_image_url.'" class="img_responsive" />
						</div>';
						break;
					case 'map':
						$col_content .= '<div class="menu_custom_item menu_custom_item_map">
							<img src="http://maps.googleapis.com/maps/api/staticmap?center='.$row->md_tool_map_address.'&amp;scale=false&amp;size=600x400&amp;maptype=roadmap&amp;format=jpg&amp;visual_refresh=true&markers=size:mid%7Ccolor:red%7Clabel:1%7C'.$row->md_tool_map_address.'">
						</div>';
						break;
					case 'youtube':
						if ( !empty( $row->md_tool_youtube_url )){
						$col_content .= '<div class="menu_custom_item menu_custom_item_youtube">
							<iframe src="https://www.youtube.com/embed/'.$row->md_tool_youtube_url.'?autoplay=0&showinfo=0&controls=0&fs=0" frameborder="0" allowfullscreen="1"></iframe>
						</div>'; }
						break;
					case 'html':
						$col_content .= '<div class="menu_custom_item menu_custom_item_html">
							'.$row->md_tool_html_content.'
						</div>';
						break;
				}
			}
			
			$html .= strtr( $tpl['holder'], array(
				'${column}'			=> $x,
				'${total_columns}'	=> $total_columns,
				'${content}'		=> $col_content,
			));
		}
		
		
		return $html;
		
		/*
				console.log(data);
		var values = data.extra.values;
		//	First of all check how much columns the menu tab contains
		var columns	= values.length; 
		//	Foreach column we render content
		for ( var x in values ){
			// Render content foreach md_tool
			$('#menu_custom_tool_holder').tmpl({
				column			: x,
				total_columns	: columns,
			}).appendTo('#menu-expanded-lobby');
			for ( var y in values[x] ){
				var tool = values[x][y];
				$('#menu_custom_tool_'+tool.md_tool_type)
					.tmpl(tool).appendTo('#menu_custom_holder_'+x+' > .menu_custom_inside');
			}
		}

		*/
		
	}
	/**
	*	Export search form
	*	
	*/
	private function search_form( &$html ){
		
		$html .= '<a href="#" id="search_opener" class="md_search_pattern">&nbsp;</a>';
		$html .= '<form role="search" method="get" id="md_search_form_desktop" class="md_search_form primary-color" action="'.get_site_url().'">
				<div>
					<label class="screen-reader-text" for="s"></label>
					<input class="md_search_input" type="text" value="" name="s" id="s" placeholder="Search..." />
					<input type="submit" class="md_search_submit" value="" />
				</div>
			</form>';
	}

	/**
	*	Export social links
	*/	
	private function social_link( &$html, $provider, $link ){
		
		$html .= '<a href="'.$link.'" class="menu-link menu-social menu-social-'.$provider.'" data-type="social"></a>';
	}
	
    /**
     * Register the ajax calls
     */
/*	public function load_panel_category(){
		
		$response = new stdClass();
		$response->error = 0;
		
		//$response->term_id = isset( $_POST['term'] ) ? $_POST['term'] : 0;
		if ( is_array( $_GET ) ){
			foreach( $_GET as $key => $value ){
				$response->{$key} = $value;
			}
		}
		
		if ( !empty( $response->extra ) ){
			$response->extra = json_decode( stripslashes( $response->extra ) );
		}
		
//		$this->render_posts( $term_id );

		//	BIG LOBBY
		if ( $response->id != 0 ){
			
			$args = array(
				'post_type'		=> 'post',
				'post_status'	=> 'publish',
				'meta_query' 	=> array(array('key' => '_thumbnail_id')),
				'tax_query'		=> array(
					array(
						'taxonomy'	=> $response->type,
						'field'		=> 'term_id',
						'terms'		=> $response->id,
					),
				),
			);
			
			if ( !empty( $response->extra->from_term_id )){
				$args['tax_query'][] = array(
						'taxonomy'	=> 'post_tag',
						'field'		=> 'term_id',
						'terms'		=> $response->extra->from_term_id,
				);
			}
			
			$posts = new WP_Query( $args );
								
			if ( $posts->post_count > 0 ){
				
				$response->count = $posts->post_count;
				$response->posts = $posts->posts;
				
				for ( $i = 0; $i < $posts->post_count; $i++ ){
					
					$img = wp_get_attachment_image_src( get_post_thumbnail_id( $response->posts[$i]->ID ), 'medium_fixed' );
					
					$response->posts[$i]->img_src = $img[0];
					
					$response->posts[$i]->permalink = get_permalink( $response->posts[$i]->ID);
				}
			}
			else{
				$response->error++;
				$response->link = get_term_link( intval( $response->id ), $response->type );
				wp_send_json( $response );		
			}

		//	SMALL LOBBY
		if ( $response->id != 0 && !empty( $response->extra->show_small_thumbs ) ){
			
			$args = array(
				'post_type'		=> 'post',
				'post_status'	=> 'publish',
				'meta_query' 	=> array(array('key' => '_thumbnail_id')),
				'tax_query'		=> array(
					array(
						'taxonomy'	=> $response->type,
						'field'		=> 'term_id',
						'terms'		=> $response->id,
					),
				),
			);
			
			$smallposts = new WP_Query( $args );
			
					
			if ( $smallposts->post_count > 0 ){
				
				$response->count = $smallposts->post_count;
				$response->smallposts = $smallposts->posts;
				
				for ( $i = 0; $i < $smallposts->post_count; $i++ ){
					
					$img = wp_get_attachment_image_src( get_post_thumbnail_id( $response->smallposts[$i]->ID ), 'thumbnail' );
					
					$response->smallposts[$i]->img_src = $img[0];
					
					$response->smallposts[$i]->permalink = get_permalink( $response->smallposts[$i]->ID);
				}
			}
		}
			
			// children categories
			$args = array(
				'hide_empty'        => false, 
				'parent'            => $response->id,
				'child_of'			=> '',//$response->id,
				//'childless'			=> false,
				//'number'			=> 6,
				'hierarchical'      => true, 
			);
			$response->terms = get_terms('category', $args);
			for ( $i = 0; $i < count($response->terms) ; $i++ ){
				
				@$response->terms[$i]->term_link = get_term_link( $response->terms[$i]->term_id );
			}
		}
		else{
			$response->error++;
			$response->message = 'The term id is not defined';
		}
		
		wp_send_json( $response );
	}*/
	
	/*public function load_panel_post( ){
		
		$response = new stdClass();
		$response->error = 0;
		
		//$response->term_id = isset( $_POST['term'] ) ? $_POST['term'] : 0;
		if ( is_array( $_GET ) ){
			foreach( $_GET as $key => $value ){
				$response->{$key} = $value;
			}
		}
		
		if ( !empty( $response->extra ) ){
			$response->extra = json_decode( stripslashes( $response->extra ) );
		}
		
		
		$img = wp_get_attachment_image_src( get_post_thumbnail_id( $response->id ), 'medium_fixed' );
		
		
		$response->post		= get_post( $response->id );
		
		$response->post->image	= $img[0];
		$response->post->post_content = apply_filters('the_content', $response->post->post_content);
		
		wp_send_json( $response );
	}

	public function load_panel_youtube( ){

		$response = new stdClass();
		$response->error = 0;
		
		if ( is_array( $_GET ) ){
			foreach( $_GET as $key => $value ){
				$response->{$key} = $value;
			}
		}
		
		if ( !empty( $response->extra ) ){
			$response->extra = json_decode( stripslashes( $response->extra ) );

			$response->iframe = '<iframe class="menu_youtube_video" src="'.$response->extra->video_url.'?rel=0&autoplay='.$response->extra->autoplay.'&showinfo=0&controls='.$response->extra->show_controls.'" width="853" height="480" frameborder="0" allowfullscreen="allowfullscreen"></iframe>';
		}

		wp_send_json( $response );
	}*/
	
	/*public function load_panel_custom(){
		$response = new stdClass();
		$response->error = 0;
		
		if ( is_array( $_GET ) ){
			foreach( $_GET as $key => $value ){
				$response->{$key} = $value;
			}
		}
		
		if ( !empty( $response->extra ) ){
			$response->extra = json_decode( stripslashes( $response->extra ) );

		}

		wp_send_json( $response );
	}*/
	
	/**
	*	Retrieve posts from database
	*/
	private function retrieve_posts( $term_id ){
		
		$args = array(
			'post_type'		=> 'product',
			'post_status'	=> 'publish',
			'tax_query'		=> array(
				array(
					'taxonomy'	=> 'product_cat',
					'field'		=> 'term_id',
					'terms'		=>  $term_id,
				),
			),
		);
		
		$posts = new WP_Query( $args );
		
		return $posts;
	}

	/**
	*	Render posts html
	*/
	private function render_posts( $term_id ){
		
		$posts = $this->retrieve_posts( $term_id );
		
		if ( $posts->post_count > 1 ){
			
			foreach ( $posts->posts as $post ){
				
				echo $post->post_title;
			}
		}
		
	}
	
	protected function stored_settings(){
		
		$Settings = get_option( 'menudash_values' );
		
		return $Settings;
	}
	
	protected function get_menu( $md_id ){
		
		//$cache = $this->check_cache( $md_id );
		
		/*if ( $cache ){
			
			$results = json_decode( file_get_contents( $cache ) );
			
			return $results;		
		}
		else{*/
			
		global $wpdb;
		
		//	Get the menu itself
		$query = "SELECT * FROM ". $this->table ." WHERE md_id = '".$md_id."'";
		
		$results = $wpdb->get_results($query);
		
		//	Check if the query above returned result (if the menu exists)
		if ( count($results) > 0 ){
		
			$results = array_shift($results);
			
			$results->md_values = json_decode( $results->md_values );
			
			//	Menu ids concentration of the extracted menu
			$concetrate_ids	= array();
			
			$concetrate_ids = @array_merge( 
				$results->md_values->left, 
				$results->md_values->center, 
				$results->md_values->right 
			); 

			//	$concetrate_ids contains all the ids
			if ( is_array( $concetrate_ids) && count($concetrate_ids) > 0 ){
				
				foreach ( $concetrate_ids as &$id ){
					$id = "md_id = '".$id."'";
				}
				//	Form the where clause
				$where = implode( ' OR ', $concetrate_ids );
				
				$query = "SELECT * FROM ". $this->table ." WHERE ". $where;
				
				$items = $wpdb->get_results($query);
				
				foreach( $items as $key => &$item ){
					$key = $item->md_id;
				}
			}
			
	
			//	Final formation of the array
			$aligns = array( 'left', 'center', 'right' );
			//	Foreach menu alignment we parse the md_values of the menu
			foreach ( $aligns as $align ){
				
			  if ( 	isset( $results->md_values->{$align} ) &&
					is_array( $results->md_values->{$align} ) ):		
			
				foreach ( $results->md_values->{$align} as &$value ){

					reset( $items ); 

					while ( $value != current( $items )->md_id &&
					 current( $items ) !== false ){
						 
						if ( next( $items ) === false ){
							break;
						}
					}
					if ( current( $items ) ){
					$value = current( $items )->md_values;}

				}
			  endif;
			}
			
			//$this->create_cache( $results, $md_id );

			return $results; 
			}
		else{
			
			return false;
		}
		//}
	}
	
	//	Check if cache if older than the user's configuration
	private function check_cache( $md_id, $settings ){
		
		if ( isset($settings['enable']) && $settings['enable'] === 'true' ){
			
			$settings['expiry']	= isset( $settings['expiry'] ) ? 3600 : intval( $settings['expiry'] );
			
			$upload_dir = wp_upload_dir();
			//	List the directory
			$ls = glob( $upload_dir['basedir'].'/allincache/md_cache_'.$md_id.'.json' );
			//	If file exists the result must be single
			if ( count( $ls ) == 1 ){
				//	Check the timestamp difference
				if ( time() - filemtime(current( $ls ) )  < $settings['expiry'] ){
					
					return current( $ls ) ;
				}
				else{
					return false;
				}
			}
			else{
				return false;
			}
		}
		else{
			return false;
		}
	}
	
	private function create_cache( $text, $md_id ){
		
		$upload_dir = wp_upload_dir();
		
		if (! file_exists( $upload_dir['basedir'].'/allincache/' )){
			mkdir($upload_dir['basedir'].'/allincache/');
		}
		
		$cache_file = fopen($upload_dir['basedir'].'/allincache/md_cache_'.$md_id.'.json', "w") or die("Unable to open file!");
		
		//$txt = json_encode( $results, JSON_UNESCAPED_UNICODE ); // PHP >= 5.4

		fwrite($cache_file, $text);
		
		fclose($cache_file);		
	}
	
	
	/*private function export_html_scripts(){
		
		//	SIDEBAR SUBCATEGORIES TMPL
		echo '<script id="menu-expanded-sidebar-tmpl" type="text/x-jQuery-tmpl">
				  <li class="menu-expandable">
					  <a href="${term_link}"><h3 class="menu-expanded-title" data-termid="${term_id}">${name} (${count})</h3></a>
				  </li>
			  </script>';
		//	SIDEBAR POST TMPL
		echo '<script id="menu-post-sidebar-tmpl" type="text/x-jQuery-tmpl">
				  <figure class="menu-sidebar-post-img">
					  <a href="${link}"><img src="${image}" /></a>
				  </figure>
				  <div class="menu-sidebar-post-excerpt">
				  	{{html post_excerpt}}
				  </div>
			  </script>';
		//	LOBBY TMPL
		echo '	<script id="menu-expanded-lobby-tmpl" type="text/x-jQuery-tmpl">
					<a href="${permalink}" class="lobby-item primary-color">
					<div class="menu-expanded-item item-scale" title="${post_title}">
						<img src="${img_src}" alt="" />
						<div class="main-listing-highlight">
						<h4 class="main-listing-header">${post_title}</h4>
						</div>
					</div>
					</a>
				</script>';
		//	LOBBY POST TMPL
		echo '<script id="menu-post-lobby-tmpl" type="text/x-jQuery-tmpl">
				  <h2 class="lobby-header">${post_title}</h2>
				  <div class="menu-post-lobby-content">
				  	{{html post_content}}
				  </div>
			  </script>';
		//	SMALL LOBBY TMPL
		echo '	<script id="menu-expanded-small-lobby-tmpl" type="text/x-jQuery-tmpl">
					<a href="${permalink}" class="lobby-item primary-color">
					<div class="menu-expanded-product-item">
						<img src="${img_src}" alt="" width="75" title="${post_title}"/>
					</div>
					</a>
				</script>';
		
		echo '<script id="menu_custom_tool_holder"  type="text/x-jQuery-tmpl">
			<div id="menu_custom_holder_${column}" class="menu-custom-holder menu-custom-holder-${total_columns}">
				<div class="menu_custom_inside">
				</div>
			</div>
			</script>';
			
		echo '<script id="menu_custom_tool_header"  type="text/x-jQuery-tmpl">
			<div class="menu_custom_item menu_custom_item_header">
				<h3 class="md_tool_header_text">${md_tool_header_text}</h3>
			</div>
			</script>';
			
		echo '<script id="menu_custom_tool_link"  type="text/x-jQuery-tmpl">
			<div class="menu_custom_item menu_custom_item_link">
				<a href="${md_tool_link_url}" class="md_tool_link_html_link">
					<p class="md_tool_link_html_primary">${md_tool_link_primary}</p>
					<p class="md_tool_link_html_secondary">${md_tool_link_secondary}</p>
				</a>
			</div>
			</script>';

		echo '<script id="menu_custom_tool_paragraph"  type="text/x-jQuery-tmpl">
			<div class="menu_custom_item menu_custom_item_paragraph">
				<p class="md_tool_paragraph">${md_tool_paragraph_text}</p>
			</div>
			</script>';
			
		echo '<script id="menu_custom_tool_image"  type="text/x-jQuery-tmpl">
			<div class="menu_custom_item menu_custom_item_image">
				<img src="${md_tool_image_url}" class="img_responsive" />
			</div>
			</script>';
			
		echo '<script id="menu_custom_tool_map"  type="text/x-jQuery-tmpl">
			<div class="menu_custom_item menu_custom_item_map">
				<img src="http://maps.googleapis.com/maps/api/staticmap?center=${md_tool_map_address};zoom=${md_tool_map_zoom}&amp;scale=false&amp;size=600x400&amp;maptype=roadmap&amp;format=jpg&amp;visual_refresh=true&markers=size:mid%7Ccolor:red%7Clabel:1%7C${md_tool_map_address}">
			</div>
			</script>';
			
		echo '<script id="menu_custom_tool_youtube"  type="text/x-jQuery-tmpl">
			<div class="menu_custom_item">
				<iframe src="${md_tool_youtube_url}?autoplay=0&showinfo=0&controls=0" frameborder="0" allowfullscreen=""></iframe>
			</div>
			</script>';
		
	}*/
}

endif; 

All_In_Menu::init();