<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

/**
  * @package Core
  */

/**
  * @package Core
  */
class Sanitizer
{
    static private $tagTypes = array(
        // Groups of tags by type which can be combined by separating with '|' (eg: 'inline|block')
        'inline' => array('<strong>', '<em>', '<code>', '<dfn>', '<samp>', '<var>', '<cite>', '<span>', 
                          '<del>', '<ins>', '<b>', '<i>', '<tt>', '<big>', '<small>', '<sup>', '<sub>', '<bdo>'),
        'header' => array('<h1>', '<h2>', '<h3>', '<h4>', '<h5>', '<h6>'),
        'block'  => array('<div>', '<blockquote>', '<p>', '<hr>', '<br>', '<pre>', 'legend', '<fieldset>'),
        'link'   => array('<a>'),
        'media'  => array('<img>', '<video>', '<audio>', '<iframe>'),
        'list'   => array('<ol>', '<ul>', '<li>', '<dl>', '<dd>', '<dt>'),
        'table'  => array('<table>', '<thead>', '<tbody>', '<tfoot>', '<tr>', '<th>', '<td>', '<col>', '<colgroup>', '<caption>'),
        
        'plugin' => array('<object>', '<param>'),
        'form'   => array('<form>', 'label', '<input>', '<textarea>', '<select>', '<option>', '<optgroup>', '<button>'),
        'style'  => array('<style>'),
        'imgmap' => array('<map>', '<area>'),
        
        // Groups of tags by use case
        'editor' => array(
            '<strong>', '<em>', '<code>', '<dfn>', '<samp>', '<var>', '<cite>', '<span>', 
                '<del>', '<ins>', '<b>', '<i>', '<tt>', '<big>', '<small>', '<sup>', '<sub>', '<bdo>', 
            '<h1>', '<h2>', '<h3>', '<h4>', '<h5>', '<h6>',
            '<div>', '<blockquote>', '<p>', '<hr>', '<br>', '<pre>', 'legend', '<fieldset>', 
            '<a>', 
            '<img>', '<video>', '<audio>', '<source>', '<iframe>', 
            '<ol>', '<ul>', '<li>', '<dl>', '<dd>', '<dt>', 
            '<table>', '<thead>', '<tbody>', '<tfoot>', '<tr>', '<th>', '<td>', '<col>', '<colgroup>', '<caption>'),
    );
    
    static private $blockNodeNames = array(
        'div', 'blockquote', 'p', 'pre', 'ul', 'dl', 'table', 'legend', 'fieldset'
    );
    
    // <script> is always removed
    // These tags have content which is only useful if the tag is there:
    static private $hideEntireBlockTags = array(
        'head',
        'style',
        'form',
        'map',
    );
    
    static private $reOpts = "ims";
    
    //
    // Filter to remove XSS injection attacks and unwanted tags from HTML
    // Note: always removes all javascript from HTML even if $allowedTags contains <script>
    //
    public static function sanitizeHTML($string, $allowedTags='editor') {
        $useTagWhitelist = true;
        $tagWhitelist = array();
        
        // figure out what tags are okay
        if (is_array($allowedTags)) {
          $tagWhitelist = $allowedTags;
          
        } else if (is_string($allowedTags)) {
            $allowedArray = explode('|', $allowedTags);
            
            foreach ($allowedArray as $type) {
                if (isset(self::$tagTypes[$type])) {
                    $tagWhitelist = array_merge($tagWhitelist, self::$tagTypes[$type]);
                } else if ($type == 'all') {
                    $useTagWhitelist = false;
                    break;
                }
            }
        }
        if ($useTagWhitelist) {
            $tagWhitelist = array_map('strtolower', array_unique($tagWhitelist));
        }
        
        // Remove all newlines to make regular expression mapping
        //$string = str_replace("\n", " ", $string);
        
        // The content of these tags is only useful if the tag exists.  Remove the entire block 
        // unless the caller has explicitly asked for it to be included.  
        // Otherwise the content would show up unexpectedly.
        $removeBlockTags = array('script'); // always remove script tags and their content
        foreach (self::$hideEntireBlockTags as $tag) {
            if ($useTagWhitelist && !in_array('<'.strtolower($tag).'>', $tagWhitelist)) {
                $removeBlockTags[] = $tag;
            }
        }
        foreach ($removeBlockTags as $tag) {
            $string = preg_replace(';<\s*'.$tag.'(?:\s+[^>]*|\s*)>.*<\s*/\s*'.$tag.'\s*>;'.self::$reOpts, '', $string);
        }
        
        if ($useTagWhitelist) {
            $string = strip_tags($string, implode('', $tagWhitelist));
        }
        
        // remove attribute-based injection attacks:
        $string = preg_replace_callback('/<(.*?)>/'.self::$reOpts, array(get_class(), 'tagPregReplaceCallback'), $string);
        
        return $string;
    }

