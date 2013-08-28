<?php

$info = json_decode(
<<<SITEMAP
{
  "path": "###PROJ_NAME###|Search wizard",
  "title": "Search wizard",
  "description": "Multi-step search wizard with smart database parsing."
}
SITEMAP
,TRUE);

$rpc = (@$_REQUEST['rpc'] !== NULL);
ini_set('display_errors',1);
session_start();
ob_start();
try {
  require_once('###SOURCE_FOLDER###/shared-lib/formfields.php');
  require_once('###SOURCE_FOLDER###/shared-lib/formatAs.php');
  require_once('###SOURCE_FOLDER###/shared-lib/dbScheme.php');
  require_once('###SOURCE_FOLDER###/shared-lib/dbCache.php');
  require_once('###SOURCE_FOLDER###/shared-lib/dbSearchWizard.php');
  if (!$rpc) {
    require_once('###SOURCE_FOLDER###/shared-lib/sitemap.php');
    $siteMap = new siteMap_class($info,'###WWW_FOLDER###/admin/adminLogin.php');
    $submitAction = $scriptUrl = $_SERVER['REQUEST_URI'];
    $submitMethod = 'GET';
  }

  $dbScheme = json_decode(file_get_contents('###DB_DDF###'),TRUE);
  // add cached (result) tables that belong to this session
  require_once('###PRIVATE_FOLDER###/dbParams.php');
  $params = dbParams('###DB_NAME###','search');
  $db = dbQuery_connect($params['host'],$params['user'],$params['pwd'],$params['dbName']);
  // dbCache_importCachedTables($dbScheme, $db,session_id());
  $dbParsed = dbScheme_parse($dbScheme);  
  $formFields = new formfields_class();
  $formValues = array();

  if ($rpc) {
    $presets = array(
      'format'=>'post',
      'page'=>1
    );
  } else {
    $presets = array();
  }
  
  $searchWizard = new searchWizard_class($dbParsed,$presets,$_REQUEST);
  $searchWizard->setDatabase($db);
  
  $readyToSubmit = $searchWizard->scalarPropertiesForm($formFields,$formValues,$submitText);
  if ($readyToSubmit) {
    // ready to submit.
    if (!$rpc) {
      $msg = ob_get_clean();
      if ($msg) throw new Exception($msg);
    }
    $result = $searchWizard->submitQuery($formValues,'../js/customViewer.js');
    if ($rpc) {
      $ans = array('result'=>$result);
      // check for php warnings and notices
      $msg = ob_get_clean();
      if ($msg) throw new Exception($msg);
      formatAs_jsonHeaders();   
      echo json_encode($ans);
    }
  } else {
    if ($rpc) throw new Exception('Not ready to submit; '.$readyToSubmit.' '.json_encode($_REQUEST));

    // display the page
    echo '<html><head>';
    echo '<script type="text/javascript" src="../shared-js/browser.js"></script>';
    echo '<meta http-equiv="content-type" content="text/html; charset=UTF-8">';
    echo $siteMap->clientScript();
    echo $formFields->headSection();
    echo $siteMap->windowTitle();
    echo '</head><body>';
    
    // a bit overdone when using drupal...
    echo $siteMap->navigationBar();
    echo $siteMap->pageTitle();
    echo $siteMap->pageDescription();
    
    echo '<p><form action="'.$submitAction.'" method="'.$submitMethod.'"><table>';
    echo $formFields->formAsTableRows(array());
    $goBackURL = $searchWizard->goBackURL();
    echo '<tr><td colspan="3"><input type="button" value="Previous" onclick="document.location.replace(\''.$goBackURL.'\')"/>&nbsp;<input type="submit" value="'.$submitText.'"/></td></tr>';
    echo '</table></form></p>';
    echo '</body></html>';
  }
} catch (Exception $e) {
  $ans = array('error'=>array('message'=>$e->getMessage()));
  formatAs_jsonHeaders();
  ob_end_clean();
  echo json_encode($ans);
}
?>