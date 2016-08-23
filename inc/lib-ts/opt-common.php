<?php

if (!defined('TS_SETTINGS_MENU')) :
	define( 'TS_SETTINGS_MENU', 'tecsmith' );


/**
 * Top level Tecsmith settings page
 */
if (!function_exists('ts_admin_options_page')) :
function ts_top_admin_options_page() {
	global $title, $ts_settings;

	if ( ! current_user_can( 'manage_options' ) )
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );

	echo '<div class="wrap">';
	screen_icon();
	echo '<h2>' . $title . '</h2>';

	if (!isset($ts_settings) || !isset( $ts_settings['tecsmith_menu_items'] ))
		add_settings_error(
    			TS_SETTINGS_MENU,
	    		esc_attr(TS_SETTINGS_MENU),
    			'There are no installed items with settings',
    			'error' );

	settings_errors();

	if (isset($ts_settings) && isset( $ts_settings['tecsmith_menu_items'] )) {
		echo '<table>';
		// echo '<thead><tr><th></th><th></th></tr></thead>';
		echo '<tbody>';
		foreach ($ts_settings['tecsmith_menu_items'] as $slug => $data) {
			echo '<tr><td>';
			echo '<a href="';
			echo get_admin_url( null, 'admin.php?page=' . $data[0] );
			echo '" class="button ">' . $data[1] . '</a>';
			echo '</td><td>';
			echo $data[2];
			echo '</td><tr>';
		}
		echo '</tbody></table>';
	}

	?>
	<hr>
	<h3><i class="dashicons dashicons-smiley"></i> Thank you for using Tecsmith software!</h3>
	<p>
	<i class="dashicons dashicons-admin-home"></i> Visit our home page at <a href="http://tecsmith.com.au" target="_blank">techsmith.com.au</a>.<br>
	<i class="dashicons dashicons-media-code"></i> Our open source is hosted on <a href="http://github.com/tecsmith" target="_blank">Github</a>.<br>
	<i class="dashicons dashicons-email"></i> &lt;<a href="mailto:hello@tecsmith.com.au">hello@tecsmith.com.au</a>&gt;<br>
	<i class="dashicons dashicons-twitter"></i> <a href="http://twitter.com/tcsmth" target="_blank">@tcsmth</a><br>
	</p>
	<?

	echo '</div>';
}
endif;


/**
 * Add Tecsmith menu item
 */
if (!function_exists('add_tecsmith_menu')) :
function add_tecsmith_menu() {
	global $ts_settings;
	if (!isset( $ts_settings )) $ts_settings = array();

	if (isset( $ts_settings['tecsmith_menu'] ))
		return $ts_settings['tecsmith_menu'];

	$svg = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="128" height="128" viewBox="0 0 128 128">' .
		'<polygon stroke="#fff" stroke-width="5" fill="#058" points="64 6 118 33 64 60 10 33 64 6"/>' .
		'<polygon stroke="#fff" stroke-width="5" fill="#058" points="69 68 123 41 123 95 69 122 69 68"/>' .
		'<polygon stroke="#fff" stroke-width="5" fill="#058" points="59 68 5 41 5 95 59 122 59 68"/>' .
		'</svg>';

	$slug = add_submenu_page(
		TS_SETTINGS_MENU,
		'Installed Tecsmith Plugins',
		'Installed Plugins',
		'manage_options',
		TS_SETTINGS_MENU,
		'ts_top_admin_options_page' );

	$slug = add_menu_page(
		'Tecsmith Options',
		'Tecsmith',
		'manage_options',
		TS_SETTINGS_MENU,
		'ts_top_admin_options_page',
		'data:image/svg+xml;base64,'.base64_encode($svg) );  /* */

	$ts_settings['tecsmith_menu'] = $slug;
	return $slug;
}
endif;


/**
 * Add Tecsmith menu item
 */
if (!function_exists('add_tecsmith_page')) :
function add_tecsmith_page( $page_title, $menu_title, $capability, $menu_slug, $function = '' ) {
	global $ts_settings;
	if (!isset( $ts_settings )) $ts_settings = array();

	if (!isset( $ts_settings['tecsmith_menu_items'] ))
		$ts_settings['tecsmith_menu_items'] = array();

	add_tecsmith_menu();
	$slug = add_submenu_page(
		TS_SETTINGS_MENU,
		$page_title,
		$menu_title,
		$capability,
		$menu_slug,
		$function );

	if (!isset( $ts_settings['tecsmith_menu_items'] ))
		$ts_settings['tecsmith_menu_items'] = array();

	$ts_settings['tecsmith_menu_items'][$slug] = array($menu_slug, $menu_title, $page_title);

	return $slug;
}
endif;


endif;  // TS_SETTINGS_MENU
/* eof */
