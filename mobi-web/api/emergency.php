<?

if (isset($_REQUEST['command']) && $_REQUEST['command'] == 'contacts') {
  $data = json_decode(file_get_contents(LIBDIR . "EmergencyContacts.json"));
} else {
  require LIBDIR . "rss_services.php";
  $Emergency = new Emergency();
  $data = $Emergency->get_feed_html();
  if($data === False) {
    $data = array('Emergency information is currently not available');
  }
}

echo json_encode($data);

?>
