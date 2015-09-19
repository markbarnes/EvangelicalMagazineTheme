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

evangelical_magazine_theme::set_everything_up();