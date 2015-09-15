<?php
class evangelical_magazine_home_page {
    
    /**
    * Outputs the home page
    * 
    * Called by the 'genesis_loop' action, added by front-page.php
    */
    public static function do_home_page() {
        $recent_article_ids = self::do_most_recent_articles();
        self::do_sections(1, $recent_article_ids);
    }

    /**
    * Outputs the 'recent articles' module on the home page
    * 
    */
    public static function do_most_recent_articles() {
        $latest_issues = evangelical_magazine_issue::get_all_issues(20);
        if ($latest_issues) {
            //Output the cover of the most recent issue
            echo '<aside id="recent-articles">';
            echo "<a href=\"{$latest_issues[0]->get_link()}\"><div id=\"latest-cover\" style=\"background-image: url('{$latest_issues[0]->get_image_url('width_300')}')\"></div></a>";
            //Get the seven most recent articles from these issues
            $articles = array();
            foreach ($latest_issues as $issue) {
                $these_articles = $issue->get_articles();
                if ($these_articles) {
                    $articles = array_merge ($articles, $these_articles);
                    if (count($articles) >= 7) {
                        break;
                    }
                }
            }
            $articles = array_slice ($articles, 0, 7);
            // Output these articles
            $article_ids = array();
            if ($articles) {
                echo '<div id="latest-articles"><div class="first-row">';
                $count = 1;
                foreach ($articles as $article) {
                    $size = ($count <= 3) ? 400 : 300;
                    if ($count == 4) {
                        echo '</div><div class="second-row">';
                    }
                    echo "<a href=\"{$article->get_link()}\"><div class=\"article article-{$size}\" style=\"background-image: url('{$article->get_image_url("width_{$size}")}')\"><div class=\"article-title\">{$article->get_title()}</div></div></a>";
                    $article_ids[] = $article->get_id();
                    $count++;
                }
                echo '</div>';
            }
            echo '</aside>';
            return $article_ids;
        }
    }
    
    /**
    * Outputs the 'sections' module on the home page
    * 
    * @param integer $max_per_section - maximum number of articles per section
    * @param integer[] $exclude_article_ids - array of article ids to exclude from this process
    */
    public static function do_sections($max_per_section = 5, $exclude_article_ids = array()) {
        $args = array ('orderby' => 'name', 'order' => 'ASC', 'hide_empty' => true);
        $sections = evangelical_magazine_section::get_all_sections($args);
        if ($sections) {
            shuffle($sections);
            echo '<aside id="sections">';
                $possible_sides = array ('left', 'center', 'right');
                $side_index = 0;
                foreach ($possible_sides as $s) {
                    $outputs [$s] = '';
                }
                foreach ($sections as $section) {
                    $info_box = $section->get_info_box($max_per_section, $exclude_article_ids);
                    if ($info_box['output']) {
                        $outputs[$possible_sides[($side_index % 3)]] .= $info_box['output'];
                        $exclude_article_ids = array_merge ($exclude_article_ids, $info_box['ids']);
                        $side_index ++;
                    }
                }
                foreach ($outputs as $side => $output) {
                    if ($output) {
                        echo "<aside id=\"sections-{$side}\">{$output}</aside>";
                    }
                }
            echo '</aside>';
        }
    }
    
}