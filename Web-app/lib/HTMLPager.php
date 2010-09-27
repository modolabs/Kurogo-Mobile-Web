<?php

class HTMLPage {
    private $document;
    private $body;
    private static $header = "<html><body>";
    private static $footer = "</body></html>";


    public function __construct() {
        $this->document = new DOMDocument();
        $this->document->loadHTML(self::$header . self::$footer);
        $root = $this->document;
        $this->body = $root->getElementsByTagName("body")->item(0);
    }

    public function addNode(DOMNode $node) {
        $nodeCopy = $this->document->importNode($node, true);
        $this->body->appendChild($nodeCopy);
    }

    public function getText() {
        $text = $this->document->saveHTML();

        // removes header
        $content_position = strpos($text, self::$header) + strlen(self::$header);
        $text = substr($text, $content_position);

        // removes footer
        $text = substr($text, 0, strlen($text) - strlen(self::$footer)-1);

        return trim($text);
    }

}

function HTMLPager($html, $paragraph_limit) {
    $dom = new DOMDocument();
    $dom->loadHTML($html);
    $body = $dom->getElementsByTagName("body")->item(0);

    $current_page = NULL;
    $pages = array();
    $current_paragraph_count = 0;

    foreach($body->childNodes as $node) {
        if($current_page == NULL) {
            // need to start a new page
            if(($node->nodeName == "#text") && (trim($node->nodeValue) == "")) {
                // this node is blank so do not start a new page yet
                continue;
            }

            $current_page = new HTMLPage();
            $pages[] = $current_page;
        }

        $current_page->addNode($node);

        if($node->nodeName == "p") {
            $current_paragraph_count++;
        }

        if($current_paragraph_count == $paragraph_limit) {
            $current_page = NULL;
            $current_paragraph_count = 0;
        }
    }

    return $pages;
}
