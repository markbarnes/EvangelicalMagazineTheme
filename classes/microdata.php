<?php
/**
* Helper class containing functions to add schema.org microdata before the start of articles and reviews
*
* @see https://schema.org/Article
*
* @package evangelical-magazine-theme
* @author Mark Barnes
* @access public
*/
class evangelical_mag_microdata {

	/**
	* Returns a span tag with the required itemprop parameters
	*
	* @see http://schema.org/docs/gs.html
	*
	* @param string $itemprop
	* @param string $itemtype
	* @param string $content - the content between the opening and closing tags
	* @return string
	*/
	public function get_span ($itemprop, $itemtype, $content = '') {
		return $this->html_tag ('span', $content, array ('itemprop' => $itemprop, 'itemscope' => true, 'itemtype' => $itemtype));
	}

	/**
	* Returns a meta tag with itemprop and content parameters
	*
	* @see http://schema.org/docs/gs.html#advanced_missing
	*
	* @param string|array $itemprop
	* @param string $content
	* @return string
	*/
	public function get_meta ($itemprop, $content = '') {
		if (is_array($itemprop)) {
			$output = '';
			foreach ($itemprop as $k => $v) {
				$output .= $this->get_meta ($k, $v);
			}
			return $output;
		} else {
			return $this->html_tag ('meta', '', array ('itemprop' => $itemprop, 'content' => $content));
		}
	}

	/**
	* Helper function to convert a timestamp into ISO8601 format
	*
	* @param integer $timestamp - Unix timestamp
	* @return string
	*/
	private function convert_timestamp ($timestamp) {
		return date('Y-m-d\TH:i:sO', $timestamp);
	}

	/**
	* Returns the html for an ImageObject (schema.org)
	*
	* @see https://schema.org/image
	*
	* @param string $url
	* @param integer $width
	* @param integer $height
	* @return string
	*/
	public function get_ImageObject ($url, $width, $height) {
		return $this->get_span('image', 'https://schema.org/ImageObject', $this->get_meta (compact ('url', 'width', 'height')));
	}

	/**
	* Returns the html for datePublished (schema.org)
	*
	* @see https://schema.org/datePublished
	*
	* @param integer $timestamp
	* @return string
	*/
	public function get_datePublished ($timestamp) {
		$date = $this->convert_timestamp($timestamp);
		return $this->get_meta ('datePublished', $date);
	}

	/**
	* Returns the html for dateModified (schema.org)
	*
	* @see https://schema.org/dateModified
	*
	* @param integer $timestamp
	* @return string
	*/
	public function get_dateModified ($timestamp) {
		$date = $this->convert_timestamp($timestamp);
		return $this->get_meta ('dateModified', $date);
	}

	/**
	* Returns the html for logo (schema.org)
	*
	* @see https://schema.org/logo
	*
	* @param string $url
	* @return string
	*/
	public function get_logo($url) {
		return $this->get_span ('logo', 'https://schema.org/ImageObject', $this->get_meta(compact('url')));
	}

	/**
	* Returns the html for publisher (schema.org)
	*
	* @see https://schema.org/publisher
	*
	* @param string $name
	* @param string $url
	* @param string $logo_url
	* @return string
	*/
	public function get_publisher ($name, $url, $logo_url) {
		return $this->get_span ('publisher', 'https://schema.org/Organization', $this->get_meta (compact('name', 'url')).$this->get_logo($logo_url));
	}

	/**
	* Returns the html for the image of an item reviewed
	*
	* @see https://schema.org/itemReviewed
	*
	* @param array $image_details - an indexed array with the values (url, width, height), for example created by wp_get_attachment_image_src
	* @return string
	*/
	public function get_itemReviewed_image ($image_details) {
		return $this->get_span ('itemReviewed', 'https://schema.org/Thing', $this->get_ImageObject($image_details[0], $image_details[1], $image_details[2]));
	}

	/**
	* Converts an array of attributes into a string, ready for HTML output
	*
	* e.g. array ('key1' => 'value1', 'key2' => 'value2')
	* will become 'key1="value1" key2="value2"'
	*
	* @param array $attributes
	* @return string
	*/
	private function attr_html ($attributes) {
		$output = '';
		foreach ($attributes as $key => $value) {
			if ($value) {
				if ($value === true) {
					$output .= esc_html($key).' ';
				} else {
					$output .= sprintf('%s="%s" ', esc_html($key), esc_attr($value));
				}
			}
		}
		return trim($output);
	}

	/**
	* Returns a HTML tag
	*
	* @param string $tag - The name of the HTML tag
	* @param string $contents -  the content between the opening and closing tags
	* @param array $attributes - Various attributes and values [class, id, etc.] for the tag. The name of the attribute should be used as the key.
	* @return string
	*/
	private function html_tag ($tag, $contents, $attributes = array()) {
		if ($contents) {
			return "<{$tag} {$this->attr_html($attributes)}>{$contents}</{$tag}>";
		} else {
			return "<{$tag} {$this->attr_html($attributes)} />";
		}
	}
}