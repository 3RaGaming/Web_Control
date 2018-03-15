<!DOCTYPE html>
<?php
if ($currentpage == 'func_settings') {
  require('../../inc/functions/functions.php');
} else {
  require('./inc/functions/functions.php');
}

if ($currentpage != 'login') {
    require($_SERVER['DOCUMENT_ROOT'].'/'.$folder.'inc/functions/func_session.php');
}
?>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?php echo $title; ?></title>
  <script src="https://code.jquery.com/jquery-3.2.1.min.js" integrity="sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4=" crossorigin="anonymous"></script>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
  <script type="text/javascript" language="javascript" src="assets/js/base.js"></script>
  <script type="text/javascript" language="javascript" src="assets/js/console.js"></script>
  <script type="text/javascript" language="javascript" src="assets/js/cpumeminfo.js"></script>
  <script type="text/javascript" language="javascript" src="./inc/js/base.js"></script>
  <script defer src="https://use.fontawesome.com/releases/v5.0.6/js/all.js"></script>
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="stylesheet" href="<?php themepath(); ?>/css/master.css">
  <?php if ($currentpage != 'login'): ?>
    <script type="text/javascript">
    var base = "<?php echo $base_dir ?>";
    var server_select = "<?php if (isset($server_select)) {
    echo $server_select;
} else {
    echo "error";
} ?>";
    var user_name = "<?php echo $user_name; ?>";
    var user_level = "<?php echo $user_level; ?>";
    var token = "<?php echo generateJWTToken($user_name, $user_level); ?>";
  </script>
  <?php endif; ?>
</head>
<body>
  <?php if ($server_select == 'managepgm') {
    $link = "server1";
} else {
    $link = $server_select;
}?>
  <?php if ($currentpage != 'login' && $currentpage != 'files' && $currentpage != 'func_settings') {
    ?>
    <!--menu-->
    <nav class="navbar navbar-expand-xl navbar-dark bg-dark">
      <a class="navbar-brand" href="#">Webcontrol
        <span class="navbar-text server-name">
          <span class="text-muted">-</span>
          <input class="d-none" type="text" id="server_name" name="server_name" value="<?php servername(); ?>" />
          <?php servername(); ?>
        </span>
      </a>
      <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav mr-auto">
          <li class="nav-item">
            <a class="nav-link" id="link_home" href="<?php url('index.php?d='.$link, false); ?>">
              Home
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="<?php url('server-settings.php?d='.$link, false); ?>">
              config
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href=<?php url('logs.php?d='.$link, false); ?>>
              logs
            </a>
          </li>
        </ul>
        <button class="btn btn-sm btn-outline-success" type="button" onclick="update_web_control(user_level);">Update Web Control</button>
        <ul class="navbar-nav">
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              <i class="fa fa-user-circle" aria-hidden="true"></i> <?php echo $user_name; ?>
            </a>
            <div class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
              <a class="dropdown-item" href="login.php?logout">Logout</a>
            </div>
          </li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              <i class="fa fa-server" aria-hidden="true"></i> <?php echo $server_select; ?>
            </a>
            <div class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
              <?php dropdown(); ?>
            </div>
          </li>
        </ul>
      </div>
    </nav>
  <?php
} ?>
