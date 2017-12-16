<?php
require($_SERVER['DOCUMENT_ROOT']."/inc/config/config.php");
function url($path, $return) {
  if ($return != true) {
    $return = '';
  }
  $scheme = $_SERVER['REQUEST_SCHEME']."://".$_SERVER ['SERVER_NAME'];
  if ($_SERVER['SERVER_PORT'] == 80) {
    $port = "";
  } elseif ($_SERVER['SERVER_PORT'] == 443) {
    $port = "";
  } else {
    $port = ":".$_SERVER['SERVER_PORT'];
  }
  $slash = "/";
  if (!empty($GLOBALS["folder"])) {
    $folder = $GLOBALS["folder"]."/";
  } else {
    $folder = "";
  }
  if ($return == true) {
    return($scheme.$port.$slash.$folder.$path);
  } else {
    echo $scheme.$port.$slash.$folder.$path;
  }
};

function themepath() {
  url($GLOBALS["themepath"], false);
};

?>
