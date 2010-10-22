<?php

/* this file assumes env $WSETCDIR is already defined */

require_once "System/Daemon.php";
require_once dirname(__FILE__) . "/../../config/mobi_web_constants.php";

class DaemonWrapper {
  private $appName = "";

  function __construct($appName) {
    $this->appName = $appName;
  }

  function start($argv) { 
    $appName = $this->appName;

    if(file_exists($this->command_file())) {
      unlink($this->command_file());
    }

    if(array_search("--background", $argv)) {
      System_Daemon::setOption("appName", $appName);
      System_Daemon::setOption("appRunAsUID", posix_getuid());
      System_Daemon::setOption("appRunAsGID", posix_getgid());
      System_Daemon::setOption("logLocation", getenv('WSETCDIR') . "/logs/$appName");
      System_Daemon::setOption("appPidLocation", getenv('WSETCDIR') . "/pushd/$appName/$appName.pid");

      System_Daemon::setOption('logPhpErrors', true);
      System_Daemon::setOption('logFilePosition', true);
      System_Daemon::setOption('logLinePosition', true);

      System_Daemon::start();
    }
  }

  function stop() {
    if(file_exists($this->command_file())) {
      unlink($this->command_file());
    }
    System_Daemon::stop();
  }

  function sleep($seconds) {
    if(System_Daemon::isInBackground()) {
      // always run one loop;
      if(!$this->oneLoop(False)) {
        return false;
      }

      for($i < 0; $i < $seconds; $i++) {
        if(!$this->oneLoop()) {
          return false;
        }
      }

    } else {
      if($seconds) {
        echo "Beginning process sleep for $seconds seconds\n";
        sleep($seconds);
      }
    }
    
    return true;
  }

  function command_file() {
    return getenv('WSETCDIR') . "/pushd/{$this->appName}/command.txt";
  }

  function oneLoop($pause=True) {
    if(file_exists($this->command_file())) {
      $text = file_get_contents($this->command_file());
      $command_lines = split("\n", $text);
      $command = $command_lines[0];
      if($command == "stop") {
        return False;
      }
    }
    
    $delay = $pause ? 1 : 0;
    System_Daemon::iterate($delay);
    return True;
  }    

}

function d_echo($string, $foreground_only=False) {
  if(!$foreground_only || !System_Daemon::isInBackground()) {
    System_Daemon::log(System_Daemon::LOG_INFO, $string);
  }

  if(!System_Daemon::isInBackground()) {
    echo "$string\n";
  }
}

function d_error($msg) {
  if (System_Daemon::isInBackground()) {
    System_Daemon::log(System_Daemon::LOG_ERR, $msg);
  } else {
    echo "$msg\n";
  }
}

?>
