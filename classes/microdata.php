<?php
class evangelical_magazine_microdata {
    
    public function meta ($itemprop, $content) {
        $content = esc_html($content);
        return "<meta itemprop=\"{$itemprop}\" content=\"{$content}\">";
    }

    private function convert_timestamp ($timestamp) {
        return date('Y-m-d\TH:i:sO', $timestamp);
    }
    
    public function get_ImageObject ($url, $width, $height) {
        $url = esc_html($url);
        return "<span itemprop=\"image\" itemscope itemtype=\"https://schema.org/ImageObject\"><meta itemprop=\"url\" content=\"{$url}\"><meta itemprop=\"width\" content=\"{$width}\"><meta itemprop=\"height\" content=\"{$height}\"></span>";
    }
    
    public function get_datePublished ($timestamp) {
        $date = self::convert_timestamp($timestamp);
        return self::meta ('datePublished', $date);
    }
    
    public function get_dateModified ($timestamp) {
        $date = self::convert_timestamp($timestamp);
        return self::meta ('dateModified', $date);
    }
    
    public function get_logo($url) {
        $url = esc_html($url);
        return "<span itemprop=\"logo\" itemscope itemtype=\"https://schema.org/ImageObject\"><meta itemprop=\"url\" content=\"{$url}\"></span>";
    }
    
    public function get_publisher ($name, $url, $logo_url) {
        $name = esc_html($name);
        $url = esc_html($url);
        return "<span itemprop=\"publisher\" itemscope itemtype=\"https://schema.org/Organization\"><meta itemprop=\"name\" content=\"{$name}\"><meta itemprop=\"url\" content=\"{$url}\">".self::get_logo($logo_url).'</span>';
    }
}
?>
