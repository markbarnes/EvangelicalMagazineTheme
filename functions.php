<?php
//* Start the engine
include_once( get_template_directory() . '/lib/init.php' );
require ('classes/theme.php');
require ('classes/home_page.php');
require ('classes/widgets.php');

//* Child theme (do not remove)
define( 'CHILD_THEME_NAME', 'Evangelical Magazine Theme' );
define( 'CHILD_THEME_URL', 'http://www.evangelicalmagazine.com/' );
define( 'CHILD_THEME_VERSION', '0.1' );

//* Add theme support
add_theme_support( 'html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption')); //* Add HTML5 markup structure
add_theme_support( 'genesis-accessibility', array( 'headings', 'drop-down-menu',  'search-form', 'skip-links', 'rems' ) ); //* Add Accessibility support
//add_theme_support( 'genesis-responsive-viewport' ); //* Add viewport meta tag for mobile browsers

/**
* Add actions
*/
add_action ('wp', array ('evangelical_magazine_theme', 'rearrange_layout'));
add_action ('wp_enqueue_scripts', array ('evangelical_magazine_theme', 'enqueue_fonts'));
add_action ('wp_enqueue_scripts', array ('evangelical_magazine_theme', 'disable_superfish'));
add_action ('widgets_init', array ('evangelical_magazine_widgets', 'register_widgets'));

/**
* Add filters
*/
add_filter ('genesis_superfish_enabled', '__return_false'); // Doesn't seem to work
add_filter ('wp_nav_menu_items', array ('evangelical_magazine_theme', 'modify_menu'));
add_filter ('wp_generate_attachment_metadata',array ('evangelical_magazine_theme', 'bw_images_filter'));
add_filter ('genesis_noposts_text',array ('evangelical_magazine_theme', 'filter_noposts_text'));

/** 
* Add image sizes
*/
add_image_size ('width_800', 800, 3000);
add_image_size ('width_400', 400, 3000);
add_image_size ('width_400_bw', 400, 3000);
add_image_size ('width_300', 300, 3000);
add_image_size ('post-thumbnail', 300, 3000);
add_image_size ('width_300_bw', 300, 3000);
add_image_size ('width_210', 210, 3000);
add_image_size ('width_210_bw', 210, 3000);
add_image_size ('width_150', 150, 3000);
add_image_size ('width_150_bw', 150, 3000);
add_image_size ('thumbnail_75', 75, 75, true);