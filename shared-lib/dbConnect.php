<?php
function dbQuery_connect($param) {
  $conn = @mysqli_connect($params['host'],$params['user'],$params['pwd'],$params['dbName']);
  include_once(__DIR__.'/formatAs.php');
  if ($conn === false) formatAs_error('cannot connect to database '.$params['dbName']."\n".mysqli_connect_error());
  $conn->query('charset utf8');
  $conn->query('SET character_set_client = utf8');
  $conn->query('SET character_set_server = utf8');
  $conn->query('SET character_set_connection = utf8');
  $conn->query('SET character_set_results = utf8');
  return $conn;
}

/*class dbConnect_class {
  protected $db = null;

  function dbConnect($params) {
    // You may overload this function if your database requires a different login paradigm
    $conn = @mysqli_connect($params['host'],$params['user'],$params['pwd'],$params['dbName']);
    include_once(__DIR__.'/formatAs.php');
    if ($conn === false) formatAs_error('cannot connect to database '.$params['dbName']."\n".mysqli_connect_error());
    $conn->query('charset utf8');
    $conn->query('SET character_set_client = utf8');
    $conn->query('SET character_set_server = utf8');
    $conn->query('SET character_set_connection = utf8');
    $conn->query('SET character_set_results = utf8');
    return $conn;
  }
  
  function dbReuse() {
    // TODO: remove next line
    if (!$this->db) $this->db = $this->dbConnect();
    return $this->db;
  }
}*/
?>