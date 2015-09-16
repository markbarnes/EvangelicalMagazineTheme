<?php
class evangelical_magazine_widgets {

    static function register_widgets() {
        register_widget('evangelical_magazine_subscribe');
    }    

}

class evangelical_magazine_subscribe extends WP_Widget {
    
    function __construct() {
        parent::__construct('evangelical_magazine_subscribe', 'Subscribe', array ('description' => 'Outputs a subscription advert.'));
    }
    
    private function get_subscription_url() {
        $page = get_posts (array ('pagename' => 'subscribe', 'posts_per_page' => 1, 'post_type' => 'page'));
        if ($page) {
            return get_permalink($page[0]->ID);
        }
    }
    
    public function widget ($args, $instance) {
        if (($a = rand(1,3)) == 1) {
            $link_url = $this->get_subscription_url();
            if ($link_url) {
                $image_url = get_stylesheet_directory_uri().'/images/subscribe.png';
                echo $args['before_widget'];
                if (!empty($instance['title'])) {
                    echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ). $args['after_title'];
                }
                echo "<a href=\"{$link_url}\"><div style=\"background-image:url('{$image_url}');width:360px;height:326px\"></div></a>";
                echo $args['after_widget'];
            }
        }
    }
    
}