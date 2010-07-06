#!/usr/bin/php
<?

/**** mirror images on maps.mit.edu
 *
 * no args: download tiles only if their image changed
 * --force: download regardless of whether their image changed
 */

require_once('../mobi-config/lib_constants.inc');

echo "retrieving service capabilities\n";

$serviceUrl = MAP_SERVER_URL . '?f=json';
$service = json_decode(file_get_contents($serviceUrl), TRUE);
$extent = $service['fullExtent'];
if (!$extent) {
  echo "problem getting json contents\n";
  exit(1);
}

// generate checksum file by exporting a full map image
// export documentation: http://maps.mit.edu/arcgis/SDK/REST/export.html

$export_params = Array(
  'f' => 'image',
  'bbox' => join(',', Array($extent['xmin'],
			    $extent['ymin'],
			    $extent['xmax'],
			    $extent['ymax'])),
  'size' => '2048,2048',
  'dpi' => '', // default is 96
  'imageSR' => '', // use their spatial ref
  'bboxSR' => '', // use their spatial ref
  'format' => 'png24',
  'layers' => '', // not sure if default includes all layers
  'transparent' => 'false',
  );

$imageUrl = MAP_SERVER_URL . '/export?' . http_build_query($export_params);

echo "exporting image from $imageUrl\n";

$image = file_get_contents($imageUrl);

echo "checking for differences from cache\n";

$md5 = file_exists(MAP_TILE_CHECKSUM_FILE) ? file_get_contents(MAP_TILE_CHECKSUM_FILE) : FALSE;

if (!$image) {
  echo "failed to export image\n";
  exit(1);
} 


$new_md5 = md5($image);

// save the temporary checksum
$fh = fopen(MAP_TILE_CHECKSUM_FILE_TEMP, 'w');
fwrite($fh, $new_md5);
fclose($fh);
if ($new_md5 != $md5 || sizeof($argv) >= 2 && $argv[1] == '--force') {
  // figure out what all the tile filenames are and download

  $origin = $service['tileInfo']['origin'];
  $lods = $service['tileInfo']['lods'];
  $tile_width = $service['tileInfo']['cols'];
  $tile_height = $service['tileInfo']['rows'];

  $start_x = $extent['xmin'] - $origin['x'];
  $start_y = $origin['y'] - $extent['ymax'];
  $end_x = $extent['xmax'] - $origin['x'];
  $end_y = $origin['y'] - $extent['ymin'];

  echo "downloading tiles\n";

  foreach ($lods as $lod) {
    $level = $lod['level'];
    if ($level < 13)
      continue;
    $levelDir = MAP_TILE_CACHE_DIR . $level;
    if (!file_exists($levelDir)) {
      if (!mkdir($levelDir)) {
	echo "failed to create $levelDir\n";
	exit(1);
      }
    }

    echo "level $level...\n";

    $res = $lod['resolution'];
    $tile_geo_width = $res * $tile_width;
    $tile_geo_height = $res * $tile_height;
    for ($y = intval($start_y / $tile_geo_height); $y <= intval($end_y / $tile_geo_height); $y++) {
      $lonDir = $levelDir . '/' . $y; 
      if (!file_exists($lonDir)) {
	if (!mkdir($lonDir)) {
	  echo "unable to create directory $lonDir\n";
	  continue;
	}
      }

      for ($x = intval($start_x / $tile_geo_width); $x <= intval($end_x / $tile_geo_width); $x++) {
	$tileUrl = MAP_SERVER_URL . "/tile/$level/$y/$x";
	$tileFile = $lonDir . '/' . $x;
	$fh = fopen($tileFile, 'w');
	if ($contents = file_get_contents($tileUrl)) {
	  fwrite($fh, $contents);
	  echo "  created $tileFile\n";
	} else { // there's probably nothing else at this level
	  break;
	}
	fclose($fh);
      }
    }
  }
}

exit(0);

?>
