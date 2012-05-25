<?php

class DrupalCCKItem {

   protected $rssItem;
   
   protected $body;
 
   protected $cckFields;

   public function __construct($rssItem, $body, array $cckFields) {
     $this->rssItem = $rssItem;
     $this->body = $body;
     $this->cckFields = $cckFields;
   }

   public function getRssItem() {
     return $this->rssItem;
   }

   public function getBody() {
     return $this->body;
   }

   public function getCCKField($field) {
     return isset($this->cckFields[$field]) ? $this->cckFields[$field] : null;
   }

}  