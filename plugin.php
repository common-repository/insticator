<?php
/**
 * Insticator WordPress Widget
 *
 * The Insticator Widget allows to integrate the insticator embed in an easy way.
 *
 * @package   Insticator_Embed
 * @author    Insticator <hello@insticator.com>
 * @license   GPL-2.0+
 * @link      https://insticator.com
 * @copyright 2015 Insticator
 *
 * @wordpress-plugin
 * Plugin Name:       Insticator Embed
 * Plugin URI:        https://www.insticator.com
 * Description:       The Insticator plugin allows you to create an embed filled with interactive and related content such as polls and trivia into into your WordPress site in seconds.
 * Version:           9.0
 * Author:            Insticator
 * Author URI:        https://insticator.com
 * Text Domain:       insticator-embed
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /lang
 * GitHub Plugin URI: https://github.com/Insticator
 */

 // Prevent direct file access
if ( ! defined ( 'ABSPATH' ) ) {
	exit;
}

//require('Insticator_function.php');

require(plugin_dir_path( __FILE__ ).'Insticator_function.php');

register_activation_hook( __FILE__ , 'activate');

class Insticator_Embed extends WP_Widget {

        /**
         * The variable name is used as the text domain when internationalizing strings
         * of text. Its value should match the Text Domain file header in the main
         * widget file.
         *
         * @since    1.0
         *
         * @var      string
         */
  	protected $widget_slug = 'insticator-embed';
	protected $getSiteInfoApiUrl = 'https://dashboard.insticator.com/wordpressplugin/getsitedetails?siteURL=';
	protected $getEmbedCodeApiUrl = 'https://dashboard.insticator.com/wordpressplugin/getembedcode?embedUUID=';

	/*--------------------------------------------------*/
	/* Constructor
	/*--------------------------------------------------*/

	/**
	 * Specifies the classname and description, instantiates the widget,
	 * loads localization files, and includes necessary stylesheets and JavaScript.
	 */
	public function __construct() {
            // load plugin text domain
            add_action( 'init', array( $this, 'widget_textdomain' ) );

            parent::__construct(
                $this->get_widget_slug(),
                __( 'Insticator Embed', $this->get_widget_slug() ),
                array(
                    'classname'  => $this->get_widget_slug().'-class',
                    'description' => __( 'Insticator embed.', $this->get_widget_slug() )
                )
            );

            // Hooks fired when the Widget is deactivated
            register_deactivation_hook( __FILE__ , array( &$this, 'deactivate' ) );

            // Register admin styles and scripts
            add_action( 'admin_print_styles', array( $this, 'register_admin_styles' ) );
            add_action( 'admin_enqueue_scripts', array( $this, 'register_admin_scripts' ) );

            // Added hooks for the operatration on wp_head
            add_action( 'wp_head', array( $this, 'insertHeader') );

            // Refreshing the widget's cached output with each new post
            add_action( 'save_post',    array( $this, 'flush_widget_cache' ) );
            add_action( 'deleted_post', array( $this, 'flush_widget_cache' ) );
            add_action( 'switch_theme', array( $this, 'flush_widget_cache' ) );
	} // end constructor

        /**
         * Return the widget slug.
         *
         * @since    1.0
         *
         * @return    Plugin slug variable.
         */
        public function get_widget_slug() {
            return $this->widget_slug;
        }

	/*--------------------------------------------------*/
	/* Associate functions defined for the the use of API calling and database operation
	/*--------------------------------------------------*/
	/**
	 * Function used to insert the embed header part to the header, triggered by wp_head
	 *
	 */
	public function insertHeader() {
            $this->output();
	}

	/**
	 * Helper function for the function insertHeader()
	 */
	public function output() {
            $meta = get_option('Insticator_headerCode');
            if (empty($meta)) {
                return;
            }
            if (trim($meta) == '') {
                return;
            }
            echo stripslashes($meta);
	}

	/**
	 * Function used to insert the embed body part to the body
	 */
	public function insertBody() {
            $meta = get_option("Insticator_bodyCode");
            if (empty($meta)) {
                return;
            }
            if (trim($meta) == '') {
                return;
            }
            echo $meta;
	 }


	/**
	 * Used to get information by calling API
	 *
	 * @param url: API of embed server
	 */

	public function callAPI($url) {
            if (isset($url)) {
                $jsonResult = wp_remote_get($url);
                $body = $jsonResult['body'];
                $result = json_decode($body, true);
                return $result;
            }
	}

	/**
	 * Used to get siteUUID and clientUUID information from server with API, the siteUUID
	 * , clientUUID and the embedList will be stored in the database of wordpress
	 */
	public function getUUIDFromAPI() {
            $siteurl = get_option('siteurl');
            $url = $this->getCombinedURL($siteurl, $this->getSiteInfoApiUrl);
            $result = $this->callAPI($url);

            if (empty($result)) {
                $result = $this->callAPI($this->getCombinedURL(getUrlForSpecialCase($siteurl), $this->getSiteInfoApiUrl));
            }
            $jsonEmbedList = json_encode($result['embedList']);
            $result['siteURL'] = $siteurl;
            update_option('Insticator_siteUUID', $result['siteUUID']);
            update_option('Insticator_embedList', addslashes($jsonEmbedList));

            return $result;
 	}

