<?php

class Debug
{
    function line_break()
    {
        return PHP_SAPI == 'cli' || ini_get('html_errors')=='off' ? PHP_EOL : PHP_EOL . "<br />" . PHP_EOL;
    }

    function sprint_r_html($var)
    {
        $output = "<pre>\n";
        foreach (func_get_args() as $arg) {
            if (is_array($arg)) {
                $output .= count($arg) . ": ";
            }
            $output .= print_r($arg, true);
        }
        $output .= "\n</pre>";
        return $output;
    }
    
    function compact_backtrace()
    {
        $bt = debug_backtrace();
        array_shift($bt);
        
        $return = array();
        foreach ($bt as $step=>$trace) {
            $file = isset($trace['file']) ? $trace['file'] : 'Unknown';
            $line = isset($trace['line']) ? $trace['line'] : 'Unknown';
            $class = isset($trace['class']) ?''.   $trace['class'] . '::': '';
            $function = isset($trace['function']) ? "($class" . $trace['function'] . ")" : '';
            $return[]= "$file @ $line $function";
        }
        
        return $return;
    }
    
    function plain_text()
    {
        ini_set('html_errors', 'off');
        header('Content-type: text/plain');
    }
    
    function hex_dump($string)
    {
        $output = array();
        for($i=0; $i<strlen($string); $i++) {
            $output[] = dechex(ord($string[$i]));
        }
        
        echo implode(" ", $output);
    }

    function die_here($string='', $plain=true)
    {
        if ($plain) {
            Debug::plain_text();
        }
        $output = Debug::wp($string, false, 1);
        if (!ini_get('display_errors')) {
            trigger_error("Died: $output", E_USER_ERROR);
        }
        print($output);
        die();
    }
    
    function stop($string='Stopped')
    {
        trigger_error($string, E_USER_ERROR);
    }

    function sprint_date($date)
    {
        if (empty($date)) {
            return '';
        }
        
        return date('m/d/Y H:i:s U', $date);
    }

    function print_r_html($var) {
        print "<pre>\n";
        foreach (func_get_args() as $arg) {
            if (is_array($arg)) {
                echo count($arg) . ": ";
            }
            print_r($arg);
        }
        print "\n</pre>";
    }

    function wp($string=null, $print = true, $trace_idx=0)
    {
        $compact_backtrace = Debug::compact_backtrace();
        
        for($i=0; $i<$trace_idx; $i++) {
            if (isset($compact_backtrace[$i])) {
                unset($compact_backtrace[$i]);
            }
        }
        
        $output = Debug::line_break() . implode(Debug::line_break(), $compact_backtrace) . Debug::line_break();
       
        if ($string || $string===0) {
            if (PHP_SAPI !='cli' && !is_scalar($string) && ini_get('html_errors')!='off') {
                $output .= Debug::sprint_r_html($string);
            } else {
                if (is_array($string)) {
                    $output .= count($string) . ": ";
                }
    
                $output .= print_r($string, true);
            }
            $output .= Debug::line_break();
        } elseif (count(func_get_args())>0) {
            $output .= "(empty " . gettype($string) . ")";
            $output .= Debug::line_break();
        } else {
        }
        
        if ($print) {
            trigger_error("Watched: $output", E_USER_NOTICE);
            if (!ini_get('display_errors')) {
                print $output;
            }
    
        } 
        
        return $output;
    }
}
