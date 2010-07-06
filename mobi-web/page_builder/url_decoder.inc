<?php

function expand_id($compressed_id) {
  $decimal_id = 0;

  for($i = 0; $i < strlen($compressed_id); $i++) {
    $decimal_id = $decimal_id * 62 + char_to_number(substr($compressed_id, $i, 1));
  }
  
  return $decimal_id;
}  

function char_to_number($letter) {
  if( (ord('a') <= ord($letter)) && (ord($letter) <= ord('z')) ) {
    return ord($letter)-ord('a');
  }

  if( (ord('A') <= ord($letter)) && (ord($letter) <= ord('Z')) ) {
    return ord($letter)-ord('A') + 26;
  }

  if( (ord('0') <= ord($letter)) && (ord($letter) <= ord('9')) ) {
    return ord($letter)-ord('0') + 2*26;
  }
}

?>
