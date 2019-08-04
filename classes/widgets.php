<?php
/**
* A class that adds various widgets
*
* @package evangelical-magazine-theme
* @author Mark Barnes
* @access public
*/
class evangelical_mag_widgets {

	/**
	* Register the various widgets, and add necessary actions
	*
	* @return void
	*/
	public static function register_widgets() {
		register_widget('evangelical_magazine_subscribe');
		register_widget('evangelical_magazine_most_popular');
		register_widget('evangelical_magazine_current_issue');
		register_widget('evangelical_magazine_beacon_ad');
		register_widget('evangelical_magazine_facebook_page_plugin');
		add_action ('genesis_before', array ('evangelical_mag_theme', 'output_beacon_ads_main_code'));
		add_action ('genesis_before', array ('evangelical_mag_theme', 'output_facebook_javascript_sdk'));
	}
}

/**
* The subscription widget class
*/
class evangelical_magazine_subscribe extends WP_Widget {

	/**
	* Instantiate the widget
	*
	* @return void
	*/
	function __construct() {
		parent::__construct('evangelical_magazine_subscribe', 'Subscribe', array ('description' => 'Rotates between an advert to subscribe to the print edition, and a form to receive the email edition.'));
	}

	/**
	* Returns the URL of the subscription page
	*
	* @return string
	*/
	private function get_subscription_url() {
		$page = get_posts (array ('pagename' => 'subscribe', 'posts_per_page' => 1, 'post_type' => 'page'));
		if ($page) {
			return get_permalink($page[0]->ID);
		}
	}

	/**
	* Outputs the widget
	*
	* @param array $args - WP_Widget arguments
	* @param array $instance
	* @return void
	*/
	public function widget ($args, $instance) {
		if (rand(1,2) == 1) {
			$args['before_widget'] = str_replace ('widget_evangelical_magazine_subscribe', 'widget_evangelical_magazine_subscribe_print', $args['before_widget']);
			$link_url = $this->get_subscription_url();
			if ($link_url) {
				echo $args['before_widget'];
				echo "<a href=\"{$link_url}\"><div class=\"subscription-image image-fit\"></div></a>";
				echo $args['after_widget'];
			}
		} else {
			if (function_exists('gravity_form')) {
				$args['before_widget'] = str_replace ('widget_evangelical_magazine_subscribe', 'widget_evangelical_magazine_subscribe_online', $args['before_widget']);
				echo $args['before_widget'];
				echo "{$args['before_title']}Get the latest articles by email{$args['after_title']}";
				echo "<div class=\"widget-contents\">";
				echo "<p class=\"description\">Get the latest articles for free, every Thursday.</p>";
				gravity_form ('Email subscribe (widget)', false, true, false, null, true, 1);
				echo "</div>";
				echo $args['after_widget'];
			}
		}
	}
}

/**
* The popular posts widget class
*/
class evangelical_magazine_most_popular extends WP_Widget {

	/**
	* Instantiate the widget
	*
	* @return void
	*/
	function __construct() {
		parent::__construct('evangelical_magazine_most_popular', 'Most Popular Articles', array ('description' => 'Outputs a list of the most popular articles.'));
	}

	/**
	* Outputs the widget
	*
	* @param array $args - WP_Widget arguments
	* @param array $instance
	* @return void
	*/
	public function widget ($args, $instance) {
		global $post;
		$exclude = (isset($post->ID)) ? array($post->ID) : array();
		$num_articles = (isset ($post->post_type) && $post->post_type == 'em_article') ? 10 : 5;
		$articles = evangelical_magazine_article::get_top_articles($num_articles, $exclude);
		if ($articles) {
			echo $args['before_widget'];
			$title = (isset ($post->post_type) && $post->post_type == 'em_article') ? 'Other popular articles' : 'Popular articles';
			echo "{$args['before_title']}{$title}{$args['after_title']}";
			echo "<ul>";
			$size = 'article_large';
			foreach ($articles as $article) {
				echo "<li class=\"popular_article\">";
				echo evangelical_mag_theme::return_background_image_style ("popular-article{$article->get_id()}", $article->get_image_url($size));
				echo "<a href=\"{$article->get_link()}\"><div id=\"popular-article{$article->get_id()}\" class=\"popular-article-cover image-fit\"></div></a>";
				echo "<div class=\"article-info-wrap\"><div class=\"article-info\">{$article->get_name(true)}<br/><strong>{$article->get_author_names(true, false)}</strong></div>";
				$facebook_stats = $article->get_facebook_stats('reactions');
				if ($facebook_stats) {
					$likes = $facebook_stats > 1 ? 'likes' : 'like';
					$stats = number_format($facebook_stats);
					echo "<div class=\"facebook_stats\"><span class=\"magazine-dashicons magazine-dashicons-thumbs-up\"></span> {$stats} {$likes}</div>";
				}
				echo "</div></li>";
				$size = 'article_small';
			}
			echo "</ul>";
			echo $args['after_widget'];
		}
	}
}