    //
    // HTML-safe sanitization and truncation
    // $length is length to truncate at.
    // $margin is the amount greater than $length which the text must be before it truncates
    // $charset is the meta tag charset encoding
    //
    public static function sanitizeAndTruncateHTML($string, &$truncated, $length, $margin, $minLineLength=40, $allowedTags='editor', $encoding='utf-8') {
        $sanitized = self::sanitizeHTML($string, $allowedTags);
        
        $truncated = false;
        $dom = new DOMDocument();
        @$dom->loadHTML('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML Basic 1.1//EN" "http://www.w3.org/TR/xhtml-basic/xhtml-basic11.dtd"><html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en"><head><meta http-equiv="Content-Type" content="text/html; charset='.$encoding.'"/></head><body>'.$sanitized.'</body></html>');
        $dom->normalizeDocument();
        
        $bodies = $dom->getElementsByTagName('body');
        if ($bodies->length) {
            $count = self::walkForTruncation($dom, $bodies->item(0), $length, $margin, $minLineLength, $encoding, $lastTextNode);
            
            // use truncated version if we have exceeded the margin:
            if ($count >= $length + $margin) {
                if ($lastTextNode) {
                    self::appendTruncationSuffix($dom, $lastTextNode);
                }
                $parts = preg_split(';</?body[^>]*>;'.self::$reOpts, $dom->saveHTML());
                if (count($parts) > 1) { // should be 3
                    $sanitized = $parts[1];
                    $truncated = true;
                }
            }
        }
        
        return $sanitized;
    }
    
