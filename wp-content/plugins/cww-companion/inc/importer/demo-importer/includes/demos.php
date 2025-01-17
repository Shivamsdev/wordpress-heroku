<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Start Class
if ( ! class_exists( 'CWW_Demos' ) ) {

	class CWW_Demos {

		/**
		 * The demo content URL.
		 * @var     object
		 * @access  public
		 * @since   1.0.8
		 */
		

		/**
		 * Start things up
		 */
		public function __construct() {

			// Return if not in admin
			if ( ! is_admin() || is_customize_preview() ) {
				return;
			}
			
			$theme = get_stylesheet();
			

			// Import demos page
			if ( version_compare( PHP_VERSION, '5.4', '>=' ) ) {
				require_once( CWW_COMP_PATH . '/inc/importer/demo-importer/classes/importers/class-helpers.php' );
				require_once( CWW_COMP_PATH . '/inc/importer/demo-importer/classes/class-install-demos.php' );
			}

			// Start things
			add_action( 'admin_init', array( $this, 'init' ) );

			// Demos scripts
			add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ) );

			// Allows xml uploads
			add_filter( 'upload_mimes', array( $this, 'allow_xml_uploads' ) );

			// Demos popup
			add_action( 'admin_footer', array( $this, 'popup' ) );

		}

		/**
		 * Register the AJAX methods
		 *
		 * @since 1.0.0
		 */
		public function init() {

			// Demos popup ajax
			add_action( 'wp_ajax_cww_ajax_get_demo_data', array( $this, 'ajax_demo_data' ) );
			add_action( 'wp_ajax_cww_ajax_required_plugins_activate', array( $this, 'ajax_required_plugins_activate' ) );

			// Get data to import
			add_action( 'wp_ajax_cww_ajax_get_import_data', array( $this, 'ajax_get_import_data' ) );

			// Import XML file
			add_action( 'wp_ajax_cww_ajax_import_xml', array( $this, 'ajax_import_xml' ) );

			// Import customizer settings
			add_action( 'wp_ajax_cww_ajax_import_theme_settings', array( $this, 'ajax_import_theme_settings' ) );

			// Import Theme options (For pro)
			add_action( 'wp_ajax_cww_ajax_import_theme_options', array( $this, 'ajax_import_theme_options' ) );

			// Import widgets
			add_action( 'wp_ajax_cww_ajax_import_widgets', array( $this, 'ajax_import_widgets' ) );

			// Import sliders
			add_action( 'wp_ajax_cww_ajax_import_sliders', array( $this, 'ajax_import_sliders' ) );

			// After import
			add_action( 'wp_ajax_cww_after_import', array( $this, 'ajax_after_import' ) );

		}

		/**
		 * Load scripts
		 *
		 * @since 1.0.8
		 */
		public static function scripts( $hook_suffix ) {
			
			if ( ('appearance_page_cww-install-demos' == $hook_suffix) || ('appearance_page_cww-welcome' == $hook_suffix) || ('appearance_page_welcome-page' == $hook_suffix) ) {

				// CSS
				wp_enqueue_style( 'cww-demos-style', CWW_COMP_URL.'/inc/importer/demo-importer/assets/css/demos.css' );

				// JS
				wp_enqueue_script( 'cww-demos-js', CWW_COMP_URL.'/inc/importer/demo-importer/assets/js/demos.js', array( 'jquery', 'wp-util', 'updates' ), '1.0', true );

				wp_localize_script( 'cww-demos-js', 'cwwDemos', array(
					'ajaxurl' 					=> admin_url( 'admin-ajax.php' ),
					'demo_data_nonce' 			=> wp_create_nonce( 'get-demo-data' ),
					'cww_import_data_nonce' 	=> wp_create_nonce( 'cww_import_data_nonce' ),
					'content_importing_error' 	=> esc_html__( 'There was a problem during the importing process resulting in the following error from your server:', 'cww-companion' ),
					'button_activating' 		=> esc_html__( 'Activating', 'cww-companion' ) . '&hellip;',
					'button_active' 			=> esc_html__( 'Active', 'cww-companion' ),
				) );

			}

		}

		/**
		 * Allows xml uploads so we can import from server
		 *
		 * @since 1.0.0
		 */
		public function allow_xml_uploads( $mimes ) {
			$mimes = array_merge( $mimes, array(
				'xml' 	=> 'application/xml'
			) );
			return $mimes;
		}

		/**
		 * Get demos data to add them in the Demo Import and plugins
		 *
		 * @since 1.0.8
		 */
		public static function get_demos_data() {

			// Return
			
			$theme = get_stylesheet();
			
			
		    require CWW_COMP_PATH .'/inc/importer/'.$theme.'/demo-config.php';


			return apply_filters( 'cww_demos_data', $data );

		}

		/**
		 * Get the category list of all categories used in the predefined demo imports array.
		 *
		 * @since 1.0.8
		 */
		public static function get_demo_all_categories( $demo_imports ) {
			$categories = array();

			foreach ( $demo_imports as $item ) {
				if ( ! empty( $item['categories'] ) && is_array( $item['categories'] ) ) {
					foreach ( $item['categories'] as $category ) {
						$categories[ sanitize_key( $category ) ] = $category;
					}
				}
			}

			if ( empty( $categories ) ) {
				return false;
			}

			return $categories;
		}

		/**
		 * Return the concatenated string of demo import item categories.
		 * These should be separated by comma and sanitized properly.
		 *
		 * @since 1.0.8
		 */
		public static function get_demo_item_categories( $item ) {
			$sanitized_categories = array();

			if ( isset( $item['categories'] ) ) {
				foreach ( $item['categories'] as $category ) {
					$sanitized_categories[] = sanitize_key( $category );
				}
			}

			if ( ! empty( $sanitized_categories ) ) {
				return implode( ',', $sanitized_categories );
			}

			return false;
		}

	    /**
	     * Demos popup
	     *
		 * @since 1.0.8
	     */
	    public static function popup() {
	    	global $pagenow;
	    	$page = isset($_GET['page']) ? $_GET['page'] :'';

	        // Display on the demos pages
	        if ( ( 'themes.php' == $pagenow && 'cww-install-demos' == $page)
	            || ( 'themes.php' == $pagenow && 'cww-welcome' == $page)  
	            || ( 'themes.php' == $pagenow && 'welcome-page' == $page) ) { ?>
	        
		        
		        <div id="cww-demo-popup-wrap">
					<div class="cww-demo-popup-container">
						<div class="cww-demo-popup-content-wrap">
							<div class="cww-demo-popup-content-inner">
								<a href="#" class="cww-demo-popup-close">×</a>
								<div id="cww-demo-popup-content"></div>
							</div>
						</div>
					</div>
					<div class="cww-demo-popup-overlay"></div>
				</div>

	    	<?php
	    	}
	    }

		/**
		 * Demos popup ajax.
		 *
		 * @since 1.0.8
		 */
		public function ajax_demo_data() {

			if ( ! wp_verify_nonce( $_GET['demo_data_nonce'], 'get-demo-data' ) ) {
				die( 'This action was stopped for security purposes.' );
			}

			// Database reset url
			if ( is_plugin_active( 'wordpress-database-reset/wp-reset.php' ) ) {
				$plugin_link 	= admin_url( 'tools.php?page=database-reset' );
			} else {
				$plugin_link 	= admin_url( 'plugin-install.php?s=Wordpress+Database+Reset&tab=search' );
			}

			// Get all demos
			$demos = self::get_demos_data();

			// Get selected demo
			$demo = $_GET['demo_name'];

			// Get required plugins
			$plugins = $demos[$demo][ 'required_plugins' ];

			// Get free plugins
			$free = $plugins[ 'free' ];

			// Get premium plugins
			$premium = empty($plugins[ 'premium' ]) ? '' : $plugins[ 'premium' ]; 

			$slider = isset( $demos[$demo]['slider_file'] ) ? $demos[$demo]['slider_file'] : ''; 
			$customizer = isset( $demos[$demo]['theme_settings'] ) ? $demos[$demo]['theme_settings'] : '';
			$theme_options = isset( $demos[$demo]['theme_options'] ) ? $demos[$demo]['theme_options'] : '';

			$is_pro = isset( $demos[$demo]['is_premium'] ) ? $demos[$demo]['is_premium'] : false;
			$pro_link = isset( $demos[$demo]['pro_link'] ) ? $demos[$demo]['pro_link'] : '#';
			$purchase_link = isset( $demos[$demo]['purchase_link'] ) ? $demos[$demo]['purchase_link'] : '#';
			?>

			<div id="cww-demo-plugins">

				<h2 class="title"><?php echo sprintf( esc_html__( 'Import the %1$s demo', 'cww-companion' ), esc_attr( $demo ) ); ?></h2>

				<div class="cww-popup-text">

                    <?php if($is_pro == false){ ?>
					<p><?php echo
						sprintf(
							esc_html__( 'Importing demo data allow you to quickly edit everything instead of creating content from scratch. It is recommended uploading sample data on a fresh WordPress install to prevent conflicts with your current content. You can use this plugin to reset your site if needed: %1$sWordpress Database Reset%2$s.', 'cww-companion' ),
							'<a href="'. $plugin_link .'" target="_blank">',
							'</a>'
						); ?></p>

					<div class="cww-required-plugins-wrap">
						<h3><?php esc_html_e( 'Required Plugins', 'cww-companion' ); ?></h3>
						<p><?php esc_html_e( 'For your site to look exactly like this demo, the plugins below need to be activated.', 'cww-companion' ); ?></p>
						<div class="cww-required-plugins oe-plugin-installer">
							<?php
							self::required_plugins( $free, 'free' );
							self::required_plugins( $premium, 'premium' ); ?>
						</div>
					</div>

					<?php }else{?>
						<p><?php echo
						sprintf(
							esc_html__( 'To import this demo you must have Premium version of this theme. %1$sGet Pro Version%2$s.', 'cww-companion' ),
							'<a href="'. $pro_link .'" target="_blank">',
							'</a>'
						); ?></p>
					<?php }?>

				</div>
				<?php if($is_pro == false){ ?>
					<a class="cww-button cww-plugins-next" href="#"><?php esc_html_e( 'Go to the next step', 'cww-companion' ); ?></a>
				<?php }else{?>
					<a class="cww-button cww-plugins-next" href="<?php echo esc_url($purchase_link); ?>" target="_blank" style="background-color: #4CAF50"><?php esc_html_e( 'Purchase Now', 'cww-companion' ); ?></a>
				<?php }?>

			</div>

			<form method="post" id="cww-demo-import-form">

				<input id="cww_import_demo" type="hidden" name="cww_import_demo" value="<?php echo esc_attr( $demo ); ?>" />

				<div class="cww-demo-import-form-types">

					<h2 class="title"><?php esc_html_e( 'Select what you want to import:', 'cww-companion' ); ?></h2>
					
					<ul class="cww-popup-text">
						<li>
							<label for="cww_import_xml">
								<input id="cww_import_xml" type="checkbox" name="cww_import_xml" checked="checked" />
								<strong><?php esc_html_e( 'Import XML Data', 'cww-companion' ); ?></strong> (<?php esc_html_e( 'pages, posts, images, menus, etc...', 'cww-companion' ); ?>)
							</label>
						</li>

                        <?php if($customizer != ''){?>
						<li>
							<label for="cww_theme_settings">
								<input id="cww_theme_settings" type="checkbox" name="cww_theme_settings" checked="checked" />
								<strong><?php esc_html_e( 'Import Customizer Settings', 'cww-companion' ); ?></strong>
							</label>
						</li>
                        <?php }?>

                        <?php if($theme_options != ''){?>
						<li>
							<label for="cww_theme_options">
								<input id="cww_theme_options" type="checkbox" name="cww_theme_options" checked="checked" />
								<strong><?php esc_html_e( 'Import Theme Options', 'cww-companion' ); ?></strong>
							</label>
						</li>
                        <?php }?>

						<li>
							<label for="cww_import_widgets">
								<input id="cww_import_widgets" type="checkbox" name="cww_import_widgets" checked="checked" />
								<strong><?php esc_html_e( 'Import Widgets', 'cww-companion' ); ?></strong>
							</label>
						</li>

						<?php if($slider != ''){?>
						<li>
							<label for="cww_import_sliders">
								<input id="cww_import_sliders" type="checkbox" name="cww_import_sliders" checked="checked" />
								<strong><?php esc_html_e( 'Import Sliders', 'cww-companion' ); ?></strong>
							</label>
						</li>
						<?php }?>
					</ul>

				</div>
				
				<?php wp_nonce_field( 'cww_import_demo_data_nonce', 'cww_import_demo_data_nonce' ); ?>
				<input type="submit" name="submit" class="cww-button cww-import" value="<?php esc_html_e( 'Install this demo', 'cww-companion' ); ?>"  />

			</form>

			<div class="cww-loader">
				<h2 class="title"><?php esc_html_e( 'The import process could take some time, please be patient', 'cww-companion' ); ?></h2>
				<div class="cww-import-status cww-popup-text"></div>
			</div>

			<div class="cww-last">
				<svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52"><circle class="checkmark-circle" cx="26" cy="26" r="25" fill="none"></circle><path class="checkmark-check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"></path></svg>
				<h3><?php esc_html_e( 'Demo Imported!', 'cww-companion' ); ?></h3>
				<a href="<?php echo esc_url( get_home_url() ); ?>"" target="_blank"><?php esc_html_e( 'See the result', 'cww-companion' ); ?></a>
			</div>

			<?php
			die();
		}

		/**
		 * Required plugins.
		 *
		 * @since 1.0.8
		 */
		public function required_plugins( $plugins, $return ) {
			
			foreach ( $plugins as $key => $plugin ) {

				$api = array(
					'slug' 	=> isset( $plugin['slug'] ) ? $plugin['slug'] : '',
					'init' 	=> isset( $plugin['init'] ) ? $plugin['init'] : '',
					'name' 	=> isset( $plugin['name'] ) ? $plugin['name'] : '',
				);

				if ( ! is_wp_error( $api ) ) { // confirm error free

					// Installed but Inactive.
					if ( file_exists( WP_PLUGIN_DIR . '/' . $plugin['init'] ) && is_plugin_inactive( $plugin['init'] ) ) {

						$button_classes = 'button activate-now button-primary';
						$button_text 	= esc_html__( 'Activate', 'cww-companion' );

					// Not Installed.
					} elseif ( ! file_exists( WP_PLUGIN_DIR . '/' . $plugin['init'] ) ) {

						$button_classes = 'button install-now';
						$button_text 	= esc_html__( 'Install Now', 'cww-companion' );

					// Active.
					} else {
						$button_classes = 'button disabled';
						$button_text 	= esc_html__( 'Activated', 'cww-companion' );
					} ?>

					<div class="cww-plugin cww-clr cww-plugin-<?php echo $api['slug']; ?>" data-slug="<?php echo $api['slug']; ?>" data-init="<?php echo $api['init']; ?>">
						<h2><?php echo $api['name']; ?></h2>

						<?php
						// If premium plugins and not installed
						if ( 'premium' == $return
							&& ! file_exists( WP_PLUGIN_DIR . '/' . $plugin['init'] ) ) { 
							    $link = isset($plugin['link']) ? $plugin['link'] : '';
								?>
							<a class="button" href="<?php echo esc_url($link); ?>" target="_blank"><?php esc_html_e( 'Get This Addon', 'cww-companion' ); ?></a>
						<?php
						} else { ?>
							<button class="<?php echo $button_classes; ?>" data-init="<?php echo $api['init']; ?>" data-slug="<?php echo $api['slug']; ?>" data-name="<?php echo $api['name']; ?>"><?php echo $button_text; ?></button>
						<?php
						} ?>
					</div>

				<?php
				}
			}

		}

		/**
		 * Required plugins activate
		 *
		 * @since 1.0.8
		 */
		public function ajax_required_plugins_activate() {

			if ( ! current_user_can( 'install_plugins' ) || ! isset( $_POST['init'] ) || ! $_POST['init'] ) {
				wp_send_json_error(
					array(
						'success' => false,
						'message' => __( 'No plugin specified', 'cww-companion' ),
					)
				);
			}

			$plugin_init = ( isset( $_POST['init'] ) ) ? esc_attr( $_POST['init'] ) : '';
			$activate 	 = activate_plugin( $plugin_init, '', false, true );

			if ( is_wp_error( $activate ) ) {
				wp_send_json_error(
					array(
						'success' => false,
						'message' => $activate->get_error_message(),
					)
				);
			}

			wp_send_json_success(
				array(
					'success' => true,
					'message' => __( 'Plugin Successfully Activated', 'cww-companion' ),
				)
			);

		}

		/**
		 * Returns an array containing all the importable content
		 *
		 * @since 1.0.8
		 */
		public function ajax_get_import_data() {
			check_ajax_referer( 'cww_import_data_nonce', 'security' );

			echo json_encode( 
				array(
					array(
						'input_name' 	=> 'cww_import_xml',
						'action' 		=> 'cww_ajax_import_xml',
						'method' 		=> 'ajax_import_xml',
						'loader' 		=> esc_html__( 'Importing XML Data', 'cww-companion' )
					),

					array(
						'input_name' 	=> 'cww_theme_settings',
						'action' 		=> 'cww_ajax_import_theme_settings',
						'method' 		=> 'ajax_import_theme_settings',
						'loader' 		=> esc_html__( 'Importing Customizer Settings', 'cww-companion' )
					),

					array(
						'input_name' 	=> 'cww_theme_options',
						'action' 		=> 'cww_ajax_import_theme_options',
						'method' 		=> 'ajax_import_theme_options',
						'loader' 		=> esc_html__( 'Importing Theme Options', 'cww-companion' )
					),

					array(
						'input_name' 	=> 'cww_import_widgets',
						'action' 		=> 'cww_ajax_import_widgets',
						'method' 		=> 'ajax_import_widgets',
						'loader' 		=> esc_html__( 'Importing Widgets', 'cww-companion' )
					),

					array(
						'input_name' 	=> 'cww_import_sliders',
						'action' 		=> 'cww_ajax_import_sliders',
						'method' 		=> 'ajax_import_sliders',
						'loader' 		=> esc_html__( 'Importing Slider', 'cww-companion' )
					)
				)
			);

			die();
		}

		/**
		 * Import XML file
		 *
		 * @since 1.0.8
		 */
		public function ajax_import_xml() {
			if ( ! wp_verify_nonce( $_POST['cww_import_demo_data_nonce'], 'cww_import_demo_data_nonce' ) ) {
				die( 'This action was stopped for security purposes.' );
			}

			if ( function_exists( 'ini_get' ) ) {
				if ( 600 < ini_get( 'max_execution_time' ) ) {
					@ini_set( 'max_execution_time', 600 );
				}
				if ( 256 < intval( ini_get( 'memory_limit' ) ) ) {
					@ini_set( 'memory_limit', '256M' );
				}
			} else {
				echo 'ini_get does not exist';
			}

			// Get the selected demo
			$demo_type 			= $_POST['cww_import_demo'];

			// Get demos data
			$demo 				= CWW_Demos::get_demos_data()[ $demo_type ];

			// Content file
			$xml_file 			= isset( $demo['xml_file'] ) ? $demo['xml_file'] : '';

			// Delete the default post and page
			$sample_page 		= get_page_by_path( 'sample-page', OBJECT, 'page' );
			$hello_world_post 	= get_page_by_path( 'hello-world', OBJECT, 'post' );

			if ( ! is_null( $sample_page ) ) {
				wp_delete_post( $sample_page->ID, true );
			}

			if ( ! is_null( $hello_world_post ) ) {
				wp_delete_post( $hello_world_post->ID, true );
			}

			// Import Posts, Pages, Images, Menus.
			$result = $this->process_xml( $xml_file );
			
			if ( is_wp_error( $result ) ) {
				echo json_encode( $result->errors );
			} else {
				echo 'successful import';
			}


			die();
		}

		/**
		 * Import customizer settings
		 *
		 * @since 1.0.8
		 */
		public function ajax_import_theme_settings() {
			if ( ! wp_verify_nonce( $_POST['cww_import_demo_data_nonce'], 'cww_import_demo_data_nonce' ) ) {
				die( 'This action was stopped for security purposes.' );
			}

			// Include settings importer
			include CWW_COMP_PATH . '/inc/importer/demo-importer/classes/importers/class-settings-importer.php';

			// Get the selected demo
			$demo_type 			= $_POST['cww_import_demo'];

			// Get demos data
			$demo 				= CWW_Demos::get_demos_data()[ $demo_type ];

			// Settings file
			$theme_settings 	= isset( $demo['theme_settings'] ) ? $demo['theme_settings'] : '';

			// Import settings.
			
			$settings_importer = new CWW_Settings_Importer();
			$result = $settings_importer->process_import_file( $theme_settings );
			$csetting_import = $this->process_customizer_file($theme_settings);
			
			
			if ( is_wp_error( $result ) ) {
				echo json_encode( $result->errors );
			} else {
				echo 'successful import';
			}

			die();
		}

		public function process_customizer_file( $file ) {
	        global $wp_customize;

			// Get file contents.
			$data = CWW_Demos_Helpers::get_remote( $file );

			// Return from this function if there was an error.
			if ( is_wp_error( $data ) ) {
				return $data;
			}

			// Get file contents and decode
			$raw  = file_get_contents( $file );
			$data = @unserialize( $raw );

			// Import custom options.
			if ( isset( $data['options'] ) ) {

				foreach ( $data['options'] as $option_key => $option_value ) {
					$option = new CEI_Option( $wp_customize, $option_key, array(
					'default'    => '',
					'type'       => 'option',
					'capability' => 'edit_theme_options',
					) );

					$option->import( $option_value );
				}
			}
		}

		/**
		 * Import theme options
		 *
		 * @since 1.0.8
		 */
		public function ajax_import_theme_options() {
			if ( ! wp_verify_nonce( $_POST['cww_import_demo_data_nonce'], 'cww_import_demo_data_nonce' ) ) {
				die( 'This action was stopped for security purposes.' );
			}

			// Include settings importer
			include CWW_COMP_PATH . '/inc/importer/demo-importer/classes/importers/class-theme-options-importer.php';

			// Get the selected demo
			$demo_type 			= $_POST['cww_import_demo'];

			// Get demos data
			$demo 				= CWW_Demos::get_demos_data()[ $demo_type ];

			// Settings file
			$theme_settings 	= isset( $demo['theme_options'] ) ? $demo['theme_options'] : '';

			// Import settings.
			$settings_importer = new CWW_Options_Importer();
			$result = $settings_importer->process_import_file( $theme_options );
			
			if ( is_wp_error( $result ) ) {
				echo json_encode( $result->errors );
			} else {
				echo 'successful import';
			}

			die();
		}

		/**
		 * Import widgets
		 *
		 * @since 1.0.8
		 */
		public function ajax_import_widgets() {
			if ( ! wp_verify_nonce( $_POST['cww_import_demo_data_nonce'], 'cww_import_demo_data_nonce' ) ) {
				die( 'This action was stopped for security purposes.' );
			}

			// Include widget importer
			include CWW_COMP_PATH . '/inc/importer/demo-importer/classes/importers/class-widget-importer.php';

			// Get the selected demo
			$demo_type 			= $_POST['cww_import_demo'];

			// Get demos data
			$demo 				= CWW_Demos::get_demos_data()[ $demo_type ];

			// Widgets file
			$widgets_file 		= isset( $demo['widgets_file'] ) ? $demo['widgets_file'] : '';

			// Import settings.
			$widgets_importer = new CWW_Widget_Importer();
			$result = $widgets_importer->process_import_file( $widgets_file );
			
			if ( is_wp_error( $result ) ) {
				echo json_encode( $result->errors );
			} else {
				echo 'successful import';
			}

			die();
		}

		/**
		* Import smart slider
		*
		*/
		public function ajax_import_sliders() {
			if ( ! wp_verify_nonce( $_POST['cww_import_demo_data_nonce'], 'cww_import_demo_data_nonce' ) ) {
				die( 'This action was stopped for security purposes.' );
			}

			// Include form importer
			include CWW_COMP_PATH . '/inc/importer/demo-importer/classes/importers/class-smartslider-importer.php';

			// Get the selected demo
			$demo_type 			= $_POST['cww_import_demo'];

			// Get demos data
			$demo 				= CWW_Demos::get_demos_data()[ $demo_type ];

			// slider file
			$widgets_file 		= isset( $demo['slider_file'] ) ? $demo['slider_file'] : '';


			// Import settings.
			$slider_importer = new CWW_SmartSlider_Importer();
			$result = $slider_importer->process_import_file( $slider_file );

		    // Clear sample data content from temp slider file
		    $temp_slider = CWW_COMP_PATH . '/inc/importer/demo-importer/classes/importers/slider.ss3';
		    file_put_contents( $temp_slider, '' );

			if ( is_wp_error( $result ) ) {
				echo json_encode( $result->errors );
			} else {
				echo 'successful import';
			}

			die();
		}

		/**
		 * After import
		 *
		 * @since 1.0.8
		 */
		public function ajax_after_import() {
			if ( ! wp_verify_nonce( $_POST['cww_import_demo_data_nonce'], 'cww_import_demo_data_nonce' ) ) {
				die( 'This action was stopped for security purposes.' );
			}

			// If XML file is imported
			if ( $_POST['cww_import_is_xml'] === 'true' ) {

				// Get the selected demo
				$demo_type 			= $_POST['cww_import_demo'];

				// Get demos data
				$demo 				= CWW_Demos::get_demos_data()[ $demo_type ];

				// Elementor width setting
				$elementor_width 	= isset( $demo['elementor_width'] ) ? $demo['elementor_width'] : '';

				// Reading settings
				$homepage_title 	= isset( $demo['home_title'] ) ? $demo['home_title'] : 'Home';
				$blog_title 		= isset( $demo['blog_title'] ) ? $demo['blog_title'] : '';

				// Posts to show on the blog page
				$posts_to_show 		= isset( $demo['posts_to_show'] ) ? $demo['posts_to_show'] : '';

				// If shop demo
				$shop_demo 			= isset( $demo['is_shop'] ) ? $demo['is_shop'] : false;

				//has menus
				$menu_names = isset( $demo['menus'] ) ? $demo['menus'] : array();


				// Assign WooCommerce pages if WooCommerce Exists
				if ( class_exists( 'WooCommerce' ) && true == $shop_demo ) {

					$woopages = array(
						'woocommerce_shop_page_id' 				=> 'Shop',
						'woocommerce_cart_page_id' 				=> 'Cart',
						'woocommerce_checkout_page_id' 			=> 'Checkout',
						'woocommerce_pay_page_id' 				=> 'Checkout &#8594; Pay',
						'woocommerce_thanks_page_id' 			=> 'Order Received',
						'woocommerce_myaccount_page_id' 		=> 'My Account',
						'woocommerce_edit_address_page_id' 		=> 'Edit My Address',
						'woocommerce_view_order_page_id' 		=> 'View Order',
						'woocommerce_change_password_page_id' 	=> 'Change Password',
						'woocommerce_logout_page_id' 			=> 'Logout',
						'woocommerce_lost_password_page_id' 	=> 'Lost Password'
					);

					foreach ( $woopages as $woo_page_name => $woo_page_title ) {

						$woopage = get_page_by_title( $woo_page_title );
						if ( isset( $woopage ) && $woopage->ID ) {
							update_option( $woo_page_name, $woopage->ID );
						}

					}

					// We no longer need to install pages
					delete_option( '_wc_needs_pages' );
					delete_transient( '_wc_activation_redirect' );

				}


			    // Store All Menu
			    $menu_locations = array();

			    foreach ($menu_names as $key => $value) {
			      $menu = get_term_by( 'name', $value, 'nav_menu' );
			      if (isset($menu->term_id)) {
			        $menu_locations[$key] = $menu->term_id;
			      }
			    }

			    // Set Menu If has
			    if (isset($menu_locations)) {
			      set_theme_mod( 'nav_menu_locations', $menu_locations );
			    }

				// Disable Elementor default settings
				update_option( 'elementor_disable_color_schemes', 'yes' );
				update_option( 'elementor_disable_typography_schemes', 'yes' );
				update_option( 'elementor_load_fa4_shim', 'yes' );
			    if ( ! empty( $elementor_width ) ) {
					update_option( 'elementor_container_width', $elementor_width );
				}

				// Assign front page and posts page (blog page).
			    $home_page = get_page_by_title( $homepage_title );
			    $blog_page = get_page_by_title( $blog_title );

			    update_option( 'show_on_front', 'page' );

			    if ( is_object( $home_page ) ) {
					update_option( 'page_on_front', $home_page->ID );
				}

				if ( is_object( $blog_page ) ) {
					update_option( 'page_for_posts', $blog_page->ID );
				}

				// Posts to show on the blog page
			    if ( ! empty( $posts_to_show ) ) {
					update_option( 'posts_per_page', $posts_to_show );
				}

				//status Flag
				$opt_id = 'theme_mods_'.get_stylesheet();
				$options = get_option($opt_id);
				$options[$demo_type] = true;
				update_option( $opt_id, $options );
				
			}

			die();
		}

		/**
		 * Import XML data
		 *
		 * @since 1.0.0
		 */
		public function process_xml( $file ) {
			
			$response = CWW_Demos_Helpers::get_remote( $file );

			// No sample data found
			if ( $response === false ) {
				return new WP_Error( 'xml_import_error', __( 'Can not retrieve sample data xml file. The server may be down at the moment please try again later. If you still have issues contact the theme developer for assistance.', 'cww-companion' ) );
			}

			// Write sample data content to temp xml file
			$temp_xml = CWW_COMP_PATH . '/inc/importer/demo-importer/classes/importers/temp.xml';
			file_put_contents( $temp_xml, $response );

			// Set temp xml to attachment url for use
			$attachment_url = $temp_xml;

			// If file exists lets import it
			if ( file_exists( $attachment_url ) ) {
				$this->import_xml( $attachment_url );
			} else {
				// Import file can't be imported - we should die here since this is core for most people.
				return new WP_Error( 'xml_import_error', __( 'The xml import file could not be accessed. Please try again or contact the theme developer.', 'cww-companion' ) );
			}

		}
		
		/**
		 * Import XML file
		 *
		 * @since 1.0.0
		 */
		private function import_xml( $file ) {

			// Make sure importers constant is defined
			if ( ! defined( 'WP_LOAD_IMPORTERS' ) ) {
				define( 'WP_LOAD_IMPORTERS', true );
			}

			// Import file location
			$import_file = ABSPATH . 'wp-admin/includes/import.php';

			// Include import file
			if ( ! file_exists( $import_file ) ) {
				return;
			}

			// Include import file
			require_once( $import_file );

			// Define error var
			$importer_error = false;

			if ( ! class_exists( 'WP_Importer' ) ) {
				$class_wp_importer = ABSPATH . 'wp-admin/includes/class-wp-importer.php';

				if ( file_exists( $class_wp_importer ) ) {
					require_once $class_wp_importer;
				} else {
					$importer_error = __( 'Can not retrieve class-wp-importer.php', 'cww-companion' );
				}
			}


			if ( ! class_exists( 'WP_Import' ) ) {
				$class_wp_import = CWW_COMP_PATH . '/inc/importer/demo-importer/classes/importers/class-wordpress-importer.php';

				if ( file_exists( $class_wp_import ) ) {
					require_once $class_wp_import;
				} else {
					$importer_error = __( 'Can not retrieve wordpress-importer.php', 'cww-companion' );
				}
			}



			// Display error
			if ( $importer_error ) {
				return new WP_Error( 'xml_import_error', $importer_error );
			} else {

				// No error, lets import things...
				if ( ! is_file( $file ) ) {

					$importer_error = __( 'Sample data file appears corrupt or can not be accessed.', 'cww-companion' );
					return new WP_Error( 'xml_import_error', $importer_error );
				} else {
                    
					$importer = new WP_Import();
					$importer->fetch_attachments = true;
					$importer->import( $file );

					// Clear sample data content from temp xml file
					$temp_xml = CWW_COMP_PATH . '/inc/importer/demo-importer/classes/importers/temp.xml';
					file_put_contents( $temp_xml, '' );
				}
			}
		}

	}

}
new CWW_Demos();