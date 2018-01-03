<?php
if(!isset($base_dir) && isset($_REQUEST['base'])){
  $base_dir = $_REQUEST['base'];
}

if(isset($_REQUEST['d'])) {
  $server_select=$_REQUEST['d'];
} else {
  $server_select='server1';
}
if (!isset($_REQUEST['sort'])) {
  sort_date();
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

function savelist(){
  $server_select = $GLOBALS['server_select'];
  $base_dir = $GLOBALS['base_dir'];
  $saves = array();
  $server_dir = $base_dir . $server_select . "/";
  if(isset($server_select)) {
    $full_dir = $server_dir . "saves";
    foreach(array_diff(scandir("$full_dir"), array('..', '.')) as $file) {
      $file_full_path = "$full_dir/$file";
      $size = filesize($file_full_path);
      $humansize = human_filesize("$file_full_path");
      $date = date ("Y-m.M-d H:i:s", filemtime("$file_full_path"));
      $length = strlen($file);
      $name = substr($file, -$length, 25);
      $savedata = array('file' => $file, 'name' => $name, 'size' => $size, 'humansize' => $humansize, 'date' => $date);
      array_push($saves, $savedata);
    }
    return($saves);
  }
}

function sort_name(){
  $server_select = $GLOBALS['server_select'];
  $saves = savelist();
  foreach ($saves as $key => $row)
  {
    $name[$key]  = $row['name'];
  }
  array_multisort($file, SORT_ASC, $saves);
  foreach ($saves as $value) {
    echo "<tr>";
    echo "<td>";
    echo "<input name='filecheckbox' class='form-check-input file-checkbox' type='checkbox' title='".$value['file']."' value='".$value['file']."'>";
    echo "</td>";
    echo "<td>";
    echo "<a href='files.php?p=download&d=".$server_select."&f=".$value['file']."&l=saves' title='".$value['file']."' >".$value['name']."</a>";
    echo "</td>";
    echo "<td>";
    echo $value['humansize'];
    echo "</td>";
    echo "<td>";
    echo $value['date'];
    echo "</td>";
    echo "</tr>";
  }
}

function sort_size(){
  $server_select = $GLOBALS['server_select'];
  $saves = savelist();
  foreach ($saves as $key => $row)
  {
    $size[$key]  = $row['size'];
  }
  array_multisort($size, SORT_DESC, $saves);
  foreach ($saves as $value) {
    echo "<tr>";
    echo "<td>";
    echo "<input name='filecheckbox' class='form-check-input' type='checkbox' title='".$value['file']."' value='".$value['file']."'>";
    echo "</td>";
    echo "<td>";
    echo "<a href='files.php?p=download&d=".$server_select."&f=".$value['file']."&l=saves' title='".$value['file']."' >".$value['name']."</a>";
    echo "</td>";
    echo "<td>";
    echo $value['humansize'];
    echo "</td>";
    echo "<td>";
    echo $value['date'];
    echo "</td>";
    echo "</tr>";
  }
}

function sort_date(){
  $server_select = $GLOBALS['server_select'];
  $saves = savelist();
  foreach ($saves as $key => $row)
  {
    $date[$key]  = $row['date'];
  }
  array_multisort($date, SORT_DESC, $saves);
  foreach ($saves as $value) {
    echo "<tr>";
    echo "<td>";
    echo "<input name='filecheckbox' class='form-check-input' type='checkbox'  title='".$value['file']."' value='".$value['file']."'>";
    echo "</td>";
    echo "<td>";
    echo "<a href='files.php?p=download&d=".$server_select."&f=".$value['file']."&l=saves' title='".$value['file']."' >".$value['name']."</a>";
    echo "</td>";
    echo "<td>";
    echo $value['humansize'];
    echo "</td>";
    echo "<td>";
    echo $value['date'];
    echo "</td>";
    echo "</tr>";
  }
} ?>
