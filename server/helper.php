<?
require 'vendor/autoload.php';
require 'pngdecode/Peperzaken/Ios/DecodeImage.php';
require 'pngdecode/ZlibDecompress/ZlibDecompress.php';
session_start();

function login_needed() {
	if ($_SESSION['logged_in']) {
		return true;
	} else {
		$smarty = new Smarty;
		$content = $smarty->fetch('templates/login.template');

		$smarty = new Smarty;
		$smarty->assign('title', "Login");
		$smarty->assign('content', $content);
		$smarty->assign('footer', '');
		$smarty->display('templates/page.template');
		exit;
	}
}

function parse_info_plist($artifact, $app_name) {
	$info_plist = '';
	$z = new ZipArchive();
	if ($z->open($artifact)) {
		$plist_filename = 'Payload/'.$app_name.'.app/Info.plist';
		$stat = $z->statName($plist_filename);
		if (!$stat) {
			return array();
		}
		$fp = $z->getStream($plist_filename);
		$info_plist = fread($fp, $stat['size']);
	}
	$plist = new \CFPropertyList\CFPropertyList();
	$plist->parse($info_plist);
	$plist = $plist->toArray();
	return $plist;
}

function fetch_icon($artifact, $app_name) {
	$decoded_icon_name = dirname($artifact).'/'.basename($artifact, '.ipa').".png";
	if (file_exists($decoded_icon_name)) {
		return $decoded_icon_name;
	}

	$z = new ZipArchive();
	if ($z->open($artifact)) {
		$icon_filename = 'Payload/'.$app_name.'.app/Icon.png';
		$stat = $z->statName($icon_filename);
		if (!$stat) {
			return array();
		}
		$fp = $z->getStream($icon_filename);
		$icon = fread($fp, $stat['size']);
	}
	$encoded_icon_name = dirname($artifact).'/'.basename($artifact, '.ipa')."_encoded.png";
	file_put_contents($encoded_icon_name, $icon);
	$processor = new Peperzaken_Ios_DecodeImage($encoded_icon_name);
	$processor->decode($decoded_icon_name);
	return $decoded_icon_name;
}
?>
