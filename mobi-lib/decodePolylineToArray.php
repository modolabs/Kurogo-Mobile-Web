<?php
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
function decodePolylineToArray($encoded)
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
