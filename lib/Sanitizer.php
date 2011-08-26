<?php
/**
  * @package Core
  */

/**
  * @package Core
  */
class Sanitizer
{
    static protected $tagTypes = array(
        // Groups of tags by type which can be combined by separating with '|' (eg: 'inline|block')
        'inline' => array('<b>', '<em>', '<strong>', '<span>', '<code>'),
        'block'  => array('<div>', '<blockquote>', '<p>', '<hr>', '<br>', '<pre>'),
        'link'   => array('<a>'),
        'media'  => array('<img>', '<video>', '<audio>'),
        'list'   => array('<ol>', '<ul>', '<li>'),
        'table'  => array('<table>', '<thead>', '<tbody>', '<tr>', '<th>', '<td>'),
        
        // Groups of tags by use case
        'editor' => array('<b>', '<em>', '<strong>', '<span>', '<div>', '<blockquote>', '<p>', '<hr>', '<a>', '<img>', '<video>', '<audio>', '<ol>', '<ul>', '<li>', '<table>', '<thead>', '<tbody>', '<tr>', '<th>', '<td>'),
    );
    
    //
    // Filter to attempt to remove XSS injection attacks without removing HTML
    //
    public static function sanitizeHTML($string, $allowedTags='editor') {
        $useTagWhitelist = true;
        $tagWhitelist = array();
        
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
        $strippedString = $useTagWhitelist ? strip_tags($string, implode('', array_unique($tagWhitelist))) : $string;
        
        return preg_replace_callback('/<(.*?)>/i', array(get_class(), 'tagPregReplaceCallback'), $strippedString);
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
                '/=\s*"\s*javascript:[^"]*"/i',                           // double-quoted attr with value containing js
                '/=\s*\'\s*javascript:[^\']*\'/i',                        // single-quoted attr with value containing js
                '/=\s*javascript:[^\s]*/i',                               // quoteless attr with value containing js
                '/('.$anyJSAttr.')\s*=\s*(["][^"]*["]|[\'][^\']*[\'])/i', // attr that triggers js
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
        return preg_replace('/javascript:.*/i', '', strip_tags($string));
    }
}
