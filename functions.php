<?php
/**
* Initialise the theme functions
*
* This theme is a child theme for the Genesis Framework
* @see http://my.studiopress.com/themes/genesis/
*
* @package evangelical-magazine-theme
* @author Mark Barnes
* @access public
*/

define( 'CHILD_THEME_NAME', 'Evangelical Magazine Theme' );
define( 'CHILD_THEME_URL', 'https://www.evangelicalmagazine.com/' );
define( 'CHILD_THEME_VERSION', '1.07' );

//* Start the engine
include_once (get_template_directory() . '/lib/init.php' );

//Make sure classes autoload
spl_autoload_register('evangelical_mag_autoload_classes');

// Set everything up
add_action ('wp', array ('evangelical_mag_theme', 'set_everything_up'));
add_filter ('intermediate_image_sizes', array ('evangelical_mag_theme', 'remove_default_image_sizes'));
add_filter ('wp_generate_attachment_metadata',array ('evangelical_mag_theme', 'enhance_media_images'));
add_filter ('tiny_mce_before_init', array ('evangelical_mag_theme', 'remove_unused_tinymce_formats'));

// Theme support
add_theme_support( 'html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption')); //* Add HTML5 markup structure
add_theme_support( 'genesis-accessibility', array( 'headings', 'search-form', 'skip-links', 'rems' ) ); //* Add Accessibility support
add_editor_style();

// Remove jQuery migrate
add_action ('wp_default_scripts', array ('evangelical_mag_theme', 'remove_jquery_migrate'));

// Widgets
add_action ('widgets_init', array ('evangelical_mag_widgets', 'register_widgets'));

// Add image sizes
add_image_size ('facebook_share', 1200, 630, true);
add_image_size ('twitter_share', 800, 400, true);
add_image_size ('article_header', 860, 430, true);
add_image_size ('full-post-width', 860);
add_image_size ('issue_very_large', 320, 480, true);
add_image_size ('article_large', 380, 253, true);
add_image_size ('half-post-width', 430);
add_image_size ('author_medium', 360, 360, true);
add_image_size ('third-post-width', 287);
add_image_size ('issue_medium', 250, 359, true);
add_image_size ('article_small', 210, 140, true);
add_image_size ('issue_small', 150, 212, true);
add_image_size ('author_small', 105, 105, true);
add_image_size ('author_tiny', 75, 75, true);
add_filter ('image_size_names_choose', array ('evangelical_mag_theme', 'add_image_sizes_to_media_gallery'));

/**
* Autoloads classes
*
* @param string $class_name
* @return void
*/
function evangelical_mag_autoload_classes($class_name) {
	$prefix  = 'evangelical_mag_';
	if (strpos($class_name, $prefix) === 0) {
		require (plugin_dir_path(__FILE__).'classes/'.substr($class_name, strlen($prefix))).'.php';
	}
}