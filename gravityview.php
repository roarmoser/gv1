<?php
/**
 * The GravityView plugin
 *
 * Create directories based on a Gravity Forms form, insert them using a shortcode, and modify how they output.
 *
 * @package   GravityView
 * @author    Zack Katz <zack@katzwebservices.com>
 * @license   ToBeDefined
 * @link      http://www.katzwebservices.com
 * @copyright Copyright 2013, Katz Web Services, Inc.
 *
 * @wordpress-plugin
 * Plugin Name:       GravityView
 * Plugin URI:        http://www.seodenver.com/
 * Description:       Create directories based on a Gravity Forms form, insert them using a shortcode, and modify how they output.
 * Version:           1.0.0
 * Author:            Katz Web Services, Inc.
 * Author URI:        http://www.katzwebservices.com
 * Text Domain:       gravity-view
 * License:           ToBeDefined
 * License URI:       ToBeDefined
 * Domain Path:       /languages
 * GitHub Plugin URI: ToBeDefined
 */

/** If this file is called directly, abort. */
if ( ! defined( 'WPINC' ) ) {
	die;
}

/** Constants */
if ( !defined('GRAVITYVIEW_URL') )
	define( 'GRAVITYVIEW_URL', plugin_dir_url( __FILE__ ) );
if ( !defined('GRAVITYVIEW_DIR') )
	define( 'GRAVITYVIEW_DIR', plugin_dir_path( __FILE__ ) );


/** Register hooks that are fired when the plugin is activated and deactivated. */
if( is_admin() ) {
	register_activation_hook( __FILE__, array( 'GravityView_Plugin', 'activate' ) );
	register_deactivation_hook( __FILE__, array( 'GravityView_Plugin', 'deactivate' ) );
}

/** Load connector functions */
require_once( GRAVITYVIEW_DIR . 'includes/connector-functions.php');

/** Launch plugin */
$gravity_view_plugin = new GravityView_Plugin();


/**
 * GravityView_Plugin main class.
 */
class GravityView_Plugin {
	
	private $admin_notices = array();
	
	public function __construct() {
	
		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
		
		//Load custom post types
		add_action( 'init', array( $this, 'init_setup' ) );
		
		// check if gravityforms is active
		add_action( 'admin_init', array( $this, 'check_gravityforms' ) );
		
		//throw notice messages if needed
		add_action( 'admin_notices', array( $this, 'admin_notice' ) );


		if( is_admin() ) {
		
			add_filter( 'plugin_action_links_'. plugin_basename( __FILE__) , array( $this, 'plugin_action_links' ) );

			add_action( 'plugins_loaded', array( $this, 'backend_actions' ) );
			
		} else {
			
			add_action( 'plugins_loaded', array( $this, 'frontend_actions' ), 0 );
			
		}
		
		
		// Load default templates
		add_action( 'gravityview_init', array( $this, 'register_default_templates' ) );
		
		// Load default widgets
		add_action( 'gravityview_init', array( $this, 'register_default_widgets' ) );
		
		// set the blacklist field types across the entire plugin
		add_filter( 'gravityview_blacklist_field_types', array( $this, 'default_field_blacklist' ), 10 );
		

	}
	
	
	/**
	 * Check if Gravity Forms plugin is active
	 * 
	 * @access public
	 * @return void
	 */
	public function check_gravityforms() {
		
		$gf_status = self::get_plugin_status( 'gravityforms/gravityforms.php' );
		
		if( $gf_status !== true ) {
			// gravityforms is inactive or not installed
			
			//deactivate_plugins( basename(__FILE__) );
			//flush_rewrite_rules();
			
			if( $gf_status == 'inactive' ) {
				$this->admin_notices[] = array( 'class' => 'error', 'message' => sprintf( __( 'GravityView requires Gravity Forms to be active in order to run properly. %sActivate Gravity Forms%s to use the GravityView plugin.', 'gravity-view' ), '<strong><a href="'. wp_nonce_url( admin_url( 'plugins.php?action=activate&plugin=gravityforms/gravityforms.php' ), 'activate-plugin_gravityforms/gravityforms.php') . '">', '</a></strong>' ) );
			} else {
				$this->admin_notices[] = array( 'class' => 'error', 'message' => sprintf( __( 'GravityView requires Gravity Forms to be installed in order to run properly. %sGet Gravity Forms%s today', 'gravity-view' ), '</strong><a href="http://katz.si/gravityforms">' , '</a></strong>' ) );
			}

		}
	}

	
	
