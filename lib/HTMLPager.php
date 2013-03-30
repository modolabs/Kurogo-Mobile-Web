<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

/**
 * HTMLPage
 * @package HTML
 */

if (!class_exists('DOMDocument')) {
    throw new KurogoException('DOMDocument PHP extension is not installed. http://www.php.net/manual/en/book.dom.php');
}

if (!function_exists('mb_convert_encoding')) {
    throw new KurogoException('Multibyte String PHP extension is not installed. http://www.php.net/manual/en/book.mbstring.php');
}

/**
 * HTMLPage
 * @package HTML
 */
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

/**
 * HTMLPager
 * @package HTML
 */
class HTMLPager {
  const PARAGRAPH_LIMIT=4;
  const ALL_PAGES='all';
  private $pages = array();
  private $pageCount = 0;
  private $pageNumber = 0;
  
  public function __construct($html, $encoding, $pageNumber, $paragraphsPerPage=HTMLPager::PARAGRAPH_LIMIT) {
    $dom = new DOMDocument();
    
    libxml_use_internal_errors(true);
    libxml_clear_errors(); // clean up any errors belonging to other operations
    $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', $encoding));
    foreach (libxml_get_errors() as $error) {
      Kurogo::log(LOG_WARNING,"HTMLPager got loadHTML warning (line {$error->line}; column {$error->column}) {$error->message}",'data');
    }
    libxml_clear_errors(); // free up memory associated with the errors
    libxml_use_internal_errors(false);
    
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
    if ($this->pageNumber == HTMLPager::ALL_PAGES) {
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
