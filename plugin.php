<?php
/**
 * Plugin Name: Public Media Platform
 * Plugin URI: https://github.com/publicmediaplatform/pmp-wordpress
 * Description: Integrate your site's content with the <a href="https://support.pmp.io" target="_blank">Public Media Platform</a>.
 * Author: the PMP and INN nerds
 * Version: latest
 * Author URI: https://github.com/publicmediaplatform/pmp-wordpress
 * License: MIT
 */

// check if plugin is composer-installed
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
	require __DIR__ . '/vendor/autoload.php';
}
else {
	require __DIR__ . '/vendor/pmpsdk.phar';
}

/**
 * Plugin set up and init
 *
 * @since 0.1
 */
function pmp_init() {
	define('PMP_PLUGIN_DIR', __DIR__);
	define('PMP_PLUGIN_DIR_URI', plugins_url(basename(__DIR__), __DIR__));
	define('PMP_TEMPLATE_DIR', PMP_PLUGIN_DIR . '/templates');
	define('PMP_VERSION', 0.1);

	$includes = array(
		'inc/functions.php',
		'inc/settings.php',
		'inc/pages.php',
		'inc/assets.php',
		'inc/ajax.php',
		'inc/cron.php',
		'inc/meta-boxes.php'
	);

	foreach ($includes as $include)
		include_once PMP_PLUGIN_DIR . '/' . $include;
}
add_action('widgets_init', 'pmp_init');

/**
 * Register the plugin's menu and options page
 *
 * @since 0.1
 */
function pmp_plugin_menu() {
	$page_title = 'Public Media Platform';
	$menu_title = 'Public Media Platform';
	$capability = 'edit_posts';
	$menu_slug = 'pmp-search';
	$function = 'pmp_search_page';
	$icon_url = 'dashicons-networking';

	add_menu_page($page_title, $menu_title, $capability, $menu_slug, $function, $icon_url);

	$sub_menus = array(
		array(
			'page_title' => 'Search',
			'menu_title' => 'Search',
			'capability' => 'edit_posts',
			'menu_slug' => 'pmp-search',
			'function' => 'pmp_search_page'
		),
		array(
			'page_title' => 'Settings',
			'menu_title' => 'Settings',
			'capability' => 'manage_options',
			'menu_slug' => 'pmp-options-menu',
			'function' => 'pmp_options_page'
		),
		array(
			'page_title' => 'Groups &amp; Permissions',
			'menu_title' => 'Groups &amp; Permissions',
			'capability' => 'manage_options',
			'menu_slug' => 'pmp-groups-menu',
			'function' => 'pmp_groups_page'
		),
		array(
			'page_title' => 'Series',
			'menu_title' => 'Series',
			'capability' => 'manage_options',
			'menu_slug' => 'pmp-series-menu',
			'function' => 'pmp_collections_page'
		),
		array(
			'page_title' => 'Properties',
			'menu_title' => 'Properties',
			'capability' => 'manage_options',
			'menu_slug' => 'pmp-properties-menu',
			'function' => 'pmp_collections_page'
		)
	);

	foreach ($sub_menus as $sub_menu) {
		add_submenu_page(
			'pmp-search', $sub_menu['page_title'], $sub_menu['menu_title'],
			$sub_menu['capability'], $sub_menu['menu_slug'], $sub_menu['function']
		);
	}

}
add_action('admin_menu', 'pmp_plugin_menu');

/**
 * Add a meta box to the side column on the Post edit screen to allow users to subscribe
 * to updates for PMP-sourced posts.
 *
 * @since 0.1
 */
function pmp_add_meta_boxes() {
	$screen = get_current_screen();

	if ($screen->id == 'post') {
		global $post;

		$pmp_guid = get_post_meta($post->ID, 'pmp_guid', true);

		if (!empty($pmp_guid) && !pmp_post_is_mine($post->ID)) {
			add_meta_box(
				'pmp_subscribe_to_updates',
				'PMP: Subscribe to updates',
				'pmp_subscribe_to_updates_meta_box',
				'post', 'side'
			);
		}
	}
}
add_action('add_meta_boxes', 'pmp_add_meta_boxes');

/**
 * On activation, set a time, frequency and name of an action hook to be scheduled.
 *
 * @since 0.1
 */
function pmp_setup_cron_on_activation() {
	wp_schedule_event(time(), 'hourly', 'pmp_hourly_cron');
}
register_activation_hook(__FILE__, 'pmp_setup_cron_on_activation');

/**
 * On the scheduled action hook, run the function.
 *
 * @since 0.1
 */
function pmp_hourly_cron() {
	pmp_get_updates();
}
add_action('pmp_hourly_cron', 'pmp_hourly_cron');
