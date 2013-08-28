<?php
require_once('###SOURCE_FOLDER###/shared-lib/formatAs.php');

function dbParams($dbName,$mode) {
  $ans = array();
  $ans['host'] = '###DB_HOST###';
  $ans['dbName'] = '###DB_NAME###';
  if ($mode=='read_only') {
    $ans['user'] = '###DB_USER_RO###';
    $ans['pwd'] = '###DB_PWD_RO###';
  } elseif ($mode=='search') {
    $ans['user'] = '###DB_USER_RW###';
    $ans['pwd'] = '###DB_PWD_RW###';
  } elseif ($mode=='root') {
    $ans['user'] = '###DB_USER###';
    $ans['pwd'] = '###DB_PWD###';
  } else {
    formatAs_error('cannot open database in "'.$mode.'" mode');
  }
  return $ans;
}
?>