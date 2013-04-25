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

function fetch_files($dir, $glob) {
	if (substr($dir,-1) != '/') $dir .= '/';
	foreach (glob($dir.$glob) as $entry) {
		if ($entry == '..') continue;
		if ($entry == '.') continue;
		$file_array[] = $entry;
	}
	return $file_array;	
}

function project_list() {
	return fetch_files("projects/", '*');
}

function parse_project($dir) {
	$project_info = parse_ini_file($dir.'/project.ini');
	$project_info['versions'] = array();

	$filelist = fetch_files($dir, "*.ipa");
	if ($filelist) {
		foreach ($filelist as $artifact) {
			$stat = stat($artifact);
			$plist = parse_info_plist($artifact, $project_info['app_name']);

			$project_info['versions'][$plist["CFBundleVersion"]] = $plist;
			$project_info['versions'][$plist["CFBundleVersion"]]['filename'] = $artifact;
			$project_info['versions'][$plist["CFBundleVersion"]]['size'] = $stat['size'];
			$project_info['versions'][$plist["CFBundleVersion"]]['mtime'] = strftime('%Y-%m-%d %H:%M:%S', $stat['mtime']);
			$project_info['versions'][$plist["CFBundleVersion"]]['manifest_url'] = 'http://'. $_SERVER['HTTP_HOST'].preg_replace('/(.*)\/[a-zA-Z0-9]+\.php$/', '\1/', $_SERVER['PHP_SELF']).'install/'.$project_info['app_name'].'/'.$artifact;			
			

			$changelog = dirname($artifact).'/'.basename($artifact, '.ipa').'.html';
			if (file_exists($changelog)) {
				$log = file_get_contents($changelog);
				if ($project_info['jira_link']) {
					$log = preg_replace('/('.$project_info['jira_name'].'-[0-9]*)/', '<a href="'.$project_info['jira_link'].'\1">\1</a>' , $log);
				}
				$project_info['versions'][$plist["CFBundleVersion"]]['changelog'] = $log;
			} else {
				$project_info['versions'][$plist["CFBundleVersion"]]['changelog'] = '';
			}

			if (@isset($project_info['bad'])) {
				if (array_search($plist["CFBundleVersion"], $project_info['bad']) === false) {
					$project_info['versions'][$plist["CFBundleVersion"]]['bad'] = false;
				} else {
					$project_info['versions'][$plist["CFBundleVersion"]]['bad'] = true;				
				}
			} else {
				$project_info['versions'][$plist["CFBundleVersion"]]['bad'] = false;				
			}

			if (@isset($project_info['good'])) {
				if (array_search($plist["CFBundleVersion"], $project_info['good']) === false) {
					$project_info['versions'][$plist["CFBundleVersion"]]['good'] = false;
				} else {
					$project_info['versions'][$plist["CFBundleVersion"]]['good'] = true;				
				}
			} else {
				$project_info['versions'][$plist["CFBundleVersion"]]['good'] = false;
			}
		}
	}
	return $project_info;
}

?>
