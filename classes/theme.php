<?php

class evangelical_magazine_theme {
    
    /**
    * Actions and filters to rearrange the layout as required for the various post types.
    * 
    * Called on the 'wp' action.
    */
    public static function rearrange_layout() {
        // All post types
        add_action ('genesis_before_header', 'genesis_do_nav');
        add_filter ('genesis_structural_wrap-menu-primary', array ('evangelical_magazine_theme', 'add_logo_to_nav_bar'));
        remove_action( 'genesis_header', 'genesis_header_markup_open', 5 );
        remove_action( 'genesis_header', 'genesis_do_header' );
        remove_action( 'genesis_header', 'genesis_header_markup_close', 15 );
        remove_action ('genesis_footer', 'genesis_do_footer');
        remove_action ('genesis_after_header', 'genesis_do_nav');
        add_action ('genesis_footer', array ('evangelical_magazine_theme', 'do_footer_bottom')); // Add our own footer below the three widgets
        unregister_sidebar( 'header-right' ); // Remove the right header widget area
        //All singular pages
        if (is_singular()) {
            remove_action ('genesis_entry_header', 'genesis_post_info', 12);                                          // We'll move this to outside the entry-header
            add_filter( 'genesis_post_meta', '__return_false' );
        }
        // Single articles
        if (is_singular('em_article')) {
            add_action ('genesis_entry_header', array (__CLASS__, 'add_wrap_inside_entry_header'), 6);       // Add more markup after the <header>
            add_action ('genesis_entry_header', array (get_called_class(), 'close_wrap_inside_entry_header'), 14);    // Close the markup just before the </header>
            add_action ('genesis_entry_header', 'genesis_post_info', 16);                                             // This is AFTER the closing </header> tag
            add_action ('genesis_meta', array (get_called_class(), 'add_image_to_pages'), 11);                        // Uses styles in the HTML HEAD
            add_filter ('genesis_post_info', array (get_called_class(), 'filter_post_info'));
            add_filter ('genesis_post_title_output', array (get_called_class(), 'filter_post_title'));
            add_action ('genesis_entry_content', array (get_called_class(), 'add_to_end_of_article'), 11);
        // Single author pages
        } elseif (is_singular('em_author')) {
            add_action ('genesis_meta', array (get_called_class(), 'add_image_to_pages'), 11); // Specify the title image using styles in the <HEAD>
            // For author pages, we want the entry header to be moved inside entry content.
            remove_action ('genesis_entry_header', 'genesis_do_post_format_image', 4);
            remove_action ('genesis_entry_header', 'genesis_entry_header_markup_open', 5);
            remove_action ('genesis_entry_header', 'genesis_entry_header_markup_close', 15);
            remove_action ('genesis_entry_header', 'genesis_do_post_title');
            remove_action ('genesis_entry_header', 'genesis_post_info', 12);
            add_action ('genesis_entry_content', 'genesis_do_post_format_image', 4);
            add_action ('genesis_entry_content', 'genesis_entry_header_markup_open', 5);
            add_action ('genesis_entry_content', 'genesis_entry_header_markup_close', 7);
            add_action ('genesis_entry_content', 'genesis_do_post_title', 8);
            add_action ('genesis_entry_content', array (__CLASS__, 'add_to_end_of_author_page'));
        // Single pages that aren't articles
        } elseif (is_singular()) {
            add_filter ('genesis_post_info', '__return_false');
        }
    }
    
    /**
    * Enqueue fonts
    * 
    * Called by the wp_enqueue_scripts action
    */
    public static function enqueue_fonts() {
        wp_enqueue_style ('magazine-font-league-gothic', get_stylesheet_directory_uri().'/fonts/league-gothic.css', array(), CHILD_THEME_VERSION);
        wp_enqueue_style ('magazine-font-aleo', get_stylesheet_directory_uri().'/fonts/aleo.css', array(), CHILD_THEME_VERSION);
        wp_enqueue_style ('magazine-font-lato', get_stylesheet_directory_uri().'/fonts/lato.css', array(), CHILD_THEME_VERSION);    }
    
    /**
    * Filters the primary menu markup to add the logo
    */
    public static function add_logo_to_nav_bar($markup) {
        if ($markup == "</div>") {
            return "<a class=\"logo\" href=\"".get_site_url()."\"></a>{$markup}";
        } else {
            return $markup;
        }
    }
    
