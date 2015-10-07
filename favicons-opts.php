<?php


// Small fix to work arround windows and virtual paths while in dev env.
if ( defined('WP_DEBUG') && WP_DEBUG )
	define( 'FAVICONS_PLUGIN_SLUG',
		str_replace('-opts', '', basename(dirname(__FILE__)) . '/' . pathinfo(__FILE__, PATHINFO_FILENAME) . '.php' ) );
if ( ! defined('FAVICONS_PLUGIN_SLUG') )
	define( 'FAVICONS_PLUGIN_SLUG',
		str_replace('-opts', '', plugin_basename(__FILE__)) );


/**
 * Check if Settings API supported
 */
function ts_favicons_requires_wordpress_version() {
	global $wp_version;
	$plugin = FAVICONS_PLUGIN_SLUG;
	$plugin_data = get_plugin_data( __FILE__, false );

	if ( version_compare($wp_version, "2.7", "<" ) ) {
		if( is_plugin_active($plugin) ) {
			deactivate_plugins( $plugin );
			wp_die( "'".$plugin_data['Name']."' requires WordPress 2.7 or higher, and has been deactivated!" );
		}
	}
}

add_action( 'admin_init', 'ts_favicons_requires_wordpress_version' );

/**
 * Defaults
 */
function ts_favicons_default_options() {
	return apply_filters( 'ts_favicons_default_options', array(
		'favicon' => '',
		'favgif' => '',
		'apple_touch' => '',
		'apple_precomposed' => 0,
		) );
}

/**
 * Delete options table entries ONLY when plugin deactivated AND deleted
 */
function ts_favicons_register_uninstall_hook() {
	delete_option('ts_favicons_options');
}

register_uninstall_hook( FAVICONS_PLUGIN_SLUG, 'ts_favicons_register_uninstall_hook' );

/**
 * Define default option settings
 */
function ts_favicons_register_activation_hook() {
	$tmp = get_option('ts_favicons_options');
	if( ! is_array($tmp) ) {
		delete_option('ts_favicons_options');  /// may not be needed
		update_option('ts_favicons_options', ts_favicons_default_options());
	}
}

register_activation_hook( FAVICONS_PLUGIN_SLUG, 'ts_favicons_register_activation_hook' );

/**
 * Register settings page
 */
function ts_favicons_admin_init() {
	register_setting('ts_favicons_plugin_options', 'ts_favicons_options', 'ts_favicons_options_validate');

	add_settings_section('main', __('HTML Favicons'), '__return_false', 'ts_favicons_options');
	add_settings_section('apple', __('Apple Icons'), '__return_false', 'ts_favicons_options');

	add_settings_field( 'favicon', __('Favorite icon <code>.ico</code> file'), 'ts_favicons_option_field_favicon', 'ts_favicons_options', 'main' );
	add_settings_field( 'favgif', __('Favorite icon <code>.gif</code> file'), 'ts_favicons_option_field_favgif', 'ts_favicons_options', 'main' );
	add_settings_field( 'apple_touch', __('Apple webpage bookmark <code>.png</code> file'), 'ts_favicons_option_field_apple_touch', 'ts_favicons_options', 'apple' );
	add_settings_field( 'apple_precomposed', __('Precomposed'), 'ts_favicons_option_field_apple_precomposed', 'ts_favicons_options', 'apple' );

}

add_action( 'admin_init', 'ts_favicons_admin_init' );

/**
 * @see: http://wordpress.org/support/topic/howto-integrate-the-media-library-into-a-plugin
 */
