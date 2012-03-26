#!/usr/bin/php -q
<?php
require_once realpath(dirname(__FILE__).'/../Kurogo.php');

class KurogoShellDispatcher {

    protected $stdin; //standard input stream
    protected $stdout; //standard output stream
    protected $stderr; //standard error stream
    protected $params;
    protected $args = array();
    protected $initArgs = array();
    protected $shellCommand = '';
    protected $shellClass = null;
    
    public function getParams() {
        return $this->params;
    }
    
    public function getArgs() {
        return $this->args;
    }
    
    public function getShellCommand() {
        return $this->shellCommand;
    }
    
    /**
     * Constructor
     *
     * @param array $args the argv.
     */
 
    public function __construct($args = array()) {
        $this->initArgs = $args;
        $this->initConstants();
        $this->parseParams($args);
        $this->initEnvironment();
    }
    
    protected function initConstants() {
        define('KUROGO_SHELL', true);
        
        $Kurogo = Kurogo::sharedInstance();
        $Kurogo->initialize();

        if (function_exists('ini_set')) {
            ini_set('error_reporting', E_ALL & ~E_DEPRECATED);
            ini_set('html_errors', false);
			ini_set('implicit_flush', true);
			ini_set('max_execution_time', 0);
        }
    }
    
    protected function initEnvironment() {
        $this->stdin = fopen('php://stdin', 'r');
		$this->stdout = fopen('php://stdout', 'w');
		$this->stderr = fopen('php://stderr', 'w');
    }

    protected function parseParams($params) {
        $this->_parseParams($params);
        
    }
    
    protected function _parseParams($params) {
        $count = count($params);
        for ($i=0; $i < $count; $i++) {
            if (isset($params[$i])) {
                if (substr($params[$i], 0, 1) === '-') {
                    $key = substr($params[$i], 1);
                    $this->params[$key] = true;
                    unset($params[$i]);
                    if (isset($params[++$i])) {
                        if (substr($params[$i], 0, 1) !== '-') {
                            $this->params[$key] = str_replace('"', '', $params[$i]);
                            unset($params[$i]);
                        } else {
                            $i--;
                        }
                    }
                } else {
                    $this->args[] = $params[$i];
                    unset($params[$i]);
                }
            }
        }
    }
    
    /**
     * Prompts the user for input, and returns it.
     *
     * @param string $prompt Prompt text.
     * @param mixed $options Array or string of options.
     * @param string $default Default input value.
     * @return Either the default value, or the user provided input.
     */
    public function getInput($prompt, $options = null, $default = null) {
        if (is_array($options)) {
            $printOptions = '(' . implode('/', $options) . ')';
        } else {
            $printOptions = $options;
        }
        
        if (is_null($default)) {
			$this->stdout($prompt . " $printOptions \n" . '> ', false);
		} else {
			$this->stdout($prompt . " $printOptions \n" . "[$default] > ", false);
		}
		$result = fgets($this->stdin);

		if ($result === false) {
			exit;
		}
        $result = trim($result);

		if ($default != null && empty($result)) {
			return $default;
		}
		return $result;
    }
    
    public function stdout($string, $newLine = true) {
        if ($newLine) {
            fwrite($this->stdout, $string . "\n");
        } else {
            fwrite($this->stdout, $string);
        }
    }
    
    public function stderr($string) {
        fwrite($this->stderr, 'Error: '. $string);
    }
}

$dispatcher = new KurogoShellDispatcher($argv);