    /**
    * Dequeues the superfish javascript
    * 
    */
    public static function disable_superfish() {
        wp_dequeue_script('superfish');
        wp_dequeue_script('superfish-args');
        wp_dequeue_script('superfish-compat');
    }

    /**
    * Adds the featured image to the top of the appropriate pages, by adding <style> to the HTML head.
    * 
    * Called by the genesis_meta action, but only on single pages.
    */
    public static function add_image_to_pages() {
        $image_id = get_post_thumbnail_id ();
        if ($image_id) {
            $image = wp_get_attachment_image_src($image_id, is_singular('em_author') ? 'width_300' : 'width_800');
            if ($image) {
                echo "<style type=\"text/css\">.entry-header { background-image: url('{$image[0]}')}</style>";
            }
        } else {
            echo "<style type=\"text/css\">
                .content .entry-header { margin-top: 75px; margin-top: 7.5rem }
                .content .entry-content { padding-top: 25px; padding-top: 2.5rem; }
            </style>";
        }
    }
    
    /**
    * Filters the post info for articles
    * 
    * @param string $post_info
    * @return string
    */
    public static function filter_post_info($post_info) {
        global $post;
        if ($post && $post->post_type == 'em_article') {
            $article = new evangelical_magazine_article($post);
            $output = 'By '.$article->get_author_names(true); 
            $output .= "<span style=\"float:right\">{$article->get_issue_name(true)}";
            if ($page_num = $article->get_issue_page_num()) {
                $output .= ", page {$page_num}";
            }
            return "{$output}</span>";
        } else {
            return $post_info;
        }
    }
    
    /**
    * Outputs Author/Series/Section information at the end of articles
    * 
    * Called by the genesis_entry_content action.
    * 
    */
    public static function add_to_end_of_article () {
        global $post;
        $article = new evangelical_magazine_article($post);
        $authors = $article->get_authors();
        if ($authors) {
            $is_single_author = count($authors) == 1;
            echo "<div class =\"author-meta\"><h2>About the author".($is_single_author ? '' : 's')."</h2>";
            foreach ($authors as $author) {
                echo $author->get_author_info_html();
            }
            if (!$article->has_series()) {
                $also_by = $article->get_articles_by_same_authors();
                if ($also_by) {
                    if ($is_single_author) {
                        $author = current ($authors);
                        echo '<h3>Also by '.$author->get_name(true).'</h3>';
                    } else {
                        echo '<h3>Also by these authors</h3>';
                    }
                    foreach ($also_by as $also_article) {
                        echo $also_article->get_small_box_html(true, $also_article->get_issue_name(true));
                    }
                }
            }
            echo '</div>';
        }
        $excluded_articles = array();
        if ($article->has_series()) {
            $also_in = $article->get_articles_in_same_series();
            if (count($also_in) > 1) {
                echo "<div class =\"series-meta\"><h2>Also in the {$article->get_series_name(true)} series</h2>";
                $also_articles_array = array(); // We're going to split it into rows of three
                foreach ($also_in as $also_article) {
                    $class = $also_article->get_id() == $article->get_id() ? 'current' : '';
                    $also_articles_array[] = $also_article->get_small_box_html(!(bool)$class, "Part {$also_article->get_series_order()}", $class);
                    $excluded_articles[] = $also_article->get_id();
                }
                $chunked_also_articles_array = array_chunk ($also_articles_array, 3);
                foreach ($chunked_also_articles_array as $chunk) {
                    echo '<div class="row-wrap">'.implode('', $chunk).'</div>';
                }
                echo '</div>';
            }
        }
        $excluded_articles[] = $article->get_id();
        if ($article->has_sections()) {
            $sections = $article->get_sections();
            if ($sections) {
                foreach ($sections as $section) {
                    $also_in = $section->get_articles_in_this_section(3, $excluded_articles);
                    if ($also_in) {
                        echo "<div class =\"sections-meta\"><h2>Also in the {$section->get_name(true)} section</h2>";
                        foreach ($also_in as $also_article) {
                            echo $also_article->get_small_box_html(true, $also_article->get_issue_name(true));
                            $excluded_articles[] = $also_article->get_id();
                        }
                        echo '</div>';
                    }
                }
            }
        }
    }
    
