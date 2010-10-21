#!/usr/bin/php
<?

/**** mirror images on maps.mit.edu
 *
 * no args: download tiles only if their image changed
 * --force: download regardless of whether their image changed
 */

define("MAP_SEARCH_URL", 'http://whereis.mit.edu/search');
define('MAP_SERVER_URL', 'http://maps.mit.edu/ArcGIS/rest/services/Mobile/WhereIs_MobileAll/MapServer');
define("MAP_TILE_CACHE_DIR", '/var/local/maptiles/tile2/');
define("MAP_TILE_CHECKSUM_FILE", '/var/local/maptiles/export.md5');
define("TILES_LAST_UPDATED_FILE", '/var/local/maptiles/tiles_last_updated.txt');
define("MAP_SERVICE_JSON_CACHE", '/var/local/maptiles/service.json');

echo "retrieving service capabilities\n";

$serviceUrl = MAP_SERVER_URL . '?f=json';
$service = json_decode(file_get_contents($serviceUrl), TRUE);
$extent = $service['fullExtent'];
if (!$extent) {
  echo "problem getting json contents\n";
  exit(1);
}

$fh = fopen(MAP_SERVICE_JSON_CACHE, 'w');
fwrite($fh, json_encode($service));
fclose($fh);

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
if ($new_md5 != $md5 || $argv[1] == '--force') {

  $fh = fopen(MAP_TILE_CHECKSUM_FILE, 'w');
  fwrite($fh, $new_md5);
  fclose($fh);

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
    $res = $lod['resolution'];
    $tile_geo_width = $res * $tile_width;
    $tile_geo_height = $res * $tile_height;

    $start_tile_y = intval($start_y / $tile_geo_height);
    $end_tile_y = intval($end_y / $tile_geo_height);
    $start_tile_x = intval($start_x / $tile_geo_width);
    $end_tile_x = intval($end_x / $tile_geo_width);
    if ($end_tile_y - $end_tile_x < 2) {
      echo "skipping level $level -- too few tiles\n";
      continue;
    }

    $levelDir = MAP_TILE_CACHE_DIR . $level;
    if (!file_exists($levelDir)) {
      if (!mkdir($levelDir)) {
	echo "failed to create $levelDir\n";
	exit(1);
      }
    }

    echo "fetching level $level...\n";

    for ($y = $start_tile_y; $y <= $end_tile_y; $y++) {
      $lonDir = $levelDir . '/' . $y; 
      if (!file_exists($lonDir)) {
	if (!mkdir($lonDir)) {
	  echo "unable to create directory $lonDir\n";
	  continue;
	}
      }

      for ($x = $start_tile_x; $x <= $end_tile_x; $x++) {
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

  $fh = fopen(TILES_LAST_UPDATED_FILE, 'w');
  fwrite($fh, mktime());
  fclose($fh);
}

exit(0);

?>