/**
* The current issue widget class
*/
class evangelical_magazine_current_issue extends WP_Widget {

	/**
	* Instantiate the widget
	*
	* @return void
	*/
	function __construct() {
		parent::__construct('evangelical_magazine_current_issue', 'Current issue', array ('description' => 'Provides a link to the current issue of the magazine.'));
	}

	/**
	* Outputs the widget
	*
	* @param array $args - WP_Widget arguments
	* @param array $instance
	* @return void
	*/
	public function widget ($args, $instance) {
		$issues = evangelical_magazine_issue::get_all_issues(1);
		if ($issues) {
			if (!((is_singular('em_issue') && get_the_ID() == $issues[0]->get_id()) || is_post_type_archive('em_issue'))) {
				echo $args['before_widget'];
				echo "{$args['before_title']}Latest issue{$args['after_title']}";
				foreach ($issues as $issue) {
					echo evangelical_mag_theme::return_background_image_style ("current-issue{$issue->get_id()}", $issue->get_image_url('issue_very_large'));
					echo $issue->get_link_html("<div id=\"current-issue{$issue->get_id()}\" class=\"cover-image image-fit\"></div>");
				}
				echo $args['after_widget'];
			}
		}
	}
}

/**
* The Facebook page widget class
*/
class evangelical_magazine_facebook_page_plugin extends WP_Widget {

	/**
	* Instantiate the widget
	*
	* @return void
	*/
	function __construct() {
		parent::__construct('evangelical_magazine_facebook_page_plugin', 'Facebook Page Plugin', array ('description' => 'Displays a mini Facebook page to encourage likes.'));
	}

	/**
	* Outputs the widget
	*
	* @param array $args - WP_Widget arguments
	* @param array $instance
	* @return void
	*/
	public function widget ($args, $instance) {
		echo $args['before_widget'];
		echo "{$args['before_title']}Like us on Facebook{$args['after_title']}";
		echo '<div class="fb-page" data-href="https://www.facebook.com/evangelicalmagazine/" data-width="520" data-small-header="false" data-adapt-container-width="true" data-hide-cover="false" data-show-facepile="true" data-hide-cta="true"><div class="fb-xfbml-parse-ignore"><blockquote cite="https://www.facebook.com/evangelicalmagazine/"><a href="https://www.facebook.com/evangelicalmagazine/">Evangelical Magazine</a></blockquote></div></div>';
		echo $args['after_widget'];
	}
}

/**
* The beacon ads widget class
*/
class evangelical_magazine_beacon_ad extends WP_Widget {

	/**
	 * Default instance.
	 *
	 * @var array
	 */
	protected $default_instance = array(
		'zone' => 'Name of zone',
		'ad_code' => '',
	);

	/**
	* Instantiate the widget
	*
	* @return void
	*/
	function __construct() {
		parent::__construct('evangelical_magazine_beacon_ad', 'Beacon Ads Plugin', array ('description' => 'Inserts an ad from Beacon Ads.'));
	}

	/**
	* Displays the widget options
	*
	* @param array $instance - current instance
	* @return void
	*/
	public function form ($instance) {
		$instance = wp_parse_args ((array)$instance, $this->default_instance);
		echo "<p><label for=\"{$this->get_field_id ('zone')}\">Zone:</label>";
		echo "<input id=\"{$this->get_field_id ('zone')}\" name=\"{$this->get_field_name ('zone')}\" class=\"widefat title\" value=\"".esc_attr($instance ['zone'])."\"></p>";
		echo "<p><label for=\"{$this->get_field_id ('ad_code')}\">Ad code (from step 2):</label>";
		echo "<textarea id=\"{$this->get_field_id ('ad_code')}\" name=\"{$this->get_field_name ('ad_code')}\" class=\"widefat content\" style=\"height:100px\">".esc_textarea($instance['ad_code'])."</textarea></p>";
	}

	/**
	* Handles updating the options
	*
	* @param array $new_instance  - the new settings for this instance
	* @param array $old_instance - the old settings for this instance
	* @return array|bool - settings to save or false to cancel
	*/
	public function update ($new_instance, $old_instance) {
		$instance = array_merge($this->default_instance, $old_instance);
		$instance['zone'] = sanitize_text_field ($new_instance['zone']);
		if (current_user_can ('unfiltered_html')) {
			$instance['ad_code'] = $new_instance['ad_code'];
		} else {
			$instance['ad_code'] = wp_kses_post($new_instance['ad_code']);
		}
		return $instance;
	}

	/**
	* Outputs the widget
	*
	* @param array $args - WP_Widget arguments
	* @param array $instance
	* @return void
	*/
	public function widget ($args, $instance) {
		echo $args['before_widget'];
		echo $instance['ad_code'];
		echo $args['after_widget'];
	}
}