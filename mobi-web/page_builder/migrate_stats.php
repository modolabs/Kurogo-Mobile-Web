<?php


require_once "../config/mobi_web_constants.php";
require_once LIBDIR . "db.php";

$old_sql = file_get_contents("page_views.SQL");
$sql_fp = fopen("migrate_page_views_1.SQL", "w");

fwrite($sql_fp, "DROP TABLE PageViews;\n");
fwrite($sql_fp, $old_sql);

$db = db::$connection;
$result = $db->query("SELECT * FROM PageViews");
while($row = $result->fetch_assoc()) {
  foreach($row as $field => $value) {
    if($field != 'day') {
      fwrite($sql_fp, "INSERT INTO PageViews (day, name, count) VALUES ('{$row['day']}', '$field', $value);\n");
    }
  } 
}

fclose($sql_fp);

?>