    private static function walkForTruncation($dom, $node, $length, $margin, $minLineLength, $encoding, 
                                              &$lastTextNode, $count=0, &$currentBlock=null, &$currentBlockCount=0) {
        // We only truncate once the margin is exceeded.  This avoids the problem where
        // the truncated version is only a couple words less than the full version.
        // If we have exceeded the margin, we can stop counting and just delete nodes.
        // Otherwise we keep counting and truncate as needed.
        if ($count > ($length + $margin)) {
            if ($node->parentNode) {
                $node->parentNode->removeChild($node);
            }
            
        } else if ($node->nodeType != XML_TEXT_NODE) {
            // only remove after counting text for margins
            if ($node->hasChildNodes()) {
                if (self::nodeIsBlock($node)) {
                    // new block started causing a newline, reset
                    $currentBlockCount = 0;
                    $currentBlock = $node;
                }
                
                // walk the children
                // because this function can change node's child count, figure out
                // which nodes we need to look at before calling ourselves recursively
                $childNodes = array();
                $nodeCount = $node->childNodes->length;
                for ($i = 0; $i < $nodeCount; $i++) {
                    $childNodes[] = $node->childNodes->item($i);
                }
                
                foreach ($childNodes as $childNode) {
                    $count = self::walkForTruncation($dom, $childNode, $length, $margin, $minLineLength, 
                        $encoding, $lastTextNode, $count, $currentBlock, $currentBlockCount);
                }
                
                if (self::nodeIsBlock($node)) {
                    // block ended causing another newline, reset
                    $currentBlockCount = 0;
                    $currentBlock = null;
                }
            }
            
        } else {
            // Text node!
            //
            // remove newlines and replace runs of whitespace with single space
            $text = preg_replace('/\s+/', ' ', str_replace("\n", '', $node->wholeText)); 
            $textLength = mb_strlen($text, $encoding);
            $remaining = $length - $count;
            if ($remaining > 0 && $currentBlockCount < $minLineLength) {
                $remaining = max($remaining, $minLineLength - $currentBlockCount);
            }
            
            if ($remaining > 0) {
                if (mb_strlen(trim($text), $encoding) > 0) {
                    // text node contains non-whitespace so can take ellipsis
                    $lastTextNode = $node;
                }
                
                if ($textLength > $remaining) {
                    // need to clip text node
                    $basicClipped = mb_substr($text, 0, $remaining + 1, $encoding);
                    
                    // truncate text node at a word nearest to $length
                    $clipped = preg_replace('/\s+?(\S+)?$/', '', $basicClipped);
                    $node->replaceData(0, $node->length, $clipped);
                    
                } else if (isset($currentBlock)) {
                    $currentBlockCount += $textLength;
                }
            } else {
                // past length, remove node but keep counting
                // since we haven't hit the limit
                $node->parentNode->removeChild($node);
            }
            
            $count += $textLength;
        }
        
        return $count;
    }
    
    private static function appendTruncationSuffix(&$dom, &$node, $replacementText=null) {
        
        $text = isset($replacementText) ? $replacementText : $node->wholeText;
        $clipped = preg_replace('/[.\s]*$/', '', $text);
        if (trim($clipped)) {
            $node->replaceData(0, $node->length, $clipped);
            
            $suffix = $dom->createElement('span');
            $suffix->appendChild($dom->createTextNode(Kurogo::getLocalizedString('SANITIZER_HTML_TRUNCATION_SUFFIX')));
            $suffix->setAttribute('class', 'trunctation-suffix');
            if ($node->nextSibling) {
                $node->parentNode->insertBefore($suffix, $node->nextSibling);
            } else {
                $node->parentNode->appendChild($suffix);
            }
        }
    }
    
    private static function nodeIsBlock($node) {
        return $node->nodeType == XML_ELEMENT_NODE && 
               in_array(strtolower($node->nodeName), self::$blockNodeNames);
    }
    
    protected static function tagPregReplaceCallback($matches) {
        // From http://us3.php.net/manual/en/function.strip-tags.php
        static $regexps = array();
        static $replacements = array();
        
        if (!$regexps || !$replacements) {
            // Build these so the code is easier to read
            $jsAttributes = array('onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavaible', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragdrop', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterupdate', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmoveout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload');
            $anyJSAttr = implode('|', $jsAttributes);
            
            $regexps = array(
                '/=\s*"\s*javascript:[^"]*"/'.self::$reOpts,                           // double-quoted attr with value containing js
                '/=\s*\'\s*javascript:[^\']*\'/'.self::$reOpts,                        // single-quoted attr with value containing js
                '/=\s*javascript:[^\s]*/'.self::$reOpts,                               // quoteless attr with value containing js
                '/('.$anyJSAttr.')\s*=\s*(["][^"]*["]|[\'][^\']*[\'])/'.self::$reOpts, // attr that triggers js
            );
            $replacements = array(
                '=""', // remove js in attr value
                '=""', // remove js in attr value
                '=""', // remove js in attr value
                '',    // remove attr that triggers js
            );
        }
        
        return preg_replace($regexps, $replacements, $matches[0]);
    }

    //
    // Filter to remove javascript from urls
    // Assumes URL is dumped into href or src attr as-is
    //
    public static function sanitizeURL($string) {
        return preg_replace('/javascript:.*/'.self::$reOpts, '', strip_tags($string));
    }
}
