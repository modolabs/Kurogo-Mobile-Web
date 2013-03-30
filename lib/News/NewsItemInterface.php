<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

interface NewsItemInterface extends KurogoObject
{
    public function init($args);
    public function getTitle();
    public function getAuthor();
    public function getDescription();
    public function getImage();
    public function getThumbnail();
    public function getGUID();
    public function getLink();
    public function getContent();
    public function getPubDate();
    public function getPubTimestamp();
    
}
