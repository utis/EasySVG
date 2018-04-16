<?php
// $url = "theme://images/example.svg";
// $matches = array();
// preg_match('/^theme:\/\/(.*)$/', $url, $matches);
// var_dump($matches);

// include ('pseudoInterface.php');
// $test = new PseudoEasySVGAccess ();
// echo $test->url("theme://images/smiley.svg"), PHP_EOL;

// $doc = new \DOMDocument();
// $doc->load('images/smiley.svg');
// $doc->documentElement->removeAttributeNS("http://www.w3.org/2000/svg", "");
// $doc->documentElement->removeAttributeNS("http://www.w3.org/1999/xlink", "xlink");
// echo $doc->saveXML();
// echo "----------------------------------", PHP_EOL;
// $xpath = new \DOMXPath($doc);
// // $xpath->registerNamespace("s", "http://www.w3.org/2000/svg");
// // *[not(self::defs)]

$svg = <<<EOF
<svg width="300" height="150" viewBox="0 0 30 15">
  <defs id="pinkiepie">
    <radialGradient id="rg" cx=".7" cy=".3" r=".5" fx=".5" fy=".5">
      <stop id="test" offset="20%" stop-color="yellow"/>
      <stop offset="100%" stop-color="#Fa0"/>
    </radialGradient>
  </defs>
  <circle id="fluttershy" cx="7.5" cy="7.5" r="7" stroke-width="1" fill="url(#rg)" stroke="black"/>
  <circle id="twilightsparkle" cx="4.5" cy="5.5" r="1" fill="black"/>
  <circle cx="10.5" cy="5.5" r="1" fill="black"/>

  <path stroke-width="1" stroke="black" fill="none" d="M4 8.5 C5 12, 10 12, 11 8.5"/>
  <symbol><defs>lala</defs></symbol>
</svg>
EOF;

$svg2 = <<<EOF
<svg width="300" height="150" viewBox="0 0 30 15">
  <defs id="pinkiepie">
    <radialGradient id="rg" cx=".7" cy=".3" r=".5" fx=".5" fy=".5">
      <stop id="test" offset="20%" stop-color="yellow"/>
      <stop offset="100%" stop-color="#Fa0"/>
    </radialGradient>
  </defs>
  <symbol id="applejack">
  <circle id="fluttershy" cx="7.5" cy="7.5" r="7" stroke-width="1" fill="url(#rg)" stroke="black"/>
  <circle id="twilightsparkle" cx="4.5" cy="5.5" r="1" fill="black"/>
  <circle cx="10.5" cy="5.5" r="1" fill="black"/>
  <path stroke-width="1" stroke="black" fill="none" d="M4 8.5 C5 12, 10 12, 11 8.5"/>
  </symbol>
  <symbol id="rarity"><defs>lala</defs></symbol>
</svg>
EOF;

$path = __DIR__ . "/test.svg";
$doc = new DOMDocument();
// $doc->loadXML($svg2);
$doc->load($path);
$xpath = new DOMXPath ($doc);


// $exp = "string(/svg/@viewBox)";
$exp = "string(/svg/image/@xlink:href)";

$res = $xpath->evaluate($exp);
var_dump($res);

if (gettype($res) == 'object' && get_class($res) == 'DOMNodeList') {
    foreach ($res as $e) {
        echo '----', PHP_EOL;
        echo $doc->saveXML($e), PHP_EOL;
    }
}

// Only defs and symbol are children of <svg>: "count(/svg/*[not(self::defs | self::symbol)])" = 0
// No defs other than as adirect child of <svg>: "count(//defs[not(parent::svg)])" = 0

?>