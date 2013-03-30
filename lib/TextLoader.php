<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class TextLoader extends FileLoader
{
    protected static function subDirectory() {
        return 'text';
    }
    
    public static function precache($file, $contents) {
        return self::generateURL($file, $contents, self::subDirectory());
    }
}
