<?php
if(!isset($_SESSION)) { session_start(); }
if(isset($_SERVER["HTTPS"]) == false)
{
    header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
    exit();
    die();
}
//If logged in, and requested to logout... log them out and show login screen
if(isset($_SESSION['login'])) {
    if(isset($_REQUEST['logout'])) {
        unset($_SESSION['login']);
        $report = "<br />You have been logged out</br >";
    } else {
        //if not requesting to logout... Take back home
        header("Location: ./?d=server1");
        exit();
        die();
    }
}
$user_name="";
$passW="";
if(isset($_POST['uname'])) {
    $user_name = addslashes($_POST['uname']);
}
if(isset($_POST['passw'])) {
    $passW = addslashes(md5(trim($_POST['passw'])));
}
if(!empty($user_name) && !empty($passW)) {
    $userlist = file ('/var/www/users.txt');
    $success = false;
    foreach ($userlist as $user) {
        $user_details = explode('|', $user);
        if ((strtolower($user_details[0]) == strtolower($user_name)) && trim($user_details[1]) == $passW) {
            var_dump($user_details);
            $user_name  = trim($user_details[0]);
            $user_level = trim($user_details[2]);
            $success = true;
            break;
        }
    }
    if ($success) {
        $_SESSION['login']['user']  = $user_name;
        $_SESSION['login']['level'] = $user_level;
        //Send home if logged in
        header("Location: ./?d=server1");
        die();
    } else {
        $report =  "<br />You have entered the wrong username or password. Please try again.<br />";
    }
} elseif(isset($_POST['submit'])) {
    $report = "<br />I don't like no input<br />";
}
session_write_close();
?>
    <html>
    <head>
        <link rel="stylesheet" media="all" href="assets/css/login.css" />
    </head>
    <body>
    <div class="login-page">
        <div class="form">
            <form class="login-form" name="login" method="post">
                <input type="hidden" name="login" value="submit" />
                <input type="text" name="uname" <?php echo (empty($user_name)?'placeholder="username"':'value="'.$user_name.'"'); ?> />
                <input type="password" name="passw" placeholder="password"/>
                <button onclick="document.login.submit();">login</button>
            </form>
            <?php if(isset($report)) { echo $report; } ?>
        </div>
    </div>
    </body>
    </html>
<?php
//End login page
?>