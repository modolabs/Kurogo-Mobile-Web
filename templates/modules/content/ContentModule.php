<?php

abstract class ContentModule extends Module {
   protected $id = 'content';
  
  protected function initializeForPage() {
    switch ($this->page)
    {
    case 'index':
        break;
   }
  }
  
}
