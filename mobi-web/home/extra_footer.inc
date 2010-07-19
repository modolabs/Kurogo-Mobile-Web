<?php

$ua = $_SERVER['HTTP_USER_AGENT']; 
$guess = json_decode(file_get_contents($ini['MOBI_SERVICE_URL'] . '?user-agent=' . urlencode($ua)), TRUE);

?>
<p>Your user agent is "<?=$ua?>"<br />
You are classified as <?=$guess['pagetype']?>, <?=$guess['platform']?><br />
  You <? if (!$guess['supports_certificate']) { echo "don't"; } ?> support certs</p>
