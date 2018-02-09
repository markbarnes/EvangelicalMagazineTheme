<?php
/**
* This file adds the Home Page to the Evangelical Magazine Theme.
*
* @package evangelical-magazine-theme
* @author Mark Barnes
* @access public
*/

// Remove sidebar
add_filter('genesis_pre_get_option_site_layout', '__genesis_return_full_width_content');
// Remove standard page elements
remove_action('genesis_loop', 'genesis_do_loop');
// Add in our elements
add_action('genesis_loop', array ('evangelical_mag_home_page', 'do_home_page'));

genesis();