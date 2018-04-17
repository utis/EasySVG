<?php
namespace Grav\Plugin;

use Grav\Common\Plugin;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

/**
 * Class EasySVGPlugin
 * @package Grav\Plugin
 */
class EasySVGPlugin extends Plugin
{
    public $svg;
    
    public static function getSubscribedEvents()
    {
        return [
            'onPluginsInitialized' => ['onPluginsInitialized', 0],
            'onTwigExtensions' => ['onTwigExtensions', 0]
        ];
    }

    /**
     * Initialize the plugin
     */
    public function onPluginsInitialized()
    {
        // Don't proceed if we are in the admin plugin
        if ($this->isAdmin()) {
            return;
        }

        $interface = new EasySVGAccess ($this->grav, $this->config);

        require_once __DIR__ . '/SVGAssets.php';
        $this->svg = new \SVGAssets ($interface);
    }

    public function onTwigExtensions()
    {
        if ($this->isAdmin()) {
            return;
        }

        $this->grav["twig"]->twig->addGlobal('svg', $this->svg);
    }
}


/**
 * Class EasySVGAccess
 * @package Grav\Plugin
 *
 * URL handling and logging, interfacing with the CMS.
 */
class EasySVGAccess {
    // UniformResourceLocator $locator,
    protected $locator;
    protected $log;
    
    public $base_url;
    public $base_dir;
    public $cfg;

    // FIXME: DTRT with external URLs.  (Since external SVG poses a
    // security risk, the right thing is probably to ignore them unless
    // some flag is explicitely set.)
    
    /**
     * Return local absolute file path corresponding to PHP stream.
     *
     * The URL must point to an existing file or directory, otherwise this
     * returns false. Handles URLs like theme://images/example.svg. Returns
     * an _absolute_ file path.
     *
     * @param string $url
     *
     * @return string|false
     */
    public function filePath ($url) {
        return $this->locator->findResource($url, true);
    }


    protected function relativePath ($url) {
        return $this->locator->findResource($url, false);
    }

    /**
     * Return URL corresponding to PHP stream.
     *
     * This resolves URLs like "theme://images/example.svg" to either an
     * absolute Http URL like
     * "http://<my-domain>/<my-theme-path>/images/example.svg" or into an
     * relative one, depending on Grav's settings.
     *
     * @param string $url
     *
     * @return string
     */
    public function url ($url) {
        // FIXME: Return false if file or directory doesn't exist.
        return $this->base_url . '/' . $this->relativePath($url);
    }
        

    public function warn ($message) {
        $this->log->warning($message);

        if ($this->cfg->debug) {
            echo "<!-- WARNING: $message -->";
        }
    }


    public function error ($message) {
        $this->log->error($message);

        if ($this->cfg->debug) {
            echo "<!-- ERROR: $message -->";
        }
    }


    public function __construct ($grav, $config) {
        $this->locator = $grav['locator'];
        $this->base_url = $grav['base_url'];
        $this->base_dir = $this->locator->base;

        $this->log = $grav['log'];
        $this->cfg = new EasySVGConfig ($config);
    }
}


/**
 * Class EasySVGConfig
 * @package Grav\Plugin
 *
 * Interfacing configuration settings.
 */
class EasySVGConfig
{    
    public $containerAttributes;
    public $domainsAllowed;
    public $removeDimensions;
    public $removeScriptElements;
    public $prefix;
    public $write_with_php_dom;
    public $debug;

    public function __construct ($config) {
        $this->containerAttributes = $config->get('plugins.easysvg.container_attributes');
        $this->debug = $config->get('system.debugger.enabled');
        $this->domainsAllowed = $config->get('plugins.easysvg.domains_allowed');
        $this->removeDimensions = $config->get('plugins.easysvg.remove.dimensions');
        $this->removeScriptElements = $config->get('plugins.easysvg.remove.script_elements');
        $this->prefix = $config->get('plugins.easysvg.prefix');
        $this->write_with_php_dom = $config->get('plugins.easysvg.write_with_php_dom');
    }
}