function ts_favicons_admin_scripts() {
	global $wp_version;
	if ( version_compare($wp_version, "3.5", "<" ) ) return;

	wp_enqueue_media();  // *new in WP3.5+

	/** @see http://jscompress.com */
	wp_register_script( 'ts-nmp-media', trailingslashit(FAVICON_PLUGIN_URL) . 'js/ts-media' .
		((defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? '' : '.min') .
		'.js', array( 'jquery' ), '1.0.0', true );
	wp_localize_script( 'ts-nmp-media', 'ts_nmp_media', array(
		'title' => __( 'Upload File or Select from Media Library' ),  // This will be used as the default title
		'button' => __( 'Insert' ),  // This will be used as the default button text
		) );
	wp_enqueue_script( 'ts-nmp-media' );
}

function ts_favicons_admin_styles() {
	wp_enqueue_style('ts-favicons', trailingslashit(FAVICON_PLUGIN_URL) . 'css/favicons.css');
}

if (isset($_GET['page']) && $_GET['page'] == 'ts-favicons') {
	add_action('admin_print_scripts', 'ts_favicons_admin_scripts');
	add_action('admin_print_styles', 'ts_favicons_admin_styles');
}

/**
 * Get options or defaults
 */
function ts_favicons_get_options() {
	$saved = (array) get_option( 'ts_favicons_options' );
	$defaults = ts_favicons_default_options();
	$options = wp_parse_args( $saved, $defaults );
	$options = array_intersect_key( $options, $defaults );
	return $options;
}

/**
 * Helper
 */
function _ts_favicons_option_field_image(
	$name = 'image',
	$value = '',
	$help = '',
	$empty = '',
	$preview_box_size = false,
	$fakeapple = false)
{
	?><div class="layout"><?php
	if ( ! empty($help) )
		echo '<p>' . $help . '</p>';

	?>
	<label for="<?php echo $name; ?>">
	<?php

	$_show = empty($value) ? $empty : $value;
	if (empty($_show)) {
		?><span class="image-not-found"><?php _e( 'No image set' ); ?></span><?php
	} else {
		if ($preview_box_size)
			$szs = ' width="' . $preview_box_size . '" height="' . $preview_box_size . '"';
		else
			$szs = '';
		?><img class="image-preview imgprev-<?php echo $name; ?>" src="<?php echo $_show ?>"<?php echo $szs; ?> /><?php
		if ($fakeapple) {
			$_show = trailingslashit(FAVICON_PLUGIN_URL) . 'img/fakeapple.php?s=57&u=' . urlencode($_show);
			?><span class="fake-apple"><img class="image-preview imgprev-<?php echo $name; ?>" src="<?php echo $_show ?>"<?php echo $szs; ?> title="<?php _e('Simulated Rendering'); ?>" /></span><?php
		}
	}
	?><br />

		<span class="description"><?php _e( 'Enter an URL or upload an image' ); ?></span><br />
		<input type="url" name="ts_favicons_options[<?php echo $name; ?>]" id="<?php echo $name; ?>" value="<?php echo esc_attr( $value ); ?>" />

		<input class="ts-open-media button" type="button" value="<?php echo _e('Upload Image');?>" style="display:none" />

		<input type="button" class="button" value="<?php _e( 'Clear' ); ?>" onclick="jQuery('#<?php echo $name; ?>').val('')" />
	</label>
	</div>
	<?php
}

function ts_favicons_option_field_favicon() {
	$options = ts_favicons_get_options();

	$help = __('You can upload a custom icon image to be used as the site favicon.');
	$help .= '<br />';
	$help .= __('Suggested size combinations are <b>16x16 pixels</b>, <b>32x32 pixels</b> and <b>48x48 pixels</b>.');
	$help .= '<br />';
	$help .= __('If left blank the file <code>favicon.ico</code> on the root of the WordPress install will be used.');

	$empty = (empty($options['favicon']) && @file_exists(ABSPATH . 'favicon.ico')) ?
		$empty = home_url( '/' ) . 'favicon.ico' :
		'';
	_ts_favicons_option_field_image( 'favicon', $options['favicon'], $help, $empty, 16 );
}

function ts_favicons_option_field_favgif() {
	$options = ts_favicons_get_options();

	$help = __('You can upload a custom GIF file to be used as the site favicon.');
	$help .= '<br />';
	$help .= __('Suggested size is <b>48x48 pixels</b>.');
	$help .= '<br />';
	$help .= __('If left blank the file <code>favicon.gif</code> on the root of the WordPress install will be used.');

	$empty = (empty($options['favgif']) && @file_exists(ABSPATH . 'favicon.gif')) ?
		$empty = home_url( '/' ) . 'favicon.gif' :
		'';
	_ts_favicons_option_field_image( 'favgif', $options['favgif'], $help, $empty, 16 );
}

function ts_favicons_option_field_apple_touch() {
	$options = ts_favicons_get_options();

	$help = __('You can upload a custom PNG image to be used as the site\'s iOS bookmark image.');
	$help .= '<br />';
	$help .= __('Suggested size is <b>57x57 pixels</b>, but <b>72x72 pixels</b>, <b>114x114 pixels</b> and <b>144x144 pixels</b> may work better in higher resolution devices.');
	$help .= '<br />';
	$help .= __('If left blank the file <code>apple-touch-icon.png</code> on the root of the WordPress install will be used.');

	$empty = '';
	if (empty($options['apple_touch'])) :
		$file = $options['apple_precomposed'] ? '-precomposed' : '';
		$file = 'apple-touch-icon' . $file . '.png';
		if (@file_exists(ABSPATH . $file))
			$empty = home_url( '/' ) . $file;
	endif;
	$fakeapple = !$options['apple_precomposed'];
	_ts_favicons_option_field_image( 'apple_touch', $options['apple_touch'], $help, $empty, 57, $fakeapple);
}

function ts_favicons_option_field_apple_precomposed() {
	$options = ts_favicons_get_options();
	$name = 'apple_precomposed';
	$description = __('Precomposed images are displayed as is, for non-precomposed iOS will generate rounded corners and a reflective shine.');
	?>
	<label for="<?php echo $name; ?>">
		<input type="checkbox" name="ts_favicons_options[<?php echo $name; ?>]" id="<?php echo $name; ?>" <?php checked( '1', $options[$name] ); ?> />
		<?php if ( ! empty($description) ) : ?> &nbsp; <span class="description"><?php echo $description  ?></span><?php endif; ?>
	</label>
	<?php
}

/**
 *
 */
function ts_favicons_admin_options_page() {
	if ( ! current_user_can( 'manage_options' ) )
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
?>
<div class="wrap">
	<?php screen_icon('favicon'); ?>
	<h2><?php _e('TS Favicons Plugin Options'); ?></h2>
	<p></p>

	<form method="post" action="options.php">
		<?php
			settings_fields( 'ts_favicons_plugin_options' );
			do_settings_sections( 'ts_favicons_options' );
			submit_button();
		?>
	</form>
</div>
<?php
}

/**
 * Options validation
 */
function ts_favicons_options_validate( $input ) {
	$output = array();

	if ( isset( $input['favicon'] ) )
		$output['favicon'] = $input['favicon'];

	if ( isset( $input['favgif'] ) )
		$output['favgif'] = $input['favgif'];

	if ( isset( $input['apple_touch'] ) )
		$output['apple_touch'] = $input['apple_touch'];

	$output['apple_precomposed'] = isset( $input['apple_precomposed'] ) ? 1 : 0;

	return apply_filters( 'ts_favicons_options_validate', $output, $input );
}

/**
 *
 */
function ts_favicons_plugin_action_links( $actions /* , $plugin_file, $plugin_data, $context */ ) {
	$menu_slug = dirname(FAVICONS_PLUGIN_SLUG);
	$settings_link = '<a href="' . admin_url("themes.php?page=") . $menu_slug . '">' . __('Settings') . '</a>';
	array_unshift( $actions, $settings_link );
	return $actions;
}

/**
 *
 */
function ts_favicons_admin_menu() {
	add_filter( 'plugin_action_links_' . FAVICONS_PLUGIN_SLUG, 'ts_favicons_plugin_action_links', 10 /* , 4 */ );
	add_theme_page(
		__('TS Favicons Plugin Options'),
		__('Favicons'),
		'manage_options',
		'ts-favicons',
		'ts_favicons_admin_options_page' );

}

add_action( 'admin_menu', 'ts_favicons_admin_menu' );

/* eof */
