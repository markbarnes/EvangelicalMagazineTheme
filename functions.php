<?php
//* Start the engine
include_once (get_template_directory() . '/lib/init.php' );
require ('classes/theme.php');
require ('classes/home_page.php');
require ('classes/widgets.php');

//* Child theme
define( 'CHILD_THEME_NAME', 'Evangelical Magazine Theme' );
define( 'CHILD_THEME_URL', 'http://www.evangelicalmagazine.com/' );
define( 'CHILD_THEME_VERSION', '0.1' );

add_action ('wp', array ('evangelical_magazine_theme', 'set_everything_up'));
add_action ('widgets_init', array ('evangelical_magazine_widgets', 'register_widgets'));
add_filter ('intermediate_image_sizes_advanced', array ('evangelical_magazine_theme', 'remove_default_image_sizes'));
add_filter ('wp_generate_attachment_metadata',array ('evangelical_magazine_theme', 'enhance_media_images'));

        
// Add image sizes
add_image_size ('facebook_share', 1200, 630, true);
add_image_size ('article_header', 800, 400, true);
add_image_size ('article_large', 380, 253, true);
add_image_size ('article_large_bw', 382, 253, true);
add_image_size ('article_sidebar', 360, 240, true);
add_image_size ('issue_large', 360, 508, true);
add_image_size ('article_medium', 307, 204, true);
add_image_size ('article_medium_bw', 309, 204, true);
add_image_size ('author_page', 300, 300, true);
add_image_size ('issue_medium', 250, 359, true);
add_image_size ('issue_archive', 210, 297, true);
add_image_size ('article_small', 210, 140, true);
add_image_size ('article_small_bw', 212, 140, true);
add_image_size ('issue_small', 150, 212, true);
add_image_size ('author_small', 113, 113, true);
add_image_size ('article_very_small', 113, 75, true);
add_image_size ('article_very_small_bw', 115, 75, true);
add_image_size ('square_thumbnail_tiny', 75, 75, true);