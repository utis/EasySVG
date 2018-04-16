<?php
class SVGAssets {
    protected $svgs = array();
    protected $cms;

    const UNWANTED_NSs = ['http://www.w3.org/1999/xlink',
                          'http://www.w3.org/XML/1998/namespace'];

    const SVG_NS = "http://www.w3.org/2000/svg";
    const XLINK_NS = 'http://www.w3.org/1999/xlink';
    const XLINK_PFX = 'xlink';


    protected function loadSVG ($path) {
        $tmp_doc = new \DOMDocument();

        if (!$tmp_doc->load($path)) {
            return false;
        }

        $tmp_doc->documentElement->removeAttributeNS(self::SVG_NS, "");
        $doc = new \DOMDocument();
        $doc->loadXML($tmp_doc->saveXML());

        return $doc;
    }

    /**
     * Parse SVG file and add the result to internal list of assets.
     *
     * Optional argument $id is the key by which the SVG is referenced
     * later. If omitted or null, this defaults to an ID generated from the
     * file name.
     *
     * @param string $url
     * @param null | string $id
     *
     */
    public function add ($url, $id=null) {
        $path = $this->cms->filePath($url);

        if (!$path) {
            $this->cms->error("No such file: $url");
            return false;
        }

        $doc = $this->loadSVG($path);
        if (!$doc) {
            $this->cms->error("Couldn't load SVG: $path");
            return false;
        }

        $id = $this->checkId($doc, $id, $path, $this->svgs);
        $prefix = $this->cms->cfg->prefix;
        $doc->documentElement->setAttribute("id", "{$prefix}-{$id}");

        $viewbox = $this->getViewbox($doc);

        $doc->documentElement->removeAttribute("viewBox");

        $data = [
            'doc' => $doc,
            'path' => $path,
            'orig_url' => $url,
            'viewbox' => $viewbox
        ];

        $this->svgs[$id] = $data;

        return $this;
    }


    /**
     * Return SVG generated from internal list of SVG.
     *
     * The latter are processed and converted to `symbol` elements; `defs`
     * elements inside the original SVGs are move up to the beginning of
     * the generated one. Return value is a string: the XML representation
     * of the generated SVG for inclusion in HTML5 from Twig.
     *
     * Internal SVGs are marked as 'processed', so that future calls to
     * SVGAssets::symbols() will ignore them, while keeping them available
     * for referencing.
     *
     * @return string
     */
    public function symbols () {
        if (count($this->svgs) == 0) {
            $this->cms->warn("svg.symbols() called without any SVG added");
            return false;
        }

        $docs = array();
        $updated = array();

        foreach($this->svgs as $k => $s) {
            if (!array_key_exists('processed', $s)
                  || !$s['processed']) {
                $docs[] = $this->processSVG($s['doc']);
                $s['processed'] = true;
            }
            $updated[$k] = $s;
        }

        $this->svgs = $updated;

        if (count($docs) == 0) {
            $this->cms->warn("svg.symbols() called without any SVG added");
            return false;
        }

        $new = $this->svgs2Symbols($docs);
        $new = $this->moveDefsUp($new);

        $xml = $this->writeSVG($new);

        return $xml;
    }


    // Move <devs> elements up to the top of the document.
    // More precisely: Remove <defs> elements deep inside the document and
    // place their children inside a single <defs> as the first child of
    // the root element.
    protected function moveDefsUp ($doc) {
        $xp = new \DOMXpath ($doc);

        $defs = $xp->query('//defs');

        if ($defs->length > 0) {
            $new = $doc->createElement("defs");

            foreach ($defs as $d) {
                while ($d->childNodes->length > 0) {
                    $new->appendChild($d->childNodes->item(0));
                }
                $d->parentNode->removeChild($d);
            }

            $doc->documentElement->insertBefore($new, $doc->documentElement->firstChild);
        }

        return $doc;
    }


    /**
     * Return SVG string referencing symbol with ID $id.
     *
     * FIXME: DESCRIPTION
     * @param string $id
     * @param false | string | array
     *
     * @return string | false
     */
    public function use ($id, $attr = false) {
        if (!array_key_exists($id, $this->svgs)) {
            $this->cms->error("Easy SVG: No such SVG symbol: $id");
            return false;
        }

        $attr = $this->useProcessClass($attr);
        $data = $this->svgs[$id];
        $pfx = $this->cms->cfg->prefix;
        return "<svg {$attr}viewBox='{$data['viewbox']}'><use xlink:href='#{$pfx}-{$id}'/></svg>";
    }

