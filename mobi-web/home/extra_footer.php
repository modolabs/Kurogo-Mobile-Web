<?

$ua = $_SERVER['HTTP_USER_AGENT']; 
$guess = json_decode(file_get_contents(MOBI_SERVICE_URL . '?ua=' . urlencode($ua) . '&action=classify'), TRUE);

?>
<p>Your user agent is "<?=$ua?>"<br />
You are classified as <?=$guess['pagetype']?>, <?=$guess['platform']?><br />
  You <? if (!$guess['certs']) { echo "don't"; } ?> support certs</p>
