<?php

/**
* Main theme class (using a class mostly to avoid function name collisions)
* 
* @package evangelical-magazine-theme
* @author Mark Barnes
* @access public
*/
class evangelical_mag_theme {
    
    /**
    * All the actions and filters to rearrange the layout as required for the various post types.
    * 
    * Called on the 'wp' action.
    */
    public static function set_everything_up() {
        // HTML HEAD
        add_action ('wp_enqueue_scripts', array (__CLASS__, 'enqueue_fonts'));
        add_action ('wp_enqueue_scripts', array (__CLASS__, 'disable_superfish'));
        add_action ('wp_enqueue_scripts', array (__CLASS__, 'enqueue_media_stylesheets'));
        add_filter ('genesis_superfish_enabled', '__return_false'); // Doesn't seem to work
        remove_action ('wp_head', 'feed_links_extra', 3);
        remove_action ('wp_head', 'feed_links', 2 );
        add_action ('wp_head', array (__CLASS__, 'add_rss_feeds'));
        add_action ('wp_head', array (__CLASS__, 'configure_reftagger'));
        add_action ('wp_head', array (__CLASS__, 'add_icons_to_head'));
        add_action ('wp_head', array (__CLASS__, 'add_link_prefetching_to_head'));
        add_filter ('genesis_pre_load_favicon', array (__CLASS__, 'return_favicon_url'));
        // Menu
        add_filter ('wp_nav_menu_items', array (__CLASS__, 'modify_menu'));
        add_filter ('genesis_structural_wrap-menu-primary', array (__CLASS__, 'add_logo_to_nav_bar'));
        add_filter ('wp_nav_menu_items', array (__CLASS__, 'add_search_button_to_nav_bar'), 10, 2);
        remove_action ('genesis_after_header', 'genesis_do_nav');
        add_action    ('genesis_before_header', 'genesis_do_nav');
        // Remove the standard header and footer
        remove_action ('genesis_header', 'genesis_header_markup_open', 5 );
        remove_action ('genesis_header', 'genesis_do_header' );
        remove_action ('genesis_header', 'genesis_header_markup_close', 15 );
        remove_action ('genesis_footer', 'genesis_do_footer');
         // Add our own footer
        add_action ('genesis_footer', array (__CLASS__, 'do_footer_bottom'));
        // Other bits and pieces
        add_action ('genesis_meta', array (__CLASS__, 'add_viewport'));
        remove_action ('genesis_entry_footer', 'genesis_post_meta' );
        unregister_sidebar( 'header-right' );
        add_filter ('genesis_attr_entry-header', array (__CLASS__, 'add_attributes_to_entry_header'));
        //* Theme support
        add_theme_support( 'html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption')); //* Add HTML5 markup structure
        add_theme_support( 'genesis-accessibility', array( 'headings', 'search-form', 'skip-links', 'rems' ) ); //* Add Accessibility support
        
        // Front page
        if (is_front_page()) {
            add_action ('genesis_meta', array (__CLASS__, 'add_google_structured_data_to_homepage'));
            add_action ('genesis_meta', array (__CLASS__, 'add_facebook_app_id_to_homepage'));
        }
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
            // Adds Facebook javascript SDK for social media buttons
            add_action ('genesis_before', array (__CLASS__, 'output_facebook_javascript_sdk'));
            // Add some schema.org meta
            add_action ('genesis_before_entry_content', array (__CLASS__, 'add_before_start_of_article'));
            // Add the author/'see also' detail at the end of the article (also increases the view count)
            add_action ('genesis_after_entry_content', array (__CLASS__, 'add_after_end_of_article'));
            self::add_full_size_header_image();
            // Add extra meta tags for social media embeds
            add_action ('genesis_meta', array (__CLASS__, 'add_facebook_open_graph'));
            add_action ('genesis_meta', array (__CLASS__, 'add_twitter_card'));
            add_action ('genesis_meta', array (__CLASS__, 'add_google_breadcrumb'));
            // Adds the correct schema.org microdata
            add_filter ('genesis_attr_entry', array (__CLASS__, 'add_schema_org_microdata'), 10, 2);
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
        // Search results
        elseif (is_search()) {
            remove_action( 'genesis_entry_header', 'genesis_do_post_title' );
            remove_action ('genesis_entry_content', 'genesis_do_post_image', 8);
            add_action ('genesis_loop', 'genesis_posts_nav', 3);
            add_action ('genesis_entry_content', array(__CLASS__, 'do_post_image_for_search'), 6);
            add_action ('genesis_entry_content', array (__CLASS__, 'open_div'), 6);
            add_action ('genesis_entry_content', 'genesis_do_post_title', 8);
            add_action ('genesis_entry_content', array (__CLASS__, 'do_article_meta_for_search'), 9);
            add_action ('genesis_entry_content', array (__CLASS__, 'close_div'), 13);
            add_filter ('genesis_post_title_output', array(__CLASS__, 'filter_post_title_for_search_terms'));
            add_action ('genesis_after_loop', array (__CLASS__, 'add_to_end_of_search_page'), 12);
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
        wp_enqueue_style ('magazine-font-lato', get_stylesheet_directory_uri().'/fonts/lato.css', array(), CHILD_THEME_VERSION);
        wp_enqueue_style ('dashicons');
    }
    
