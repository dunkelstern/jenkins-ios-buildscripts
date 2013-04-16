<?
require 'vendor/autoload.php';
require 'helper.php';

header("Content-Type: text/xml");

$plist = parse_info_plist($_REQUEST['artifact'], $_REQUEST['app_name']);

$smarty = new Smarty;
$smarty->assign('url', "http://".$_SERVER['HTTP_HOST'].str_replace("install.php", "", $_SERVER['PHP_SELF']).$_REQUEST['artifact']);
$smarty->assign('bundle_identifier', $plist['CFBundleIdentifier']);
$smarty->assign('version', $plist['CFBundleShortVersionString']);
$smarty->assign('build', $plist['CFBundleVersion']);
$smarty->assign('app_name', $_REQUEST['app_name']);

$smarty->display('templates/install_plist.template');

?>