    public function useProcessClass ($arg) {
        if (is_string($arg)) {
            return "class=\"$arg\" ";
        } else if (is_array ($arg)) {
            $str="";
            foreach ($arg as $k => $v) {
                $str = $str . "{$k}=\"{$v}\" ";
            }
            return $str;
        } else {
            return "";
        }
    }

    protected function getViewbox ($doc) {
        return $doc->documentElement->getAttribute('viewBox');
    }


    protected function removeViewbox ($doc) {
        $doc->documentElement->removeAttribute("viewBox");
        return $doc;
    }

    // Apply various manipulations to DOMDocument $doc.
    // Returns $doc.
    protected function processSVG ($doc) {
        $xp = new \DOMXPath($doc);
        $xp->registerNamespace(self::XLINK_PFX, self::XLINK_NS);

        $doc = $this->updateXLinks($doc, $xp);
        $doc = $this->removeViewbox($doc);
        // ... more to come
        return $doc;
    }


    protected function checkId ($doc, $id, $path, $svgs) {
        if ($id) {
            // FIXME: Check if $id is already in use.
        } else {
            $id = $this->fileNameId($path, $svgs);
        }
        return $id;
    }

    // Generate a human-usable reference string from $path.
    protected function fileNameId ($path, $svgs) {
        // FIXME: Deal with files of the same name in different folders.
        // FIXME: Check if ID is alread in use.
        return basename($path, ".svg");
    }


    protected function isRemoteURL ($url) {
        return preg_match('/^https?:\/\//', $url) !== 0;
    }


    protected function isAbsoluteFileLink ($url) {
        return preg_match('/^file:\/\//', $url) !== 0;
    }


    protected function isLocalId ($url) {
        return preg_match('/^#/', $url) !== 0;
    }


    // Generate http URL from absolute file path.
    // The file path needs to point to a file or directory inside the CMS's
    // base directory.
    protected function absolutePath2URL ($path) {
        $rx = '/^file:\/\/' . preg_quote($this->cms->base_dir, '/') . '/i';
        if (preg_match($rx, $path)) {
                return preg_replace($rx, $this->cms->base_url, $path);
        } else {
            return false;
        }
    }

    // DOM manipulation: Update xlink:href attributes to an http URL.
    protected function updateXLinks ($doc, $xp) {
        $links = $xp->query("//*/@xlink:href");
        foreach ($links as $l) {
            $url = $l->value;
            $new = false;

            if ($this->isRemoteURL($url)) {
                // FIXME
            } else if ($this->isAbsoluteFileLink ($url)) {
                $new = $this->absolutePath2URL($url);

                if (!$new) {

                    $this->cms->error("Couldn't resolve path: $url");
                    $new = "{{{ERROR: $url }}}";

                }
            } else if ($this->isLocalId ($url)) {
                // Do nothing!
            } else {

            }

            $l->value = $new;
        }
        return $doc;
    }



    protected function svgs2Symbols ($docs) {
        $new = new \DOMDocument ();
        $new->loadXML('<svg '
                      . $this->cms->cfg->containerAttributes
                      . '></svg>');

        foreach ($docs as $d) {
            $elt = $new->createElement("symbol");

            $d = $new->importNode($d->documentElement, true);

            // Copy attributes.
            foreach ($d->attributes as $a) {
                $elt->setAttribute($a->name, $a->value);
            }

            // Move children.
            while ($d->childNodes->length > 0) {
                $elt->appendChild($d->childNodes->item(0));
            }
            $new->documentElement->appendChild($elt);
        }

        return $new;
    }


    // protected function getNamespaces ($doc) {
    //     $xpath = new \DOMXPath($doc);

    //     $ns = $xpath->query('namespace::*', $doc->documentElement);
    //     $ns_str = "";

    //     foreach($ns as $n) {
    //         if ($n->namespaceURI != $default_ns && !in_array($n->namespaceURI, $delete_ns_decls))
    //             $ns_str = $ns_str . " {$n->nodeName}='{$n->namespaceURI}'";
    //     };
    // }

    protected function writeSVG ($doc) {
        if ($this->cms->cfg->write_with_php_dom) {
            return $doc->saveXML($doc->documentElement);
        } else {
            return $this->traverseDOM($doc, [$this, 'writeDOMElement'])['xml'];
        }
    }

