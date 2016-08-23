<?php
/**
 * Plugin Name: TS Favicons Plugin
 * Plugin URI: http://tecsmith.com.au
 * Description: Add links to your sites (hosted at DreamHost) favicons
 * Author: Vino Rodrigues
 * Version: 1.0.3
 * Author URI: http://vinorodrigues.com
 *
 * @see: http://en.wikipedia.org/wiki/Favicon
 * @see: http://developer.apple.com/library/ios/#documentation/AppleApplications/Reference/SafariWebContent/ConfiguringWebApplications/ConfiguringWebApplications.html
 * Code based on http://www.presscoders.com/2010/05/wordpress-settings-api-explained/
**/

if (!defined('FAVICON_PLUGIN_URL'))
	define( 'FAVICON_PLUGIN_URL', str_replace( ' ', '%20', plugins_url( '', __FILE__ ) ) );


include_once('favicons-opts.php');


/**
 *
 */
function ts_favicons_wp_head() {
	$options = ts_favicons_get_options();

	extract( wp_parse_args( $options, array(
	    'favicon' => '',
	    'favgif' => '',
	    'apple_precomposed' => 0,
	    'apple_touch' => '',
	) ) );
	if ( empty($favicon) ) $favicon = home_url( '/' ) . 'favicon.ico';
	if ( empty($favgif) ) $favgif = home_url( '/' ) . 'favicon.gif';
	$precomposed = $apple_precomposed ? '-precomposed' : '';
	if ( empty($apple_touch) ) $apple_touch = home_url( '/' ) . 'apple-touch-icon' . $precomposed . '.png';


	echo '<link rel="shortcut icon" href="' . $favicon . '" />' . PHP_EOL;
	echo '<link rel="icon" type="image/gif" href="' . $favgif . '" />' . PHP_EOL;
	echo '<link rel="apple-touch-icon' . $precomposed . '" href="' . $apple_touch  . '" />' . PHP_EOL;
}
add_action( 'wp_head', 'ts_favicons_wp_head', 1 );


/* eof */
