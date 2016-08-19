<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package   Editor_Picks_Posts
 * @author    Daniella Valentin
 * @license   GPL-2.0+
 */

// If uninstall, not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Define uninstall functionality here.
delete_option( 'epp_plugin_options' );
