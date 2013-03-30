<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

includePackage('readability');
/**
 * KurogoReader 
 * 
 * @uses Readability
 * @package 
 * @license 
 */
class KurogoReader {

    protected $DEFAULT_READER_RETRIEVER_CLASS = "URLDataRetriever";
    protected $DEFAULT_READER_PARSER_CLASS = "KurogoReaderDataParser";

    private $title;
    private $content;

    public function __construct($url, $args = array()) {
        $this->args = $args;

        $this->fetchContent($url);
    }

    protected function getRetriever() {
        if(isset($this->args['READER_RETRIEVER_CLASS'])) {
            $retrieverClass = $this->args['READER_RETRIEVER_CLASS'];
        }else {
            $retrieverClass = $this->DEFAULT_READER_RETRIEVER_CLASS;
        }
        if(isset($this->args['READER_PARSER_CLASS'])) {
            $parserClass = $this->args['READER_PARSER_CLASS'];
        }else if ($this->DEFAULT_READER_PARSER_CLASS) {
            $parserClass = $this->DEFAULT_READER_PARSER_CLASS;
        }else {
            $parserClass = "PassthroughDataParser";
        }
        $this->args["PARSER_CLASS"] = $parserClass;
        $this->args['SHOW_WARNINGS'] = false;
        return DataRetriever::factory($retrieverClass, $this->args);
    }

    /**
     * fetchContent 
     * fetch content from specified url through KurogoReaderDataParser
     * 
     * @param mixed $url 
     * @access private
     * @return void
     */
    private function fetchContent($url) {
        $retriever = $this->getRetriever();
        $retriever->setBaseURL($url);
        $retriever->setOption('readerUrl', $url);
        $article = $retriever->getData();

        if (isset($article['title'])) {
            $this->title = $article['title'];
        }
        if (isset($article['content'])) {
            $this->content = $article['content'];
        }
    }

    /**
     * getTitle 
     * main portal to fetch article title
     * 
     * @access public
     * @return void
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * getContent 
     * main portal to fetch article content
     * 
     * @access public
     * @return void
     */
    public function getContent() {
        return $this->content;
    }
}
