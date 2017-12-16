<?php
require($_SERVER['DOCUMENT_ROOT']."/inc/config/config.php");
function url($path, $return) {
  if ($return == null) {
    $return = '';
  }
  $scheme = $_SERVER['REQUEST_SCHEME']."://".$_SERVER ['SERVER_NAME'];
  if ($_SERVER['SERVER_PORT'] == 80) {
    ;
  } elseif ($_SERVER['SERVER_PORT'] == 443) {
    ;
  } else {
    $port = ":".$_SERVER['SERVER_PORT'];
  }
  $slash = "/";
  if (!empty($GLOBALS["folder"])) {
    $folder = $GLOBALS["folder"]."/";
  } else {
    ;
  }
  if ($return == true) {
    return($scheme.$port.$slash.$folder.$path);
  } else {
    echo $scheme.$port.$slash.$folder.$path;
  }
};

function themepath() {
  url($GLOBALS["themepath"]);
};

?>
