<?php
require($_SERVER['DOCUMENT_ROOT']."/inc/config/config.php");
function url($path) {
  echo $_SERVER ['REQUEST_SCHEME'];
  echo "://";
  echo $_SERVER ['SERVER_NAME'];
  if ($_SERVER['SERVER_PORT'] == 80) {
    ;
  } elseif ($_SERVER['SERVER_PORT'] == 443) {
    ;
  } else {
    echo ":";
    echo $_SERVER['SERVER_PORT'];
  }
  echo "/";
  if (!empty($GLOBALS["folder"])) {
    echo _GLOBALS["folder"];
    echo "/";
  } else {
    ;
  }
  echo $path;
};

function themepath() {
  url($GLOBALS["themepath"]);
};

?>
