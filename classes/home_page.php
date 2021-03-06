<?php
/**
* Class containing functions to generate the home page
*
* @package evangelical-magazine-theme
* @author Mark Barnes
* @access public
*/
class evangelical_mag_home_page {

	/**
	* Outputs the home page
	*
	* Called by the 'genesis_loop' action, added by front-page.php
	*
	* @return void
	*/
	public static function do_home_page() {
		$recent_article_ids = self::do_most_recent_articles_and_reviews();
		self::do_subscription_form();
		self::do_sections(1, $recent_article_ids);
	}

	/**
	* Outputs the 'recent articles' module on the home page
	*
	* @return void
	*/
	public static function do_most_recent_articles_and_reviews() {
		$latest_issues = evangelical_magazine_issue::get_all_issues(1);
		if ($latest_issues) {
			//Output the cover of the most recent issue
			echo '<aside id="recent-articles">';
			echo evangelical_mag_theme::return_background_image_style ('latest-cover', $latest_issues[0]->get_image_url('issue_medium'));
			echo "<a href=\"{$latest_issues[0]->get_link()}\"><div id=\"latest-cover\" class=\"box-shadow-transition image-fit\"></div></a>";
			//Get the seven most recently published articles
			$articles = evangelical_magazine_articles_and_reviews::get_recent_articles_and_reviews(7);
			$next = evangelical_magazine_articles_and_reviews::get_next_future_article_or_review();
			if ($next) {
				array_unshift($articles, $next);
				$articles = array_slice ($articles, 0, 7);
			}
			// Output these articles
			$article_ids = array();
			if ($articles) {
				echo '<div id="latest-articles">';
				foreach ($articles as $article) {
					echo '<div class="article-wrap">';
					echo evangelical_mag_theme::return_background_image_style ("article{$article->get_id()}", $article->get_image_url('article_large'));
					if ($article->is_future()) {
						echo "<div id=\"article{$article->get_id()}\" class=\"article future image-fit\"></div>";
						echo "<div class=\"article-coming-soon\"><span class=\"coming-soon\">Coming {$article->get_coming_date()}</span><span class=\"article-title\">{$article->get_title()}</span></div>";
					} else {
						echo "<a href=\"{$article->get_link()}\"><span id=\"article{$article->get_id()}\" class=\"article current image-fit box-shadow-transition\"><span class=\"article-title\">{$article->get_title()}</span></span></a>";
					}
					echo '</div>';
					$article_ids[] = $article->get_id();
				}
				echo '</div>';
			}
			echo '</aside>';
			return $article_ids;
		}
	}

	/**
	* Outputs the subscription form on the home page
	*
	* return void
	*/
	public static function do_subscription_form() {
		if (function_exists ('gravity_form')) {
			echo "<aside id=\"subscription-form\"><p>Get a new article every week:</p>";
			gravity_form ('Email subscribe (homepage)', false, true, false, null, true, 1);
			echo "</aside>";
		}
	}

	/**
	* Outputs the 'sections' module on the home page
	*
	* @param integer $max_per_section - maximum number of articles per section
	* @param integer[] $exclude_article_ids - array of article ids to exclude from this process
	* @return void
	*/
	public static function do_sections($max_per_section = 5, $exclude_article_ids = array()) {
		$args = array ('orderby' => 'name', 'order' => 'ASC');
		$sections = evangelical_magazine_section::get_all_sections($args);
		$output = array();
		if ($sections) {
			foreach ($sections as $section) {
				$articles = $section->get_articles_and_reviews(1, $exclude_article_ids);
				$info_box = evangelical_mag_theme::get_article_list_box($articles, true, $section->get_name(true));
				if ($info_box) {
					$output[$articles[0]->get_publish_date('U')] = $info_box;
					$exclude_article_ids = array_merge ($exclude_article_ids, evangelical_magazine_article::get_object_ids_from_array($articles));
				}
			}
			echo '<aside id="sections">';
			krsort ($output);
			foreach ($output as $o) {
				echo $o;
			}
			echo '</aside>';
		}
	}
}