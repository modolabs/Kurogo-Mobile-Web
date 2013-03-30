<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

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
