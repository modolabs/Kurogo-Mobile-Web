<?
require LIBDIR . 'mit_ldap.php';
$raw_people = mit_search($_REQUEST['q']);
$people = Array();
foreach ($raw_people as $raw_person) {
  $person = Array();
  foreach ($raw_person as $attribute => $value) {
    if ($value) $person[$attribute] = $value;
  }
  $people[] = $person;
}

echo json_encode($people);

?>
