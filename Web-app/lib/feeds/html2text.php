<?php

/****************************************************************
 *
 *  Copyright 2010 The President and Fellows of Harvard College
 *  Copyright 2010 Modo Labs Inc.
 *
 *****************************************************************/

function HTML2TEXT($Document) {
$Rules = array ('@<script[^>]*?>.*?</script>@si', // Strip out javascript
                '@<[\/\!]*?[^<>]*?>@si',          // Strip out HTML tags
                '@([\r\n])[\s]+@',                // Strip out white space
                '@&(quot|#34);@i',                // Replace HTML entities
                '@&(amp|#38);@i',                 //   Ampersand &
                '@&(lt|#60);@i',                  //   Less Than <
                '@&(gt|#62);@i',                  //   Greater Than >
                '@&(nbsp|#160);@i',               //   Non Breaking Space
                '@&(iexcl|#161);@i',              //   Inverted Exclamation point
                '@&(cent|#162);@i',               //   Cent
                '@&(pound|#163);@i',              //   Pound
                '@&(copy|#169);@i',               //   Copyright
                '@&(reg|#174);@i',                //   Registered
                '@&#(d+);@e');                   // Evaluate as php
$Replace = array ('',
                  '',
                  '1',
                  '"',
                  '&',
                  '<',
                  '>',
                  ' ',
                  chr(161),
                  chr(162),
                  chr(163),
                  chr(169),
                  chr(174),
                  'chr()');

$ent = htmlentities($Document);
$decoded = html_entity_decode($ent);
$further_decoded = htmlspecialchars_decode($decoded);
  return preg_replace($Rules, $Replace, $further_decoded);
}