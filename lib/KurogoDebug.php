<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class KurogoDebug
{
    private static function compactTrace()
    {
        $bt = array_reverse(debug_backtrace());
        array_pop($bt);
        
        $return = array();
        foreach ($bt as $step=>$trace) {
            $file = isset($trace['file']) ? $trace['file'] : 'Unknown';
            if (substr($file, 0, strlen(ROOT_DIR))==ROOT_DIR) {
                $file = substr($file, strlen(ROOT_DIR)+1);
            }
            $line = isset($trace['line']) ? $trace['line'] : 'Unknown';
            $class = isset($trace['class']) ?''.   $trace['class'] . '::': '';
            $function = isset($trace['function']) ? "($class" . $trace['function'] . ")" : '';
            $return[]= "$step. $file @ $line $function";
        }
        
        return $return;
    }

    public static function debug($val='', $halt=false, $trace=true)
    {
        $plain = false;
        if ($halt && !headers_sent()) {
            $plain = true;
            header('Content-type: text/plain; charset=' . Kurogo::getCharset());
        }
       
        $line_break = (PHP_SAPI == 'cli' || $plain) ? PHP_EOL : (PHP_EOL . "<br />" . PHP_EOL);
         
        $output = '';
        if ($trace) {
            $output .= implode($line_break, self::compactTrace(debug_backtrace())) . $line_break . $line_break;
        }
       
        if ($val || $val===0) {
            if (PHP_SAPI !='cli' && !is_scalar($val) && ini_get('html_errors')!='off') {
                if (!$halt) { $output .= "<pre>" . PHP_EOL; }
                if (is_array($val)) {
                    $output .= count($val) . ": ";
                }
                $output .= print_r($val, true);
                if (!$halt) {
                    $output .= PHP_EOL . "</pre>";
                }
            } else {
                if (is_array($val)) {
                    $output .= count($val) . ": ";
                }
    
                $output .= print_r($val, true);
            }
            $output .= $line_break;
        } else {
            $output .= "(empty " . gettype($val) . ")";
            $output .= $line_break;
        }
                        
        print $output;
        if ($halt) {
            die();
        }
    }
    
}