    /**
    * Filters the primary menu markup to add the logo
    * 
    * @param string $markup
    * return string
    */
    public static function add_logo_to_nav_bar($markup) {
        if ($markup == "<div class=\"wrap\">") {
            return "{$markup}<a class=\"logo\" href=\"".get_site_url()."\"></a>";
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
            $image = wp_get_attachment_image_src($image_id, is_singular('em_author') ? 'author_medium' : (is_singular('em_issue') ? 'issue_very_large' : 'article_header'));
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
            $output = 'By '.$article->get_author_names(true, true); 
            $output .= "<span style=\"float:right\">{$article->get_issue_name(true)}";
            if ($page_num = $article->get_page_num()) {
                $output .= ", page <span itemprop=\"pageStart\">{$page_num}</span>";
            }
            return "{$output}</span>";
        } else {
            return $post_info;
        }
    }
    
    /**
    * Outputs schema.org microdata before the text of the article
    * 
    * Called by the genesis_before_entry_content action.
    * 
    */
    public static function add_before_start_of_article() {
        global $post;
        $article = new evangelical_magazine_article($post);
        $date = $article->get_issue_datetime();
        $logo = get_template_directory_uri().'/images/emw-logo.png';
        $microdata = new evangelical_mag_microdata();
        echo $microdata->get_ImageObject($article->get_image_url('article_header'), 800, 400);
        echo $microdata->get_datePublished($article->get_issue_datetime());
        echo $microdata->get_dateModified($article->get_post_datetime());
        echo $microdata->get_publisher('Evangelical Movement of Wales', 'https://www.emw.org.uk/', $logo);
        echo $microdata->meta ('mainEntityOfPage', $article->get_link());
        $sections = $article->get_sections();
        if ($sections) {
            foreach ($sections as $section) {
                echo $microdata->meta ('articleSection', $section->get_name());
            }
        }
        echo $microdata->meta ('isFamilyFriendly', 'true');
   }
    
