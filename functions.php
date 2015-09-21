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

// Add image sizes
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
