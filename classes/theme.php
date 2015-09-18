<?php

/**
* Wrapper class to make sure there are no collisions
*/
class evangelical_magazine_theme {
    
    /**
    * Actions and filters to rearrange the layout as required for the various post types.
    * 
    * Called on the 'wp' action.
    */
    public static function rearrange_layout() {
        // All post types
        add_action ('genesis_meta', array (__CLASS__, 'add_viewport'));
        add_action ('genesis_before_header', 'genesis_do_nav');
        add_filter ('genesis_structural_wrap-menu-primary', array (__CLASS__, 'add_logo_to_nav_bar'));
        remove_action( 'genesis_header', 'genesis_header_markup_open', 5 );
        remove_action( 'genesis_header', 'genesis_do_header' );
        remove_action( 'genesis_header', 'genesis_header_markup_close', 15 );
        remove_action ('genesis_footer', 'genesis_do_footer');
        remove_action ('genesis_after_header', 'genesis_do_nav');
        remove_action( 'genesis_entry_footer', 'genesis_post_meta' );
         // Add our own footer below the three widgets
        add_action ('genesis_footer', array (__CLASS__, 'do_footer_bottom'));
        unregister_sidebar( 'header-right' );

        // All singular pages
        if (is_singular()) {
            remove_action ('genesis_entry_header', 'genesis_post_info', 12);
            add_filter( 'genesis_post_meta', '__return_false' );
        // All archive pages
        } elseif (is_archive()) {
            // Remove all the standard entry headers/content
            remove_action( 'genesis_entry_header', 'genesis_do_post_title' );
            remove_action( 'genesis_entry_header', 'genesis_do_post_format_image', 4 );
            remove_action( 'genesis_entry_content', 'genesis_do_post_image', 8 );
            remove_action( 'genesis_entry_content', 'genesis_do_post_content' );
            remove_action( 'genesis_entry_content', 'genesis_do_post_content_nav', 12 );
            remove_action( 'genesis_entry_content', 'genesis_do_post_permalink', 14 );
        }
        // Everything apart from articles
        if (!is_singular('em_article')) {
            add_filter ('genesis_post_info', '__return_false');
        }

        // Single articles
        if (is_singular('em_article')) {
            // Move the post_info to AFTER the closing </header> tag
            add_action ('genesis_entry_header', 'genesis_post_info', 16);
            // Filter the post_info
            add_filter ('genesis_post_info', array (__CLASS__, 'filter_post_info'));
            // Filter the title
            add_filter ('genesis_post_title_output', array (__CLASS__, 'filter_post_title'));
            // Add the author/'see also' detail at the end of the article (also increases the view count)
            add_action ('genesis_entry_content', array (__CLASS__, 'add_to_end_of_article'), 11);
            self::add_full_size_header_image();
        }
        // Single author pages
        elseif (is_singular('em_author')) {
             // Specify the title image using styles in the <HEAD>
            add_action ('genesis_meta', array (__CLASS__, 'add_image_to_pages'), 11);
            add_action ('genesis_entry_content', array (__CLASS__, 'add_to_end_of_author_page'));
            self::move_entry_header_inside_entry_content();
            add_filter ('body_class', function($classes) {$classes[]="half-size-header-image";return $classes;});
            remove_action ('genesis_entry_header', 'genesis_do_post_title');
            add_action ('genesis_entry_content', 'genesis_do_post_title', 9);
        }
        // Single issue pages
        elseif (is_singular('em_issue')) {
            add_action ('genesis_meta', array (__CLASS__, 'add_image_to_pages'), 11);
            self::move_entry_header_inside_entry_content();
            remove_action ('genesis_entry_header', 'genesis_do_post_title');
            add_action ('genesis_entry_content', 'genesis_do_post_title', 4);
            add_action ('genesis_entry_content', array (__CLASS__, 'open_div'), 4);
            add_action ('genesis_entry_content', array (__CLASS__, 'close_div'), 11);
            add_action ('genesis_entry_content', array (__CLASS__, 'add_to_end_of_issue_page'), 12);
        }
        // Single section pages
        elseif (is_singular('em_section')) {
            self::move_entry_header_inside_entry_content();
            remove_action ('genesis_entry_header', 'genesis_do_post_title');
            add_action ('genesis_entry_content', 'genesis_do_post_title', 4);
            add_action ('genesis_entry_content', array (__CLASS__, 'add_to_end_of_section_page'), 12);
        }
        // Single pages
        elseif (is_singular('page')) {
            self::add_full_size_header_image();
        }


        // Author archive page
        elseif (is_post_type_archive('em_author')) {
            add_action ('genesis_entry_content', array(__CLASS__, 'output_author_archive_page'));
        }
        // Issues archive page
        elseif (is_post_type_archive('em_issue')) {
            add_action ('genesis_entry_content', array(__CLASS__, 'output_issue_archive_page'));
        }
        // Sections archive page
        elseif (is_post_type_archive('em_section')) {
            add_action ('genesis_entry_content', array(__CLASS__, 'output_section_archive_page'));
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
                .content .entry-title { position: initial; bottom:initial;text-shadow:none;color:#086788}
                .content .entry-header {background-color: white;padding:20px 40px}
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
            if ($page_num = $article->get_page_num()) {
                $output .= ", page {$page_num}";
            }
            return "{$output}</span>";
        } else {
            return $post_info;
        }
    }
    
    /**
    * Outputs Author/Series/Section information at the end of articles
    * Also updates the view count, as it's only called for singular article views.
    * 
    * Called by the genesis_entry_content action.
    * 
    */
    public static function add_to_end_of_article () {
        global $post;
        $article = new evangelical_magazine_article($post);
        if (!is_user_logged_in()) {
            $article->record_view_count();
        }
        $authors = $article->get_authors();
        if ($article->has_series()) {
            $also_in = $article->get_articles_in_same_series();
        } else {
            $also_in = array();
        }

        if ($authors) {
            $is_single_author = count($authors) == 1;
            echo "<div class =\"author-meta\"><h2>About the author".($is_single_author ? '' : 's')."</h2>";
            foreach ($authors as $author) {
                echo $author->get_author_info_html();
            }
            if (count($also_in) < 2) {
                $also_by = $article->get_articles_by_same_authors(3);
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
        if ($article->has_series() && count($also_in) > 1) {
            echo "<div class =\"series-meta\"><h2>Also in the {$article->get_series_name()} series</h2>";
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
        $excluded_articles[] = $article->get_id();
        if ($article->has_sections()) {
            $sections = $article->get_sections();
            if ($sections) {
                foreach ($sections as $section) {
                    $also_in = $section->get_articles(3, $excluded_articles);
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
            return "{$title}<h2 class=\"entry-title-series\">Part {$article->get_series_order()} of the {$article->get_series_name()} series</h2>";
        } else {
            return $title;
        }
    }
    
    /**
    * Outputs an opening div
    * 
    * Called by various actions
    * 
    */
    public static function open_div() {
        echo '<div>';
    }

    /**
    * Outputs a closing div
    * 
    * Called by various actions
    * 
    */
    public static function close_div() {
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
    * Modifies the main nav menu html to add the 'recent issues' and 'recent authors' sub-menu
    * 
    * Filters wp_nav_menu_items
    * 
    * @param string $menu
    * @return string
    */
    public static function modify_menu ($menu) {
        //Recent issues
        $text_to_look_for = '<span itemprop="name">Recent Issues</span></a>';
        if (strpos($menu, $text_to_look_for) !== FALSE) {
            $menu = str_replace ('<a href="#" itemprop="url"><span itemprop="name">Recent Issues</span></a>', '<a href="'.get_post_type_archive_link ('em_issue').'" itemprop="url"><span itemprop="name">Recent Issues</span></a>', $menu);
            $issues = evangelical_magazine_issue::get_all_issues(6);
            if ($issues) {
                $issue_menu = '<ul class="sub-menu sub-menu-issues"><div class="wrap">';
                foreach ($issues as $issue) {
                    $issue_name = str_replace('/','/<wbr>',$issue->get_name());
                    if (strpos($issue_name, '<wbr>') !== FALSE) {
                        $issue_name = str_replace(' ','&nbsp;',$issue_name);
                    }
                    $issue_menu .= "<li id=\"menu-item-issue-{$issue->get_id()}\" class=\"menu-item menu-item-type-issue menu-item-issue-{$issue->get_id()}\">";
                    $issue_menu .= "<a href=\"{$issue->get_link()}\" itemprop=\"url\">{$issue->get_image_html ('width_150')}<span itemprop=\"name\">{$issue_name}</span></a></li>";
                }
                $issue_menu .= "<li id=\"menu-item-more-issues\" class=\"menu-item menu-item-type-custom menu-item-object-custom menu-item-more-issues\"><a href=\"".get_post_type_archive_link ('em_issue')."\" itemprop=\"url\"><span itemprop=\"name\">More&hellip;</span></a></li>";
                $issue_menu .= '</ul>'; // The closing div will be added by the str_replace at the end of the function
                $menu = str_replace ($text_to_look_for, $text_to_look_for.$issue_menu, $menu);
            }
        }
        //Recent authors
        $text_to_look_for = '<span itemprop="name">Authors</span></a>';
        if (strpos($menu, $text_to_look_for) !== FALSE) {
            $menu = str_replace ('<a href="#" itemprop="url"><span itemprop="name">Authors</span></a>', '<a href="'.get_post_type_archive_link ('em_author').'" itemprop="url"><span itemprop="name">Authors</span></a>', $menu);
            $authors = evangelical_magazine_author::get_top_authors(10);
            if ($authors) {
                $author_menu = '<ul class="sub-menu sub-menu-authors"><div class="wrap">';
                foreach ($authors as $author) {
                    $author_menu .= "<li id=\"menu-item-author-{$author->get_id()}\" class=\"menu-item menu-item-type-author menu-item-author-{$author->get_id()}\">";
                    $author_menu .= "<a href=\"{$author->get_link()}\" itemprop=\"url\">{$author->get_image_html ('thumbnail_75')}<span itemprop=\"name\">{$author->get_name()}</span></a></li>";
                }
                $author_menu .= "<li id=\"menu-item-more-authors\" class=\"menu-item menu-item-type-custom menu-item-object-custom menu-item-more-authors\"><a href=\"".get_post_type_archive_link ('em_author')."\" itemprop=\"url\"><span itemprop=\"name\">More&hellip;</span></a></li>";
                $author_menu .= '</ul>';  // The closing div will be added by the str_replace at the end of the function
                $menu = str_replace ($text_to_look_for, $text_to_look_for.$author_menu, $menu);
            }
        }
        //Sections
        $text_to_look_for = '<span itemprop="name">Sections</span></a>';
        if (strpos($menu, $text_to_look_for) !== FALSE) {
            $menu = str_replace ('<a href="#" itemprop="url"><span itemprop="name">Sections</span></a>', '<a href="'.get_post_type_archive_link ('em_sections').'" itemprop="url"><span itemprop="name">Sections</span></a>', $menu);
            $sections = evangelical_magazine_section::get_all_sections();
            if ($sections) {
                $section_menu = '<ul class="sub-menu sub-menu-section"><div class="wrap">';
                foreach ($sections as $section) {
                    $section_menu .= "<li id=\"menu-item-section-{$section->get_id()}\" class=\"menu-item menu-item-type-section menu-item-section-{$section->get_id()}\">";
                    $section_menu .= "<a href=\"{$section->get_link()}\" itemprop=\"url\"><span itemprop=\"name\">{$section->get_name()}</span></a></li>";
                }
                $section_menu .= '</ul>'; // The closing div will be added by the str_replace at the end of the function
                $menu = str_replace ($text_to_look_for, $text_to_look_for.$section_menu, $menu);
            }
        }
        //Add wrap to Wordpress menus
        $menu = str_replace(array('<ul class="sub-menu">', '</ul>'), array('<ul class="sub-menu"><div class="wrap">', '</div></ul>'), $menu);
        return $menu;
    }
    
    /**
    * Adds article info to the end of author pages.
    * 
    * Called by genesis_entry_content
    * 
    */
    public static function add_to_end_of_author_page() {
        $author_id = get_the_ID();
        $author = new evangelical_magazine_author($author_id);
        $articles = $author->get_articles($author::_future_posts_args());
        if ($articles) {
            echo "<h3 class=\"articles_by\">Articles by {$author->get_name()}</h3>";
            $chunks = array_chunk ($articles, 3);
            foreach ($chunks as $chunk) {
                echo "<div class=\"article-box-row-wrap\">";
                foreach ($chunk as $article) {
                    $sub_title = $article->is_future() ? "Coming {$article->get_coming_date()}" : $article->get_issue_name(true);
                    echo $article->get_small_box_html(true, $sub_title);
                }
                echo '</div>';
            }
        }
    }
   
    /**
    * Custom version of the genesis() and genesis_standard_loop() functions.
    * 
    * Used for generating custom archive pages.
    */
    public static function my_genesis() {
        get_header();
        do_action ('genesis_before_content_sidebar_wrap');
        genesis_markup( array(
            'html5'   => '<div %s>',
            'xhtml'   => '<div id="content-sidebar-wrap">',
            'context' => 'content-sidebar-wrap',
        ) );
        do_action( 'genesis_before_content' );
        genesis_markup( array(
            'html5'   => '<main %s>',
            'xhtml'   => '<div id="content" class="hfeed">',
            'context' => 'content',
        ) );
        do_action( 'genesis_before_loop' );
        do_action( 'genesis_before_entry' );
        printf( '<article %s>', genesis_attr( 'entry' ) );
        do_action( 'genesis_entry_header' );
        do_action( 'genesis_before_entry_content' );
        printf( '<div %s>', genesis_attr( 'entry-content' ) );
        do_action( 'genesis_entry_content' );
        echo '</div>';
        do_action( 'genesis_after_entry_content' );
        do_action( 'genesis_entry_footer' );
        echo '</article>';
        do_action( 'genesis_after_entry' );
        do_action( 'genesis_after_loop' );
        genesis_markup( array(
            'html5' => '</main>', //* end .content
            'xhtml' => '</div>', //* end #content
        ) );
        do_action( 'genesis_after_content' );
        echo '</div>'; //* end .content-sidebar-wrap or #content-sidebar-wrap
        do_action( 'genesis_after_content_sidebar_wrap' );
        get_footer();       
    }
   
    /**
    * Outputs the author archive page
    * 
    * Called on the 'genesis_entry_content' action
    * 
    * @param string $content
    */
    public static function output_author_archive_page ($content) {
       echo "<h1>Authors</h1>";
       $authors = evangelical_magazine_author::get_top_authors();
       if ($authors) {
           foreach ($authors as $author) {
               echo "<a href=\"{$author->get_link()}\"><div class=\"author-grid image-fit\" style=\"background-image:url('{$author->get_image_url('width_150')}')\"><div class=\"author-description\">{$author->get_filtered_content()}</div></div></a>";
           }
       }
    }

    /**
    * Outputs the issue archive page
    * 
    * Called on the 'genesis_entry_content' action
    * 
    * @param string $content
    */
    public static function output_issue_archive_page ($content) {
       $max_articles_displayed = 4;
       echo "<h1>Issues</h1>";
       $issues = evangelical_magazine_issue::get_all_issues();
       if ($issues) {
           echo "<ul class=\"issue-list\">";
           foreach ($issues as $issue) {
               echo "<li class=\"issue\"><a href=\"{$issue->get_link()}\"><div class=\"magazine-cover image-fit box-shadow-transition\" style=\"background-image:url('{$issue->get_image_url('width_210')}')\"></div></a>";
               echo "<div class=\"issue-contents\"><h4>{$issue->get_name(true)}</h4>";
               $articles = $issue->get_top_articles($max_articles_displayed);
               if ($articles) {
                   echo "<ul class=\"top-articles\">";
                   foreach ($articles as $article) {
                       echo "<li><span class=\"article-title\">{$article->get_title(true)}</span><br/><span class=\"article-authors\">by {$article->get_author_names(true)}</span></li>";
                   }
                   $remaining_articles = $issue->get_article_count() - $max_articles_displayed;
                   if ($remaining_articles > 0) {
                       echo "</ul><p>&hellip;and <a href=\"{$issue->get_link()}\">{$remaining_articles} more</a></p>";
                   } else {
                       echo "</ul>";
                   }
               }
               else {
                   echo "<p>Coming soon&hellip;</p>";
               }
               echo "</div></li>";
           }
           echo "</ul>";
       }
    }

    /**
    * Outputs the section archive page
    * 
    * Called on the 'genesis_entry_content' action
    * 
    * @param string $content
    */
    public static function output_section_archive_page ($content) {
       $max_articles_displayed = 3;
       echo "<h1>Sections</h1>";
       $sections = evangelical_magazine_section::get_all_sections();
       if ($sections) {
           echo "<ul class=\"section-list\">";
           $exclude_ids = array();
           foreach ($sections as $section) {
               echo "<li class=\"issue\"><a href=\"{$section->get_link()}\"></a>";
               echo "<div class=\"issue-contents\"><h4>{$section->get_name(true)}</h4>";
               $articles = $section->get_top_articles($max_articles_displayed, $exclude_ids);
               if ($articles) {
                   echo "<ul class=\"top-articles\">";
                   foreach ($articles as $article) {
                       echo "<li><a href=\"{$article->get_link()}\"><div class=\"article-image\" style=\"background-image:url('{$article->get_image_url('width_210')}')\"></div></a><span class=\"article-title\">{$article->get_title(true)}</span><br/><span class=\"article-authors\">by {$article->get_author_names(true)}</span></li>";
                       $exclude_ids[] = $article->get_id();
                   }
                   $remaining_articles = $section->get_article_count() - $max_articles_displayed;
                   if ($remaining_articles > 0) {
                       if (class_exists('NumberFormatter')) {
                           $r = new NumberFormatter("en", NumberFormatter::SPELLOUT);
                           $r = $r->format($remaining_articles);
                       } else {
                           $r = $remaining_articles;
                       }
                       echo "</ul><p>&hellip;and <a href=\"{$section->get_link()}\">{$r} more</a></p>";
                   } else {
                       echo "</ul>";
                   }
               }
               else {
                   echo "<p>Coming soon&hellip;</p>";
               }
               echo "</div></li>";
           }
           echo "</ul>";
       }
    }

    /**
    * Move the entry-header inside entry-content
    * 
    * Useful for header image that don't display well in landscape modes.
    * Used for author and issue single pages.
    */
    public static function move_entry_header_inside_entry_content() {
        remove_action ('genesis_entry_header', 'genesis_do_post_format_image', 4);
        remove_action ('genesis_entry_header', 'genesis_entry_header_markup_open', 5);
        remove_action ('genesis_entry_header', 'genesis_entry_header_markup_close', 15);
        remove_action ('genesis_entry_header', 'genesis_post_info', 12);
        add_action ('genesis_entry_content', 'genesis_do_post_format_image', 3);
        add_action ('genesis_entry_content', 'genesis_entry_header_markup_open', 5);
        add_action ('genesis_entry_content', 'genesis_entry_header_markup_close', 7);
    }

    /**
    * Adds issue info to the end of issue pages.
    * 
    * Called by genesis_entry_content
    * 
    */
    public static function add_to_end_of_issue_page($content) {
        $issue_id = get_the_ID();
        $issue = new evangelical_magazine_issue($issue_id);
        $args = evangelical_magazine_article::_future_posts_args();
        $args['order'] = 'ASC';
        $articles = $issue->_get_articles ($args);
        $html = $issue->get_html_article_list($articles);
        echo ($html) ? $html : '<div class="article-list-box"><p>Coming soon.</p></div>';
    }

    /**
    * Adds section info to the end of section pages.
    * 
    * Called by genesis_entry_content
    * 
    */
    public static function add_to_end_of_section_page($content) {
        $section_id = get_the_ID();
        $section = new evangelical_magazine_section($section_id);
        $args = evangelical_magazine_article::_future_posts_args();
        $args['order'] = 'ASC';
        $articles = $section->_get_articles ($args);
        if ($articles) {
            $column_index = array ('left', 'right');
            $count = 0;
            foreach ($articles as $article) {
                if ($count == 2 || ($count%2) == 1) {
                    $col = 1;
                } else {
                    $col = 0;
                }
                $column [$column_index[$col]][] = $article;
                $count++;
            }
            echo "<div class=\"section-page section-left\">{$section->get_html_article_list($column['left'])}</div>";
            if (isset($column['right'])) {
                echo "<div class=\"section-page section-right\">{$section->get_html_article_list($column['right'], false)}</div>";
            }
        } else {
            echo '<div class="article-list-box"><p>Coming soon.</p></div>';
        }
    }

    /**
    * Creates black and white image for the *_bw sizes.
    * 
    * Filters 'wp_generate_attachment_metadata'
    * 
    * @param array $meta
    * @return array
    */
    public static function bw_images_filter ($meta) {
        $dir = wp_upload_dir();
        foreach ($meta['sizes'] as $size => &$details) {
            if (substr($size, -3) == '_bw') {
                $file = trailingslashit($dir['path']).$details['file'];
                list($orig_w, $orig_h, $orig_type) = @getimagesize($file);
                @ini_set( 'memory_limit', apply_filters( 'image_memory_limit', WP_MAX_MEMORY_LIMIT ) );
                $image = imagecreatefromstring (file_get_contents($file));
                imagefilter($image, IMG_FILTER_GRAYSCALE);
                switch ($orig_type) {
                case IMAGETYPE_GIF:
                   $file = str_replace(".gif", "-bw.gif", $file);
                   $details['file'] = str_replace(".gif", "-bw.gif", $details['file']);
                   imagegif( $image, $file );
                   break;
                case IMAGETYPE_PNG:
                   $file = str_replace(".png", "-bw.png", $file);
                   $details['file'] = str_replace(".png", "-bw.png", $details['file']);
                   imagepng( $image, $file );
                   break;
                case IMAGETYPE_JPEG:
                   $file = str_replace(".jpg", "-bw.jpg", $file);
                   $details['file'] = str_replace(".jpg", "-bw.jpg", $details['file']);
                   imagejpeg( $image, $file );
                   break;
                }
            }
        }
        return $meta;       
    }
    
    public static function add_viewport() {
        echo '<meta name="viewport" content="width=1300, initial-scale=1" />' . "\n";
    }
    
    public static function add_full_size_header_image() {
        global $post;
        add_action ('genesis_meta', array (__CLASS__, 'add_image_to_pages'), 11);
        if (has_post_thumbnail()) {
            add_filter ('body_class', function($classes) {$classes[]="full-size-header-image";return $classes;});
        }
    }
}