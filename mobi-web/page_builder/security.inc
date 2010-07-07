<?php

class AllowedUsers {
  public static $list = array(
    'alchow',
    'andrewyu',
    'blpatt',
    'sonya',
    'lxs',
    'jander',
    'lwatts',
    'huafi',
    'irishman',
    'taeminn'
  );
}
  

function ssl_required() {
  if(isset($_SERVER['HTTPS']) & $_SERVER['HTTPS'] == 'on') {
    $expire_time = strtotime($_SERVER["SSL_CLIENT_V_END"]);
    setcookie("mitcertificate", "yes", $expire_time, "/");
    return;
  } else {
    header("Location: https://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}");
    die(0);
  }
}

function users_restricted() {
  ssl_required();
  if( array_search(get_username(), AllowedUsers::$list) === False ) {
    // user not allowed to view this page
    // redirect to another page saying as much
    header('Location: ../error-page/?code=forbidden');
    die(0);
  }  
}

function get_username() {
  $email = $_SERVER["SSL_CLIENT_S_DN_Email"];
  $at_pos = strpos($email, '@');
  return substr($email, 0, $at_pos);
}

function get_fullname() {
  return $_SERVER["SSL_CLIENT_S_DN_CN"];
}

?>
