<?
$device_names = Array(
  'iphone' => 'iPhone',
  'android' => 'Android',
  'webos' => 'webOS',
  'winmo' => 'Windows Mobile',
  'bbplus' => "BlackBerry",
  'blackberry' => 'BlackBerry',
  'symbian' => 'Symbian',
  'palmos' => 'Palm OS',
  'featurephone' => 'Feature Phone',
  'computer' => 'Computer',
  'spider' => 'Robot',
  );

$device_apps = Array(
  'bbplus' => 'media/HarvardBBLauncher1.jad',
  'blackberry' => 'media/HarvardBBLauncher1.jad',
  );

$device_instructions = Array(
  'bbplus' => 'On the next screen, click "Download", and "OK" or "Run" once the download is complete. Once you\'ve installed the shortcut, it will appear on your BlackBerry home screen or in the Downloads folder.',
  'blackberry' => 'On the next screen, click "Download", and "OK" or "Run" once the download is complete. Once you\'ve installed the shortcut, it will appear on your BlackBerry home screen or in the Downloads folder.',
  );

$download_items = Array(
  'bbplus' => 'shortcut',
  'blackberry' => 'shortcut',
);

$device_name = $device_names[$page->platform];

if (array_key_exists($page->platform, $device_apps)) {

  $download_url = $device_apps[$page->platform];
  $instructions = $device_instructions[$page->platform];
  $download_item = $download_items[$page->platform];
  require "$page->branch/index.html";
  $page->cache();

} else {

  $page->prepare_error_page('Download', 'download', 'Sorry, we do not have downloads for ' . $device_name . ' devices yet');
}

$page->output();

?>