    /**
    * Adds series information into the header
    * 
    * Filters 'genesis_post_title_output', but only for articles
    * 
    * @param string $title
    * @return string
    */
    public static function filter_post_title ($title) {
        global $post;
        $article = new evangelical_magazine_article($post);
        if ($article->has_series()) {
            return "{$title}<h2 class=\"entry-title-series\">Part {$article->get_series_order()} of the {$article->get_series_name(true)} series</h2>";
        } else {
            return $title;
        }
    }
    
    /**
    * Outputs an additional div into the entry header of single pages
    * 
    * Called by the 'genesis-entry-header' action
    * 
    */
    public static function add_wrap_inside_entry_header() {
        echo '<div class="entry-title-wrap">';
    }

    /**
    * Outputs to close the additional div in the entry header of single pages
    * 
    * Called by the 'genesis-entry-header' action
    * 
    */
    public static function close_wrap_inside_entry_header() {
        echo '</div>';
    }

    /**
    * Outputs the footer on all pages
    * 
    * Called by the 'genesis_foooter' action
    * 
    */
    public static function do_footer_bottom() {
        echo "<a class=\"logo\" href=\"".get_site_url()."\"></a>";
        echo '<span class="emw">The Evangelical Magazine is published by the <a href="https://www.emw.org.uk/">Evangelical Movement of Wales</a></span><br/>';
        echo '<span class="address">Waterton Cross Business Park, South Road, Bridgend CF31 3UL</span><br/>';
        echo '<span class="registration">Registered charity number 222407</span>';
    }
    
    /**
    * Modifies the main nav menu html to add the 'recent issues' sub-menu
    * 
    * Filters wp_nav_menu_items
    * 
    * @param string $menu
    * @return string
    */
    public static function modify_menu ($menu) {
        $text_to_look_for = '<span itemprop="name">Recent Issues</span></a>';
        if (strpos($menu, $text_to_look_for) !== FALSE) {
            $menu = str_replace ('<a href="#" itemprop="url"><span itemprop="name">Recent Issues</span></a>', '<a href="'.get_post_type_archive_link ('em_issue').'" itemprop="url"><span itemprop="name">Recent Issues</span></a>', $menu);
            $issues = evangelical_magazine_issue::get_all_issues(6);
            if ($issues) {
                $issue_menu = '<ul class="sub-menu">';
                foreach ($issues as $issue) {
                    $issue_name = str_replace('/','/<wbr>',$issue->get_name());
                    if (strpos($issue_name, '<wbr>') !== FALSE) {
                        $issue_name = str_replace(' ','&nbsp;',$issue_name);
                    }
                    $issue_menu .= "<li id=\"menu-item-issue-{$issue->get_id()}\" class=\"menu-item menu-item-type-issue menu-item-issue-{$issue->get_id()}\">";
                    $issue_menu .= "<a href=\"{$issue->get_link()}\" itemprop=\"url\">{$issue->get_image_html ('width_150')}<span itemprop=\"name\">{$issue_name}</span></a></li>";
                }
                $issue_menu .= "<li id=\"menu-item-more-issues\" class=\"menu-item menu-item-type-custom menu-item-object-custom menu-item-more-issues\"><a href=\"".get_post_type_archive_link ('em_issue')."\" itemprop=\"url\"><span itemprop=\"name\">More&hellip;</span></a></li>";
                $issue_menu .= '</ul>';
                $menu = str_replace ($text_to_look_for, $text_to_look_for.$issue_menu, $menu);
            }
        }
        $menu = str_replace(array('<ul class="sub-menu">', '</ul>'), array('<ul class="sub-menu"><div class="wrap">', '</div></ul>'), $menu);
        return $menu;
    }
    
    public static function add_to_end_of_author_page() {
        $author_id = get_the_ID();
        $author = new evangelical_magazine_author($author_id);
        $articles = $author->get_articles();
        if ($articles) {
            echo "<h3 class=\"articles_by\">Articles by {$author->get_name()}</h3>";
            $chunks = array_chunk ($articles, 3);
            foreach ($chunks as $chunk) {
                echo "<div class=\"article-box-row-wrap\">";
                foreach ($chunk as $article) {
                    echo $article->get_small_box_html(true, $article->get_issue_name(true));
                }
                echo '</div>';
            }
        }
   }
}