<?php
require './../config/config.php';
echo "<html>";
if(!isset($_REQUEST['s'])) {
  echo "Please select a server";
} else {
  $server_select=$_REQUEST['s'];
  if (!isset($_REQUEST['sort'])) {
    echo "Please select a way to sort this";
  } else {
    $sort = $_REQUEST['sort'];
    if ($sort == 'name') {
      sort_name();
    } elseif ($sort == 'size') {
      sort_size();
    } elseif ($sort == 'date') {
      sort_date();
    } else {
      echo "Wrong way of sorting selected";
    }
  }
}


function savelist(){
  $server_select = $GLOBALS['server_select'];
  $base_dir = $GLOBALS['base_dir'];
  $saves = array();
  $server_dir = $base_dir . $server_select . "/";
  if(isset($_REQUEST['d'])) {
    if($_REQUEST['d']!==$server_select||$server_select=="failed") {
      die('Error in check');
    }
    $full_dir = $server_dir . "saves";
    foreach(array_diff(scandir("$full_dir"), array('..', '.')) as $file) {
      $file_full_path = "$full_dir/$file";
      $size = filesize($file_full_path);
      $humansize = human_filesize("$file_full_path");
      $date = date ("Y-m.M-d H:i:s", filemtime("$file_full_path"));
      $length = strlen($file);
      $name = substr($file, -$length, 22);
      $savedata = array('file' => $file, 'name' => $name, 'size' => $size, 'humansize' => $humansize, 'date' => $date);
      array_push($saves, $savedata);
    }
    $test = $saves['0'];
    echo $test;
    echo "pizza";
    print_r($saves);
  }
}

function sort_name(){
savelist();
/*array_multisort($saves,SORT_ASC,SORT_STRING);*/
}

function sort_size(){

}

function sort_date(){

}
echo "</html>";
?>
