<?php
/*
Plugin Name: Google Maps for WordPress
Author: Iwebslog Labs
Plugin URI: http://www.iwebslog.com/
Description: Easily Integrate Google Maps in your website and blog. Use coordinates or shortcode to display the map on website.
Version: 1.2
Author URI: http://www.iwebslog.com/
Domain Path: /languages
Text Domain: googlemap
Licence: GPLv2
*/

new googlemap();

class googlemap{

private $shortcode_tag = 'map';
private $class_name = 'googlemap';
private $width      = '100%';
private $height     = '200px';
private $zoom       = 16;
private $breakpoint = 480;
private $max_breakpoint = 640;

function __construct()
{
    add_action('init', array($this, 'init'));
}

public function init()
{
    add_action('wp_head', array($this, 'wp_head'));
    add_shortcode($this->get_shortcode_tag(), array($this, 'shortcode'));

    wp_embed_register_handler(
        'google-map',
        '#(https://maps.google.co.jp/maps(/ms)?\?.+)#i',
        array(&$this, 'oembed_handler')
    );
}

public function oembed_handler($match)
{
    return sprintf(
        '[%s url="%s"]',
        $this->get_shortcode_tag(),
        esc_url($match[0])
    );
}

public function wp_head()
{
    echo "<style>.googlemap img{max-width:none !important;padding:0 !important;margin:0 !important;}.staticmap,.staticmap img{max-width:100% !important;height:auto !important;}</style>\n";
}

public function wp_enqueue_scripts()
{
    wp_register_script(
        'google-maps-api',
        'http://maps.google.com/maps/api/js?sensor=false',
        false,
        null,
        true
    );

    wp_register_script(
        'googlemap',
        apply_filters(
            "googlemap_script",
            plugins_url('js/google-map.min.js' , __FILE__)
        ),
        array('jquery', 'google-maps-api'),
        filemtime(dirname(__FILE__).'/js/google-map.min.js'),
        true
    );
    wp_enqueue_script('googlemap');
}

public function shortcode($p, $content = null)
{
    add_action("wp_footer", array(&$this, "wp_enqueue_scripts"));

    if (isset($p['width']) && preg_match("/^[0-9]+(%|px)$/", $p['width'])) {
        $w = $p['width'];
    } else {
        $w = apply_filters("googlemap_default_width", $this->width);
    }
    if (isset($p['height']) && preg_match("/^[0-9]+(%|px)$/", $p['height'])) {
        $h = $p['height'];
    } else {
        $h = apply_filters("googlemap_default_height", $this->height);
    }
    if (isset($p['zoom']) && $p['zoom']) {
        $zoom = $p['zoom'];
    } else {
        $zoom = apply_filters('googlemap_default_zoom', $this->zoom);
    }
    if (isset($p['breakpoint']) && intval($p['breakpoint'])) {
        if (intval($p['breakpoint']) > $this->max_breakpoint) {
            $breakpoint = $this->max_breakpoint;
        } else {
            $breakpoint = intval($p['breakpoint']);
        }
    } else {
        $breakpoint = apply_filters(
            'googlemap_default_breakpoint',
            $this->breakpoint
        );
    }
    if ($content) {
        $content = do_shortcode($content);
    }

    $addr = '';
    $lat = '';
    $lng = '';

    if (isset($p['url']) && $p['url']) {
        $iframe = '<iframe width="%s" height="%s" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="%s"></iframe>';

        return sprintf(
            $iframe,
            $w,
            $h,
            esc_url($p['url'].'&output=embed')
        );
    } elseif (isset($p['lat']) && preg_match("/^\-?[0-9\.]+$/", $p['lat'])
                && isset($p['lng']) && preg_match("/^\-?[0-9\.]+$/", $p['lng'])){
        $lat = $p['lat'];
        $lng = $p['lng'];
    } elseif (isset($p['addr']) && $p['addr']) {
        if ($content) {
            $addr = esc_html($p['addr']);
        } else {
            $content = esc_html($p['addr']);
        }
    } elseif (!$content) {
        return;
    }
    return sprintf(
        '<div class="%s"><div data-breakpoint="%s" data-lat="%s" data-lng="%s" data-zoom="%s" data-addr="%s" style="width:%s;height:%s;">%s</div></div>',
        apply_filters("googlemap_class_name", $this->class_name),
        $breakpoint,
        $lat,
        $lng,
        $zoom,
        $addr,
        $w,
        $h,
        trim($content)
    );
}

private function get_shortcode_tag()
{
    return apply_filters('googlemap_shortcode_tag', $this->shortcode_tag);
}

} // end class


// EOF
