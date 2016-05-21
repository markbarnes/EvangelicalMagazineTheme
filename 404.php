<?php
/**
* 404 Error page.
*
* @package evangelical-magazine-theme
* @author Mark Barnes
* @access public
*/

//* Instead of displaying a standard 404 error page, we're going to display a search page based on strings in the URL
add_action ('genesis_loop', 'genesis_404', 9);
remove_action( 'genesis_loop', 'genesis_do_loop' );

add_filter ('get_search_query', array ('evangelical_magazine_theme', 'filter_search_query_on_404'));

/**
* Outputs the 404 page
* 
*/
function genesis_404() {
    echo '<article class="entry"><h1 class="entry-title">Sorry, page not found</h1><div class="entry-content">';
    echo '<p>The page you are looking for doesn&#0146;t exist. Perhaps you can find what you&#0146;re looking for by searching. Enter your query, then click <strong>search</strong>.</p>';
    echo get_search_form(false);
    echo '</article>';
}

genesis();