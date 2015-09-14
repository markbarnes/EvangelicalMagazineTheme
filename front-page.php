<?php
/**
 * This file adds the Home Page to the Evangelical Magazine Theme.
 *
 * @author Mark Barnes
 * @package Generate
 * @subpackage Customizations
 */

add_filter('genesis_pre_get_option_site_layout', '__genesis_return_full_width_content');    // Remove sidebar
remove_action('genesis_loop', 'genesis_do_loop');                                           // Remove standard page elements
add_action('genesis_loop', array ('evangelical_magazine_theme', 'do_home_page'));           // Add in our elements

genesis();