<?php
    // Redirect image pages to the post parent
    wp_redirect(get_permalink($post->post_parent), 301);
