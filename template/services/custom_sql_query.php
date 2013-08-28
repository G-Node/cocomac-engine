<?php
ini_set('display_errors',1);
ini_set('memory_limit','10M');
$timeLimit = 10;
$info = json_decode(
<<<SITEMAP
{
  "path": "###PROJ_NAME###|services|custom sql query",
  "title": "Query ###PROJ_NAME### with a custom SQL query",
  "description": "Send a parameterized SQL query to the ###PROJ_NAME### database and retrieve the results in html, plain text or json format."
}
SITEMAP
,TRUE);

require_once('###SOURCE_FOLDER###/shared-lib/sitemap.php');
require_once('###SOURCE_FOLDER###/shared-lib/applet.php');
$siteMap = new siteMap_class($info,'###WWW_FOLDER###/admin/adminLogin.php');
$applet = new applet_class();

/* Create form fields for this applet */
$runLevel = 0;
$attr = array('size'=>120);
$f_sql = new textField_class('Web-safe sql query',$attr,0,10000);
if (isset($_REQUEST['sql'])) {
  $sql = $_REQUEST['sql'];
  $sql = str_replace('\'','',$sql);
  $sql = str_replace('"','',$sql);
  $sql = str_replace('`','',$sql);
  $_REQUEST['sql'] = $sql;
  require_once('###SOURCE_FOLDER###/shared-lib/dbQuery.php');
  $paramTypes = dbQuery_namedParameterTypes($sql);

  $f_sql->setDefault($sql);
  $f_sql->setReadOnly(TRUE);
  $applet->addFormField('sql',$f_sql);
  
  if (count($paramTypes) == 0) {
    $f = new commentField_class('It does not contain any named parameters.');
    $applet->addFormField('comment2',$f);
  } else {
    foreach ($paramTypes as $name=>$tp) {
      $f = new textField_class($name);
      $applet->addFormField($name,$f);
    }
  }
  $f = new selectField_class('Output format');
  $f->setChoices(array('json'=>'javascript notation (json)','json_rpc'=>'json remote procedure call','tsv'=>'tab-separated unquoted (empty string for NULL)','csv'=>'comma separated and quoted (unquoted NULL)','html'=>'html table'),'json');
  $applet->addFormField('format',$f);

  $f = new numField_class('Truncate very long strings to ',array(),0,10000);
  $f->setDefault(100);
  $applet->addFormField('truncate',$f);

  if (@$_REQUEST['run'] == 1) {
    $runLevel = 1;
  } else {
    $runLevel = 2;
  }
} else {
  $f = new hiddenField_class(1);
  $applet->addFormField('run',$f);
  $applet->addFormField('sql',$f_sql);
}

$errors = $applet->parseAndValidateInputs($_REQUEST);
if ($runLevel < 2) {
  /*
   * Interactive mode
   */
  echo '<html><head>';
  echo '<script type="text/javascript" src="../shared-js/browser.js"></script>';
  echo $siteMap->windowTitle();
  echo $siteMap->clientScript();
  echo $applet->clientScript();
  echo '</head><body style="width: 1000px">';
  if (!isset($_GET['iframe'])) {
    echo $siteMap->navigationBar();
    echo $siteMap->pageTitle();
  }
  echo $siteMap->pageDescription();
  echo '<p><img style="float: right; margin-left: 10px" als="open access" src="../img/openaccess_s.png"><br/>';
  echo 'For the stability of our server, the query must meet these requirements:<ul>';
  echo '<li>It must finish <b>within '.$timeLimit.' seconds</b>.</li>';
  echo '<li>It must start with a <b>SELECT</b> statement, and may use joins, where clauses etc. etc.</li>';
  echo '<li>It is <b>not allowed to contain any type of quotes</b> (single, double, backtick).</li>';
  echo '</ul>To include string values in your query, give the value a name, and enter it as $name in the query.<br/>';
  echo 'Example: <br/>&nbsp;&nbsp;SELECT * FROM Literature WHERE TextID=$lit<br/>';
  if ($runLevel == 0) {
    echo 'On submit, you will be asked to provide values for your named parameters.</p>';
  } else {
    echo '&nbsp;&nbsp;lit: A85</p>';
  }
  if ($runLevel == 0) {
    echo $applet->standardFormHtml('Next...','_TOP');
  } else {
    echo $applet->standardFormHtml('Submit query and parameters');
  } 
  echo '</body></html>';
  exit;
} elseif (count($errors)) {
  echo '<html>'.$applet->errorReport($errors).'</html>';
  exit;
}

/*
 * On submit
 */
?>
<?php
require_once('###PRIVATE_FOLDER###/dbParams.php');
require_once('###SOURCE_FOLDER###/shared-lib/dbConnect.php');
require_once('###SOURCE_FOLDER###/shared-lib/formatAs.php');
set_time_limit($timeLimit);
$params = array();
foreach ($paramTypes as $name=>$tp) {
  $params[$name] = @$_REQUEST[$name];
}
// allow only SELECT from cocomac_relational
$dbConnect = new dbConnect_class();
$db = $dbConnect->dbConnect(dbParams('###DB_NAME###','read_only'));
$rs = dbQuery_namedParameters($db,$sql,$params);
$outputFormat = $_REQUEST['format'];
$truncate = $_REQUEST['truncate'];

if ($outputFormat == 'json' || $outputFormat == 'json_rpc') {
  $T = dbQuery_rs2struct($rs,FALSE);
  formatAs_jsonHeaders();
  if ($outputFormat == 'json_rpc') $T = array('result'=>$T);
  echo formatAs_prettyJson($T);
  return;
} 

// else...

$T = array();
while ($row=$rs->fetch_row()) {
  if ($outputFormat == 'csv') {
    foreach ($row as &$v) $v = isset($v) ? '\''.$db->real_escape_string($v).'\'' : 'NULL'; 
    unset($v);
    $T[] = implode(',',$row);
  } elseif ($outputFormat == 'tsv') {
    foreach ($row as &$v) $v = preg_replace('/[\t\n\r]/',' ',$v);
    unset($v);
    $T[] = implode("\t",$row);
  } else {
    $T[] = $row;
  }
}
$fields = mysqli_fetch_fields($rs);
foreach ($fields as &$f) $f = $f->name;
unset($f);

if ($outputFormat != 'html') formatAs_textHeaders();
if ($outputFormat == 'csv') {
  echo "'".implode("','",$fields)."'\n";
  echo implode(",\n",$T);
} elseif ($outputFormat == 'tsv') {
  echo implode("\t",$fields)."\n".implode("\n",$T);
} else {
  $mx = 0;
  foreach ($fields as $f) { $len=strlen($f); if ($len>$mx) $mx = $len; }
  echo '<style>table { border-collapse: collapse } td { border: 1px solid #44a } div.vertical { width: 1em; height: 1em; overflow: visible; writing-mode:tb-rl;-webkit-transform:rotate(-60deg);-moz-transform:rotate(-60deg); -o-transform: rotate(-60deg);}</style>';
  echo '<table><tr><th style="vertical-align: bottom"><div class="vertical">#</div></th>';
  foreach ($fields as $f) {
    echo '<th style="vertical-align: bottom; horizontal-align: top; height: '.round(1.125*$mx).'ex"><div class="vertical">'.$f.'</div></th>';
  }
  echo '</tr>'.formatAs_basicTable($T,array('<tr>','</tr>'),array('<td>','</td>'),TRUE,FALSE,TRUE,$truncate).'</table>';
}
?>
