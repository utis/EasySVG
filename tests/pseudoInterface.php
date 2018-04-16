<?php
class PseudoEasySVGAccess {
    public $base_url;
    public $base_dir;
    public $cfg;

    public function filePath ($url) {
        $relative = $this->relativePath($url);
        return $relative ? __DIR__ . '/' . $relative : false;
    }


    public function relativePath ($url) {
        $matches = array();
        return preg_match('/^theme:\/\/(.*)$/', $url, $matches) ?
            $matches[1] : false;
    }

    public function url ($url) {
        $relative = $this->relativePath($url);
        return $relative ?
            $this->base_url . '/user/themes/exampletheme/' . $relative
            : false;
    }        
        
    public function warn ($message) {
    }

    public function error ($message) {
    }

    public function __construct () {
        $this->base_url = "http://example.com";
        $this->base_dir = "/var/www/example.com";
        $this->cfg = new PseudoEasySVGConfig ();
    }
}


/**
 * Class EasySVGConfig
 * @package Grav\Plugin
 *
 * Interfacing configuration settings.
 */
class PseudoEasySVGConfig
{    
    public $containerAttributes;
    public $debug = false;
    // public $domainsAllowed;
    public $removeDimensions = true;
    public $removeScriptElements = true;
    public $prefix = "symbol";
    public $write_with_php_dom = false;

    public function __construct () {
        $this->containerAttributes = 'width="0" height="0" style="position:absolute;"';
    }
}
?>