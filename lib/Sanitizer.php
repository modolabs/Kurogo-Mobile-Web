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
        'block'  => array('<div>', '<blockquote>', '<p>', '<hr>', '<br>', '<pre>', '<legend>', '<fieldset>'),
        'link'   => array('<a>'),
        'media'  => array('<img>', '<video>', '<audio>', '<iframe>'),
        'list'   => array('<ol>', '<ul>', '<li>', '<dl>', '<dd>', '<dt>'),
        'table'  => array('<table>', '<thead>', '<tbody>', '<tfoot>', '<tr>', '<th>', '<td>', '<col>', '<colgroup>', '<caption>'),
        
        'plugin' => array('<object>', '<param>', '<embed>'),
        'form'   => array('<form>', '<label>', '<input>', '<textarea>', '<select>', '<option>', '<optgroup>', '<button>'),
        'style'  => array('<style>'),
        'imgmap' => array('<map>', '<area>'),
        
        // Groups of tags by use case
        'editor' => array(
            '<strong>', '<em>', '<code>', '<dfn>', '<samp>', '<var>', '<cite>', '<span>', 
                '<del>', '<ins>', '<b>', '<i>', '<tt>', '<big>', '<small>', '<sup>', '<sub>', '<bdo>', 
            '<h1>', '<h2>', '<h3>', '<h4>', '<h5>', '<h6>',
            '<div>', '<blockquote>', '<p>', '<hr>', '<br>', '<pre>', '<legend>', '<fieldset>', 
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
    
    static private $entityNameToNumber = array('&apos;'=>'&#39;', '&minus;'=>'&#45;', '&circ;'=>'&#94;', '&tilde;'=>'&#126;', '&Scaron;'=>'&#138;', '&lsaquo;'=>'&#139;', '&OElig;'=>'&#140;', '&lsquo;'=>'&#145;', '&rsquo;'=>'&#146;', '&ldquo;'=>'&#147;', '&rdquo;'=>'&#148;', '&bull;'=>'&#149;', '&ndash;'=>'&#150;', '&mdash;'=>'&#151;', '&tilde;'=>'&#152;', '&trade;'=>'&#153;', '&scaron;'=>'&#154;', '&rsaquo;'=>'&#155;', '&oelig;'=>'&#156;', '&Yuml;'=>'&#159;', '&yuml;'=>'&#255;', '&OElig;'=>'&#338;', '&oelig;'=>'&#339;', '&Scaron;'=>'&#352;', '&scaron;'=>'&#353;', '&Yuml;'=>'&#376;', '&fnof;'=>'&#402;', '&circ;'=>'&#710;', '&tilde;'=>'&#732;', '&Alpha;'=>'&#913;', '&Beta;'=>'&#914;', '&Gamma;'=>'&#915;', '&Delta;'=>'&#916;', '&Epsilon;'=>'&#917;', '&Zeta;'=>'&#918;', '&Eta;'=>'&#919;', '&Theta;'=>'&#920;', '&Iota;'=>'&#921;', '&Kappa;'=>'&#922;', '&Lambda;'=>'&#923;', '&Mu;'=>'&#924;', '&Nu;'=>'&#925;', '&Xi;'=>'&#926;', '&Omicron;'=>'&#927;', '&Pi;'=>'&#928;', '&Rho;'=>'&#929;', '&Sigma;'=>'&#931;', '&Tau;'=>'&#932;', '&Upsilon;'=>'&#933;', '&Phi;'=>'&#934;', '&Chi;'=>'&#935;', '&Psi;'=>'&#936;', '&Omega;'=>'&#937;', '&alpha;'=>'&#945;', '&beta;'=>'&#946;', '&gamma;'=>'&#947;', '&delta;'=>'&#948;', '&epsilon;'=>'&#949;', '&zeta;'=>'&#950;', '&eta;'=>'&#951;', '&theta;'=>'&#952;', '&iota;'=>'&#953;', '&kappa;'=>'&#954;', '&lambda;'=>'&#955;', '&mu;'=>'&#956;', '&nu;'=>'&#957;', '&xi;'=>'&#958;', '&omicron;'=>'&#959;', '&pi;'=>'&#960;', '&rho;'=>'&#961;', '&sigmaf;'=>'&#962;', '&sigma;'=>'&#963;', '&tau;'=>'&#964;', '&upsilon;'=>'&#965;', '&phi;'=>'&#966;', '&chi;'=>'&#967;', '&psi;'=>'&#968;', '&omega;'=>'&#969;', '&thetasym;'=>'&#977;', '&upsih;'=>'&#978;', '&piv;'=>'&#982;', '&ensp;'=>'&#8194;', '&emsp;'=>'&#8195;', '&thinsp;'=>'&#8201;', '&zwnj;'=>'&#8204;', '&zwj;'=>'&#8205;', '&lrm;'=>'&#8206;', '&rlm;'=>'&#8207;', '&ndash;'=>'&#8211;', '&mdash;'=>'&#8212;', '&lsquo;'=>'&#8216;', '&rsquo;'=>'&#8217;', '&sbquo;'=>'&#8218;', '&ldquo;'=>'&#8220;', '&rdquo;'=>'&#8221;', '&bdquo;'=>'&#8222;', '&dagger;'=>'&#8224;', '&Dagger;'=>'&#8225;', '&bull;'=>'&#8226;', '&hellip;'=>'&#8230;', '&permil;'=>'&#8240;', '&prime;'=>'&#8242;', '&Prime;'=>'&#8243;', '&lsaquo;'=>'&#8249;', '&rsaquo;'=>'&#8250;', '&oline;'=>'&#8254;', '&frasl;'=>'&#8260;', '&euro;'=>'&#8364;', '&image;'=>'&#8465;', '&weierp;'=>'&#8472;', '&real;'=>'&#8476;', '&trade;'=>'&#8482;', '&alefsym;'=>'&#8501;', '&larr;'=>'&#8592;', '&uarr;'=>'&#8593;', '&rarr;'=>'&#8594;', '&darr;'=>'&#8595;', '&harr;'=>'&#8596;', '&crarr;'=>'&#8629;', '&lArr;'=>'&#8656;', '&uArr;'=>'&#8657;', '&rArr;'=>'&#8658;', '&dArr;'=>'&#8659;', '&hArr;'=>'&#8660;', '&forall;'=>'&#8704;', '&part;'=>'&#8706;', '&exist;'=>'&#8707;', '&empty;'=>'&#8709;', '&nabla;'=>'&#8711;', '&isin;'=>'&#8712;', '&notin;'=>'&#8713;', '&ni;'=>'&#8715;', '&prod;'=>'&#8719;', '&sum;'=>'&#8721;', '&minus;'=>'&#8722;', '&lowast;'=>'&#8727;', '&radic;'=>'&#8730;', '&prop;'=>'&#8733;', '&infin;'=>'&#8734;', '&ang;'=>'&#8736;', '&and;'=>'&#8743;', '&or;'=>'&#8744;', '&cap;'=>'&#8745;', '&cup;'=>'&#8746;', '&int;'=>'&#8747;', '&there4;'=>'&#8756;', '&sim;'=>'&#8764;', '&cong;'=>'&#8773;', '&asymp;'=>'&#8776;', '&ne;'=>'&#8800;', '&equiv;'=>'&#8801;', '&le;'=>'&#8804;', '&ge;'=>'&#8805;', '&sub;'=>'&#8834;', '&sup;'=>'&#8835;', '&nsub;'=>'&#8836;', '&sube;'=>'&#8838;', '&supe;'=>'&#8839;', '&oplus;'=>'&#8853;', '&otimes;'=>'&#8855;', '&perp;'=>'&#8869;', '&sdot;'=>'&#8901;', '&lceil;'=>'&#8968;', '&rceil;'=>'&#8969;', '&lfloor;'=>'&#8970;', '&rfloor;'=>'&#8971;', '&lang;'=>'&#9001;', '&rang;'=>'&#9002;', '&loz;'=>'&#9674;', '&spades;'=>'&#9824;', '&clubs;'=>'&#9827;', '&hearts;'=>'&#9829;', '&diams;'=>'&#9830;');
    
    private static function regexForDelimiters($tags) {
        return implode('|', array_map(function ($tag) {
            return rtrim(ltrim($tag, '<'), '>');
        }, $tags));
    }
    
    private static function stripTags($string, $tagWhitelist=array()) {
        $selfClosingBlockTags = array('<br>', '<hr>');
        $blockTags = array_diff(array_merge(
            self::$tagTypes['header'],
            self::$tagTypes['block'],
            self::$tagTypes['list'],
            self::$tagTypes['table']
        ), $selfClosingBlockTags); // handle <br> separately below because it is self-closing
        
        if (count($tagWhitelist)) {
            // Leave whitelisted tags alone
            $blockTags = array_diff($blockTags, $tagWhitelist);
            $selfClosingBlockTags = array_diff($selfClosingBlockTags, $tagWhitelist);
        }
        
        // add spaces after block close tags which will be removed
        $string = preg_replace(';\s*(<\s*/\s*('.
            self::regexForDelimiters($blockTags).')\s*>)\s*;'.self::$reOpts, '\1 ', $string);
        
        if (count($selfClosingBlockTags)) {
            // add spaces after self-closing block tags <br> if not in the whitelist
            $string = preg_replace(';\s*(<\s*('.
                self::regexForDelimiters($selfClosingBlockTags).')\s*/?\s*>)\s*;'.self::$reOpts, '\1 ', $string);
        }

        return trim(strip_tags($string, implode('', $tagWhitelist)));
    }

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
            $string = self::stripTags($string, $tagWhitelist);
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
    
    //
    // Function to remove HTML tags and convert HTML entities to UTF-8
    // Safe to call on strings which already contain UTF-8
    //
    public static function htmlStripTags2UTF8($string) {
        $string = self::stripTags($string);
        
        // There are lots of html entity names which are not supported
        // by html_entity_decode so we convert them to entity numbers first:
        $entityNames = array_keys(self::$entityNameToNumber);
        $entityNumbers = array_values(self::$entityNameToNumber);
        $string = str_replace($entityNames, $entityNumbers, $string);
        
        // Do not use mb_convert_encoding because string may already contain UTF-8
        return html_entity_decode($string, ENT_QUOTES, 'UTF-8');
    }
}
