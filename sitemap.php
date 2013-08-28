<?php
$q = $_SERVER['QUERY_STRING'];
$location = 'services/sitemap.php?'.$q;
header('Location:'.$location);
?>
