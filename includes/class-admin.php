<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class that provides admin functionalities
 *
 * @package Black_Studio_TinyMCE_Widget
 * @since 2.0.0
 */

if ( ! class_exists( 'Black_Studio_TinyMCE_Admin' ) ) {

	final class Black_Studio_TinyMCE_Admin {

		/**
		 * The single instance of the class
		 *
		 * @var object
		 * @since 2.0.0
		 */
		protected static $_instance = null;

		/**
		 * Array containing the plugin links
		 *
		 * @var array
		 * @since 2.0.0
		 */
		protected $links;

		/**
		 * Return the single class instance
		 *
		 * @return object
		 * @since 2.0.0
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Class constructor
		 *
		 * @uses add_action()
		 * @uses add_filter()
		 * @uses get_option()
		 * @uses get_bloginfo()
		 *
		 * @global object $wp_embed
		 * @return void
		 * @since 2.0.0
		 */
		protected function __construct() {
			// Register action and filter hooks
			add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
			add_action( 'admin_init', array( $this, 'admin_init' ), 20 );
			add_filter( 'wp_default_editor', array( $this, 'editor_accessibility_mode' ) );
		}

		/**
		 * Prevent the class from being cloned
		 *
		 * @return void
		 * @since 2.0.0
		 */
		protected function __clone() {
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; uh?' ), '2.0' );
		}

		/**
		 * Load language files
		 *
		 * @uses load_plugin_textdomain()
		 *
		 * @return void
		 * @since 2.0.0
		 */
		public function load_textdomain() {
			load_plugin_textdomain( 'black-studio-tinymce-widget', false, dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/' );
		}

		/**
		 * Checks if the plugin admin code should be loaded
		 *
		 * @uses apply_filters()
		 *
		 * @global string $pagenow
		 * @return void
		 * @since 2.0.0
		 */
		public function enabled() {
			global $pagenow;
			$enabled_pages = apply_filters( 'black_studio_tinymce_enable_pages', array( 'widgets.php', 'customize.php', 'admin-ajax.php' ) );
			return apply_filters( 'black_studio_tinymce_enable', in_array( $pagenow, $enabled_pages ) );
		}

		/**
		 * Add actions and filters (only in widgets admin page)
		 *
		 * @uses add_action()
		 * @uses add_filter()
		 * @uses do_action()
		 *
		 * @return void
		 * @since 2.0.0
		 */
		public function admin_init() {
			$this->init_links();
			add_action( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
			if ( $this->enabled() ) {
				add_action( 'admin_head', array( $this, 'enqueue_media' ) );
				add_action( 'admin_print_scripts', array( $this, 'admin_print_scripts' ) );
				add_action( 'admin_print_styles', array( $this, 'admin_print_styles' ) );
				add_action( 'admin_print_footer_scripts', array( $this, 'admin_print_footer_scripts' ) );
				add_action( 'black_studio_tinymce_editor', array( $this, 'editor' ), 10, 3 );
				add_action( 'black_studio_tinymce_before_editor', array( $this, 'display_links' ) ); // consider donating if you remove links
				add_filter( 'wp_editor_settings', array( $this, 'editor_settings' ), 10, 2 );
				add_action( 'admin_print_scripts', array( $this, 'pointer_load' ) );
				add_filter( 'black_studio_tinymce_admin_pointers-widgets', array( $this, 'pointer_register' ) );
				do_action( 'black_studio_tinymce_load' );
			}
		}

		/**
		 * Instantiate tinyMCE editor
		 *
		 * @uses add_thickbox()
		 * @uses wp_enqueue_media()
		 *
		 * @return void
		 * @since 2.0.0
		 */
		public function enqueue_media() {
			// Add support for thickbox media dialog
			add_thickbox();
			// New media modal dialog (WP 3.5+)
			if ( function_exists( 'wp_enqueue_media' ) ) {
				wp_enqueue_media();
			}
		}

		/**
		 * Enqueue styles
		 *
		 * @uses wp_enqueue_style()
		 * @uses Black_Studio_TinyMCE_Plugin::enqueue_style()
		 *
		 * @return void
		 * @since 2.0.0
		 */
		public function admin_print_styles() {
			wp_enqueue_style( 'wp-jquery-ui-dialog' );
			wp_enqueue_style( 'editor-buttons' );
			$this->enqueue_style();
		}

		/**
		 * Helper function to enqueue style
		 *
		 * @uses apply_filters()
		 * @uses wp_enqueue_style()
		 * @uses plugins_url()
		 * @uses SCRIPT_DEBUG
		 *
		 * @return void
		 * @since 2.0.0
		 */
		public function enqueue_style() {
			$style = apply_filters( 'black-studio-tinymce-widget-style', 'black-studio-tinymce-widget' );
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
			wp_enqueue_style(
				$style,
				plugins_url( 'css/' . $style . $suffix. '.css', dirname( __FILE__ ) ),
				array(),
				bstw()->get_version()
			);
		}

		/**
		 * Enqueue header scripts
		 *
		 * @uses wp_enqueue_script()
		 * @uses do_action()
		 *
		 * @return void
		 * @since 2.0.0
		 */
		public function admin_print_scripts() {
			wp_enqueue_script( 'media-upload' );
			wp_enqueue_script( 'wplink' );
			wp_enqueue_script( 'wpdialogs-popup' );
			$this->enqueue_script();
			$this->localize_script();
			do_action( 'wp_enqueue_editor', array( 'tinymce' => true ) );
		}

		/**
		 * Helper function to enqueue script
		 *
		 * @uses apply_filters()
		 * @uses wp_enqueue_script()
		 * @uses plugins_url()
		 * @uses SCRIPT_DEBUG
		 *
		 * @return void
		 * @since 2.0.0
		 */
		public function enqueue_script() {
			$script = apply_filters( 'black-studio-tinymce-widget-script', 'black-studio-tinymce-widget' );
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
			wp_enqueue_script(
				$script,
				plugins_url( 'js/' . $script . $suffix . '.js', dirname( __FILE__ ) ),
				array( 'jquery', 'editor', 'quicktags' ),
				bstw()->get_version(),
				true
			);
		}

		/**
		 * Helper function to enqueue localized script
		 *
		 * @uses apply_filters()
		 * @uses wp_localize_script()
		 *
		 * @return void
		 * @since 2.0.0
		 */
		public function localize_script() {
			$container_selectors = apply_filters( 'black_studio_tinymce_container_selectors', array(  'div.widget', 'div.widget-inside' ) );
			$activate_events = apply_filters( 'black_studio_tinymce_activate_events', array() );
			$deactivate_events = apply_filters( 'black_studio_tinymce_deactivate_events', array() );
			$data = array(
				'container_selectors' => implode( ', ', $container_selectors ),
				'activate_events' => $activate_events,
				'deactivate_events' => $deactivate_events,
				/* translators: error message shown when a duplicated widget ID is detected */
				'error_duplicate_id' => __( 'ERROR: Duplicate widget ID detected. To avoid content loss, please create a new widget with the same content and then delete this one.', 'black-studio-tinymce-widget' )
			);
			wp_localize_script( apply_filters( 'black-studio-tinymce-widget-script', 'black-studio-tinymce-widget' ), 'bstw_data', $data );
		}

		/**
		 * Enqueue footer scripts
		 *
		 * @return void
		 * @since 2.0.0
		 */
		public function admin_print_footer_scripts() {
			$this->editor( '', 'black-studio-tinymce-widget', 'black-studio-tinymce-widget' );
		}

		/**
		 * Output the visual editor
		 *
		 * @uses wp_editor()
		 *
		 * @param string $text
		 * @param string $editor_id
		 * @param string $name
		 * @return void
		 * @since 2.0.0
		 */
		public function editor( $text, $editor_id, $name = '' ) {
			wp_editor( $text, $editor_id, $this->editor_settings( array( 'textarea_name' => $name ), $editor_id ) );
		}

		/**
		 * Set editor settings
		 *
		 * @param mixed[] $settings
		 * @param string $editor_id
		 * @return mixed[]
		 * @since 2.0.0
		 */
		public function editor_settings( $settings, $editor_id ) {
			if ( strstr( $editor_id, 'black-studio-tinymce' ) ) {
				$settings['default_editor'] = 'tmce';
				$settings['tinymce'] = array( 'wp_skip_init' => true, 'add_unload_trigger' => false );
				$settings['editor_height'] = 350;
			}
			return $settings;
		}

		/**
		 * Edit widgets in accessibility mode
		 *
		 * @global string $pagenow
		 * @param string $editor
		 * @return string
		 * @since 2.0.0
		 */
		public function editor_accessibility_mode( $editor ) {
			global $pagenow;
			if ( $pagenow == 'widgets.php' && isset( $_GET['editwidget'] ) && strpos( $_GET['editwidget'], 'black-studio-tinymce' ) === 0 ) {
				$editor = 'html';
			}
			return $editor;
		}

		/**
		 * Initialize plugin links
		 *
		 * @return void
		 * @since 2.0.0
		 */
		public function init_links() {
			$this->links = array(
				/* translators: text used for plugin home link */
				'https://wordpress.org/plugins/black-studio-tinymce-widget/' => __( 'Home', 'black-studio-tinymce-widget' ),
				/* translators: text used for support faq link */
				'https://wordpress.org/plugins/black-studio-tinymce-widget/faq/' => __( 'FAQ', 'black-studio-tinymce-widget' ),
				/* translators: text used for support forum link */
				'https://wordpress.org/support/plugin/black-studio-tinymce-widget' => __( 'Support', 'black-studio-tinymce-widget' ),
				/* translators: text used for reviews link */
				'https://wordpress.org/support/view/plugin-reviews/black-studio-tinymce-widget' => __( 'Rate', 'black-studio-tinymce-widget' ),
				/* translators: text used for follow on twitter link */
				'https://twitter.com/blackstudioita' => __( 'Follow', 'black-studio-tinymce-widget' ),
				/* translators: text used for donation link */
				'http://www.blackstudio.it/en/wordpress-plugins/black-studio-tinymce-widget/' => __( 'Donate', 'black-studio-tinymce-widget' ),
			);
		}

		/**
		 * Display plugin links
		 *
		 * @return void
		 * @since 2.0.0
		 */
		public function display_links() {
			echo "\t<div class='bstw-links'>\n";
			echo "\t\t<span class='bstw-links-list'>\n";
			$counter = count( $this->links ) - 1;
			foreach ( $this->links as $url => $label ) {
				$separator = ( $counter-- > 0 ? ' | ' : '' );
				echo "\t\t\t<a href='" . esc_url( $url ) . "' target='_blank'>" . esc_html( $label ) . "</a>$separator\n"; // xss ok
			}
			echo "\t\t</span>\n";
			/* translators: text used for the icon that shows the plugin links */
			echo "\t\t<a class='bstw-links-icon icon16 icon-plugins' href='#' title='" . esc_attr( __( 'About Black Studio TinyMCE Widget plugin', 'black-studio-tinymce-widget' ) ) . "'></a>\n";
			echo "\t</div>\n";
		}

		/**
		 * Show row meta on the plugin screen
		 *
		 * @uses esc_html()
		 * @uses esc_url()
		 *
		 * @param string[] $links
		 * @param string $file
		 * @return string[]
		 * @since 2.0.0
		 */
		public function plugin_row_meta( $links, $file ) {
			if ( $file == bstw()->get_basename() ) {
				foreach ( $this->links as $url => $label ) {
					$links[ $label ] = '<a href="' . esc_url( $url ) . '" target="_blank">' . esc_html( $label ) . '</a>';
				}
			}
			return $links;
		}

		/**
		 * Load admin pointer
		 *
		 * @uses get_current_screen()
		 * @uses apply_filters()
		 * @uses get_user_meta()
		 * @uses get_current_user_id()
		 * @uses wp_enqueue_style()
		 * @uses wp_enqueue_script()
		 * @uses plugins_url()
		 * @uses wp_localize_script()
		 * @uses SCRIPT_DEBUG
		 *
		 * @return void
		 * @since 2.1.0
		 */
		public function pointer_load() {
			$screen = get_current_screen();
			$screen_id = $screen->id;
			$pointers = apply_filters( 'black_studio_tinymce_admin_pointers-' . $screen_id, array() );
			if ( $pointers && is_array( $pointers ) ) {
				$dismissed = explode( ',', (string) get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true ) );
				$valid_pointers = array();
				foreach ( $pointers as $pointer_id => $pointer ) {
					if ( ! in_array( $pointer_id, $dismissed ) ) {
						$pointer['pointer_id'] = $pointer_id;
						$valid_pointers['pointers'][] =  $pointer;
					}
				}
				if ( ! empty( $valid_pointers ) ) {
					wp_enqueue_style( 'wp-pointer' );
					$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
					wp_enqueue_script(
						'black-studio-tinymce-widget-pointer',
						plugins_url( 'js/black-studio-tinymce-widget-pointer' . $suffix . '.js', dirname( __FILE__ ) ),
						array( 'wp-pointer' ),
						bstw()->get_version()
					);
					wp_localize_script( 'black-studio-tinymce-widget-pointer', 'bstw_pointer', $valid_pointers );
				}
			}
		}

		/**
		 * Register admin pointer
		 *
		 * @param mixed[] $pointers
		 * @return mixed[]
		 * @since 2.1.0
		 */
		public function pointer_register( $pointers ) {
			$pointers['black_studio_tinymce_widget'] = array(
				'target' => 'div[id$=black-studio-tinymce-__i__] .widget-top',
				'options' => array(
					'content' => sprintf( '<h3>%s</h3> <p>%s</p>',
						/* translators: title for the dismissable admin pointer tooltip (same as plugin name) */
						__( 'Black Studio TinyMCE Widget', 'black-studio-tinymce-widget'),
						/* translators: text for the dismissable admin pointer tooltip */
						__( 'The Visual Editor widget allows you to insert rich text and media objects in your sidebars', 'black-studio-tinymce-widget' )
					),
					'position' => array( 'edge' => 'left', 'align' => 'middle' )
				)
			);
			return $pointers;
		}


	} // END class Black_Studio_TinyMCE_Admin

} // END class_exists check
