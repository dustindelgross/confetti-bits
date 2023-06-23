<?php 
defined( 'ABSPATH' ) || exit;

add_action( 'plugins_loaded', 'cb_loaded', 10 );
add_action( 'init', 'cb_init', 1 );
add_action( 'customize_register', 'cb_customize_register', 20 ); // After WP core.
add_action( 'wp', 'cb_ready', 10 );

add_action( 'set_current_user', 'cb_setup_current_user', 10 );
add_action( 'setup_theme', 'cb_setup_theme', 10 );
add_action( 'after_setup_theme', 'cb_after_setup_theme', 10 ); // After WP themes.
add_action( 'wp_enqueue_scripts', 'cb_enqueue_scripts', 5 );
add_action( 'template_redirect', 'cb_template_redirect', 10 );
add_action( 'generate_rewrite_rules', 'cb_generate_rewrite_rules', 10 );

add_action( 'cb_loaded', 'cb_core_set_role_globals', 1 );
add_action( 'cb_loaded', 'cb_core_add_admin_caps', 2 );
add_action( 'cb_loaded', 'cb_setup_components', 3 );
add_action( 'cb_loaded', 'cb_core_secrets_manager_init', 4 );
add_action( 'cb_loaded', 'cb_include', 5 );

add_action( 'rest_api_init', 'cb_rest_api_init', 1 );

add_action( 'cb_init', 'cb_register_post_types', 1 );
add_action( 'cb_init', 'cb_register_taxonomies', 2 );
add_action( 'cb_init', 'cb_core_set_uri_globals', 3 );
add_action( 'cb_init', 'cb_setup_globals', 4 );
add_action( 'cb_init', 'cb_setup_canonical_stack', 5 );
add_action( 'cb_init', 'cb_setup_nav', 6 );
add_action( 'cb_init', 'cb_setup_title', 7 );
add_action( 'cb_init', 'cb_add_rewrite_tags', 8 );
add_action( 'cb_init', 'cb_add_rewrite_rules', 9 );
add_action( 'cb_init', 'cb_add_permastructs', 10 );
add_action( 'cb_register_taxonomies', 'cb_register_member_types' );
add_action( 'cb_setup_canonical_stack', 'cb_late_include', 20 );
add_action( 'cb_template_redirect', 'cb_actions', 4 );
add_action( 'cb_template_redirect', 'cb_screens', 6 );
add_action( 'cb_template_redirect', 'cb_post_request', 10 );
add_action( 'cb_template_redirect', 'cb_get_request', 10 );