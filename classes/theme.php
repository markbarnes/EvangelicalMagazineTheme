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
	*
	* @return void
	*/
	public static function set_everything_up() {
		// Remove jQuery migrate
		add_action ('wp_default_scripts', array ('evangelical_mag_theme', 'remove_jquery_migrate'));

		//Editor tweaks
		add_action ('admin_enqueue_scripts', array (__CLASS__, 'enqueue_fonts'));

		// HTML HEAD
		add_action ('wp_enqueue_scripts', array (__CLASS__, 'enqueue_fonts'));
		add_action ('wp_enqueue_scripts', array (__CLASS__, 'enqueue_media_stylesheets'));
		add_action ('wp_enqueue_scripts', array (__CLASS__, 'enqueue_webp_detection'));
		add_action ('wp_enqueue_scripts', array (__CLASS__, 'disable_emojis'));
		add_action ('wp_enqueue_scripts', array (__CLASS__, 'enqueue_reftagger'));
		add_filter ('genesis_superfish_enabled', '__return_false');
		remove_action ('wp_head', 'feed_links_extra', 3);
		remove_action ('wp_head', 'feed_links', 2 );
		add_action ('wp_head', array (__CLASS__, 'add_rss_feeds'));
		add_action ('wp_head', array (__CLASS__, 'configure_reftagger'));
		add_action ('wp_head', array (__CLASS__, 'add_icons_to_head'));
		add_filter ('genesis_pre_load_favicon', array (__CLASS__, 'return_favicon_url'));
		add_filter ('wp_resource_hints', array(__CLASS__, 'filter_resource_hints'), 10, 2 );
		// Menu
		add_filter ('wp_nav_menu_items', array (__CLASS__, 'modify_menu'));
		add_filter ('genesis_structural_wrap-menu-primary', array (__CLASS__, 'add_logo_to_nav_bar'));
		add_filter ('wp_nav_menu_items', array (__CLASS__, 'add_search_button_to_nav_bar'), 10, 2);
		remove_action ('genesis_after_header', 'genesis_do_nav');
		add_action	('genesis_before_header', 'genesis_do_nav');
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
		add_filter ('option_rg_gforms_disable_css', '__return_true'); // Disables Gravity Forms CSS

		// Front page
		if (is_front_page()) {
			add_action ('genesis_meta', array (__CLASS__, 'add_google_structured_data_to_homepage'));
			add_action ('genesis_meta', array (__CLASS__, 'add_facebook_app_id_to_homepage'));
		}
		// All singular pages
		if (is_singular()) {
			remove_action ('genesis_entry_header', 'genesis_post_info', 12);
			add_filter ('genesis_post_meta', '__return_false');
			// Put the post text inside an extra div inside entry-content
			remove_filter ('genesis_attr_entry-content', 'genesis_attributes_entry_content');
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
		// Everything apart from articles and reviews
		if (!is_singular('em_article') && !is_singular('em_review')) {
			add_filter ('genesis_post_info', '__return_false');
		}

		// Single articles
		if (is_singular('em_article')) {
			// Add extra info to the title tag
			add_filter ('pre_get_document_title', array (__CLASS__, 'filter_title_tag'));
			// Wrap entry-content
			add_action ('genesis_entry_content', array (__CLASS__, 'open_div_with_itemprop_text'), 9);
			add_action ('genesis_entry_content', array (__CLASS__, 'close_div'), 11);
			// Move the post_info to AFTER the closing </header> tag
			add_action ('genesis_entry_header', 'genesis_post_info', 16);
			// Filter the post_info
			add_filter ('genesis_post_info', array (__CLASS__, 'add_author_issue_banner'));
			// Add series info to the post title, if required
			add_filter ('genesis_post_title_output', array (__CLASS__, 'add_series_to_title'));
			// Adds Facebook javascript SDK for social media buttons
			add_action ('genesis_before', array (__CLASS__, 'output_facebook_javascript_sdk'));
			// Add some schema.org meta
			add_action ('genesis_before_entry_content', array (__CLASS__, 'add_schema_org_data_to_articles'));
			add_filter ('genesis_attr_entry', array (__CLASS__, 'add_schema_org_itemtype_to_articles'), 10, 2);
			// Add the author/'see also' detail at the end of the article (also increases the view count)
			add_action ('genesis_entry_content', array (__CLASS__, 'add_series_toc_if_required'), 8);
			add_action ('genesis_entry_content', array (__CLASS__, 'add_next_in_series_if_required'), 12);
			add_action ('genesis_after_entry_content', array (__CLASS__, 'add_after_end_of_article_or_review'));
			self::add_full_size_header_image();
			// Add extra meta tags for social media embeds
			add_action ('genesis_meta', array (__CLASS__, 'add_facebook_open_graph'));
			add_action ('genesis_meta', array (__CLASS__, 'add_twitter_card'));
			add_action ('genesis_meta', array (__CLASS__, 'add_google_breadcrumb'));
		}
		// Single reviews
		if (is_singular('em_review')) {
			// Add extra info to the title tag
			add_filter ('pre_get_document_title', array (__CLASS__, 'filter_title_tag'));
			// Wrap entry-content
			add_action ('genesis_entry_content', array (__CLASS__, 'open_div_with_itemprop_text'), 9);
			add_action ('genesis_entry_content', array (__CLASS__, 'close_div'), 11);
			//Filter the post title
			add_filter('genesis_post_title_text', array ('evangelical_magazine_review', 'add_review_type_to_title'));
			// Move the post_info to AFTER the closing </header> tag
			add_action ('genesis_entry_header', 'genesis_post_info', 16);
			// Filter the post_info
			add_filter ('genesis_post_info', array (__CLASS__, 'add_review_metadata_to_page'));
			self::move_entry_header_inside_entry_content();
			//remove_action ('genesis_entry_header', 'genesis_do_post_title');
			add_action ('genesis_entry_content', array (__CLASS__, 'output_review_image'), 8); //Output the review image just after the header
			// Adds Facebook javascript SDK for social media buttons
			add_action ('genesis_before', array (__CLASS__, 'output_facebook_javascript_sdk'));
			// Add some schema.org meta
			add_action ('genesis_before_entry_content', array (__CLASS__, 'add_schema_org_data_to_reviews'));
			add_filter ('genesis_attr_entry', array (__CLASS__, 'add_schema_org_itemtype_to_reviews'), 10, 2);
			// Add the author/'see also' detail at the end of the article (also increases the view count)
			add_action ('genesis_after_entry_content', array (__CLASS__, 'add_after_end_of_article_or_review'));
			// Add extra meta tags for social media embeds
			add_action ('genesis_meta', array (__CLASS__, 'add_facebook_open_graph'));
			add_action ('genesis_meta', array (__CLASS__, 'add_twitter_card'));
			add_action ('genesis_meta', array (__CLASS__, 'add_google_breadcrumb'));
		}
		// Single author pages
		elseif (is_singular('em_author')) {
			// Specify the title image using styles in the <HEAD>
			add_action ('genesis_meta', array (__CLASS__, 'add_image_to_entry_header'), 11);
			self::move_entry_header_inside_entry_content();
			remove_action ('genesis_entry_header', 'genesis_do_post_title');
			add_action ('genesis_entry_content', 'genesis_do_post_title', 4);
			add_action ('genesis_entry_content', array (__CLASS__, 'open_div'), 4);
			add_action ('genesis_entry_content', array (__CLASS__, 'close_div'), 11);
			add_action ('genesis_entry_content', array (__CLASS__, 'add_to_end_of_author_page'), 12);
		}
		// Single issue pages
		elseif (is_singular('em_issue')) {
			add_action ('genesis_meta', array (__CLASS__, 'add_image_to_entry_header'), 11);
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
		// Single series pages
		elseif (is_singular('em_series')) {
			// Move the post_info to AFTER the closing </header> tag
			add_action ('genesis_entry_header', 'genesis_post_info', 16);
			// Add series info to the post title, if required
			add_filter ('genesis_post_title_output', array (__CLASS__, 'add_series_to_title'));
			self::add_full_size_header_image();
			add_action ('genesis_entry_content', array (__CLASS__, 'add_to_end_of_series_page'), 12);
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
			add_filter ('genesis_post_title_text', array('evangelical_magazine_review', 'add_review_type_to_title'));
			add_filter ('genesis_post_title_text', array(__CLASS__, 'filter_post_title_for_search_terms'));
			add_action ('genesis_after_loop', array (__CLASS__, 'add_to_end_of_search_page'), 12);
		}
	}

	/**
	* Adds the author name to the page title tag for articles and reviews
	* Filters pre_get_document_title
	*
	* @param string $title - the current title
	* @return string - the revised title
	*/
	public static function filter_title_tag ($title) {
		global $post;
		if ($post) {
			/**
			* @var evangelical_magazine_articles_and_reviews
			*/
			$object = evangelical_magazine::get_object_from_post($post);
			if ($object->is_article_or_review()) {
				$title = $object->get_title().$object->get_author_names(false, false, $object->is_article() ? ' — by ' : ', reviewed by ');
			}
		}
		return $title;
	}

	/**
	* Remove jQuery Migrate
	*
	* @param WP_Scripts $scripts - passed as a reference by the wp_default_scripts action
	*/
	public static function remove_jquery_migrate($scripts) {
		if (!is_admin() && isset($scripts->registered['jquery']) && $scripts->registered['jquery']->deps) {
			$scripts->registered['jquery']->deps = array_diff ($scripts->registered['jquery']->deps, array ('jquery-migrate'));
		}
	}

	/**
	* Enqueue fonts
	*
	* Called by the wp_enqueue_scripts action
	*
	* @return void
	*/
	public static function enqueue_fonts() {
		self::enqueue_style ('magazine-font-league-gothic', '/fonts/league-gothic.css');
		self::enqueue_style ('magazine-font-aleo', '/fonts/aleo.css');
		self::enqueue_style ('magazine-font-lato', '/fonts/lato.css');
		wp_enqueue_style ('dashicons');
	}

	/**
	* Enqueue reftagger
	*
	* Called by the wp_enqueue_scripts action
	*
	* @return void
	*/
	public static function enqueue_reftagger() {
		self::enqueue_script ('magazine-reftagger', '/js/reftagger.js', array(), true);
	}

	/**
	* Remove emoji support for older browsers to speed up page loading
	*
	* @link https://wordpress.org/plugins/disable-emojis/
	*
	* @return void
	*/
	public static function disable_emojis() {
		remove_action ('wp_head', 'print_emoji_detection_script', 7);
		remove_action ('admin_print_scripts', 'print_emoji_detection_script');
		remove_action ('wp_print_styles', 'print_emoji_styles');
		remove_action ('admin_print_styles', 'print_emoji_styles');
		remove_filter ('the_content_feed', 'wp_staticize_emoji');
		remove_filter ('comment_text_rss', 'wp_staticize_emoji');
		remove_filter ('wp_mail', 'wp_staticize_emoji_for_email');
	}

	/**
	* Filters the primary menu markup to add the logo
	*
	* @param string $markup - the existing markup
	* return string - the modified markup
	*/
	public static function add_logo_to_nav_bar($markup) {
		if ($markup == "<div class=\"wrap\">") {
			return "{$markup}<a class=\"logo\" href=\"".get_site_url()."\"></a>";
		} else {
			return $markup;
		}
	}

	/**
	* Adds the featured image to the top of the appropriate pages, by adding <style> to the HTML head.
	*
	* Called by the genesis_meta action, but only on single pages.
	*
	* @return void
	*/
	public static function add_image_to_entry_header() {
		$image_id = get_post_thumbnail_id ();
		if ($image_id) {
			$image = wp_get_attachment_image_src($image_id, (is_singular('em_author') || is_singular('em_review')) ? 'author_medium' : (is_singular('em_issue') ? 'issue_very_large' : 'article_header'));
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
	* Adds the author/issue banner below the article header image
	*
	* Filters genesis_post_info
	*
	* @param string $post_info - the existing post info
	* @return string - the new post info
	*/
	public static function add_author_issue_banner ($post_info) {
		global $post;
		if ($post && $post->post_type == 'em_article') {
			$article = new evangelical_magazine_article($post);
			$output = $article->get_author_names(true, true, 'By ');
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
	* Adds the review metadata below the article header image
	*
	* Filters genesis_post_info
	*
	* @param string $post_info - the existing post info
	* @return string - the new post info
	*/
	public static function add_review_metadata_to_page ($post_info) {
		global $post;
		if ($post && $post->post_type == 'em_review') {
			$review = new evangelical_magazine_review($post);
			$microdata = new evangelical_mag_microdata();
			$image_details = wp_get_attachment_image_src (get_post_thumbnail_id($review->get_id()), 'full');
			$image = $microdata->get_ImageObject($image_details[0], $image_details[1], $image_details[2]);
			$output = "<span class=\"media-metadata\" itemprop=\"itemReviewed\" itemscope itemtype=\"https://schema.org/Thing\">";
			$output .= $review->get_media_type_name('<span class="metadata-item"><span class="metadata-name">', ":</span> {$review->get_name(false, true)}</span>");
			$output .= $review->get_creator("<span class=\"metadata-item\"><span class=\"metadata-name\">{$review->get_creator_type()}:</span> ", '</span>');
			$output .= $review->get_publisher ('<span class="metadata-item"><span class="metadata-name">Publisher:</span> ', '</span>');
			$a = $review->get_price();
			$output .= $review->get_price('<span class="metadata-item"><span class="metadata-name">Retail Price:</span> ', $review->get_purchase_url(' (<a href="', '" target="_blank">'.(($review->get_price() == 'free') ? 'get' : 'buy now').'</a>)').'</span>');
			$output .= "</span>{$image}<br/>";
			$output .= "<span class=\"review-metadata\">{$review->get_author_names(true, true, 'Review by ')}";
			$output .= "<span style=\"float:right\">{$review->get_issue_name(true)}";
			if ($page_num = $review->get_page_num()) {
				$output .= ", page {$page_num}";
			}
			return "{$output}</span></span>";
		} else {
			return $post_info;
		}
	}

	/**
	* Outputs schema.org microdata before the text of the article
	*
	* @return void
	*/
	public static function add_schema_org_data_to_articles () {
		global $post, $_wp_additional_image_sizes;
		$article = new evangelical_magazine_article($post);
		$date = $article->get_issue_datetime();
		$logo = get_template_directory_uri().'/images/emw-logo.png';
		$microdata = new evangelical_mag_microdata();
		if (isset($_wp_additional_image_sizes['article_header'])) {
			echo $microdata->get_ImageObject($article->get_image_url('article_header'), $_wp_additional_image_sizes['article_header']['width'], $_wp_additional_image_sizes['article_header']['height']);
		}
		echo $microdata->get_datePublished($article->get_issue_datetime());
		echo $microdata->get_dateModified($article->get_post_datetime());
		echo $microdata->get_publisher('Evangelical Movement of Wales', 'https://www.emw.org.uk/', $logo);
		echo $microdata->get_meta ('mainEntityOfPage', $article->get_link());
		$sections = $article->get_sections();
		if ($sections) {
			foreach ($sections as $section) {
				echo $microdata->get_meta ('articleSection', $section->get_name());
			}
		}
		echo $microdata->get_meta ('isFamilyFriendly', 'true');
	}

	/**
	* Outputs schema.org microdata before the text of the review
	*
	* @return void
	*/
	public static function add_schema_org_data_to_reviews () {
		global $post;
		$review = new evangelical_magazine_review($post);
		$date = $review->get_issue_datetime();
		$logo = get_template_directory_uri().'/images/emw-logo.png';
		$microdata = new evangelical_mag_microdata();
		echo $microdata->get_datePublished($review->get_issue_datetime());
		echo $microdata->get_dateModified($review->get_post_datetime());
		echo $microdata->get_publisher('Evangelical Movement of Wales', 'https://www.emw.org.uk/', $logo);
		echo $microdata->get_meta ('mainEntityOfPage', $review->get_link());
		echo $microdata->get_meta ('isFamilyFriendly', 'true');
	}

	/**
	* Outputs all the data to be added to the end of articles and reviews
	* Also updates the view count, as it's only called for singular article/review views.
	*
	* @return void
	*/
	public static function add_after_end_of_article_or_review () {
		global $post;
		/**
		* @var evangelical_magazine_articles_and_reviews
		*/
		$object = evangelical_magazine::get_object_from_post($post);
		if (!is_user_logged_in()) {
			$object->record_view_count();
		}
		echo "<div class=\"after-article\">";
		self::output_facebook_like_share_buttons ($object);
		self::output_email_subscription_box();
		$articles_in_same_series = $object->get_articles_in_same_series();
		if (count($articles_in_same_series) > 1) {
			self::output_about_the_author ($object, $articles_to_be_excluded, false);
			if ($object->is_article()) {
				self::output_also_in_this_series ($object, $articles_in_same_series, $articles_to_be_excluded);
			}
		} else {
			self::output_about_the_author ($object, $articles_to_be_excluded, true);
		}
		$articles_to_be_excluded[] = $object->get_id();
		$sections = $object->get_sections();
		self::output_also_in_this_section ($sections, $articles_to_be_excluded);
		echo "</div>";
	}

	/**
	* Outputs a Gravity Form email subscription box
	*
	* @return void
	*/
	private static function output_email_subscription_box() {
		if (function_exists ('gravity_form')) {
				echo "<aside id=\"subscription-form\"><p class=\"title\">Want more like this? Get the latest articles direct by email every week:</p>";
				gravity_form ('Email subscribe (article)', false, true, false, null, true, 10);
				echo "<p class=\"nospam\">Your personal details are safe. We won’t spam you, or pass your details onto anyone else. You can unsubscribe at any time.</p></aside>";
		}
	}

	/**
	* Outputs Facebook like and share buttons
	*
	* @param evangelical_magazine_article $article - the article to 'like' or 'share'
	* @return void
	*/
	private static function output_facebook_like_share_buttons ($article) {
		echo "<div class=\"found-helpful\">Found this helpful? Like or share on Facebook ";
		echo "<div class=\"fb-like\" data-href=\"{$article->get_link()}\" data-width=\"680\" data-size=\"large\" data-layout=\"button_count\" data-action=\"like\" data-show-faces=\"true\" data-share=\"true\"></div></div>\r\n";
	}

	/**
	* Outputs the "About the author" section
	*
	* @param evangelical_magazine_article $article - the article this applies to
	* @param array &$articles_to_be_excluded - an array of article_ids to be excluded (will be modified - passed by reference)
	* @param bool $output_also_by_section - true if the "Also by this author" section is to be included
	* @return void
	*/
	private static function output_about_the_author ($article, &$articles_to_be_excluded, $output_also_by_section = false) {
		$authors = $article->get_authors();
		$articles_to_be_excluded = array();
		if ($authors) {
			$is_single_author = (count($authors) == 1);
			if ($article->is_article()) {
				echo "<div class =\"author-meta\"><h2>".($is_single_author ? 'About the author' : 'About the authors')."</h2>";
			} else {
				echo "<div class =\"author-meta\"><h2>".($is_single_author ? 'About the reviewer' : 'About the reviewers')."</h2>";
			}
			foreach ($authors as $author) {
				echo $author->get_author_info_html('author_small');
			}
			if ($output_also_by_section) {
				$also_by = $article->get_articles_and_reviews_by_same_authors(3);
				if ($also_by) {
					if ($is_single_author) {
						$author = current ($authors);
						echo '<h3>Also by '.$author->get_name(true, false).'</h3>';
					} else {
						echo '<h3>Also by these authors</h3>';
					}
					foreach ($also_by as $also_article) {
						echo $also_article->get_small_box_html(true);
						$articles_to_be_excluded[] = $also_article->get_id();
					}
				}
			}
			echo '</div>';
		}
	}

	/**
	* Outputs the "Also in this series" section
	*
	* @param evangelical_magazine_article $current_article - the current article in the series
	* @param evangelical_magazine_article[] $articles_in_this_series - an array of articles in this series
	* @param array &$articles_to_be_excluded - an array of article_ids to be excluded (will be modified - passed by reference)
	* @return void
	*/
	private static function output_also_in_this_series ($current_article, $articles_in_this_series, &$articles_to_be_excluded) {
		echo "<div class =\"series-meta\"><h2>Also in the {$current_article->get_series_name(true)} series</h2>";
		$also_articles_array = array(); // We're going to split it into rows of three
		foreach ($articles_in_this_series as $also_article) {
			$class = $also_article->get_id() == $current_article->get_id() ? 'current' : '';
			$also_articles_array[] = $also_article->get_small_box_html(!(bool)$class, "Part {$also_article->get_series_order()}", $class);
			$articles_to_be_excluded[] = $also_article->get_id();
		}
		$chunked_also_articles_array = array_chunk ($also_articles_array, 3);
		foreach ($chunked_also_articles_array as $chunk) {
			echo '<div class="row-wrap">'.implode('', $chunk).'</div>';
		}
		echo '</div>';
	}

	/**
	* Outputs the "Also in this section" section
	*
	* @param evangelical_magazine_section[] $sections - an array of sections the article is in
	* @param array &$articles_to_be_excluded - an array of article_ids to be excluded (will be modified - passed by reference)
	* @return void
	*/
	private static function output_also_in_this_section ($sections, &$articles_to_be_excluded) {
		if ($sections) {
			foreach ($sections as $section) {
				$articles_in_same_section = $section->get_articles_and_reviews(3, $articles_to_be_excluded);
				if ($articles_in_same_section) {
					echo "<div class =\"sections-meta\"><h2>Also in the {$section->get_name(true)} section</h2>";
					foreach ($articles_in_same_section as $also_article) {
						echo $also_article->get_small_box_html(true);
						$articles_to_be_excluded[] = $also_article->get_id();
					}
					echo '</div>';
				}
			}
		}
	}

	/**
	* Adds series information into the h2 header
	*
	* Filters 'genesis_post_title_output', but only for articles and series
	*
	* @param string $title - the current page title
	* @return string - the revised page title
	*/
	public static function add_series_to_title ($title) {
		global $post;
		if ($post->post_type == 'em_article') {
			$article = new evangelical_magazine_article($post);
			if ($article->has_series()) {
				return "{$title}<h2 class=\"entry-title-series\">Part {$article->get_series_order()} of the {$article->get_series_name(true)} series</h2>";
			} else {
				return $title;
			}
		} elseif ($post->post_type == 'em_series') {
			return "{$title}<h2 class=\"entry-title-series\">Series</h2>";
		}
		return $title;
	}

	/**
	* Outputs an opening div
	*
	* @return string
	*/
	public static function open_div() {
		echo '<div>';
	}

	/**
	* Outputs a closing div
	*
	* @return string
	*/
	public static function close_div() {
		echo '</div>';
	}

	/**
	* Outputs an opening div with itemprop="text"
	*
	* @return string
	*/
	public static function open_div_with_itemprop_text() {
		echo '<div itemprop="text">';
	}

	/**
	* Outputs the footer on all pages
	*
	* @return void
	*/
	public static function do_footer_bottom() {
		$admin_link = '<a href="'.get_admin_url().'">.</a>';
		echo "<a class=\"logo\" href=\"".get_site_url()."\"></a>";
		echo "<p class=\"footer-details\"><span class=\"emw\">The Evangelical Magazine is published by the <a href=\"https://www.emw.org.uk/\">Evangelical Movement of Wales</a>{$admin_link}</span><br/>";
		echo "<span class=\"address\">Waterton Cross Business Park, South Road, Bridgend CF31 3UL{$admin_link}</span><br/>";
		echo "<span class=\"registration\">Registered charity number 222407. View our <a href=\"/about-us/privacy-policy\">privacy policy</a>{$admin_link}</span></p>";
		$rss_feed = get_post_type_archive_feed_link('em_article');
		echo '<p class="social-icons"><a href="https://www.facebook.com/evangelicalmagazine"><span class="dashicons dashicons-facebook"></span></a><a href="https://twitter.com/EvangelicalMag"><span class="dashicons dashicons-twitter"></span></a><a href="mailto:admin@evangelicalmagazine.com"><span class="dashicons dashicons-email"></span></a><a href="'.$rss_feed.'"><span class="dashicons dashicons-rss"></span></a></p>';
	}

	/**
	* Modifies the main nav menu html to add the 'recent issues' and 'recent authors' sub-menu
	*
	* Filters wp_nav_menu_items
	*
	* @param string $menu - the current menu html
	* @return string - the revised menu html
	*/
	public static function modify_menu ($menu) {
		//Recent issues
		$text_to_look_for = '<span itemprop="name">Recent Issues</span></a>';
		if (strpos($menu, $text_to_look_for) !== FALSE) {
			$menu = str_replace ('<a href="#" itemprop="url"><span itemprop="name">Recent Issues</span></a>', '<a href="'.get_post_type_archive_link ('em_issue').'" itemprop="url"><span itemprop="name">Recent Issues</span></a>', $menu);
			$issues = evangelical_magazine_issue::get_all_issues(6);
			if ($issues) {
				$issue_menu = '<ul class="sub-menu sub-menu-issues"><li class="wrap"><ul>';
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
				$author_menu = '<ul class="sub-menu sub-menu-authors"><li class="wrap"><ul>';
				foreach ($authors as $author) {
					$author_menu .= "<li id=\"menu-item-author-{$author->get_id()}\" class=\"menu-item menu-item-type-author menu-item-author-{$author->get_id()}\">";
					$author_menu .= "<a href=\"{$author->get_link()}\" itemprop=\"url\">{$author->get_image_html ('author_tiny')}<span itemprop=\"name\">{$author->get_name()}</span></a></li>";
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
				$section_menu = '<ul class="sub-menu sub-menu-section"><li class="wrap"><ul>';
				foreach ($sections as $section) {
					$section_menu .= "<li id=\"menu-item-section-{$section->get_id()}\" class=\"menu-item menu-item-type-section menu-item-section-{$section->get_id()}\">";
					$section_menu .= "<a href=\"{$section->get_link()}\" itemprop=\"url\"><span itemprop=\"name\">{$section->get_name()}</span></a></li>";
				}
				$section_menu .= '</ul>'; // The closing div will be added by the str_replace at the end of the function
				$menu = str_replace ($text_to_look_for, $text_to_look_for.$section_menu, $menu);
			}
		}
		//Add wrap to Wordpress menus
		$menu = str_replace(array('<ul class="sub-menu">', '</ul>'), array('<ul class="sub-menu"><li class="wrap"><ul>', '</ul></li></ul>'), $menu);
		return $menu;
	}

	/**
	* Outputs article info to the end of author pages
	*
	* @return void
	*/
	public static function add_to_end_of_author_page() {
		$author_id = get_the_ID();
		$author = new evangelical_magazine_author($author_id);
		$args = evangelical_magazine_article::_future_posts_args();
		$args ['order_by'] = array ('date' => 'DESC');
		$articles = $author->_get_articles_and_reviews($args);
		if ($articles) {
			echo self::get_article_list_box($articles, true, '', false, true);
		}
	}

	/**
	* Outputs the html for the feature image of the current review
	*
	* @return void
	*/
	public static function output_review_image() {
		global $post;
		$image_id = get_post_thumbnail_id ();
		if ($image_id) {
			$review = new evangelical_magazine_review($post);
			echo wp_get_attachment_image($image_id, 'third-post-width', false, array ('class' => 'review-image', 'alt' => $review->get_name()));
		}
	}

	/**
	* Custom version of the genesis() and genesis_standard_loop() functions
	*
	* Used for generating custom archive pages.
	*
	* @return void
	*/
	public static function my_genesis() {
		get_header();
		do_action ('genesis_before_content_sidebar_wrap');
		genesis_markup( array(
			'html5'	=> '<div %s>',
			'xhtml'	=> '<div id="content-sidebar-wrap">',
			'context' => 'content-sidebar-wrap',
		) );
		do_action( 'genesis_before_content' );
		genesis_markup( array(
			'html5'	=> '<main %s>',
			'xhtml'	=> '<div id="content" class="hfeed">',
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
	* @return void
	*/
	public static function output_author_archive_page () {
		echo "<h1>Authors</h1>";
		$output = '';
		$authors = evangelical_magazine_author::get_all_authors();
		$output_index = (bool)(count($authors) >= 20);
		if ($authors) {
			$previous_letter = $letters_used = '';
			foreach ($authors as $author) {
				if ($output_index) {
					if (($current_letter = strtoupper(substr($author->get_name(),0,1))) != $previous_letter) {
						if ($previous_letter != '') {
							$output .= "</div>";
						}
						$output .= "<h2 class=\"grid-author-heading\" id=\"grid-author-{$current_letter}\"><a href=\"#author-index\">{$current_letter}</a></h2><div class= \"grid-author-{$current_letter}\">";
						$previous_letter = $current_letter;
						$letters_used .= $current_letter;
					}
				}
				$output .= "<div class=\"grid-author-container\">";
				$output .= "<a href=\"{$author->get_link()}\" class=\"grid-author-image image-fit\" style=\"background-image:url('{$author->get_image_url('author_small')}')\"></a>";
				$output .= "<div class=\"author-name-description\">";
				$output .= "<div class=\"author-name\">{$author->get_name(true)}</div>";
				$output .= "<div class=\"author-description\">{$author->get_filtered_content()}</div>";
				$output .= "<div class=\"author-article-count\"><a href=\"{$author->get_link()}\">{$author->get_article_and_review_count(true, true, true)}</a></div>";
				$output .= "</div></div>";
			}
			if ($previous_letter != '') {
				$output .= "</div>";
			}
			if ($output_index) {
				$index = '<div id="author-index">';
				$letters_needed = array_unique(array_merge(range('A','Z'),str_split($letters_used)));
				sort($letters_needed, SORT_STRING);
				foreach ($letters_needed as $l) {
					$index .= '<span class="author-index-cell">';
					if (strpos($letters_used, $l) !== FALSE) {
						$index .= "<a href=\"#grid-author-{$l}\">{$l}</a>";
					} else {
						$index .= $l;
					}
					$index .= '</span>';
				}
				$index .= '</div>';
				$output = $index.$output;
			}
		} else {
			$output .= '<p>No authors found.</p>';
		}
		echo $output;
	}

	/**
	* Outputs the issue archive page
	*
	* @return void
	*/
	public static function output_issue_archive_page () {
		$max_articles_displayed = 4;
		echo "<h1>Issues</h1>";
		$issues = evangelical_magazine_issue::get_all_issues();
		$output = '';
		if ($issues) {
			$years = array();
			foreach ($issues as $issue) {
				$date = $issue->get_date();
				if (isset($date['year']) && !in_array($date['year'], $years)) {
					if ($years) {
						$output .= '</ul>';
					}
					$years[] = $date['year'];
					$output .= "<h2 class=\"issue-year-heading\" id=\"issue-year-{$date['year']}\"><a href=\"#issue-index\">{$date['year']}</a></h2>";
					$output .= "<ul class=\"issue-list\">";
				}
				$output .= "<li class=\"issue\"><a href=\"{$issue->get_link()}\"><div class=\"magazine-cover image-fit box-shadow-transition\" style=\"background-image:url('{$issue->get_image_url('issue_medium')}')\"></div></a>";
				$output .= "<div class=\"issue-contents\"><h4>{$issue->get_name(true)}</h4>";
				$articles = $issue->get_top_articles_and_reviews($max_articles_displayed);
				if ($articles) {
					$output .= "<ul class=\"top-articles\">";
					foreach ($articles as $article) {
						$output .= "<li><span class=\"article-title\">{$article->get_title(true)}</span><br/><span class=\"article-authors\">{$article->get_author_names(true, false, 'by ')}</span></li>";
					}
					$remaining_articles = $issue->get_article_and_review_count() - $max_articles_displayed;
					if ($remaining_articles > 0) {
						$output .= "</ul><p>&hellip;and <a href=\"{$issue->get_link()}\">{$remaining_articles} more</a></p>";
					} else {
						$output .= "</ul>";
					}
				}
				else {
					$output .= "<p>Coming soon&hellip;</p>";
				}
				$output .= "</div></li>";
			}
			$output .= "</ul>";
			echo '<div id="issue-index">';
			foreach ($years as $year) {
				echo "<span class=\"issue-index-cell\"><a href=\"#issue-year-{$year}\">{$year}</a></span>";
			}
			echo '</div>';
			echo $output;
		}
	}

	/**
	* Outputs the section archive page
	*
	* @return void
	*/
	public static function output_section_archive_page () {
		$max_articles_displayed = 3;
		echo "<h1>Sections</h1>";
		$sections = evangelical_magazine_section::get_all_sections();
		if ($sections) {
			echo "<ul class=\"section-list\">";
			$exclude_ids = array();
			foreach ($sections as $section) {
				echo "<li class=\"issue\"><a href=\"{$section->get_link()}\"></a>";
				echo "<div class=\"issue-contents\"><h4>{$section->get_name(true)}</h4>";
				$articles = $section->get_top_articles_and_reviews($max_articles_displayed, $exclude_ids);
				if ($articles) {
					echo "<ul class=\"top-articles\">";
					foreach ($articles as $article) {
						echo "<li><a href=\"{$article->get_link()}\"><div class=\"article-image image-fit\" style=\"background-image:url('{$article->get_image_url('article_small')}')\"></div></a><span class=\"article-title\">{$article->get_title(true)}</span>";
						if ($authors = $article->get_author_names(true, false, 'by ')) {
							echo "<br/><span class=\"article-authors\">{$authors}</span>";
						}
						if ($likes = $article->get_facebook_stats()) {
							echo "<br/><span class=\"article-facebook-likes\">{$likes} people liked this</span>";
						}
						echo "</li>";
						$exclude_ids[] = $article->get_id();
					}
					$remaining_articles = $section->get_article_and_review_count() - $max_articles_displayed;
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
	* Used for author, issue and review single pages.
	*
	* @return void
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
	* Outputs issue info to the end of issue pages.
	*
	* @return void
	*/
	public static function add_to_end_of_issue_page() {
		global $post;
		$issue = new evangelical_magazine_issue($post);
		$args = evangelical_magazine_article::_future_posts_args();
		$args['order'] = 'ASC';
		$content = $issue->_get_articles_and_reviews($args);
		$html = self::get_article_list_box($content);
		echo ($html) ? $html : '<div class="article-list-box"><p>Coming soon.</p></div>';
	}

	/**
	* Outputs section info to the end of section pages.
	*
	* @return void
	*/
	public static function add_to_end_of_section_page() {
		$section_id = get_the_ID();
		$section = new evangelical_magazine_section($section_id);
		$args = evangelical_magazine_article::_future_posts_args();
		$articles = $section->_get_articles_and_reviews ($args);
		if ($articles) {
			echo "<div class=\"section-page\">".self::get_article_list_box($articles, true, '', false, true)."</div>";
		} else {
			echo '<div class="article-list-box"><p>Coming soon.</p></div>';
		}
	}

	/**
	* Outputs series info to the end of series pages.
	*
	* Called by genesis_entry_content
	*
	* @var $content - the existing content of the page
	* @return string - the updated content
	*/
	public static function add_to_end_of_series_page($content) {
		$series_id = get_the_ID();
		$series = new evangelical_magazine_series($series_id);
		$args = evangelical_magazine_article::_future_posts_args();
		$articles = $series->_get_articles ($args);
		if ($articles) {
			echo '<div class="series-page">';
			$article_array = array();
			foreach ($articles as $article) {
				$article_array[0] = $article;
				$heading = ($article->is_future()) ? array('text' => "Part {$article->get_series_order()}", 'class' => 'future') : "Part {$article->get_series_order()}";
				echo self::get_article_list_box($article_array, false, $heading, false, true);
			}
			echo "</div>";
		} else {
			echo '<div class="article-list-box"><p>Coming soon.</p></div>';
		}
	}

	/**
	* Sharpens resized images
	*
	* Filters 'wp_generate_attachment_metadata'
	* This method doesn't change the metadata, but we need the metadata to be able to sharpen the images
	*
	* @param array $meta - metadata for the image
	* @return array - the same metadata
	*/
	public static function enhance_media_images ($meta) {
		global $wp_filesystem;
		if ($wp_filesystem === NULL) {
			WP_Filesystem();
		}
		$dir = wp_upload_dir();
		if ($meta['sizes']) {
			foreach ($meta['sizes'] as $size => &$details) {
				$uploaded_file = trailingslashit($dir['basedir']).$meta['file'];
				$upload_folder = dirname($uploaded_file);
				$file = trailingslashit($upload_folder).$details['file'];
				list($orig_w, $orig_h, $orig_type) = @getimagesize($file);
				@ini_set ('memory_limit', apply_filters('image_memory_limit', WP_MAX_MEMORY_LIMIT));
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
		}
		return $meta;
	}

	/**
	* Outputs the viewport in the meta tag (at the moment, to disable mobile resizing)
	*
	* @return void
	*/
	public static function add_viewport() {
		echo '<meta name="viewport" content="width=device-width, initial-scale=1" />' . "\n";
	}

	/**
	* Outputs the full size header image to the page
	*
	* @return void
	*/
	public static function add_full_size_header_image() {
		global $post;
		add_action ('genesis_meta', array (__CLASS__, 'add_image_to_entry_header'), 11);
		if (has_post_thumbnail()) {
			add_filter ('body_class', function($classes) {$classes[]="full-size-header-image";return $classes;});
		}
	}

	/**
	* Outputs the post thumbnail on the search page
	*
	* @return void
	*/
	public static function do_post_image_for_search () {
		$object = evangelical_magazine::get_object_from_id(get_the_ID());
		if ($object) {
			$size = $object->is_author() ? 'author_small' : ($object->is_article() ? 'article_small' : 'issue_small');
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
	* Filters genesis_post_title_text
	*
	* @param string $title - the current page title
	* @return string - the modified page title
	*/
	public static function filter_post_title_for_search_terms ($title) {
		if (function_exists('relevanssi_highlight_terms')) {
			return relevanssi_highlight_terms($title, get_search_query(false));
		} else {
			return $title;
		}
	}

	/**
	* Outputs the author names and issue for the current post in the loop (articles only)
	*
	* Used in search results
	*
	* @return void
	*/
	public static function do_article_meta_for_search() {
		$object = evangelical_magazine::get_object_from_id(get_the_ID());
		if ($object && $object->is_article()) {
			echo "<p class=\"article-meta\">{$object->get_author_names(true, false, 'by ')} ({$object->get_issue_name(true)})</p>";
		}
	}

	/**
	* Adds the search button to the nav_bar
	*
	* Filters wp_nav_menu_items
	*
	* @param string $items - the HTML list content for the menu item
	* @param stdClass $args - an object containing wp_nav_menu() arguments
	* @return string - the updated HTML
	*/
	public static function add_search_button_to_nav_bar ($items, $args) {
		if ($args->theme_location === 'primary') {
			$output = $items."<li class=\"menu-item search\"><a href=\"#\"><span class=\"dashicons dashicons-search\"></span></a>";
			$output .="<ul class=\"sub-menu sub-menu-search\"><li class=\"wrap\"><ul><li id=\"seach-form-container\" class=\"menu-item\">".get_search_form(false)."</li></ul></li></ul></li>";
			return $output;
		} else {
			return $items;
		}
	}

	/**
	* Filters the search query to add terms from the URL to the search boxes.
	*
	* Filters get_search_query on 404 pages
	*
	* @param string $query - the current search query (should be empty)
	* @return string - the revised search query
	*/
	public static function filter_search_query_on_404 ($query) {
		$uri = $_SERVER['REQUEST_URI'];
		$wordpress_url = parse_url(home_url());
		$wordpress_path = (isset($wordpress_url['path'])) ? $wordpress_url['path'] : '';
		$keywords = urldecode(str_replace (array($wordpress_path,'/','-'), array('', ' ',' '), $uri));
		return $keywords;
	}

	/**
	* Outputs pagination at the end of search pages
	*
	* @return void
	*/
	public static function add_to_end_of_search_page() {
		echo "<div class=\"search-after-pagination\">".get_search_form(false)."</div>";
	}

	/**
	* Returns the HTML for a list of articles with thumbnails, title and author
	*
	* @param evangelical_magazine_article[]|evangelical_magazine_review[] $content - an array of articles and/or reviews
	* @param bool $make_first_image_bigger - true if the first image will be larger
	* @param string $heading - a text string to add as a heading
	* @param bool $shrink_text_if_long - true if long text is to be shortened
	* @param bool $add_facebook_likes - true if Facebook stats are to be added in the boxes
	* @return string - the HTML
	*/
	public static function get_article_list_box($content, $make_first_image_bigger = true, $heading = '', $shrink_text_if_long = false, $add_facebook_likes = false) {
		if ($content) {
			$output = "<div class=\"article-list-box\">";
			if ($heading) {
				if (is_array($heading) && isset($heading['text']) && isset($heading['class'])) {
					$output .= $heading ? "<h3 class=\"{$heading['class']}\">{$heading['text']}</h3>" : '';
				} else {
					$output .= $heading ? "<h3>{$heading}</h3>" : '';
				}
			}
			$output .= "<ol>";
			$class = $make_first_image_bigger ? 'large-image' : '';
			foreach ($content as $article) {
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
				$output .= "<div class=\"title-author-wrapper\"><span class=\"article-list-box-title\"><span{$style}>{$article->get_title(true)}</span></span><span class=\"article-list-box-author\"><br/>{$article->get_author_names(!$article->is_future(), false, 'by ')}</span>";
				if ($article->is_future()) {
					$output .= "<br/><span class=\"article-list-box-coming-soon\">Coming {$article->get_coming_date()}</span>";
				} elseif ($add_facebook_likes && ($likes = $article->get_facebook_stats())) {
					$output .= "<br/><span class=\"article-list-box-likes\">{$likes} likes</span>";
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
	* Outputs the RSS feeds to the HTML HEAD
	*
	* @return void
	*/
	public static function add_rss_feeds() {
		echo "<link rel=\"alternate\" type=\"application/rss+xml\" title=\"Evangelical Magazine Articles\" href=\"".get_post_type_archive_feed_link('em_article')."\" />\r\n";
		echo "<link rel=\"alternate\" type=\"application/rss+xml\" title=\"Evangelical Magazine Reviews\" href=\"".get_post_type_archive_feed_link('em_review')."\" />\r\n";
		echo "<link rel=\"alternate\" type=\"application/rss+xml\" title=\"Evangelical Magazine Issues\" href=\"".get_post_type_archive_feed_link('em_issue')."\" />\r\n";
	}

	/**
	* Removes the default 'medium', 'medium_large' and 'large' image sizes, so images are not unnecessarily created
	*
	* Filters intermediate_image_sizes
	*
	* @param array $sizes - the existing array of image sizes
	* @return array - the modified array
	*/
	public static function remove_default_image_sizes($sizes) {
		$image_sizes_to_remove = array ('medium', 'medium_large', 'large');
		foreach ($image_sizes_to_remove as $i) {
			$index = array_search ($i, $sizes);
			if ($index) {
				unset ($sizes[$index]);
			}
		}
		return $sizes;
	}

	/**
	* Outputs the Reftagger code to the HTML HEAD
	*
	* @return void
	*/
	public static function configure_reftagger() {
		echo "<script>var refTagger = {settings: {bibleVersion: \"NIV\",libronixBibleVersion: \"DEFAULT\",addLogosLink: false,appendIconToLibLinks: false,libronixLinkIcon: \"dark\",noSearchClassNames: [],useTooltip: true,noSearchTagNames: [\"h1\"],linksOpenNewWindow: true,convertHyperlinks: false,caseInsensitive: false,tagChapters: true}}</script>";
	}

	/**
	* Outputs the Facebook Open Graph tags to single articles
	*
	* @return void
	*/
	public static function add_facebook_open_graph() {
		/** @var evangelical_magazine_article */
		$object = evangelical_magazine::get_object_from_id(get_the_ID());
		if ($object && $object->is_article_or_review()) {
			$image_details = $object -> get_image_details($object->is_article() ? 'facebook_share' : 'half-post-width');
			$authors = $object->get_author_names(false, false, $object->is_article() ? ' — by ' : ', reviewed by ');
			$article_preview = esc_html(wp_trim_words (strip_shortcodes($object->get_content()), 75, '…'));
			$rich_content = $object->is_review() ? 'false' : 'true';
			echo "\r\n\t<meta property=\"og:url\" content=\"{$object->get_link()}\" />\r\n";
			echo "\t<meta property=\"og:title\" content=\"".esc_html($object->get_title().$authors)."\" />\r\n";
			echo "\t<meta property=\"og:description\" content=\"{$article_preview}\" />\r\n";
			echo "\t<meta property=\"og:site_name\" content=\"".esc_html(get_bloginfo('name'))."\" />\r\n";
			echo "\t<meta property=\"og:image\" content=\"{$image_details['url']}\" />\r\n";
			echo "\t<meta property=\"og:image:url\" content=\"{$image_details['url']}\" />\r\n";
			echo "\t<meta property=\"og:image:width\" content=\"{$image_details['width']}\" />\r\n";
			echo "\t<meta property=\"og:image:height\" content=\"{$image_details['height']}\" />\r\n";
			echo "\t<meta property=\"og:image:type\" content=\"{$image_details['mimetype']}\" />\r\n";
			echo "\t<meta property=\"og:type\" content=\"article\" />\r\n";
			echo "\t<meta property=\"article:publisher\" content=\"https://www.facebook.com/evangelicalmagazine/\" />\r\n";
			echo "\t<meta property=\"og:locale\" content=\"en_GB\" />\r\n";
			echo "\t<meta property=\"og:rich_attachment\" content=\"{$rich_content}\" />\r\n";
			echo "\t<meta property=\"fb:app_id\" content=\"1248516525165787\" />\r\n";
		}
	}

	/**
	* Outputs the Twitter Summary Card tags to single articles and reviews
	*
	* @return void
	*/
	public static function add_twitter_card() {
		$object = evangelical_magazine::get_object_from_id(get_the_ID());
		if ($object && $object->is_article_or_review()) {
			$image_details = $object -> get_image_details($object->is_article() ? 'twitter_share' : 'half-post-width');
			$authors = $object->get_author_names(false, false, $object->is_article() ? ' — by ' : ', reviewed by ');
			$article_preview = esc_html(wp_trim_words (strip_shortcodes($object->get_content()), 75, '…'));
			$image_size = $object->is_article() ? 'summary_large_image' : 'summary';
			echo "\r\n\t<meta name=\"twitter:card\" content=\"{$image_size}\">\r\n";
			echo "\t<meta name=\"twitter:site\" content=\"@EvangelicalMag\">\r\n";
			echo "\t<meta name=\"twitter:title\" content=\"".esc_html($object->get_title().$authors)."\" />\r\n";
			echo "\t<meta name=\"twitter:description\" content=\"{$article_preview}\" />\r\n";
			echo "\t<meta name=\"twitter:image\" content=\"{$image_details['url']}\" />\r\n";
		}
	}

	/**
	* Outputs a breadcrumb to Google, for single articles and reviews
	*
	* @return void
	*/
	public static function add_google_breadcrumb () {
		$object = evangelical_magazine::get_object_from_id(get_the_ID());
		if ($object && $object->is_article_or_review() && $object->has_issue()) {
			$issue_name = esc_html($object->get_issue_name());
			echo "<script type=\"application/ld+json\">\r\n";
			echo "{\"@context\": \"http://schema.org\", \"@type\": \"BreadcrumbList\", \"itemListElement\": [";
			echo "{ \"@type\": \"ListItem\", \"position\": 1, \"item\": { \"@id\": \"{$object->get_issue_link()}\", \"name\": \"{$issue_name}\"}}";
			echo "]}\r\n";
			echo "</script>\r\n";
		}
	}

	/**
	* Outputs structured data to the homepage, for Google
	*
	* @return void
	*/
	public static function add_google_structured_data_to_homepage () {
		$site_name = esc_html(get_bloginfo('name'));
		$url = esc_html(get_home_url());
		$search_url = str_replace('search_term_string', '{search_term_string}', esc_html(get_search_link('search_term_string')));
		$logo = esc_html(get_stylesheet_directory_uri().'/images/square-logo.png');
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
	* Outputs the HTML for favicon and mobile shortcut icons
	*
	* @return void
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
	* @return void
	*/
	public static function output_facebook_javascript_sdk() {
		if (is_active_widget(false, false, 'evangelical_magazine_facebook_page_plugin')) {
			echo '<div id="fb-root"></div><script>(function(d, s, id) { var js, fjs = d.getElementsByTagName(s)[0]; if (d.getElementById(id)) return; js = d.createElement(s); js.id = id; js.src = "//connect.facebook.net/en_GB/sdk.js#xfbml=1&version=v2.5&appId=1248516525165787"; fjs.parentNode.insertBefore(js, fjs);}(document, \'script\', \'facebook-jssdk\'));</script>'."\r\n";
		}
	}

	/**
	* Adds the 'image-fit' class to the entry header div
	*
	* Filters genesis_attr_entry-header
	*
	* @param array $attributes - the existing HTML attributes
	* @return array - the revised HTML attributes
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
	* Filters the list of images sizes available in the media gallery
	*
	* Filters image_size_names_choose
	*
	* @param array $size_names - array of image sizes and their names
	* @return array - the revised array
	*/
	public static function add_image_sizes_to_media_gallery ($size_names) {
		$size_names ['third-post-width'] = 'Third-width';
		$size_names ['half-post-width'] = 'Half-width';
		$size_names ['full-post-width'] = 'Full-width';
		return $size_names;
	}

	/**
	* Enqueues the stylesheets for various media queries
	*
	* Having separate stylesheets for media types is slightly inefficient over http, but makes editing easier, and possibly slightly reduces time to paint
	*
	* @return void
	*/
	public static function enqueue_media_stylesheets() {
		$sizes = array ('1000-1299' => 'screen and (min-width: 1000px) and (max-width: 1299px)',
						'735-999' => 'screen and (min-width:735px) and (max-width: 999px)',
						'560-734' => 'screen and (min-width:560px) and (max-width: 734px)',
						'470-559' => 'screen and (min-width:470px) and (max-width: 559px)',
						'370-469' => 'screen and (min-width:370px) and (max-width: 469px)',
						'0-369' => 'screen and (max-width: 369px)');
		foreach ($sizes as $name => $media) {
			self::enqueue_style ("magazine-css-{$name}", "/css/style-{$name}.css", false, $media);
		}
	}

	/**
	* Enqueues WebP description
	*
	* Adds the webp or no-webp CSS classes to <html>, as appropriate
	* @see https://modernizr.com/download?webp-setclasses
	*
	* @return void
	*/
	public static function enqueue_webp_detection() {
		if (WP_DEBUG === true) {
			self::enqueue_script ('magazine-webp-detection', "/js/webp-detection.js");
		} else {
			self::enqueue_script ('magazine-webp-detection-minified', "/js/webp-detection.min.js");
		}
	}

	/**
	* Outputs the Facebook app ID to the HEAD section of the homepage
	*
	* Provides authentication for Facebook
	*
	* @return void
	*/
	public static function add_facebook_app_id_to_homepage() {
		echo "\t<meta property=\"fb:app_id\" content=\"1248516525165787\" />\r\n";
		echo "\t<meta property=\"fb:pages\" content=\"317371662084\" />\r\n";
	}

	/**
	* Adds Beacon Ads javascript
	*
	* @return void
	*/
	public static function output_beacon_ads_main_code() {
		if (is_active_widget(false, false, 'evangelical_magazine_beacon_ad')) {
			echo "\t<script type=\"text/javascript\">(function(){ var bsa = document.createElement('script'); bsa.type = 'text/javascript'; bsa.async = true; bsa.src = '//cdn.beaconads.com/ac/beaconads.js'; (document.getElementsByTagName('head')[0]||document.getElementsByTagName('body')[0]).appendChild(bsa);})();</script>\r\n";
		}
	}

	/**
	* Adds schema.org microdata to articles
	*
	* Filters genesis_attr_entry
	*
	* @param array $attributes - the existing attributes
	* @param string $context - the context (HTML tag)
	* @return array
	*/
	public static function add_schema_org_itemtype_to_articles ($attributes, $context) {
		if ($context == 'entry') {
			$attributes['itemtype'] = 'https://schema.org/Article';
		}
		return $attributes;
	}

	/**
	* Adds schema.org microdata to reviews
	*
	* Filters genesis_attr_entry
	*
	* @param array $attributes - the existing attributes
	* @param string $context - the context (HTML tag)
	* @return array
	*/
	public static function add_schema_org_itemtype_to_reviews ($attributes, $context) {
		if ($context == 'entry') {
			$attributes['itemtype'] = 'https://schema.org/Review';
		}
		return $attributes;
	}

	/**
	* Custom resource hints for improved http(s) performance
	*
	* Filters wp_resource_hints
	*
	* @param array $urls - URLs to print for resource hints
	* @param string $relation_type - the relation type the URLs are printed for, e.g. 'preconnect' or 'prerender'
	* @return array - the revised URLs
	*/
	public static function filter_resource_hints ($urls, $relation_type) {
		// Don't prefetch emojis
		if ($relation_type == 'dns-prefetch') {
			foreach ($urls as $key => $value) {
				if (strpos ($value, 'https://s.w.org/images/core/emoji/') !== false) {
					unset($urls[$key]);
				}
			}
		}
		elseif ($relation_type == 'preconnect') {
			//Preconnect to CDN if using BunnyCDN plugin
			if (method_exists('BunnyCDN', 'getOptions')) {
				$cdn_options = BunnyCDN::getOptions();
				if (isset($cdn_options['cdn_domain_name']) && $cdn_options['cdn_domain_name']) {
					$urls[] = array('href' => $cdn_options['cdn_domain_name'], 'crossorigin');
				}
			}
			//Add Reftagger domains
			$urls = array_merge($urls, array ('https://api.reftagger.com/', 'https://reftaggercdn.global.ssl.fastly.net/'));
			//Add Beaconads domains
			if (is_active_widget(false, false, 'evangelical_magazine_beacon_ad')) {
				$urls = array_merge($urls, array ('https://cdn.beaconads.com/','https://s3.buysellads.com/','https://srv.buysellads.com/'));
			}
			//Add Google Analytics
			$urls = array_merge($urls, array ('https://www.googletagmanager.com/', 'https://www.google-analytics.com/'));
			//Add Facebook domains
			$urls = array_merge($urls, array ('https://connect.facebook.net/', 'https://static.xx.fbcdn.net/', 'https://staticxx.facebook.com/', 'https://web.facebook.com/', 'https://www.facebook.com/'));
		}
		return $urls;
	}

	/**
	* Outputs a series table of contents at the top of articles
	*
	* @return void
	*/
	public static function add_series_toc_if_required() {
		global $post;
		/** @var evangelical_magazine_article */
		$article = evangelical_magazine::get_object_from_post($post);
		if ($article->is_article() && $article->has_series()) {
			$series = $article->get_series();
			$articles_in_series = $series->get_articles(-1, array(), $series->_future_posts_args());
			if ($series && count($articles_in_series) > 1) {
				echo "<div id=\"series-contents\">";
				echo "<h3>About this series</h3>";
				echo "<h4>{$series->get_name (true)}</h4>";
				echo "<ul>";
				foreach ($articles_in_series as $a) {
					if ($article->get_id() == $a->get_id()) {
						echo "<li><strong>{$a->get_name()}</strong>&nbsp;({$a->get_series_order()})</li>";
					} elseif ($a->is_future()) {
						echo "<li><em>{$a->get_name()}</em> — coming {$a->get_coming_date()}</li>";
					} else {
						$link = "<a href=\"{$a->get_link()}#series-contents\">{$a->get_name()}</a>";
						echo "<li>{$link} ({$a->get_series_order()})</li>";
					}
				}
				echo "</ul></div>";
			}
		}
	}

	/**
	* Outputs the "Next in series" link if required
	*
	* @return void
	*/
	public static function add_next_in_series_if_required() {
		global $post;
		/** @var evangelical_magazine_article */
		$article = evangelical_magazine::get_object_from_post($post);
		if ($article->is_article() && $article->has_series()) {
			$next = $article->get_next_in_series();
			if ($next) {
				echo "<p class=\"next-in-series\">Next in this series: {$next->get_name(true)} &raquo;</p>";
			}
		}
	}

	/**
	* Limits the paragraph styles shown in the TinyMCE dropdown
	*
	* Filters tiny_mce_before_init
	*
	* @param array $settings - the current settings
	* @return array - the modified settings
	*/
	public static function remove_unused_tinymce_formats($settings) {
		$settings['block_formats'] = 'Paragraph=p;Heading 2=h2;Heading 3=h3';
		return $settings;
	}

	/**
	* Custom version of wp_enqueue_style
	*
	* The file's version number is created automatically from the timestamp of the file being enqueued
	*
	* @see wp_enqueue_style()
	*
	* @param string $handle - unique name of the stylesheet
	* @param string $path - the path of the style, this theme's directory
	* @param array $deps - an array of registered stylesheet handles this stylesheet depends on
	* @param string $media - the media for which this stylesheet has been defined (e.g. 'all', 'print', 'screen', '(orientation: portrait)' and '(max-width: 640px)'
	* @return void
	*/
	public static function enqueue_style ($handle, $path = '', $deps = array(), $media = 'all') {
		$timestamp = @filemtime(get_stylesheet_directory().$path);
		$src = get_stylesheet_directory_uri().$path;
		wp_enqueue_style($handle, $src, $deps, $timestamp, $media);
	}

	/**
	* Custom version of wp_enqueue_script
	*
	* The file's version number is created automatically from the timestamp of the file being enqueued
	*
	* @see wp_enqueue_script()
	*
	* @param string $handle - unique name of the stylesheet
	* @param string $path - the path of the style, this theme's directory
	* @param array $deps - an array of registered stylesheet handles this stylesheet depends on
 	* @param bool $in_footer - whether to enqueue the script before </body> instead of in the <head>.
 	* @return void
	*/
	public static function enqueue_script ($handle, $path = '', $deps = array(), $in_footer = false) {
		$timestamp = @filemtime(get_stylesheet_directory().$path);
		$src = get_stylesheet_directory_uri().$path;
		wp_enqueue_script($handle, $src, $deps, $timestamp, $in_footer);
	}
}