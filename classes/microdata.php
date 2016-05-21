<?php
/**
* Helper class containing functions to add schema.org microdata before the start of articles
* 
* @see https://schema.org/Article
* 
* @package evangelical-magazine-theme
* @author Mark Barnes
* @access public
*/
class evangelical_magazine_microdata {
    
    /**
    * Generic function to return meta tag with itemprop and content parameters (schema.org)
    * 
    * @param string $itemprop
    * @param string $content
    * @return string
    */
    public function meta ($itemprop, $content) {
        $content = esc_html($content);
        return "<meta itemprop=\"{$itemprop}\" content=\"{$content}\">";
    }

    /**
    * Helper function to convert a timestampt into ISO8601 format
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
        $url = esc_html($url);
        return "<span itemprop=\"image\" itemscope itemtype=\"https://schema.org/ImageObject\"><meta itemprop=\"url\" content=\"{$url}\"><meta itemprop=\"width\" content=\"{$width}\"><meta itemprop=\"height\" content=\"{$height}\"></span>";
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
        $date = self::convert_timestamp($timestamp);
        return self::meta ('datePublished', $date);
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
        $date = self::convert_timestamp($timestamp);
        return self::meta ('dateModified', $date);
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
        $url = esc_html($url);
        return "<span itemprop=\"logo\" itemscope itemtype=\"https://schema.org/ImageObject\"><meta itemprop=\"url\" content=\"{$url}\"></span>";
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
        $name = esc_html($name);
        $url = esc_html($url);
        return "<span itemprop=\"publisher\" itemscope itemtype=\"https://schema.org/Organization\"><meta itemprop=\"name\" content=\"{$name}\"><meta itemprop=\"url\" content=\"{$url}\">".self::get_logo($logo_url).'</span>';
    }
}