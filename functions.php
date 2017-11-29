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

//* Start the engine
include_once (get_template_directory() . '/lib/init.php' );
require ('classes/theme.php');
require ('classes/home_page.php');
require ('classes/microdata.php');

//* Child theme
define( 'CHILD_THEME_NAME', 'Evangelical Magazine Theme' );
define( 'CHILD_THEME_URL', 'http://www.evangelicalmagazine.com/' );
define( 'CHILD_THEME_VERSION', '0.87' );

add_action ('wp', array ('evangelical_mag_theme', 'set_everything_up'));
add_filter ('intermediate_image_sizes_advanced', array ('evangelical_mag_theme', 'remove_default_image_sizes'));
add_filter ('wp_generate_attachment_metadata',array ('evangelical_mag_theme', 'enhance_media_images'));

//* Theme support
add_theme_support( 'html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption')); //* Add HTML5 markup structure
add_theme_support( 'genesis-accessibility', array( 'headings', 'search-form', 'skip-links', 'rems' ) ); //* Add Accessibility support

// Add image sizes
add_image_size ('facebook_share', 1200, 630, true);
add_image_size ('twitter_share', 800, 400, true);
add_image_size ('article_header', 800, 400, true);
add_image_size ('full-post-width', 800);
add_image_size ('issue_very_large', 540, 762, true);
add_image_size ('article_very_large', 540, 360, true);
add_image_size ('article_large', 380, 253, true);
add_image_size ('half-post-width', 400);
add_image_size ('author_medium', 300, 300, true);
add_image_size ('issue_medium', 250, 359, true);
add_image_size ('article_small', 210, 140, true);
add_image_size ('issue_small', 150, 212, true);
add_image_size ('author_small', 113, 113, true);
add_image_size ('square_thumbnail_tiny', 75, 75, true);

add_filter ('image_size_names_choose', array ('evangelical_mag_theme', 'add_image_sizes_to_media_gallery'));