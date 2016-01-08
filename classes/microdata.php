<?php
    class evangelical_magazine_microdata {
        
        public function convert_timestamp ($timestamp) {
            return date('Y-m-d\TH:i:sO', $timestamp);
        }
        
        public function get_ImageObject ($url, $width, $height) {
            $url = esc_html($url);
            return "<span itemprop=\"image\" itemscope itemtype=\"https://schema.org/ImageObject\"><meta itemprop=\"url\" content=\"{$url}\"><meta itemprop=\"width\" content=\"{$width}\"><meta itemprop=\"height\" content=\"{$height}\"></span>";
        }
        
        public function get_datePublished ($timestamp) {
            $date = self::convert_timestamp($timestamp);
            return "<meta itemprop=\"datePublished\" content=\"{$date}\">";
        }
        
        public function get_dateModified ($timestamp) {
            $date = self::convert_timestamp($timestamp);
            return "<meta itemprop=\"dateModified\" content=\"{$date}\">";
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
        
        public function get_mainEntityOfPage ($url) {
            $url = esc_html($url);
            return "<meta itemprop=\"mainEntityOfPage\" content=\"{$url}\">";
        }
    }
?>