	/**
	 * Helper function that used to combine site url and the the api url
	 * @since 8.1
 	 */
	public function getCombinedURL($siteurl, $getSiteInfoApiUrl) {
            return $getSiteInfoApiUrl.$siteurl;
	}

	/**
	 * Function used to get embed code(header and body part) from the server and store them into the database)
	 *
	 * @param embedUUID: embedUUID that is chosen by clients.
	 * @param async: client decide the embed to be loaded synchronized or asynchronized
	 */
	public function storeEmbed($embedUUID) {
            if (isset($embedUUID)) {
                $isAsync = 'ASYNC';
                $url = $this->getEmbedCodeApiUrl.$embedUUID.'&codeType='.$isAsync;
                $result = $this->callAPI($url);
                update_option('Insticator_headerCode', $result['headerCode']);
                update_option('Insticator_bodyCode', $result['bodyCode']);
                return $result;
            }
	 }

        /**
         * Clear the information stored in the database.
         *
         * @since    2.0
         */
	public function cleanOptionCache() {
            $clientUUID = "Insticator_clientUUID";
            $siteUUID = "Insticator_siteUUID";
            $embedList = "Insticator_embedList";
            $async = "Insticator_async";
            $headerCode = "Insticator_headerCode";
            $bodyCode = "Insticator_bodyCode";
            $embedUUID = "Insticator_embedUUID";

            delete_option($clientUUID);
            delete_option($siteUUID);
            delete_option($embedList);
            delete_option($headerCode);
            delete_option($bodyCode);
            delete_option($embedUUID);
	}

	/*--------------------------------------------------*/
	/* Widget API Functions
	/*--------------------------------------------------*/

	/**
	 * Outputs the content of the widget.
	 *
	 * @param array args  The array of form elements
	 * @param array instance The current instance of the widget
	 */
	public function widget( $args, $instance ) {
            // Check if there is a cached output
            $cache = wp_cache_get( $this->get_widget_slug(), 'widget' );

            if ( !is_array( $cache ) ) {
                $cache = array();
            }

            if ( ! isset ( $args['widget_id'] ) ) {
                $args['widget_id'] = $this->id;
            }

            if ( isset ( $cache[ $args['widget_id'] ] ) ) {
                return print $cache[ $args['widget_id'] ];
            }

            extract( $args, EXTR_SKIP );

            $widget_string = $before_widget;

            ob_start();
            $this->insertBody();
            $widget_string .= ob_get_clean();
            $widget_string .= $after_widget;


            $cache[ $args['widget_id'] ] = $widget_string;

            wp_cache_set( $this->get_widget_slug(), $cache, 'widget' );

            print $widget_string;

	} // end widget


	public function flush_widget_cache() {
            wp_cache_delete( $this->get_widget_slug(), 'widget' );
	}
	/**
	 * Processes the widget's options to be saved.
	 *
	 * @param array new_instance The new instance of values to be generated via the update.
	 * @param array old_instance The previous instance of values before the update.
	 */
	public function update( $new_instance, $old_instance ) {
            $instance = $old_instance;

            // Consider to apply strip_tags() to all the $new_instance['']
            $instance['embedUUID']  = $new_instance['embedUUID'];
            update_option('Insticator_embedUUID', $instance['embedUUID']);
            $newResult = $this->storeEmbed($instance['embedUUID']);

            return $instance;
	} // end widget

	/**
	 * Generates the administration form for the widget.
	 *
	 * @param array instance The array of keys and values for the widget.
	 */
	public function form( $instance ) {
            $default_settings = array(
                'embedUUID'=>'',
              );

            $instance = wp_parse_args(
                (array) $instance,
                $default_settings
              );

            include( plugin_dir_path(__FILE__) . 'views/admin.php' );
	} // end form

	/*--------------------------------------------------*/
	/* Public Functions
	/*--------------------------------------------------*/

	/**
	 * Loads the Widget's text domain for localization and translation.
	 */
	public function widget_textdomain() {
            load_plugin_textdomain( $this->get_widget_slug(), false, plugin_dir_path( __FILE__ ) . 'lang/' );
	} // end widget_textdomain

	/**
	 * Fired when the plugin is deactivated.
	 * @since 3.0
	 * @param boolean $network_wide True if WPMU superadmin uses "Network Activate" action, false if WPMU is disabled or plugin is activated on an individual blog
	 */
	public function deactivate( $network_wide ) {
            $this->cleanOptionCache();
	} // end deactivate

	/**
	 * Registers and enqueues admin-specific styles.
	 */
	public function register_admin_styles() {
            wp_enqueue_style( $this->get_widget_slug().'-admin-styles', plugins_url( 'css/admin.css', __FILE__ ) );
	} // end register_admin_styles

	/**
	 * Registers and enqueues admin-specific JavaScript.
	 */
	public function register_admin_scripts() {
            wp_enqueue_script( $this->get_widget_slug().'-admin-script', plugins_url( 'js/admin.js', __FILE__ ), array('jquery') );
	} // end register_admin_scripts

} // end class

// Register "Insticator_Embed" to the widget while widget init
add_action( 'widgets_init', create_function( '', 'register_widget("Insticator_Embed");' ) );