   protected function writeDOMElement($e, $acc, $rest) {
       // FIXME: Write namespace declarations other then the
       // ones prohibited by self:UNWANTED_NS.
        if (is_null($acc)) {
            $acc = array('xml' => "", 'first' => $e);
        }

        switch ($e->nodeType) {
        case XML_TEXT_NODE:
            $acc['xml'] = $acc['xml'] . $e->wholeText;
            break;
        case XML_CDATA_SECTION_NODE:
            $acc['xml'] = $acc['xml'] . $e->wholeText;
            break;
        case XML_ELEMENT_NODE:
            $str = $e->tagName;

            foreach ($e->attributes as $a) {
                // FIXME: escape quotation marks.
                $str = $str . " {$a->nodeName}=\"{$a->value}\"";
            }
            if ($e->hasChildNodes()) {
                $acc['xml'] = $acc['xml'] . "<{$str}>";
            } else {
                $acc['xml'] = $acc['xml'] . "<{$str}/>";
            }
        }

        // Get the last element of an array or null:
        $array_peek = function ($a) {
            $size = sizeof($a);
            if ($size > 0) {
                return $a[$size - 1];
            } else {
                return null;
            }
        };

        // Add closing tags.
        $next = $array_peek($rest);

        while (($next
                && !($e === $next->parentNode)
                && !($e->nextSibling === $next))
               || (!$next && $e !== $acc['first'])) {
            $acc['xml'] = $acc['xml'] . "</{$e->parentNode->tagName}>";
            $e = $e->parentNode;
        }

        return $acc;
    }


    protected function traverseDOM ($doc, $func) {
        // Traverse all elements in a DOM tree depth-first.
        if ($doc->nodeType == XML_DOCUMENT_NODE) {
            $doc = $doc->documentElement;
        }
        $r = array($doc);
        $acc = null;

        while ($elt = array_pop($r)) {

            if ($elt->hasChildNodes()) {
                for ($i = $elt->childNodes->length - 1; $i >= 0; $i--) {
                    array_push($r, $elt->childNodes->item($i));
                }
            }

            // Call function with element, accumulator and the nodes
            // currently next in line.  Set accumulator to the return
            // value. What the accumulator is and what the function does
            // with it is the function's business alone.

            $acc = $func($elt, $acc, $r);
        }
        return $acc;
    }


    protected function getXPathNodes ($doc, $exp) {
        $xp = new \DOMXPath ($doc);
        $xp->registerNamespace(self::XLINK_PFX, self::XLINK_NS);

        $lst = $xp->query($exp);

        if (! (gettype($lst) == 'object'
               && get_class($lst) == 'DOMNodeList')) {
            return false;
        }

        if ($lst->length == 0) {
            return false;
        }

        return $lst;
    }

    protected function getDocumentById ($id) {
        $svg = array_key_exists($id, $this->svgs) ? $this->svgs[$id] : null;
        if (!$svg || (array_key_exists('processed', $svg) && $svg['processed'])) {

            return false;
        }
        return $svg['doc'];
    }


    public function setAttribute($id, $xpath, $attr, $value) {
        $doc = $this->getDocumentById ($id);

        if (!$doc) {
            $this->cms->warn("setAttribute(): No such ID: $id");
            return $this;
        }

        $lst = $this->getXpathNodes($doc, $xpath);

        if (!$lst) {
            $this->cms->warn("setAttribute(): No valid results from '$xpath'");
        }

        foreach ($lst as $e) {
            if (get_class($e) != 'DOMElement') {
                $this->cms->warn("setAttribute(): Not an element from '$xpath'");
            } else {
                if ($value) {
                    $e->setAttribute($attr, $value);
                } else {
                    $e->removeAttribute($attr);
                }
            }
        }

        return $this;
    }

    public function removeAttribute($id, $xpath, $attr=false) {
        if ($attr) {
            return $this->setAttribute($id, $xpath, $attr, false);
        } else {
            $doc = $this->getDocumentById ($id);

            if (!$doc) {
                $this->cms->warn("removeAttribute(): No such ID: $id");
                return $this;
            }

            $lst = $this->getXpathNodes($doc, $xpath);

            if (!$lst) {
                $this->cms->warn("removeAttribute(): No valid results from '$xpath'");
            }

            foreach ($lst as $a) {
                switch (get_class($a)) {
                case 'DOMElement':
                    $this->cms->warn("removeAttribute(): Third argument false, but XPath returns element: '$xpath'");
                    break;
                case 'DOMAttr':
                    $a->ownerElement->removeAttributeNode($a);
                    break;
                default:
                    $this->cms->warn("removeAttribute(): Not an attribute from '$xpath'");
                }
            }
            return $this;
        }
    }


    public function __construct ($interface) {
        $this->cms = $interface;
    }
}

?>