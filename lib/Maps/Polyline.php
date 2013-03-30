<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

define('POLYLINE_VERY_SMALL', 0.00001);

class Polyline {

  static private function distance($p0, $p1, $p2) {
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
  
  static private function encodeSignedNumber($num) {
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
  
  static public function encodeFromArray($points) {
  
    if(count($points) > 2) {
      $stack[] = array(0, count($points)-1);
      while(count($stack) > 0) {
        $current = array_pop($stack);
        $maxDist = 0;
        for($i = $current[0]+1; $i < $current[1]; $i++) {
          $temp = self::distance($points[$i], $points[$current[0]], $points[$current[1]]);
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
        // modified to support geometries passed as associative arrays
        $lat = isset($point['lat']) ? $point['lat'] : $point[0];
        $lng = isset($point['lon']) ? $point['lon'] : $point[1];
        $late5 = floor($lat * 1e5);
        $lnge5 = floor($lng * 1e5);
        $dlat = $late5 - $plat;
        $dlng = $lnge5 - $plng;
        $plat = $late5;
        $plng = $lnge5;
        $encodedPoints .= self::encodeSignedNumber($dlat) . self::encodeSignedNumber($dlng);
      }
    }
    
    return $encodedPoints;
  }


  // The following copyright and license applies to decodeToArray function 
  // below. Code is unmodified except for the addition of "static public" to 
  // the function and a rename from decodePolylineToArray to decodeToArray.
  
  /*
   * Copyright (c) 2008 Peter Chng, http://unitstep.net/
   * 
   * Permission is hereby granted, free of charge, to any person obtaining
   * a copy of this software and associated documentation files (the
   * "Software"), to deal in the Software without restriction, including
   * without limitation the rights to use, copy, modify, merge, publish,
   * distribute, sublicense, and/or sell copies of the Software, and to
   * permit persons to whom the Software is furnished to do so, subject to
   * the following conditions:
   * 
   * The above copyright notice and this permission notice shall be
   * included in all copies or substantial portions of the Software.
   * 
   * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
   * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
   * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
   * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
   * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
   * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
   * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
   */
  
  /**
   * Decodes a polyline that was encoded using the Google Maps method.
   *
   * The encoding algorithm is detailed here:
   * http://code.google.com/apis/maps/documentation/polylinealgorithm.html
   *
   * This function is based off of Mark McClure's JavaScript polyline decoder
   * (http://facstaff.unca.edu/mcmcclur/GoogleMaps/EncodePolyline/decode.js)
   * which was in turn based off Google's own implementation.
   *
   * This function assumes a validly encoded polyline.  The behaviour of this
   * function is not specified when an invalid expression is supplied.
   *
   * @param String $encoded the encoded polyline.
   * @return Array an Nx2 array with the first element of each entry containing
   *  the latitude and the second containing the longitude of the
   *  corresponding point.
   */
   
  static public function decodeToArray($encoded)
  {
    $length = strlen($encoded);
    $index = 0;
    $points = array();
    $lat = 0;
    $lng = 0;
  
    while ($index < $length)
    {
      // Temporary variable to hold each ASCII byte.
      $b = 0;
  
      // The encoded polyline consists of a latitude value followed by a
      // longitude value.  They should always come in pairs.  Read the
      // latitude value first.
      $shift = 0;
      $result = 0;
      do
      {
        // The `ord(substr($encoded, $index++))` statement returns the ASCII
        //  code for the character at $index.  Subtract 63 to get the original
        // value. (63 was added to ensure proper ASCII characters are displayed
        // in the encoded polyline string, which is `human` readable)
        $b = ord(substr($encoded, $index++)) - 63;
  
        // AND the bits of the byte with 0x1f to get the original 5-bit `chunk.
        // Then left shift the bits by the required amount, which increases
        // by 5 bits each time.
        // OR the value into $results, which sums up the individual 5-bit chunks
        // into the original value.  Since the 5-bit chunks were reversed in
        // order during encoding, reading them in this way ensures proper
        // summation.
        $result |= ($b & 0x1f) << $shift;
        $shift += 5;
      }
      // Continue while the read byte is >= 0x20 since the last `chunk`
      // was not OR'd with 0x20 during the conversion process. (Signals the end)
      while ($b >= 0x20);
  
      // Check if negative, and convert. (All negative values have the last bit
      // set)
      $dlat = (($result & 1) ? ~($result >> 1) : ($result >> 1));
  
      // Compute actual latitude since value is offset from previous value.
      $lat += $dlat;
  
      // The next values will correspond to the longitude for this point.
      $shift = 0;
      $result = 0;
      do
      {
        $b = ord(substr($encoded, $index++)) - 63;
        $result |= ($b & 0x1f) << $shift;
        $shift += 5;
      }
      while ($b >= 0x20);
  
      $dlng = (($result & 1) ? ~($result >> 1) : ($result >> 1));
      $lng += $dlng;
  
      // The actual latitude and longitude values were multiplied by
      // 1e5 before encoding so that they could be converted to a 32-bit
      // integer representation. (With a decimal accuracy of 5 places)
      // Convert back to original values.
      $points[] = array($lat * 1e-5, $lng * 1e-5);
    }
  
    return $points;
  }
}
