<?php
if ($currentpage == 'login') {
  //check if https
  if(session_status()!=2) { session_start(); }
  if(isset($_SERVER["HTTPS"]) == false) {
    header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
    die();
  }
}else {
  if(!isset($_SESSION)) { session_start(); }
  if(!isset($_SESSION['login'])) {
    header("Location: ./login.php");
    die();
  } else {
    if(isset($_SERVER["HTTPS"]) == false) {
      header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
      die();
    }
  }

  if(!isset($base_dir)) { die(); }
  if(isset($_REQUEST['d'])) {
    $server_select=$_REQUEST['d'];
  }
  else {
    $server_select="server1";
  }
  
  if(isset($_SESSION['login']['cmd_history'][$server_select])) {
    $session['login']['cmd_history'][$server_select] = $_SESSION['login']['cmd_history'][$server_select];
  }
  if(isset($_SESSION['login']['level'])) { $user_level = $_SESSION['login']['level']; }  else { $user_level = "viewonly"; }
  if(isset($_SESSION['login']['user'])) { $user_name = $_SESSION['login']['user']; }  else { $user_name = "guest"; }
  if(isset($_SESSION['login']['reload_report'])) {
    $session['login']['reload_report'] = $_SESSION['login']['reload_report'];
    unset($_SESSION['login']['reload_report']);
  }
  session_write_close();
}

if ($currentpage == 'logs') {
  if($user_level=="viewonly") {
    die('Not allowed for view only');
  }
}

if(!isset($_SESSION)) { session_start(); }
if(!isset($_SESSION['login'])) {
  die('Please sign in');
} else {
  if(isset($_SERVER["HTTPS"]) == false)
  {
    die('Must use HTTPS');
  }
}


?>
