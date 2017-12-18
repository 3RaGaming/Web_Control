<!DOCTYPE html>
<?php require('./inc/functions/functions.php');
if ($currentpage != 'login') {

  require($_SERVER['DOCUMENT_ROOT'].'/'.$folder.'inc/functions/func_session.php');
}
?>
<html>
<head>
  <meta charset="utf-8">
  <title><?php echo $title; ?></title>
  <script src="https://code.jquery.com/jquery-3.2.1.min.js" integrity="sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4=" crossorigin="anonymous"></script>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.2/css/bootstrap.min.css" integrity="sha384-PsH8R72JQ3SOdhVi3uxftmaW6Vc51MKb0q5P2rRUpPvrszuE4W1povHYgTpBfshb" crossorigin="anonymous">
  <script type="text/javascript" language="javascript" src="assets/js/base.js"></script>
  <script type="text/javascript" language="javascript" src="assets/js/console.js"></script>
  <script type="text/javascript" language="javascript" src="assets/js/cpumeminfo.js"></script>
  <script type="text/javascript" language="javascript" src="./inc/js/base.js"></script>
  <script src="https://use.fontawesome.com/674cd09dad.js"></script>
  <link rel="stylesheet" href="<?php themepath(); ?>/css/master.css">
  <script type="text/javascript">
  var server_select = "<?php if(isset($server_select)) { echo $server_select; }  else { echo "error"; } ?>";
  var user_name = "<?php echo $user_name; ?>";
  var user_level = "<?php echo $user_level; ?>";
</script>
</head>
<body>
  <?php if ($server_select == 'managepgm') {
    $link = "server1";
  } else {
    $link = $server_select;
  }?>
  <?php if ($currentpage != 'login' && $currentpage != 'files') { ?>
    <!--menu-->
    <nav class="navbar navbar-expand-xl navbar-dark bg-dark">
      <a class="navbar-brand" href="#">Webcontrol</a>
      <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav mr-auto">
          <li class="nav-item">
            <a class="nav-link" id="link_home" href="<?php url('index.php?d='.$link, false); ?>">
              <button class="btn" type="button" name="button">Home</button>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="<?php url('server-settings.php?d='.$link, false); ?>">
              <button class="btn" type="button" name="button">config</button>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href=<?php url('logs.php?d='.$link, false); ?>>
              <button class="btn" type="button" name="button">logs</button>
            </a>
          </li>
          <li class="nav-item">
            <div class="nav-link">
              <span class="navbar-text server-name">
                <input class="d-none" type="text" id="server_name" name="server_name" value="<?php servername(); ?>" />
                <?php servername();  ?>
              </span>
            </div>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#">
              <button class="btn" type="button" onclick="update_web_control(user_level);">Update Web Control</button>
            </a>
          </li>
        </ul>
        <ul class="navbar-nav">
          <li class="nav-item dropdown float-right">
            <a class="nav-link" href="#" id="navbarDropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              <button class="btn dropdown-toggle" type="button" name="button">Welcome, <?php echo $user_name; ?></button>
            </a>
            <div class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
              <a class="dropdown-item" href="login.php?logout">Logout</a>
            </div>
          </li>
          <li class="nav-item dropdown">
            <a class="nav-link" href="#" id="navbarDropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              <button class="btn dropdown-toggle" type="button" name="button"><?php echo $server_select; ?></button>
            </a>
            <div class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
              <?php dropdown(); ?>
            </div>
          </li>
        </ul>
      </div>
    </nav>
  <?php } ?>
