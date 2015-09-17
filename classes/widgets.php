<?php
class evangelical_magazine_widgets {

    static function register_widgets() {
        register_widget('evangelical_magazine_subscribe');
        register_widget('evangelical_magazine_most_popular');
    }    

}

class evangelical_magazine_subscribe extends WP_Widget {
    
    function __construct() {
        parent::__construct('evangelical_magazine_subscribe', 'Subscribe', array ('description' => 'Rotates between an advert to subscribe to the print edition, and a form to receive the email edition.'));
    }
    
    private function get_subscription_url() {
        $page = get_posts (array ('pagename' => 'subscribe', 'posts_per_page' => 1, 'post_type' => 'page'));
        if ($page) {
            return get_permalink($page[0]->ID);
        }
    }
    
    public function widget ($args, $instance) {
        if (rand(1,2) == 1) {
            $args['before_widget'] = str_replace ('widget_evangelical_magazine_subscribe', 'widget_evangelical_magazine_subscribe_print', $args['before_widget']);
            $link_url = $this->get_subscription_url();
            if ($link_url) {
                $image_url = get_stylesheet_directory_uri().'/images/subscribe.png';
                echo $args['before_widget'];
                echo "<a href=\"{$link_url}\"><div style=\"background-image:url('{$image_url}');width:386px;height:350px\"></div></a>";
                echo $args['after_widget'];
            }
        } else {
            if (function_exists('gravity_form')) {
                $args['before_widget'] = str_replace ('widget_evangelical_magazine_subscribe', 'widget_evangelical_magazine_subscribe_online', $args['before_widget']);
                echo $args['before_widget'];
                echo "{$args['before_title']}Get the latest articles by email{$args['after_title']}";
                echo "<div class=\"widget-contents\">";
                echo "<p class=\"description\">Get the latest articles for free, every Thursday evening.</p>";
                gravity_form (1, false, false, false, false, true);
                echo "</div>";
                echo $args['after_widget'];
            }
        }
    }
}
    
class evangelical_magazine_most_popular extends WP_Widget {
    
    function __construct() {
        parent::__construct('evangelical_magazine_most_popular', 'Most Popular Articles', array ('description' => 'Outputs a list of the most popular articles.'));
    }
    
    public function widget ($args, $instance) {
        global $post;
        $exclude = (isset($post->ID)) ? array($post->ID) : array();
        $articles = evangelical_magazine_article::get_top_articles(5, $exclude);
        if ($articles) {
            shuffle($articles);
            echo $args['before_widget'];
            echo "{$args['before_title']}Other popular articles{$args['after_title']}";
            echo "<ul>";
            $size = 'width_400';
            foreach ($articles as $article) {
                echo "<li class=\"popular_article\">";
                echo "<a href=\"{$article->get_link()}\"><div class=\"popular-article-cover\" style=\"background-image:url('{$article->get_image_url($size)}')\"></div></a>";
                echo "<div class=\"article-info\">{$article->get_name(true)} by {$article->get_author_names(true)}</div></li>";
                $size = 'thumbnail_75';
            }
            echo "</ul>";
            echo $args['after_widget'];
        }
    }
}