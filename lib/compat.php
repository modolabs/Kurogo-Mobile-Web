<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

/**
  * Compatibility Functions
  * @package Compatability
  */

require_once(LIB_DIR . '/Watchdog.php');

/**
  * Returns a mime type for a given extension
  */
function mime_type($filename) {
  // http://www.php.net/manual/en/function.mime-content-type.php#87856
  $mime_types = array(
  
    'txt'  => 'text/plain',
    'htm'  => 'text/html',
    'html' => 'text/html',
    'php'  => 'text/html',
    'css'  => 'text/css',
    'js'   => 'application/javascript',
    'json' => 'application/json',
    'xml'  => 'application/xml',
    'swf'  => 'application/x-shockwave-flash',
    'flv'  => 'video/x-flv',
    
    // images
    'png'  => 'image/png',
    'jpe'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'jpg'  => 'image/jpeg',
    'gif'  => 'image/gif',
    'bmp'  => 'image/bmp',
    'ico'  => 'image/vnd.microsoft.icon',
    'tiff' => 'image/tiff',
    'tif'  => 'image/tiff',
    'svg'  => 'image/svg+xml',
    'svgz' => 'image/svg+xml',
    
    // archives
    'zip' => 'application/zip',
    'rar' => 'application/x-rar-compressed',
    'exe' => 'application/x-msdownload',
    'msi' => 'application/x-msdownload',
    'cab' => 'application/vnd.ms-cab-compressed',
    
    // audio/video
    'mp3' => 'audio/mpeg',
    'qt'  => 'video/quicktime',
    'mov' => 'video/quicktime',
    
    // adobe
    'pdf' => 'application/pdf',
    'psd' => 'image/vnd.adobe.photoshop',
    'ai'  => 'application/postscript',
    'eps' => 'application/postscript',
    'ps'  => 'application/postscript',
    
    // ms office
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'rtf' => 'application/rtf',
    'xls' => 'application/vnd.ms-excel',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'ppt' => 'application/vnd.ms-powerpoint',
    'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'pptm' => 'application/vnd.ms-powerpoint.presentation.macroEnabled.12',
    
    // open office
    'odt' => 'application/vnd.oasis.opendocument.text',
    'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
    
    // blackberry
    'jad' => 'text/vnd.sun.j2me.app-descriptor',
    'cod' => 'application/vnd.rim.cod',
  );
  
  $filebits = explode('.', $filename);
  $ext = strtolower(array_pop($filebits));
  
  if (array_key_exists($ext, $mime_types)) {
    return $mime_types[$ext];
    
  } elseif (function_exists('finfo_open') && is_readable($filename)) {
    $finfo = finfo_open(FILEINFO_MIME);
    $mimetype = finfo_file($finfo, $filename);
    finfo_close($finfo);
    return $mimetype;
    
  } else {
    return 'application/octet-stream';
  }
}

function realpath_exists($path, $safe = true) {
    if ($safe) {
        return Watchdog::kurogoPath($path);
    } else {
        return realpath($path) && file_exists($path);
    }
}
