<?php

/**
* Instead of displaying standard WordPress image (attachment) pages, redirect image pages to the post parent
*
* @package evangelical-magazine-theme
* @author Mark Barnes
* @access public
*/

wp_redirect(get_permalink($post->post_parent), 301);