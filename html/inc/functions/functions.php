<?php
require("./inc/config/config.php");
function url($path, $return) {
  if ($return != true) {
    $return = '';
  }

  if (isset($_SERVER['HTTPS'])) {
        $scheme = $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
  } elseif ($_SERVER['REQUEST_SCHEME']) {
        $scheme = $_SERVER['REQUEST_SCHEME'];
  } else {
        $scheme = 'http';
  }
  $scheme .= "://".$_SERVER ['SERVER_NAME'];
  if ($_SERVER['SERVER_PORT'] == 80) {
    $port = "";
  } elseif ($_SERVER['SERVER_PORT'] == 443) {
    $port = "";
  } else {
    $port = ":".$_SERVER['SERVER_PORT'];
  }
  $slash = "/";
  if (!empty($GLOBALS["folder"])) {
    $folder = $GLOBALS["folder"];
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

// function to print files size in human-readable form
function human_filesize($file, $decimals = 2) {
  $bytes = filesize($file);
  $sz = 'BKMGTP';
  $factor = floor((strlen($bytes) - 1) / 3);
  return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
}

function dropdown(){
  $server_select = $GLOBALS['server_select'];
  $base_dir = $GLOBALS['base_dir'];
  if ($GLOBALS['currentpage'] == 'index') {
    $url = url("index.php?d=", true);
  } elseif ($GLOBALS['currentpage'] == 'logs') {
    $url = url("logs.php?d=", true);
  } elseif ($GLOBALS['currentpage'] == 'server-settings') {
    $url = url("server-settings.php?d=", true);
  }
  foreach(glob("$base_dir*", GLOB_ONLYDIR) as $dir) {
    $dir = str_replace($base_dir, '', $dir);
    if($dir!="node_modules"&&$dir!="logs") {
      if($server_select=="$dir") {
        echo "<a class='dropdown-item active' href='$url$server_select'>$server_select</a>";
      } else {
        echo "<a class='dropdown-item' href='$url$dir'>$dir</a>";
      }
    }
  }
}

function navbar(){
  $server_select = $GLOBALS['server_select'];
  $base_dir = $GLOBALS['base_dir'];
  if ($GLOBALS['currentpage'] == 'server-settings') {
    $url = url("server-settings.php?d=", true);
  } elseif ($GLOBALS['currentpage'] == 'logs') {
    $url = url("logs.php?d=", true);
    if ($_GET['d'] == 'managepgm') {
      echo "<li class='nav-item'>
      <a class='nav-link active' href='$url$server_select'>$server_select</a>
      </li>";
    } else {
      $urlpgm = url("logs.php?d=managepgm", true);
      echo "<li class='nav-item'>
      <a class='nav-link' href='$urlpgm'>managepgm</a>
      </li>";
    }
  }

  foreach(glob("$base_dir*", GLOB_ONLYDIR) as $dir) {
    $dir = str_replace($base_dir, '', $dir);
    if($dir!="node_modules"&&$dir!="logs") {
      if($server_select=="$dir") {
        echo "<li class='nav-item'>
        <a class='nav-link active' href='$url$server_select'>$server_select</a>
        </li>";
      } else {
        echo "<li class='nav-item'>
        <a class='nav-link' href='$url$dir'>$dir</a>
        </li>";
      }
    }
  }
}

function servername(){
  $server_select = $GLOBALS['server_select'];
  $base_dir = $GLOBALS['base_dir'];
  if ($server_select == 'managepgm') {
    echo "Managepgm";
  } else {
    if(file_exists("$base_dir$server_select/server-settings.json")) {
      $server_settings = json_decode(file_get_contents("$base_dir$server_select/server-settings.json"), true);
      if($server_settings != NULL) {
        if(isset($server_settings["name"])) {
          if($GLOBALS['user_level'] == "viewonly") {
            echo "Viewonly mode";
          } else {
            $server_select = $GLOBALS['server_select'];
            $base_dir = $GLOBALS['base_dir'];
            $server_name = htmlspecialchars($server_settings["name"]);
            $length = strlen($server_name);
            $name = substr($server_name, -$length, 50);
            echo $name;
          }
        } else {
          echo "#ERROR Name not configured";
        }
      } else {
        echo "#ERROR: WITH server-settings.json#";
      }
    } else{
      echo "#ERROR: server-settings.json NOT FOUND#";
    }
  }
}



?>
