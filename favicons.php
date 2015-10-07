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

// Small fix to work arround windows and virtual paths while in dev env.
if ( defined('WP_DEBUG') && WP_DEBUG )
	define( 'FAVICON_PLUGIN_URL', plugins_url() . '/ts-favicons' );
if (!defined('FAVICON_PLUGIN_URL'))
	define( 'FAVICON_PLUGIN_URL', plugins_url( '', __FILE__ ) );

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