	/**
	 * Plugin activate function.
	 * 
	 * @access public
	 * @static
	 * @param mixed $network_wide
	 * @return void
	 */
	public static function activate( $network_wide ) {
		
		self::init_setup();
		
		flush_rewrite_rules();
		
	}
	
	
	/**
	 * Plugin deactivate function.
	 * 
	 * @access public
	 * @static
	 * @param mixed $network_wide
	 * @return void
	 */
	public static function deactivate( $network_wide ) {
		
		flush_rewrite_rules();
		
	}
	
	
	/**
	 * Loads the plugin's translated strings.
	 * 
	 * @access public
	 * @return void
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'gravity-view', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}
	
	
	/**
	 * Modify plugin action links at plugins screen
	 * 
	 * @access public
	 * @static
	 * @param mixed $links
	 * @return void
	 */
	public static function plugin_action_links( $links ) {
		$support_link = 'https://katzwebservices.zendesk.com/hc/en-us/categories/200136096';
		$action = array( '<a href="' . $support_link . '">'. esc_html__( 'Support', 'gravity-view' ) .'</a>' );
		return array_merge( $action, $links );
	}
	
	
	/**
	 * Init plugin components such as register own custom post types
	 * 
	 * @access public
	 * @static
	 * @return void
	 */
	public static function init_setup() {
		
		//Register Custom Post Type - gravityview
		$labels = array(
			'name'                => _x( 'Views', 'Post Type General Name', 'gravity-view' ),
			'singular_name'       => _x( 'View', 'Post Type Singular Name', 'gravity-view' ),
			'menu_name'           => __( 'Views', 'gravity-view' ),
			'parent_item_colon'   => __( 'Parent View:', 'gravity-view' ),
			'all_items'           => __( 'All Views', 'gravity-view' ),
			'view_item'           => __( 'View', 'gravity-view' ),
			'add_new_item'        => __( 'Add New View', 'gravity-view' ),
			'add_new'             => __( 'New View', 'gravity-view' ),
			'edit_item'           => __( 'Edit View', 'gravity-view' ),
			'update_item'         => __( 'Update View', 'gravity-view' ),
			'search_items'        => __( 'Search Views', 'gravity-view' ),
			'not_found'           => __( 'No Views found', 'gravity-view' ),
			'not_found_in_trash'  => __( 'No Views found in Trash', 'gravity-view' ),
		);
		$args = array(
			'label'               => __( 'view', 'gravity-view' ),
			'description'         => __( 'Create views based on a Gravity Forms form', 'gravity-view' ),
			'labels'              => $labels,
			'supports'            => array( 'title', ),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => false,
			'show_in_admin_bar'   => true,
			'menu_position'       => 15,
			/* 'menu_icon'           => 'dashicons-feedback', */
			'menu_icon'           => GRAVITYVIEW_URL . 'images/gravity-view-icon.png',
			'can_export'          => true,
			'has_archive'         => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'rewrite'             => false,
			'capability_type'     => 'page',
		);
		register_post_type( 'gravityview', $args );
		
		
		include_once( GRAVITYVIEW_DIR .'includes/class-frontend-views.php' );
		GravityView_frontend::init_rewrite();
		
		do_action( 'gravityview_init' );
	}
	
	
	/**
	 * Function to launch admin objects
	 * 
	 * @access public
	 * @return void
	 */
	public function backend_actions() {
	
		include_once( GRAVITYVIEW_DIR .'includes/class-admin-views.php' );
		new GravityView_Admin_Views();
		
		include_once( GRAVITYVIEW_DIR .'includes/class-admin-add-shortcode.php' );
		new GravityView_Admin_Add_Shortcode();
		
		include_once( GRAVITYVIEW_DIR .'includes/class-admin-approve-entries.php' );
		new GravityView_Admin_ApproveEntries();
		
	}
	
	
	
	/**
	 * Function to launch frontend objects
	 * 
	 * @access public
	 * @return void
	 */
	public function frontend_actions() {
	
		include_once( GRAVITYVIEW_DIR .'includes/class-template.php' );
		include_once( GRAVITYVIEW_DIR .'includes/class-api.php' );
		include_once( GRAVITYVIEW_DIR .'includes/class-frontend-views.php' );
		
		// Shortcode to render view (directory)
		add_shortcode( 'gravityview', array( 'GravityView_frontend', 'render_view_shortcode' ) );
		add_action( 'init', array( 'GravityView_frontend', 'init_rewrite' ) );
		add_filter( 'query_vars', array( 'GravityView_frontend', 'add_query_vars_filter' ) );
		add_action( 'wp_enqueue_scripts', array( 'GravityView_frontend', 'add_scripts_and_styles' ) );
	}	
	

	function register_default_templates() {
		
		include_once( GRAVITYVIEW_DIR .'includes/default-templates.php' );
		
		$this->gravityview_register_template( 'GravityView_Default_Template_Table' );
		$this->gravityview_register_template( 'GravityView_Default_Template_List' );
		
		
	}
	
	function register_default_widgets() {
		include_once( GRAVITYVIEW_DIR .'includes/default-widgets.php' );
		new GravityView_Widget_Pagination();
		new GravityView_Widget_Page_Links();
		new GravityView_Widget_Search_Bar();
		
	}
	
	
	
	function gravityview_register_template( $class ) {
		new $class();
	}
	
	
	
	
	
	
	/**
	 * List the field types without presentation properties (on a View context)
	 * 
	 * @access public
	 * @return void
	 */
	function default_field_blacklist() {
		return array( 'html', 'section', 'captcha' );
	}
	
	
	/**
	 * Check if specified plugin is active, inactive or not installed
	 * 
	 * @access public
	 * @static
	 * @param string $location (default: '')
	 * @return void
	 */
	static function get_plugin_status( $location = '' ) {
	
		if( ! function_exists('is_plugin_active') ) {
			include_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		}
	
		if( is_plugin_active( $location ) ) {
			return true;
		}
	
		if( !file_exists( trailingslashit( WP_PLUGIN_DIR ) . $location ) ) {
			return false;
		}
	
		if( is_plugin_inactive( $location ) ) {
			return 'inactive';
		}
	}
	
	
	function admin_notice() {
		
		if( empty( $this->admin_notices ) ) {
			return;
		}
		
		foreach( $this->admin_notices as $notice ) {
			
			echo '<div class="'. $notice['class'].'">';
			echo '<p>'. $notice['message'] .'</p>';
			echo '</div>';

		}
		//reset the notices handler
		$this->admin_notices = array();
	}
	
	


} // end class GravityView_Plugin

