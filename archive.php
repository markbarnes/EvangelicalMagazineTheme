<?php
/**
* This file handles the author archive page.
* 
* To generate this page, we filter they main query to return only one result.
* We then filter the contents of that page to out the archive we want.
*
* @package evangelical-magazine-theme
* @author Mark Barnes
* @access public
*/

// Remove standard post content output
remove_action( 'genesis_post_content', 'genesis_do_post_content' );
remove_action( 'genesis_entry_content', 'genesis_do_post_content' );

/**
* We won't call the standard Genesis function, because we need to change too much.
* Instead we'll cannabalise the genesis() and genesis_standard_loop() functions to
* output a single post which we can then modify to give us the output we need.
*/
evangelical_magazine_theme::my_genesis();