   /**
   * Outputs Author/Series/Section information at the end of articles
   * Also updates the view count, as it's only called for singular article views.
   * 
   * Called by the genesis_after_entry_content action.
   * 
   */
   public static function add_after_end_of_article () {
        global $post;
        $article = new evangelical_magazine_article($post);
        if (!is_user_logged_in()) {
            $article->record_view_count();
        }
        echo "<div class=\"after-article\">";
        // Facebook buttons
        echo "<h3>Share or recommend</h3><div style=\"margin-bottom: 2em\" class=\"fb-like\" data-href=\"{$article->get_link()}\" data-width=\"680\" data-layout=\"standard\" data-action=\"like\" data-show-faces=\"true\" data-share=\"true\"></div>\r\n";
        // Show authors
        $authors = $article->get_authors();
        if ($article->has_series()) {
            $also_in = $article->get_articles_in_same_series();
        } else {
            $also_in = array();
        }
        $excluded_articles = array();
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
                        $excluded_articles[] = $also_article->get_id();
                    }
                }
            }
            echo '</div>';
        }
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
        echo "</div>";
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
   * Helper function to outputs an opening div
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
        $admin_link = '<a href="'.get_admin_url().'">.</a>';
        echo "<a class=\"logo\" href=\"".get_site_url()."\"></a>";
        echo "<p class=\"footer-details\"><span class=\"emw\">The Evangelical Magazine is published by the <a href=\"https://www.emw.org.uk/\">Evangelical Movement of Wales</a>{$admin_link}</span><br/>";
        echo "<span class=\"address\">Waterton Cross Business Park, South Road, Bridgend CF31 3UL{$admin_link}</span><br/>";
        echo "<span class=\"registration\">Registered charity number 222407{$admin_link}</span></p>";
        $rss_feed = get_post_type_archive_feed_link('em_article');
        echo '<p class="social-icons"><a href="https://www.facebook.com/evangelicalmagazine"><span class="dashicons dashicons-facebook"></span></a><a href="https://twitter.com/EvangelicalMag"><span class="dashicons dashicons-twitter"></span></a><a href="mailto:admin@evangelicalmagazine.com"><span class="dashicons dashicons-email"></span><a href="'.$rss_feed.'"><span class="dashicons dashicons-rss"></span></a></p>';
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
                    $issue_menu .= "<a href=\"{$issue->get_link()}\" itemprop=\"url\">{$issue->get_image_html ('issue_small')}<span itemprop=\"name\">{$issue_name}</span></a></li>";
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
                    $author_menu .= "<a href=\"{$author->get_link()}\" itemprop=\"url\">{$author->get_image_html ('square_thumbnail_tiny')}<span itemprop=\"name\">{$author->get_name()}</span></a></li>";
                }
                $author_menu .= "<li id=\"menu-item-more-authors\" class=\"menu-item menu-item-type-custom menu-item-object-custom menu-item-more-authors\"><a href=\"".get_post_type_archive_link ('em_author')."\" itemprop=\"url\"><span itemprop=\"name\">More&hellip;</span></a></li>";
                $author_menu .= '</ul>';  // The closing div will be added by the str_replace at the end of the function
                $menu = str_replace ($text_to_look_for, $text_to_look_for.$author_menu, $menu);
            }
        }
        //Sections
        $text_to_look_for = '<span itemprop="name">Sections</span></a>';
        if (strpos($menu, $text_to_look_for) !== FALSE) {
            $menu = str_replace ('<a href="#" itemprop="url"><span itemprop="name">Sections</span></a>', '<a href="'.get_post_type_archive_link ('em_section').'" itemprop="url"><span itemprop="name">Sections</span></a>', $menu);
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
        $articles = $author->_get_articles($author::_future_posts_args());
        if ($articles) {
            echo "<h3 class=\"articles_by\">Articles by {$author->get_name()}</h3>";
            $chunks = array_chunk ($articles, 3);
            foreach ($chunks as $chunk) {
                //echo "<div class=\"article-box-row-wrap\">";
                foreach ($chunk as $article) {
                    $sub_title = $article->is_future() ? "Coming {$article->get_coming_date()}" : $article->get_issue_name(true);
                    echo $article->get_small_box_html(true, $sub_title);
                }
                //echo '</div>';
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
               echo "<div class=\"grid-author-container\"><a href=\"{$author->get_link()}\" class=\"grid-author-image image-fit\" style=\"background-image:url('{$author->get_image_url('author_medium')}')\"></a><div class=\"author-name-description\"><div class=\"author-name\">{$author->get_name(true)}</div><div class=\"author-description\">{$author->get_filtered_content()}</div></div></div>";
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
               echo "<li class=\"issue\"><a href=\"{$issue->get_link()}\"><div class=\"magazine-cover image-fit box-shadow-transition\" style=\"background-image:url('{$issue->get_image_url('issue_medium')}')\"></div></a>";
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
                       echo "<li><a href=\"{$article->get_link()}\"><div class=\"article-image image-fit\" style=\"background-image:url('{$article->get_image_url('article_small')}')\"></div></a><span class=\"article-title\">{$article->get_title(true)}</span><br/><span class=\"article-authors\">by {$article->get_author_names(true)}</span></li>";
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
        $html = self::get_article_list_box($articles);
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
        $articles = $section->_get_articles ($args);
        if ($articles) {
            echo "<div class=\"section-page\">".self::get_article_list_box($articles)."</div>";
        } else {
            echo '<div class="article-list-box"><p>Coming soon.</p></div>';
        }
   }
   
   /**
   * Sharpens resized images.
   * 
   * Filters 'wp_generate_attachment_metadata'
   * 
   * @param array $meta
   * @return array
   */
   public static function enhance_media_images ($meta) {
        global $wp_filesystem;
        if ($wp_filesystem === NULL) {
            WP_Filesystem();
        }
        $dir = wp_upload_dir();
        foreach ($meta['sizes'] as $size => &$details) {
            $uploaded_file = trailingslashit($dir['basedir']).$meta['file'];
            $upload_folder = dirname($uploaded_file);
            $file = trailingslashit($upload_folder).$details['file'];
            list($orig_w, $orig_h, $orig_type) = @getimagesize($file);
            @ini_set( 'memory_limit', apply_filters( 'image_memory_limit', WP_MAX_MEMORY_LIMIT ) );
            $image = imagecreatefromstring ($wp_filesystem->get_contents($file));
            //Sharpen
            $matrix = array(array(-1, -1, -1), array(-1, 35, -1), array(-1, -1, -1));
            $divisor = array_sum(array_map('array_sum', $matrix));
            $offset = 0; 
            imageconvolution($image, $matrix, $divisor, $offset);
            // Save
            switch ($orig_type) {
                case IMAGETYPE_GIF:
                   imagegif ($image, $file);
                   break;
                case IMAGETYPE_PNG:
                   imagepng ($image, $file);
                   break;
                case IMAGETYPE_JPEG:
                   imagejpeg ($image, $file, apply_filters ('wp_editor_set_quality', 82, 'image/jpeg'));
                   break;
            }
        }
        return $meta;       
   }
    
   /**
   * Adds the viewport in the meta tag (at the moment, to disable mobile resizing)
   * 
   */
   public static function add_viewport() {
        echo '<meta name="viewport" content="width=device-width, initial-scale=1" />' . "\n";
   }
    
   /**
   * Adds the full size header image to the page
   * 
   */
   public static function add_full_size_header_image() {
        global $post;
        add_action ('genesis_meta', array (__CLASS__, 'add_image_to_pages'), 11);
        if (has_post_thumbnail()) {
            add_filter ('body_class', function($classes) {$classes[]="full-size-header-image";return $classes;});
        }
   }
    
   /**
   * Outputs the post thumbnail on the search page
   */
   public static function do_post_image_for_search () {
        $object = evangelical_magazine::get_object_from_id(get_the_ID());
        if ($object) {
            $size = $object->is_author() ? 'square_thumbnail_tiny' : ($object->is_article() ? 'article_small' : 'issue_medium');
            echo $object->get_image_html($size, true, 'search-thumbnail');
        } else {
            if (has_post_thumbnail()) {
                $src = wp_get_attachment_image_src (get_post_thumbnail_id(), 'article_small');
                echo "<a class=\"search-thumbnail\" href=\"".get_permalink()."\"><img src=\"{$src[0]}\" width=\"{$src[1]}\" height=\"{$src[2]}\"/></a>";
                
            }
        }
   }
    
   /**
   * Highlights search terms in the post title, if Relevanssi plugin is installed
   * 
   * @param string $title
   * @return string;
   */
   public static function filter_post_title_for_search_terms ($title) {
        if (function_exists('relevanssi_highlight_terms')) {
            return relevanssi_highlight_terms($title, get_search_query(false));
        } else {
            return $title;
        }
   }
    
   /**
   * Outputs the author names and issue.
   * 
   * Used in search results
   */
   public static function do_article_meta_for_search() {
        $object = evangelical_magazine::get_object_from_id(get_the_ID());
        if ($object && $object->is_article()) {
            echo "<p class=\"article-meta\">by {$object->get_author_names(true)} ({$object->get_issue_name(true)})</p>";
        }
   }
    
   /**
   * Adds the search button to the nav_bar
   * 
   * Filters wp_nav_menu_items
   * 
   * @param string $menu
   * @param object $args
   * @return string
   */
   public static function add_search_button_to_nav_bar ($menu, $args) {
        if ($args->theme_location === 'primary') {
            $output = $menu."<li class=\"menu-item search\"><a href=\"#\"><span class=\"dashicons dashicons-search\"></span></a>";
            $output .="<ul class=\"sub-menu sub-menu-search\"><div class=\"wrap\"><li id=\"\" class=\"menu-item\">".get_search_form(false)."</li></div></ul></li>";
            return $output;
        } else {
            return $menu;
        }
   }
    
   /**
   * Filters the search query to add terms from the URL to the search boxes.
   * 
   * Added to the get_search_query_filter on 404 pages
   * 
   * @param string $query
   * @return string
   */
   public static function filter_search_query_on_404 ($query) {
        $uri = $_SERVER['REQUEST_URI'];
        $wordpress_url = parse_url(home_url());
        $wordpress_path = $wordpress_url['path'];
        $keywords = urldecode(str_replace (array($wordpress_path,'/','-'), array('', ' ',' '), $uri));
        return $keywords;
   }
    
   /**
   * Outputs pagination at the end of search pages
   * 
   * Called by the genesis_after_loop action
   */
   public static function add_to_end_of_search_page() {
        echo "<div class=\"search-after-pagination\">".get_search_form(false)."</div>";
   }
    
   /**
   * Returns the HTML for a list of articles with thumbnails, title and author
   * 
   * @param array $articles
   * @return string
   */
   public static function get_article_list_box($articles, $make_first_image_bigger = true, $heading = '', $shrink_text_if_long = false) {
        if ($articles) {
            $output = "<div class=\"article-list-box\">";
            $output .= $heading ? "<h3>{$heading}</h3>" : '';
            $output .= "<ol>";
            $class = $make_first_image_bigger ? 'large-image' : '';
            foreach ($articles as $article) {
                $url = $article->get_image_url('article_large');
                $image_html = "<div class=\"box-shadow-transition article-list-box-image image-fit\" style=\"background-image: url('{$url}')\"></div>";
                if ($article->is_future()) {
                    $class = trim ($class.' future');
                } else {
                    $image_html = $article->get_link_html($image_html, array ('class' => 'article-image'));
                }
                $class = $class ? " class=\"{$class}\"" : '';
                $output .= "<li{$class}>{$image_html}";
                $title = $article->get_title();
                $style = ($shrink_text_if_long && strlen($title) > 35) ? ' style="font-size:'.round(35/strlen($title)*1,2).'em"' : '';
                $output .= "<div class=\"title-author-wrapper\"><span class=\"article-list-box-title\"><span{$style}>{$article->get_title(true)}</span></span><br/><span class=\"article-list-box-author\">by {$article->get_author_names(!$article->is_future())}</span>";
                if ($article->is_future()) {
                    $output .= "<br/><span class=\"article-list-box-coming-soon\">Coming {$article->get_coming_date()}</span>";
                }
                $output .= "</div></li>";
                $class = '';
            }
            $output .= "</ol>";
            $output .= '</div>';
            return $output;
        }
   }
    
   /**
   * Adds the RSS feeds to the HTML HEAD
   * 
   */
   public static function add_rss_feeds() {
        echo "<link rel=\"alternate\" type=\"application/rss+xml\" title=\"Evangelical Magazine Articles\" href=\"".get_post_type_archive_feed_link('em_article')."\" />\r\n";
        echo "<link rel=\"alternate\" type=\"application/rss+xml\" title=\"Evangelical Magazine Issues\" href=\"".get_post_type_archive_feed_link('em_issue')."\" />\r\n";
   }
    
   /**
   * Removes the default 'medium' and 'large' image sizes, so they're not unnecessarily created.
   * 
   * @param array $sizes
   * @return array
   */
   public static function remove_default_image_sizes($sizes) {
        unset( $sizes['medium']);
        unset( $sizes['large']);
        return $sizes;
   }
    
   /**
   * Adds the Reftagger code to the HTML HEAD
   * 
   */
   public static function configure_reftagger() {
        echo "<script>var refTagger = {settings: {bibleVersion: \"NIV\",libronixBibleVersion: \"DEFAULT\",addLogosLink: false,appendIconToLibLinks: false,libronixLinkIcon: \"dark\",noSearchClassNames: [],useTooltip: true,noSearchTagNames: [\"h1\"],linksOpenNewWindow: true,convertHyperlinks: false,caseInsensitive: false,tagChapters: true}};(function(d, t) {var g = d.createElement(t), s = d.getElementsByTagName(t)[0];g.src = '".get_stylesheet_directory_uri()."/js/reftagger.js';s.parentNode.insertBefore(g, s);}(document, 'script'));</script>\r\n";
   }
    
   /**
   * Adds the Facebook Open Graph tags to single articles
   * 
   */
   public static function add_facebook_open_graph() {
        $article = evangelical_magazine::get_object_from_id(get_the_ID());
        if ($article && $article->is_article()) {
            $image_details = $article -> get_image_details('facebook_share');
            $authors = $article->get_author_names();
            $article_preview = htmlspecialchars(wp_trim_words (strip_shortcodes($article->get_content()), 75, '…'), ENT_HTML5);
            echo "\r\n\t<meta property=\"og:url\" content=\"{$article->get_link()}\" />\r\n";
            echo "\t<meta property=\"og:title\" content=\"".htmlspecialchars($article->get_name()." — by {$authors}", ENT_HTML5)."\" />\r\n";
            echo "\t<meta property=\"og:description\" content=\"{$article_preview}\" />\r\n";
            echo "\t<meta property=\"og:site_name\" content=\"".htmlspecialchars(get_bloginfo('name'), ENT_HTML5)."\" />\r\n";
            echo "\t<meta property=\"og:image\" content=\"{$image_details['url']}\" />\r\n";
            echo "\t<meta property=\"og:image:url\" content=\"{$image_details['url']}\" />\r\n";
            echo "\t<meta property=\"og:image:width\" content=\"{$image_details['width']}\" />\r\n";
            echo "\t<meta property=\"og:image:height\" content=\"{$image_details['height']}\" />\r\n";
            echo "\t<meta property=\"og:image:type\" content=\"{$image_details['mimetype']}\" />\r\n";
            echo "\t<meta property=\"og:type\" content=\"article\" />\r\n";
            if ($authors == 'Mark Barnes') {
                echo "\t<meta property=\"article:author\" content=\"573010528\" />\r\n";
            }
            echo "\t<meta property=\"article:publisher\" content=\"https://www.facebook.com/evangelicalmagazine/\" />\r\n";
            echo "\t<meta property=\"og:locale\" content=\"en_GB\" />\r\n";
            echo "\t<meta property=\"og:rich_attachment\" content=\"true\" />\r\n";
            echo "\t<meta property=\"fb:app_id\" content=\"1248516525165787\" />\r\n";
        }
   }

   /**
   * Adds the Twitter Summary Card tags to single articles
   * 
   */
   public static function add_twitter_card() {
        $article = evangelical_magazine::get_object_from_id(get_the_ID());
        if ($article && $article->is_article()) {
            $image_details = $article -> get_image_details('twitter_share');
            $authors = $article->get_author_names();
            $article_preview = htmlspecialchars(wp_trim_words (strip_shortcodes($article->get_content()), 75, '…'), ENT_HTML5);
            echo "\r\n\t<meta name=\"twitter:card\" content=\"summary_large_image\">\r\n";
            echo "\t<meta name=\"twitter:site\" content=\"@EvangelicalMag\">";
            echo "\t<meta name=\"twitter:title\" content=\"".htmlspecialchars($article->get_name()." — by {$authors}", ENT_HTML5)."\" />\r\n";
            echo "\t<meta name=\"twitter:description\" content=\"{$article_preview}\" />\r\n";
            echo "\t<meta name=\"twitter:image\" content=\"{$image_details['url']}\" />\r\n";
            if ($authors == 'Mark Barnes') {
                echo "\t<meta name=\"twitter:creator\" content=\"@mbarnes\" />\r\n";
            }
        }
   }

   /**
   * Adds a breadcrumb to Google, for single articles
   * 
   */
   public static function add_google_breadcrumb () {
        $article = evangelical_magazine::get_object_from_id(get_the_ID());
        if ($article && $article->is_article() && $article->has_issue()) {
            $issue_name = htmlspecialchars($article->get_issue_name(), ENT_HTML5);
            echo "<script type=\"application/ld+json\">\r\n";
            echo "{\"@context\": \"http://schema.org\", \"@type\": \"BreadcrumbList\", \"itemListElement\": [";
            echo "{ \"@type\": \"ListItem\", \"position\": 1, \"item\": { \"@id\": \"{$article->get_issue_link()}\", \"name\": \"{$issue_name}\"}}";
            echo "]}\r\n";
            echo "</script>\r\n";
            
        }
   }
    
   /**
   * Adds structured data to the homepage, for Google
   * 
   */
   public static function add_google_structured_data_to_homepage () {
        $site_name = htmlspecialchars(get_bloginfo('name'), ENT_HTML5);
        $url = htmlspecialchars(get_home_url(), ENT_HTML5);
        $search_url = str_replace('search_term_string', '{search_term_string}', htmlspecialchars(get_search_link('search_term_string'), ENT_HTML5));
        $logo = htmlspecialchars(get_stylesheet_directory_uri().'/images/square-logo.png', ENT_HTML5);
        echo "\r\n";
        echo "<script type=\"application/ld+json\">{\"@context\" : \"http://schema.org\", \"@type\" : \"WebSite\", \"name\" : \"{$site_name}\", \"url\" : \"{$url}\", \"potentialAction\": {\"@type\": \"SearchAction\",\"target\": \"{$search_url}\",\"query-input\": \"required name=search_term_string\"}}</script>\r\n";
        echo "<script type=\"application/ld+json\">{\"@context\" : \"http://schema.org\", \"@type\" : \"Organization\", \"url\" : \"{$url}\", \"logo\" : \"{$logo}\", \"ContactPoint\" : [{ \"@type\" : \"ContactPoint\", \"telephone\" : \"+44-1656-655886\", \"contactType\" : \"customer support\" }]}</script>\r\n";
   }
    
   /**
   * Returns the URL of the favicon
   * 
   * @return string
   */
   public static function return_favicon_url() {
        return get_stylesheet_directory_uri().'/images/icons/favicon.ico';
   }
    
   /**
   * Outputs the HTML for various icons
   */
   public static function add_icons_to_head() {
        $u = get_stylesheet_directory_uri().'/images/icons';
        echo "\t<link rel=\"apple-touch-icon\" sizes=\"57x57\" href=\"{$u}/apple-touch-icon-57x57.png\">\r\n";
        echo "\t<link rel=\"apple-touch-icon\" sizes=\"60x60\" href=\"{$u}/apple-touch-icon-60x60.png\">\r\n";
        echo "\t<link rel=\"apple-touch-icon\" sizes=\"72x72\" href=\"{$u}/apple-touch-icon-72x72.png\">\r\n";
        echo "\t<link rel=\"apple-touch-icon\" sizes=\"76x76\" href=\"{$u}/apple-touch-icon-76x76.png\">\r\n";
        echo "\t<link rel=\"apple-touch-icon\" sizes=\"114x114\" href=\"{$u}/apple-touch-icon-114x114.png\">\r\n";
        echo "\t<link rel=\"apple-touch-icon\" sizes=\"120x120\" href=\"{$u}/apple-touch-icon-120x120.png\">\r\n";
        echo "\t<link rel=\"apple-touch-icon\" sizes=\"144x144\" href=\"{$u}/apple-touch-icon-144x144.png\">\r\n";
        echo "\t<link rel=\"apple-touch-icon\" sizes=\"152x152\" href=\"{$u}/apple-touch-icon-152x152.png\">\r\n";
        echo "\t<link rel=\"apple-touch-icon\" sizes=\"180x180\" href=\"{$u}/apple-touch-icon-180x180.png\">\r\n";
        echo "\t<link rel=\"icon\" type=\"image/png\" href=\"{$u}/favicon-32x32.png\" sizes=\"32x32\">\r\n";
        echo "\t<link rel=\"icon\" type=\"image/png\" href=\"{$u}/android-chrome-192x192.png\" sizes=\"192x192\">\r\n";
        echo "\t<link rel=\"icon\" type=\"image/png\" href=\"{$u}/favicon-96x96.png\" sizes=\"96x96\">\r\n";
        echo "\t<link rel=\"icon\" type=\"image/png\" href=\"{$u}/favicon-16x16.png\" sizes=\"16x16\">\r\n";
        echo "\t<link rel=\"manifest\" href=\"{$u}/manifest.json\">\r\n";
        echo "\t<link rel=\"mask-icon\" href=\"{$u}/safari-pinned-tab.svg\" color=\"#5bbad5\">\r\n";
        echo "\t<meta name=\"msapplication-TileColor\" content=\"#2d89ef\">\r\n";
        echo "\t<meta name=\"msapplication-TileImage\" content=\"{$u}/mstile-144x144.png\">\r\n";
        echo "\t<meta name=\"theme-color\" content=\"#ffffff\">\r\n";
   }
    
   /**
   * Outputs the Facebook Javascript SDK
   * 
   * Ideally called on 'genesis_before' action
   * 
   */
   public static function output_facebook_javascript_sdk() {
        echo '<div id="fb-root"></div><script>(function(d, s, id) { var js, fjs = d.getElementsByTagName(s)[0]; if (d.getElementById(id)) return; js = d.createElement(s); js.id = id; js.src = "//connect.facebook.net/en_GB/sdk.js#xfbml=1&version=v2.5&appId=1248516525165787"; fjs.parentNode.insertBefore(js, fjs);}(document, \'script\', \'facebook-jssdk\'));</script>'."\r\n";
   }
    
   /**
   * Adds the 'image-fit' class to the entry header div
   * 
   * Filters genesis_attr_entry-header
   * 
   * @param array $attributes
   * @return array
   */
   public static function add_attributes_to_entry_header($attributes) {
        if (isset($attributes['class'])) {
            $attributes['class'] .= ' image-fit';
        } else {
            $attributes['class'] = 'image-fit';
        }
        return $attributes;
   }
    
   /**
   * Enqueues the stylesheets for various media queries
   * 
   * Called on the wp_enqueue_scripts action
   * 
   * Having separate stylesheets makes editing easier.
   */
   public static function enqueue_media_stylesheets() {
        $sizes = array ('1000-1299' => 'screen and (min-width: 1000px) and (max-width: 1299px)',
                        '735-999' => 'screen and (min-width:735px) and (max-width: 999px)',
                        '560-734' => 'screen and (min-width:560px) and (max-width: 734px)',
                        '470-559' => 'screen and (min-width:470px) and (max-width: 559px)',
                        '370-469' => 'screen and (min-width:370px) and (max-width: 469px)',
                        '0-369' => 'screen and (max-width: 369px)');
        foreach ($sizes as $name => $media) {
            wp_enqueue_style ("magazine-css-{$name}", get_stylesheet_directory_uri()."/css/style-{$name}.css", false, CHILD_THEME_VERSION, $media);    
        }
   }
    
   /**
   * Adds link prefetching to the HEAD section
   * 
   * Called on the wp_head action
   * 
   * Should speed up http connections slightly in some modern browsers
   */
   public static function add_link_prefetching_to_head() {
        echo "\t<link rel=\"preconnect\" href=\"//connect.facebook.net\">\r\n";
        echo "\t<link rel=\"preconnect\" href=\"//bible.logos.com\">\r\n";
        echo "\t<link rel=\"preconnect\" href=\"//staticxx.facebook.com\">\r\n";
        echo "\t<link rel=\"preconnect\" href=\"//www.facebook.com\">\r\n";
   }
    
   /**
   * Adds the Facbook app ID to the HEAD section of the homepage
   * 
   * Called on the wp_head action
   * 
   * Provides authentication for Facebook
   */
   public static function add_facebook_app_id_to_homepage() {
        echo "\t<meta property=\"fb:app_id\" content=\"1248516525165787\" />\r\n";
        echo "\t<meta property=\"fb:pages\" content=\"317371662084\" />\r\n";
   }
    
   /**
   * Adds Beacon Ads javascript 
   * 
   * Intended to be called on the genesis_before action
   */
   public static function output_beacon_ads_main_code() {
        echo "\t<script type=\"text/javascript\">(function(){ var bsa = document.createElement('script'); bsa.type = 'text/javascript'; bsa.async = true; bsa.src = '//cdn.beaconads.com/ac/beaconads.js'; (document.getElementsByTagName('head')[0]||document.getElementsByTagName('body')[0]).appendChild(bsa);})();</script>\r\n";
   }
    
   public static function add_schema_org_microdata ($attributes, $context) {
        if ($context == 'entry') {
            $attributes['itemtype'] = 'http://schema.org/Article';
        }
        return $attributes;
   }
}