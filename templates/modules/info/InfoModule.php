<?php
/**
  * @package Module
  * @subpackage Info
  */

/**
  */
require_once realpath(LIB_DIR.'/Module.php');

/**
  * @package Module
  * @subpackage Info
  */
class InfoModule extends Module {
  protected $id = 'info';
     
  protected function initializeForPage() {
    // Just a static page
  }
}
