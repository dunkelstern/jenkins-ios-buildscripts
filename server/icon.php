<?
require 'helper.php';
$icon = fetch_icon($_REQUEST['artifact'], $_REQUEST['app_name']);
header("Content-Type: image/png");
readfile($icon);
?>