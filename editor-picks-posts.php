<?php
/**
 * Editor Picks Posts Plugin.
 *
 * Plugin Name: Editor Picks Posts
 * Plugin URI: https://github.com/properly/editor-picks-posts
 * Description: Mark post as "Editor Picks"
 * Version: 1.0.0
 * Author: Daniella Valentin
 * Text Domain: editor-picks-post
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 *
 * @package Editor_Picks_Posts
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once( plugin_dir_path( __FILE__ ) . 'class-editor-picks-posts.php' );
require_once( plugin_dir_path( __FILE__ ) . 'class-editor-picks-posts-admin.php' );

/*
 * Register hooks that are fired when the plugin is activated or deactivated.
 * When the plugin is deleted, the uninstall.php file is loaded.
 */
register_activation_hook( __FILE__, array( 'Editor_Picks_Posts', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Editor_Picks_Posts', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'Editor_Picks_Posts', 'get_instance' ) );
add_action( 'plugins_loaded', array( 'Editor_Picks_Posts_Admin', 'get_instance' ) );
