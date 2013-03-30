<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

includePackage('Calendar');
class EventDataObject extends KurogoDataObject {

  protected $range;
  protected $start;
  protected $end;

  public function filterItem($filters) {
    return true;
  }
  
  public function setRange($range) {
    $this->range = $range;
  }
  
  public function getRange() {
    return $this->range;
  }
  
  public function setStart($start) {
    $this->start = $start;
  }
  
  public function getStart() {
    return $this->start;
  }
  
  public function setEnd($end) {
    $this->end = $end;
  }
  
  public function getEnd() {
    return $this->end;
  }
  
}