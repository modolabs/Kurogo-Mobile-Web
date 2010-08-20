<?php

header("Location: ../error-page/?code=notfound");

if(isset($_REQUEST['i'])) {
  header("Location: ../courses/detail.php?id={$_REQUEST['i']}");
}

if(isset($_REQUEST['c'])) {
  $time = time();
  header("Location: ../calendar/day.php?time=$time&type=Events");
}

if(isset($_REQUEST['e'])) {
  if ($_REQUEST['e'] == 'e') {
    header("Location: ../emergency");
  } 
  if ($_REQUEST['e'] == '3') {
    header("Location: ../3down");
  } 
}


?>
