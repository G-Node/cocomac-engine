<?php
function getConfig($configFile) {
  $s = file_get_contents($configFile);
  $ok = preg_match('/^<<<CONFIG(.*)^CONFIG/ms',$s,$matches);
  return json_decode($matches[1],TRUE);
}

session_start();
//$user = @$_REQUEST['user'];
$user = 'admin';
$pwd = @$_REQUEST['pwd'];
$configFile = '###PRIVATE_FOLDER###/config.php';
if ($user && isset($pwd)) {
  $config = @getConfig($configFile);
  $stored_pwd = @$config['admin_pwd'];
  if ($user=='admin' && $pwd == $stored_pwd) {
    $_SESSION['verified_user'] = $user;
    echo '<html><head>';
    $goto = @$_REQUEST['redirect'];
    if ($goto) echo '<meta http-equiv="refresh" content="2;url='.$goto.'">';
    echo '</head><body>';
    echo 'You are now logged in as user '.$user.'<br/>';
    if ($goto) {
      echo 'You will be redirected to <a href="'.$goto.'">the page</a> you wanted to visit.<br/>';
    }
    echo '</body></html>';
  } elseif (is_array($config) && is_null($stored_pwd)) {
    echo 'Error: No administrator password configured.';
  } else {
    if (isset($_SESSION['verified_user'])) unset($_SESSION['verified_user']);
    echo 'You are not logged in.<p/>';
  }
} else {
  $sourcePath = '###SOURCE_FOLDER###';
  $currentPage = $_SERVER['REQUEST_URI'];
  require_once($sourcePath.'/shared-lib/sitemap.php');
  $siteMap = new siteMap_class(NULL,$currentPage);
  $redirect = @$_REQUEST['redirect'];
  echo $siteMap->basicLoginForm($currentPage,$redirect,'admin');
}
?>