<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'CB_Component' ) ) {

	/**
	 * CB Component
	 *
	 * Helps create components that have similar properties and methods.
	 * Gives us radical global values across the app that lets us 
	 * dynamically call and/or create functions and the like.
	 * 
	 * @package ConfettiBits\Core
	 * @since 1.0.0
	 */
	class CB_Component {

		/** Variables *************************************************************/

		/**
		 * Translatable name for the component.
		 *
		 * @var string $name
		 */
		public $name = '';

		/**
		 * Unique ID for the component.
		 *
		 * @package ConfettiBits\Core
		 * @since 1.0.0
		 * 
		 * @var string $id
		 */
		public $id = '';

		/**
		 * Unique slug for the component, for use in query strings and URLs.
		 * 
		 * @package ConfettiBits\Core
		 * @since 1.0.0
		 * 
		 * @var string $slug
		 */
		public $slug = '';

		/**
		 * Does the component need a top-level directory?
		 *
		 * @package ConfettiBits\Core
		 * @since 1.0.0
		 * 
		 * @var bool $has_directory
		 */
		public $has_directory = false;

		/**
		 * The path to the component's files.
		 *
		 * @package ConfettiBits\Core
		 * @since 1.0.0
		 * 
		 * @var string $path
		 */
		public $path = '';

		/**
		 * The WP_Query loop for this component.
		 *
		 * @package ConfettiBits\Core
		 * @since 1.0.0
		 * 
		 * @var WP_Query $query
		 */
		public $query = false;

		/**
		 * The current ID of the queried object.
		 *
		 * @package ConfettiBits\Core
		 * @since 1.0.0
		 * 
		 * @var string $current_id
		 */
		public $current_id = '';

		/**
		 * Callback for formatting notifications.
		 *
		 * @package ConfettiBits\Core
		 * @since 1.0.0
		 * 
		 * @var callable $notification_callback
		 */
		public $notification_callback = '';

		/**
		 * WordPress Toolbar links.
		 *
		 * @package ConfettiBits\Core
		 * @since 1.0.0
		 * 
		 * @var array $admin_menu
		 */
		public $admin_menu = '';

		/**
		 * Placeholder text for component directory search box.
		 *
		 * @package ConfettiBits\Core
		 * @since 1.0.0
		 * 
		 * @var string $search_string
		 */
		public $search_string = '';

		/**
		 * Root slug for the component.
		 *
		 * @package ConfettiBits\Core
		 * @since 1.0.0
		 * 
		 * @var string $root_slug
		 */
		public $root_slug = '';

		/**
		 * Metadata tables for the component (if applicable).
		 *
		 * @package ConfettiBits\Core
		 * @since 1.0.0
		 *
		 * @var array
		 */
		public $meta_tables = array();

		/**
		 * Global tables for the component (if applicable).
		 *
		 * @package ConfettiBits\Core
		 * @since 1.0.0
		 *
		 * @var array
		 */
		public $global_tables = array();

		/**
		 * Query argument for component search URLs.
		 *
		 * @package ConfettiBits\Core
		 * @since 1.0.0
		 * 
		 * @var string
		 */
		public $search_query_arg = 's';

		/** Methods ***************************************************************/

		/**
		 * Component loader.
		 *
		 * @package ConfettiBits\Core
		 * @since 1.0.0
		 *
		 * @param string $id   Unique ID. Letters, numbers, and underscores only.
		 * @param string $name Unique name. This should be a translatable name, eg.
		 *                     __( 'Groups', 'confetti_bits' ).
		 * @param string $path The file path for the component's files. Used by {@link CB_Component::includes()}.
		 * @param array  $params {
		 *     Additional parameters used by the component.
		 *     @type int    $adminbar_myaccount_order Set the position for our menu under the 
		 * 					WP Toolbar's "My Account menu".
		 *     @type array  $features An array of feature names. This is used to load additional files from your
		 * 					component directory and for feature active checks. eg. array( 'awesome' )
		 * 					would look for a file called "cb-{$this->id}-awesome.php" and you could use
		 * 					cb_is_active( $this->id, 'awesome' ) to determine if the feature is active.
		 *     @type string $search_query_arg         String to be used as the query argument in component search URLs.
		 * }
		 */
		public function start( $id = '', $name = '', $path = '', $params = array() ) {

			// Internal identifier of component.
			$this->id = $id;

			// Internal component name.
			$this->name = $name;

			// Path for includes.
			$this->path = $path;

			// Miscellaneous component parameters that need to be set early on.
			if ( ! empty( $params ) ) {
				// Sets the position for our menu under the WP Toolbar's "My Account" menu.
				if ( ! empty( $params['adminbar_myaccount_order'] ) ) {
					$this->adminbar_myaccount_order = (int) $params['adminbar_myaccount_order'];
				}

				// Register features.
				if ( ! empty( $params['features'] ) ) {
					$this->features = array_map( 'sanitize_title', (array) $params['features'] );
				}

				if ( ! empty( $params['search_query_arg'] ) ) {
					$this->search_query_arg = sanitize_title( $params['search_query_arg'] );
				}

				// Set defaults if not passed.
			} else {
				// New component menus are added before the settings menu if not set.
				$this->adminbar_myaccount_order = 90;
			}

			// Move on to the next step.
			$this->setup_actions();
		}

		/**
		 * Set up component global variables.
		 *
		 * @package ConfettiBits\Core
		 * @since 1.0.0
		 *
		 * @param array $args {
		 *     All values are optional.
		 *     @type string   $slug The component slug. Used to construct certain URLs, such as 'friends' in
		 * 							http://example.com/members/joe/friends/. Default: the value of $this->id.
		 *     @type string   $root_slug The component root slug. Note that this value is generally unused if the
		 * 								 component has a root directory (the slug will be overridden by the
		 * 								 post_name of the directory page). Default: the slug of the directory page
		 * 								 if one is found, otherwise an empty string.
		 *     @type bool     $has_directory Set to true if the component requires an associated WordPress page.
		 *     @type callable $notification_callback Optional. The callable function that formats the component's 
		 * 								 notifications.
		 *     @type string   $search_term Optional. The placeholder text in the component directory search box.
		 * 								 Eg, 'Search Groups...'.
		 *     @type array    $global_tables Optional. An array of database table names.
		 *     @type array    $meta_tables Optional. An array of metadata table names.
		 * }
		 */
		public function setup_globals( $args = array() ) {

			/** Slugs ***********************************************************/

			// If a WP directory page exists for the component, it should
			// be the default value of 'root_slug'.
			$default_root_slug = isset( Confetti_Bits()->pages->{$this->id}->slug ) ? 
				Confetti_Bits()->pages->{$this->id}->slug 
				: '';

			$r = wp_parse_args(
				$args,
				array(
					'slug'                  => $this->id,
					'root_slug'             => $default_root_slug,
					'has_directory'         => false,
					'directory_title'       => '',
					'notification_callback' => '',
					'search_string'         => '',
					'global_tables'         => '',
					'meta_tables'           => '',
					'api_endpoints'			=> '',
				)
			);

			/**
			 * Filters the slug to be used for the permalink URI chunk after root.
			 *
			 * @package ConfettiBits\Core
			 * @since 1.0.0
			 *
			 * @param string $value Slug to use in permalink URI chunk.
			 */
			$this->slug = apply_filters( 'cb_' . $this->id . '_slug', $r['slug'] );

			/**
			 * Filters the slug used for root directory.
			 *
			 * @package ConfettiBits\Core
			 * @since 1.0.0
			 *
			 * @param string $value Root directory slug.
			 */
			$this->root_slug = apply_filters( 'cb_' . $this->id . '_root_slug', $r['root_slug'] );

			/**
			 * Filters the component's top-level directory if available.
			 *
			 * @package ConfettiBits\Core
			 * @since 1.0.0
			 *
			 * @param bool $value Whether or not there is a top-level directory.
			 */
			$this->has_directory = apply_filters( 'cb_' . $this->id . '_has_directory', $r['has_directory'] );

			/**
			 * Filters the component's directory title.
			 *
			 * @package ConfettiBits\Core
			 * @since 1.0.0
			 *
			 * @param string $value Title to use for the directory.
			 */
			$this->directory_title = apply_filters( 'cb_' . $this->id . '_directory_title', $r['directory_title'] );

			/**
			 * Filters the placeholder text for search inputs for component.
			 *
			 * @package ConfettiBits\Core
			 * @since 1.0.0
			 *
			 * @param string $value Name to use in search input placeholders.
			 */
			$this->search_string = apply_filters( 'cb_' . $this->id . '_search_string', $r['search_string'] );

			/**
			 * Filters the callable function that formats the component's notifications.
			 *
			 * @package ConfettiBits\Core
			 * @since 1.0.0
			 *
			 * @param string $value Function callback.
			 */
			$this->notification_callback = apply_filters( 'cb_' . $this->id . '_notification_callback', $r['notification_callback'] );

			// Set the global table names, if applicable.
			if ( ! empty( $r['global_tables'] ) ) {
				$this->register_global_tables( $r['global_tables'] );
			}

			// Set the metadata table, if applicable.
			if ( ! empty( $r['meta_tables'] ) ) {
				$this->register_meta_tables( $r['meta_tables'] );
			}

			if ( !empty( $r['api_endpoints'] ) ) {
				$this->api_endpoints = $r['api_endpoints'];
			}

			/** Confetti_Bits ********************************************************/

			// Register this component in the loaded components array.
			Confetti_Bits()->loaded_components[ $this->slug ] = $this->id;

			/**
			 * Fires at the end of the setup_globals method inside CB_Component.
			 *
			 * This is a dynamic hook that is based on the component string ID.
			 *
			 * @package ConfettiBits\Core
			 * @since 1.0.0
			 */
			do_action( 'cb_' . $this->id . '_setup_globals' );
		}

		/**
		 * Include required files.
		 *
		 * Please note that, by default, this method is fired on the cb_include
		 * hook, with priority 8. This is necessary so that core components are
		 * loaded in time to be available to third-party plugins. However, this
		 * load order means that third-party plugins whose main files are
		 * loaded at cb_include with priority 10 (as recommended), will not be
		 * loaded in time for their includes() method to fire automatically.
		 *
		 * For this reason, it is recommended that your plugin has its own
		 * method or function for requiring necessary files. If you must use
		 * this method, you will have to call it manually in your constructor
		 * class, ie
		 *   $this->includes();
		 *
		 * Note that when you pass an array value like 'actions' to includes,
		 * it looks for the following three files (assuming your component is
		 * called 'my_component'):
		 *   - ./actions
		 *   - ./cb-my_component/actions
		 *   - ./cb-my_component/cb-my_component-actions.php
		 *
		 * @package ConfettiBits\Core
		 * @since 1.0.0
		 *
		 * @param array $includes An array of file names, or file name chunks,
		 *                        to be parsed and then included.
		 */
		public function includes( $includes = array() ) {

			// Bail if no files to include.
			if ( ! empty( $includes ) ) {
				$slashed_path = trailingslashit( $this->path );

				// Loop through files to be included.
				foreach ( (array) $includes as $file ) {

					$paths = array(

						// Passed with no extension.
						'cb-' . $this->id . '/cb-' . $this->id . '-' . $file . '.php',
						'cb-' . $this->id . '-' . $file . '.php',
						'cb-' . $this->id . '/' . $file . '.php',

						// Passed with extension.
						$file,
						'cb-' . $this->id . '-' . $file,
						'cb-' . $this->id . '/' . $file,
					);

					foreach ( $paths as $path ) {
						if ( @is_file( $slashed_path . $path ) ) {
							require $slashed_path . $path;
							break;
						}
					}
				}
			}

			/**
			 * Fires at the end of the includes method inside CB_Component.
			 *
			 * This is a dynamic hook that is based on the component string ID.
			 *
			 * @package ConfettiBits\Core
			 * @since 1.0.0
			 */
			do_action( 'cb_' . $this->id . '_includes' );
		}

		/**
		 * Late includes method.
		 *
		 * Components should include files here only on specific pages using
		 * conditionals such as {@link cb_is_current_component()}. Intentionally left
		 * empty.
		 *
		 * @package ConfettiBits\Core
		 * @since 1.0.0
		 */
		public function late_includes() {}

		/**
		 * Sets up REST endpoints for the component.
		 * 
		 * When passed a collection of strings, uses 
		 * cb_ajax_register_api_endpoints() to register
		 * endpoints based on the component.
		 * 
		 * @package ConfettiBits\Core
		 * @since 2.3.1
		 */
		public function register_api_endpoints( $components = [] ) {

			if ( !empty( $components ) ) {
				foreach ( $components as $component ) {
					cb_ajax_register_rest_route($component);
				}
			}

		}

		/**
		 * Enqueues all of our scripts in a clean fashion.
		 * 
		 * From each according to their capability, to each
		 * according to their need.
 		 * Scripts are enqueued and localized using the following
		 * nested array structure: [ 
		 *     $unique_name_for_script => [ 
		 *         $name_of_api_component_to_use => [ $http_method1, $http_method2, ... ],
		 *         'dependencies' => [ $dependency1, $dependency2, ... ]
		 *     ]
		 * ]
		 * This structure is then picked apart to dynamically 
		 * enqueue and localize scripts. This gives us granular
		 * control over who gets access to what API endpoints,
		 * and can perform which actions, based on capability.
		 * 
		 * The scripts will be enqueued as: "cb_{$unique_name_for_script}", 
		 * and will load a corresponding file, with the underscores
		 * replaced with dashes, like so: "cb-{$unique-name-for-script}.js".
		 * This will also load any dependencies found in the
		 * 'dependencies' array.
		 * 
		 * Scripts will then be localized using the same
		 * "cb_{$unique_name_for_script}" identifier, which will 
 		 * become the global name that is usable within the file.
 		 * All API endpoints are localized as: 
		 * "{$endpoint}_{$name_of_api_component}". 
		 * 
		 * So, for example:
		 *     - "cb_participation.get_participation" will return the 
		 *       API endpoint for getting participation entries ONLY when used
		 * 		 within the cb-participation.js file.
		 *     - "cb_core_admin.new_transactions" will return the API endpoint 
		 *       for creating a new transaction ONLY when used within the 
		 * 		 cb-core-admin.js file.
		 * 
		 * To access the API key, use "{$unique_name_for_script}.api_key"
		 * in the {$unique-name-for-script}.js file.
		 * 
		 * @package ConfettiBits\Core
		 * @since 2.3.0
		 */
		public function enqueue_scripts( $components = [] ) {

			if ( empty( $components ) ) {
				return;
			}

			if ( cb_is_confetti_bits_component() ) {
				$cache_bust = 'v1.1';
				$user_id = intval(get_current_user_id());
				$api_key_safe_name = get_option( 'cb_core_api_key_safe_name' );

				foreach( $components as $component => $params ) {

					$localize_params = [];
					$with_dashes = str_replace( '_', '-', $component );

					wp_enqueue_script( 
						"cb_{$component}", 
						CONFETTI_BITS_PLUGIN_URL . "assets/js/cb-{$with_dashes}.js", 
						$params['dependencies'],
						$cache_bust,
						true
					);

					unset( $params['dependencies'] );

					foreach ( $params as $api => $endpoints ) {
						$api_with_dashes = str_replace( '_', '-', $api );
						foreach ( $endpoints as $endpoint ) {
							$localize_params["{$endpoint}_{$api}"] = home_url("wp-json/cb-ajax/v1/{$api_with_dashes}/{$endpoint}");
						}

						$localize_params['api_key'] = $api_key_safe_name;
						$localize_params['user_id'] = $user_id;

					}

					wp_localize_script( "cb_{$component}", "cb_{$component}", $localize_params );

				}
			}
		}

		/**
		 * Set up the actions.
		 *
		 * @package ConfettiBits\Core
		 * @since 1.0.0
		 */
		public function setup_actions() {

			// Setup globals.
			add_action( 'cb_setup_globals', array( $this, 'setup_globals' ), 10 );

			// Set up canonical stack.
			add_action( 'cb_setup_canonical_stack', array( $this, 'setup_canonical_stack' ), 10 );

			// Include required files. Called early to ensure that BP core
			// components are loaded before plugins that hook their loader functions
			// to cb_include with the default priority of 10. This is for backwards
			// compatibility; henceforth, plugins should register themselves by
			// extending this base class.
			add_action( 'cb_include', [ $this, 'includes' ], 8 );

			// Enqueue component scripts.
			add_action( 'cb_enqueue_scripts', [ $this, 'enqueue_scripts' ], 9 );

			// Set up REST API endpoints.
			add_action( 'cb_rest_api_init', [ $this, 'register_api_endpoints'], 10 );

			// Load files conditionally, based on certain pages.
			add_action( 'cb_late_include', array( $this, 'late_includes' ) );

			// Setup navigation.
			add_action( 'cb_setup_nav', array( $this, 'setup_nav' ), 10 );

			// Setup WP Toolbar menus.
			add_action( 'cb_setup_admin_bar', array( $this, 'setup_admin_bar' ), $this->adminbar_myaccount_order );

			// Setup component title.
			add_action( 'cb_setup_title', array( $this, 'setup_title' ), 10 );

			// Setup cache groups.
			add_action( 'cb_setup_cache_groups', array( $this, 'setup_cache_groups' ), 10 );

			// Register post types.
			add_action( 'cb_register_post_types', array( $this, 'register_post_types' ), 10 );

			// Register taxonomies.
			add_action( 'cb_register_taxonomies', array( $this, 'register_taxonomies' ), 10 );

			// Add the rewrite tags.
			add_action( 'cb_add_rewrite_tags', array( $this, 'add_rewrite_tags' ), 10 );

			// Add the rewrite rules.
			add_action( 'cb_add_rewrite_rules', array( $this, 'add_rewrite_rules' ), 10 );

			// Add the permalink structure.
			add_action( 'cb_add_permastructs', array( $this, 'add_permastructs' ), 10 );

			// Allow components to parse the main query.
			add_action( 'cb_parse_query', array( $this, 'parse_query' ), 10 );

			// Generate rewrite rules.
			add_action( 'cb_generate_rewrite_rules', array( $this, 'generate_rewrite_rules' ), 10 );

			/**
			 * Fires at the end of the setup_actions method inside CB_Component.
			 *
			 * This is a dynamic hook that is based on the component string ID.
			 *
			 * @package ConfettiBits\Core
			 * @since 1.0.0
			 */
			do_action( 'cb_' . $this->id . '_setup_actions' );
		}
		
		/**
		 * Sets up filters for redirect shenanigans.
		 * 
		 * @package ConfettiBits\Core
		 * @since 3.0.0
		 */
		public function setup_filters() {
			
			//add_filter( 'cb_query_vars', [ $this, 'add_query_vars'] );
			//add_filter( 'cb_template_include', [$this, 'template_include'] );
			
		}

		/**
		 * Set up the canonical URL stack for this component.
		 *
		 * @package ConfettiBits\Core
		 * @since 1.0.0
		 */
		public function setup_canonical_stack() {}

		/**
		 * Set up component navigation.
		 * 
		 * Maybe we'll use this someday.
		 *
		 * @package ConfettiBits\Core
		 * @since 1.0.0
		 *
		 * @see cb_core_new_nav_item() For a description of the $main_nav
		 *      parameter formatting.
		 * @see cb_core_new_subnav_item() For a description of how each item
		 *      in the $sub_nav parameter array should be formatted.
		 *
		 * @param array $main_nav Optional. Passed directly to cb_core_new_nav_item().
		 *                        See that function for a description.
		 * @param array $sub_nav  Optional. Multidimensional array, each item in
		 *                        which is passed to cb_core_new_subnav_item(). See that
		 *                        function for a description.
		 */
		public function setup_nav( $main_nav = array(), $sub_nav = array() ) {

			// No sub nav items without a main nav item.
			if ( ! empty( $main_nav ) ) {}

			/**
			 * Fires at the end of the setup_nav method inside CB_Component.
			 *
			 * This is a dynamic hook that is based on the component string ID.
			 *
			 * @package ConfettiBits\Core
			 * @since 1.0.0
			 */
			do_action( 'cb_' . $this->id . '_setup_nav' );
		}

		/**
		 * Set up the component entries in the WordPress Admin Bar.
		 *
		 * @package ConfettiBits\Core
		 * @since 1.0.0
		 *
		 * @see WP_Admin_Bar::add_menu() for a description of the syntax
		 *      required by each item in the $wp_admin_nav parameter array.
		 * @global object $wp_admin_bar
		 *
		 * @param array $wp_admin_nav An array of nav item arguments. Each item in this parameter
		 *                            array is passed to {@link WP_Admin_Bar::add_menu()}.
		 *                            See that method for a description of the required syntax for
		 *                            each item.
		 */
		public function setup_admin_bar( $wp_admin_nav = array() ) {

			// Bail if this is an ajax request.
			if ( defined( 'DOING_AJAX' ) ) {
				return;
			}

			// Do not proceed if CB_USE_WP_ADMIN_BAR constant is not set or is false.
			if ( ! cb_use_wp_admin_bar() ) {
				return;
			}

			/**
			 * Filters the admin navigation passed into setup_admin_bar.
			 *
			 * This is a dynamic hook that is based on the component string ID.
			 *
			 * @package ConfettiBits\Core
			 * @since 1.0.0
			 *
			 * @param array $wp_admin_nav Array of navigation items to add.
			 */
			$wp_admin_nav = apply_filters( 'cb_' . $this->id . '_admin_nav', $wp_admin_nav );

			// Do we have Toolbar menus to add?
			if ( ! empty( $wp_admin_nav ) ) {
				// Fill in position if one wasn't passed for backpat.
				$pos         = 0;
				$not_set_pos = 1;
				foreach ( $wp_admin_nav as $key => $nav ) {
					if ( ! isset( $nav['position'] ) ) {
						$wp_admin_nav[ $key ]['position'] = $pos + $not_set_pos;

						if ( 9 !== $not_set_pos ) {
							++$not_set_pos;
						}
					} else {
						$pos = $nav['position'];

						// Reset not set pos to 1
						if ( $pos % 10 === 0 ) {
							$not_set_pos = 1;
						}
					}
				}

				// Sort admin nav by position.
				$wp_admin_nav = cb_sort_by_key( $wp_admin_nav, 'position', 'num' );

				// Set this objects menus.
				$this->admin_menu = $wp_admin_nav;

				// Define the WordPress global.
				global $wp_admin_bar;

				// Add each admin menu.
				foreach ( $this->admin_menu as $admin_menu ) {
					$wp_admin_bar->add_menu( $admin_menu );
				}
			}

			/**
			 * Fires at the end of the setup_admin_bar method inside CB_Component.
			 *
			 * This is a dynamic hook that is based on the component string ID.
			 *
			 * @package ConfettiBits\Core
			 * @since 1.0.0
			 */
			do_action( 'cb_' . $this->id . '_setup_admin_bar' );
		}

		/**
		 * Set up the component title.
		 *
		 * @package ConfettiBits\Core
		 * @since 1.0.0
		 */
		public function setup_title() {

			/**
			 * Fires in the setup_title method inside CB_Component.
			 *
			 * This is a dynamic hook that is based on the component string ID.
			 *
			 * @package ConfettiBits\Core
			 * @since 1.0.0
			 */
			do_action( 'cb_' . $this->id . '_setup_title' );
		}

		/**
		 * Setup component-specific cache groups.
		 *
		 * @package ConfettiBits\Core
		 * @since 1.0.0
		 */
		public function setup_cache_groups() {

			/**
			 * Fires in the setup_cache_groups method inside CB_Component.
			 *
			 * This is a dynamic hook that is based on the component string ID.
			 *
			 * @package ConfettiBits\Core
			 * @since 1.0.0
			 */
			do_action( 'cb_' . $this->id . '_setup_cache_groups' );
		}

		/**
		 * Register global tables for the component, so that it may use WordPress's database API.
		 *
		 * @package ConfettiBits\Core
		 * @since 1.0.0
		 *
		 * @param array $tables Table names to register.
		 */
		public function register_global_tables( $tables = array() ) {

			/**
			 * Filters the global tables for the component, so that it may use WordPress' database API.
			 *
			 * This is a dynamic hook that is based on the component string ID.
			 * It allows for component-specific filtering of table names. To filter
			 * *all* tables, use the 'cb_core_get_table_prefix' filter instead.
			 *
			 * @package ConfettiBits\Core
			 * @since 1.0.0
			 */
			$tables = apply_filters( 'cb_' . $this->id . '_global_tables', $tables );

			// Add to the Confetti_Bits global object.
			if ( ! empty( $tables ) && is_array( $tables ) ) {
				foreach ( $tables as $global_name => $table_name ) {
					$this->$global_name = $table_name;
				}

				// Keep a record of the metadata tables in the component.
				$this->global_tables = $tables;
			}

			/**
			 * Fires at the end of the register_global_tables method inside CB_Component.
			 *
			 * This is a dynamic hook that is based on the component string ID.
			 *
			 * @package ConfettiBits\Core
			 * @since 1.0.0
			 */
			do_action( 'cb_' . $this->id . '_register_global_tables' );
		}

		/**
		 * Register component metadata tables.
		 *
		 * Metadata tables are registered in the $wpdb global, for
		 * compatibility with the WordPress metadata API.
		 *
		 * @package ConfettiBits\Core
		 * @since 1.0.0
		 *
		 * @param array $tables Table names to register.
		 */
		public function register_meta_tables( $tables = array() ) {
			global $wpdb;

			/**
			 * Filters the global meta_tables for the component.
			 *
			 * This is a dynamic hook that is based on the component string ID.
			 * It allows for component-specific filtering of table names. To filter
			 * *all* tables, use the 'cb_core_get_table_prefix' filter instead.
			 *
			 * @package ConfettiBits\Core
			 * @since 1.0.0
			 */
			$tables = apply_filters( 'cb_' . $this->id . '_meta_tables', $tables );

			/**
			 * Add the name of each metadata table to WPDB to allow Confetti_Bits
			 * components to play nicely with the WordPress metadata API.
			 */
			if ( ! empty( $tables ) && is_array( $tables ) ) {
				foreach ( $tables as $meta_prefix => $table_name ) {
					$wpdb->{$meta_prefix . 'meta'} = $table_name;
				}

				// Keep a record of the metadata tables in the component.
				$this->meta_tables = $tables;
			}

			/**
			 * Fires at the end of the register_meta_tables method inside CB_Component.
			 *
			 * This is a dynamic hook that is based on the component string ID.
			 *
		 	 * @package ConfettiBits\Core
		 	 * @since 1.0.0
			 */
			do_action( 'cb_' . $this->id . '_register_meta_tables' );
		}

		/**
		 * Set up the component post types.
		 *
		 * @package ConfettiBits\Core
		 * @since 1.0.0
		 */
		public function register_post_types() {

			/**
			 * Fires in the register_post_types method inside CB_Component.
			 *
			 * This is a dynamic hook that is based on the component string ID.
			 *
			 * @package ConfettiBits\Core
			 * @since 1.0.0
			 */
			do_action( 'cb_' . $this->id . '_register_post_types' );
		}

		/**
		 * Register component-specific taxonomies.
		 *
		 * @package ConfettiBits\Core
		 * @since 1.0.0
		 */
		public function register_taxonomies() {

			/**
			 * Fires in the register_taxonomies method inside CB_Component.
			 *
			 * This is a dynamic hook that is based on the component string ID.
			 *
			 * @package ConfettiBits\Core
			 * @since 1.0.0
			 */
			do_action( 'cb_' . $this->id . '_register_taxonomies' );
		}

		/**
		 * Add any additional rewrite tags.
		 *
		 * @package ConfettiBits\Core
		 * @since 1.0.0
		 */
		public function add_rewrite_tags() {

			/**
			 * Fires in the add_rewrite_tags method inside CB_Component.
			 *
			 * This is a dynamic hook that is based on the component string ID.
			 *
			 * @package ConfettiBits\Core
			 * @since 1.0.0
			 */
			do_action( 'cb_' . $this->id . '_add_rewrite_tags' );
		}

		/**
		 * Add any additional rewrite rules.
		 *
		 * @package ConfettiBits\Core
		 * @since 1.0.0
		 */
		public function add_rewrite_rules() {

			/**
			 * Fires in the add_rewrite_rules method inside CB_Component.
			 *
			 * This is a dynamic hook that is based on the component string ID.
			 *
			 * @package ConfettiBits\Core
			 * @since 1.0.0
			 */
			do_action( 'cb_' . $this->id . '_add_rewrite_rules' );
		}

		/**
		 * Add any permalink structures.
		 *
		 * @package ConfettiBits\Core
		 * @since 1.0.0
		 */
		public function add_permastructs() {

			/**
			 * Fires in the add_permastructs method inside CB_Component.
			 *
			 * This is a dynamic hook that is based on the component string ID.
			 *
			 * @package ConfettiBits\Core
			 * @since 1.0.0
			 */
			do_action( 'cb_' . $this->id . '_add_permastructs' );
		}

		/**
		 * Allow components to parse the main query.
		 *
		 * @package ConfettiBits\Core
		 * @since 1.0.0
		 *
		 * @param object $query The main WP_Query.
		 */
		public function parse_query( $query ) {

			/**
			 * Fires in the parse_query method inside CB_Component.
			 *
			 * This is a dynamic hook that is based on the component string ID.
			 *
			 * @package ConfettiBits\Core
			 * @since 1.0.0
			 *
			 * @param object $query Main WP_Query object. Passed by reference.
			 */
			do_action_ref_array( 'cb_' . $this->id . '_parse_query', array( &$query ) );
		}

		/**
		 * Generate any additional rewrite rules.
		 *
		 * @package ConfettiBits\Core
		 * @since 1.0.0
		 */
		public function generate_rewrite_rules() {

			/**
			 * Fires in the generate_rewrite_rules method inside CB_Component.
			 *
			 * This is a dynamic hook that is based on the component string ID.
			 *
			 * @package ConfettiBits\Core
			 * @since 1.0.0
			 */
			do_action( 'cb_' . $this->id . '_generate_rewrite_rules' );
		}
	}
}
