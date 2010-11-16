<?php

if (!class_exists('DOMDocument')) {
    die('DOMDocument Functions not available (php-xml)');
}

define("PARAGRAPH_LIMIT", 4);
define("ALL_PAGES", 'all');

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

class HTMLPager {
  private $pages = array();
  private $pageCount = 0;
  private $pageNumber = 0;
  
  public function __construct($html, $encoding, $pageNumber, $paragraphsPerPage=PARAGRAPH_LIMIT) {
    $dom = new DOMDocument();
    $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', $encoding));
    $body = $dom->getElementsByTagName("body")->item(0);

    $currentPage = NULL;
    $pages = array();
    $currentParagraphCount = 0;

    foreach($body->childNodes as $node) {
      if($currentPage == NULL) {
        // need to start a new page
        if(($node->nodeName == "#text") && (trim($node->nodeValue) == "")) {
          continue; // this node is blank so do not start a new page yet
        }

        $currentPage = new HTMLPage();
        $pages[] = $currentPage;
      }

      $currentPage->addNode($node);

      if($node->nodeName == "p") {
        $currentParagraphCount++;
      }

      if($currentParagraphCount == $paragraphsPerPage) {
        $currentPage = NULL;
        $currentParagraphCount = 0;
      }
    }

    $this->pages = $pages;
    $this->pageCount = count($pages);
    
    if ($pageNumber >= 0 && $pageNumber < $this->pageCount) {
      $this->pageNumber = $pageNumber;
    }
  }
  
  public function getPageNumber() {
    return $this->pageNumber;
  }
  
  public function getPageCount() {
    return $this->pageCount;
  }
  
  public function getPageHTML() {
    if ($this->pageNumber == ALL_PAGES) {
      return $this->getAllPagesHTML();
    
    } else if (isset($this->pages[$this->pageNumber])) {
      return $this->pages[$this->pageNumber]->getText();
    }
    return '';
  }
  
  public function getAllPagesHTML() {
    $allPagesHTML = '';
    
    foreach ($this->pages as $page) {
      $allPagesHTML .= $page->getText();
    }
    return $allPagesHTML;
  }
}
