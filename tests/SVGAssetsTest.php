<?php

require_once('SVGAssets.php');
require_once('pseudoInterface.php');

abstract class XmlTestCase extends PHPUnit_Framework_TestCase
    // Thanks to Tobias Schlitt
    // https://qafoo.com/blog/007_practical_phpunit_testing_xml_generation.html
{
    protected abstract function getDomDocument();

    protected function assertXpathMatch($expected, $xpath,
                                        $message = null, DOMNode $context = null )
    {
        $dom = $this->getDomDocument();
        $xpathObj = new DOMXPath( $dom );

        $context = $context === null
            ? $dom->documentElement
            : $context;

        $res = $xpathObj->evaluate( $xpath, $context );

        $this->assertEquals(
            $expected,
            $res,
            $message
        );
    }
}


class SVGAssetsTest extends XmlTestCase
{
    // Add SVG test files here.
    protected $file_urls = [
        "theme://images/smiley.svg",
        "theme://images/smiley-double.svg",
        "theme://images/smiley-photo-abs.svg",
        "theme://images/smiley-photo-abs-wrong.svg"
    ];

    protected $svg;
    protected $count;
    protected $dom_doc;

    protected function setUp () {
        $this->dom_doc = null;

        $interface = new PseudoEasySVGAccess ();
        $svg = new SVGAssets ($interface);

        $count = 0;
        foreach ($this->file_urls as $u) {
            $svg->add($u);
            $count++;
        }

        $this->count = $count;
        $this->svg = $svg;
    }

    protected function getDomDocument () {
        if ($this->dom_doc) {
            return $this->dom_doc;
        }

        $xml = $this->svg->symbols();

        // While the output from SVGAssets::symbols() is valid for
        // inclusion in HTML5, PHP DOM barfs about xlink:href missing
        // namespace declaration. We have to kludge this.
        $ns_decl = 'xmlns:xlink="http://www.w3.org/1999/xlink"';

        $xml = preg_replace('/^<svg/', "<svg $ns_decl", $xml, 1);
        $rdoc = new \DOMDocument();
        $rdoc->loadXML($xml);

        $this->dom_doc = $rdoc;
        return $rdoc;
    }

    public function testBasics()
    {
        $this->assertXpathMatch(
            $this->count,
            "count(/svg/symbol)",
            "Error loading example SVG and/or converting to symbols"
        );

        $this->assertXpathMatch(
            1,
            "count(/svg/defs)",
            // Because there _are_ defs in the examples:
            "There should be exactly one <defs> element"
        );

        $this->assertXpathMatch(
            0,
            "count(/svg/*[not(self::defs | self::symbol)])",
            "Only defs and symbol are allowed as children of <svg>"
        );

        $this->assertXpathMatch(
            0,
            "count(//defs[not(parent::svg)])",
            "No <defs> other than as a direct child of <svg>"
        );
    }

    public function testIds () {

        $this->assertXpathMatch(
            "symbol-smiley",
            "string(/svg/symbol[1]/@id)",
            "No symbol ID or wrong ID"
        );

        $this->assertXpathMatch(
            "symbol-smiley-double",
            "string(/svg/symbol[2]/@id)",
            "No symbol ID or wrong ID"
        );

    }

    public function testViewBox () {
        $this->assertXpathMatch(
                true,
                "not(/svg/symbol/@viewBox)",
                "Viewbox present on symbol"
        );
    }

    public function testFlush () {
        // "Write out" symbols.
        $this->svg->symbols();
        // A second time without adding new SVG should return false;
        $r = $this->svg->symbols();
        $this->assertFalse($r, "SVGAssets::symbols() not properly 'flushed'");
    }

    private function getUseXPath ($xml) {
        $ns_decl = 'xmlns:xlink="http://www.w3.org/1999/xlink"';

        $xml = preg_replace('/^<svg/', "<svg $ns_decl", $xml, 1);
        $doc = new DOMDocument ();
        $doc->loadXML($xml);
        $xp = new DOMXPath ($doc);

        return $xp;
    }

    public function testUseAfterFlush () {
        // "Write out" symbols.
        $this->svg->symbols();
        $xml = $this->svg->use('smiley');
        $this->assertNotFalse($xml,
                              "SVGAssets::use() returns empty after 'flushing' symbols");
    }

    public function testUseViewbox () {
        $xml = $this->svg->use('smiley');
        $xp = $this->getUseXPath($xml);
        $r = $xp->evaluate("string(/svg/@viewBox)");

        $this->assertEquals("0 0 15 15",
                            $r,
                            "Wrong viewBox or no viewBox on <svg><use>");
    }

    public function testUseId () {
        $xml = $this->svg->use('smiley');
        $xp = $this->getUseXPath($xml);
        $r = $xp->evaluate("string(/svg/use/@xlink:href)");

        $this->assertEquals("#symbol-smiley",
                            $r,
                            "Wrong viewBox or no viewBox on <svg><use>");
    }


    public function testXlinkAbsolute () {
        $this->assertXpathMatch(
            "http://example.com/user/themes/exampletheme/images/face.png",
            "string(/svg/symbol[@id='symbol-smiley-photo-abs']/image/@xlink:href)",
            "Wrong xlink:href URL for absolute filepath"
        );
    }

    public function testXlinkFaulty () {
        // Absolute file path not within CMS directory
        $this->assertXpathMatch(
            "{{{ERROR: file:///home/user/face.png }}}",
            "string(/svg/symbol[@id='symbol-smiley-photo-abs-wrong']/image/@xlink:href)",
            "Wrong xlink:href URL for filepath outside CMS directory"
        );
    }

    // Test: inserting classes/attributes with use(), setting/removing attributes, implement&test: escaping quotation marks when writing XML, implement: checking for existing ids and warn, implement&test: keeping namespaces other than svg and xlink, implement&test: external URLs, implement&test: remove script elements, implement&test: add directory; implement&test: remove dimensions

    // public function testSetAttribute () {

    // }
 }
?>