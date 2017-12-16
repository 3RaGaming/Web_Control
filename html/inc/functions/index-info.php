<script>
var server_select = "<?php if(isset($server_select)) { echo $server_select; }  else { echo "error"; } ?>";
//you can try to change this if you really want. Validations are also done server side.
//This is just for a better graphical experience, ie: if you're a viewonly account, why upload a file, just to be told you can't do that?
<?php
echo "\t\tvar user_level = \"$user_level\";\xA";
echo "\t\tvar user_name = \"$user_name\";\xA";
//his_array = ["/players", "/c print(\"hello\")"];
//Things to only start doing after the page has finished loading
echo "\t\t$(document).ready(function() {\xA";
if(isset($session['login']['reload_report'])) {
  echo "\t\t\t$('#fileStatus').html('".$session['login']['reload_report']."');\xA";
  unset($session['login']['reload_report']);
}
if(isset($session['login']['cmd_history'][$server_select])) {
  echo "\t\t\this_array = ".json_encode($session['login']['cmd_history'][$server_select]).";\xA";
}

// This is for displaying the server name & password in an input box
if(file_exists("$base_dir$server_select/server-settings.json")) {
  //
  $server_settings = json_decode(file_get_contents("$base_dir$server_select/server-settings.json"), true);
  if($server_settings != NULL) {
    //Do we have a server
    if(isset($server_settings["name"])) {
      if($user_level == "viewonly") {
        echo "\t\t\t$('#server_name').hide();\xA";
      } else {
        $server_name = htmlspecialchars($server_settings["name"]);
        $server_name_length = strlen($server_name);
        if($server_name_length<20) {
          $server_name_length = 20;
        }
        echo "\t\t\t$('#server_name').attr('value',\"".addslashes($server_name)."\");\xA";
        echo "\t\t\t$('#server_name').attr('size',$server_name_length);\xA";
      }
      /*var_dump($server_settings);*/
    }
    if( isset($server_settings["game_password"]) && !empty($server_settings["game_password"]) ) {
      echo "\t\t\t$('#link_config').html('<i class=\"fa fa-lock\" aria-hidden=\"true\"></i> <a href=\"./server-settings.php?d=".$server_select."#server_list-".$server_select."\">config</a>');\xA";
    } else {
      echo "\t\t\t$('#link_config').html('<i class=\"fa fa-unlock\" aria-hidden=\"true\"></i> <a href=\"./server-settings.php?d=".$server_select."#server_list-".$server_select."\">config</a>');\xA";
    }
  } else {
    // Report file came back invalid
    echo "\t\t\t$('#server_name').attr('value',\"#ERROR: WITH server-settings.json#\");\xA";
    echo "\t\t\t$('#link_config').html('<i class=\"fa fa-exclamation\" aria-hidden=\"true\"></i> <a href=\"./server-settings.php?d=".$server_select."#server_list-".$server_select."\">config</a>');\xA";
    echo "\t\t\t$('#server_name').attr('size',30);\xA";
  }
} else {
  // Report server-settings missing";
  echo "\t\t\t$('#server_name').attr('value',\"#ERROR: server-settings.json NOT FOUND#\");\xA";
  echo "\t\t\t$('#link_config').html('<i class=\"fa fa-exclamation\" aria-hidden=\"true\"></i> <a href=\"./server-settings.php?d=".$server_select."#server_list-".$server_select."\">config</a>');\xA";
  echo "\t\t\t$('#server_name').attr('size',40);\xA";
}
//Get the max upload size in megabytes and bytes for use later on
function return_bytes($val) {
  $val = trim($val);
  $last = strtolower($val[strlen($val)-1]);
  switch($last)
  {
    case 'g':
    $val *= 1024;
    case 'm':
    $val *= 1024;
    case 'k':
    $val *= 1024;
  }
  return $val;
}
$upload_max_filesize_m = ini_get('upload_max_filesize');
$upload_max_filesize_b = return_bytes($upload_max_filesize_m);
echo "\t\t\t$('#upload_max_filesize_m').attr('value',\"".addslashes($upload_max_filesize_m)."\");\xA";
echo "\t\t\t$('#upload_max_filesize_b').attr('value',\"".addslashes($upload_max_filesize_b)."\");\xA";
echo "\t\t\t$('#link_logs').attr('href',\"logs.php?d=".$server_select."#server_list-".$server_select."\");\xA";
if(isset($server_select_dropdown)) { echo $server_select_dropdown; }
echo "\t\t})\xA";
?>
</script>
