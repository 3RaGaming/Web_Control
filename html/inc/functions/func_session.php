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

  if(isset($_SESSION['login']['level'])) { $user_level = $_SESSION['login']['level']; }  else { $user_level = "viewonly"; }
  if(isset($_SESSION['login']['user'])) { $user_name = $_SESSION['login']['user']; }  else { $user_name = "guest"; }
  if(isset($_SESSION['login']['reload_report'])) {
    $session['login']['reload_report'] = $_SESSION['login']['reload_report'];
    unset($_SESSION['login']['reload_report']);
  }


  require($_SERVER['DOCUMENT_ROOT'].'/'.$folder.'getserver.php');
  if(!isset($server_select)) {
  	if(isset($_REQUEST['d'])&&$_REQUEST['d']=="Managepgm") {
  		$server_select = "server1";
  	} else {
  		die('Error in server selection index.php');
  	}
  }
  session_write_close();


}
?>
