<?php

define('POLYLINE_VERY_SMALL', 0.00001);
  
function distance($p0, $p1, $p2) {
  if($p1[0] == $p2[0] && $p1[1] == $p2[1]) {
    $out = sqrt(pow($p2[0]-$p0[0],2) + pow($p2[1]-$p0[1],2));
  } else {
    $u = (($p0[0]-$p1[0])*($p2[0]-$p1[0]) + ($p0[1]-$p1[1]) * ($p2[1]-$p1[1])) / (pow($p2[0]-$p1[0],2) + pow($p2[1]-$p1[1],2));
    if($u <= 0) {
      $out = sqrt(pow($p0[0] - $p1[0],2) + pow($p0[1] - $p1[1],2));
    }
    if($u >= 1) {
      $out = sqrt(pow($p0[0] - $p2[0],2) + pow($p0[1] - $p2[1],2));
    }
    if(0 < $u && $u < 1) {
      $out = sqrt(pow($p0[0]-$p1[0]-$u*($p2[0]-$p1[0]),2) + pow($p0[1]-$p1[1]-$u*($p2[1]-$p1[1]),2));
    }
  }
  return $out;
}

function encodeSignedNumber($num) {
  $sgn_num = $num << 1;
  if ($num < 0) {
    $sgn_num = ~($sgn_num);
  }
  $encodeString = '';
  while($sgn_num >= 0x20) {
    $nextValue = (0x20 | ($sgn_num & 0x1f)) + 63;
    $encodeString .= chr($nextValue);
    $sgn_num >>= 5;
  }
  $finalValue = $sgn_num + 63;
  $encodeString .= chr($finalValue);
  return $encodeString;
}

function encodePolylineFromArray($points) {

  if(count($points) > 2) {
    $stack[] = array(0, count($points)-1);
    while(count($stack) > 0) {
      $current = array_pop($stack);
      $maxDist = 0;
      for($i = $current[0]+1; $i < $current[1]; $i++) {
        $temp = distance($points[$i], $points[$current[0]], $points[$current[1]]);
        if($temp > $maxDist) {
          $maxDist = $temp;
          $maxLoc = $i;
        }
      }
      if($maxDist > POLYLINE_VERY_SMALL) {
        $dists[$maxLoc] = $maxDist;
        array_push($stack, array($current[0], $maxLoc));
        array_push($stack, array($maxLoc, $current[1]));
      }
    }
  }
  
  $encodedPoints = '';
  $plat = 0;
  $plng = 0;
  for($i=0; $i<count($points); $i++) {
    if(isset($dists[$i]) || $i == 0 || $i == count($points)-1) {
      $point = $points[$i];
      $lat = $point[0];
      $lng = $point[1];
      $late5 = floor($lat * 1e5);
      $lnge5 = floor($lng * 1e5);
      $dlat = $late5 - $plat;
      $dlng = $lnge5 - $plng;
      $plat = $late5;
      $plng = $lnge5;
      $encodedPoints .= encodeSignedNumber($dlat) . encodeSignedNumber($dlng);
    }
  }
  
  return $encodedPoints;
}
