<?
require 'vendor/autoload.php';

require 'helper.php';
login_needed();


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
			$project_info['versions'][$plist["CFBundleVersion"]]['manifest_url'] = 'http://'. $_SERVER['HTTP_HOST'].str_replace("index.php", "", $_SERVER['PHP_SELF']).'install/'.$project_info['app_name'].'/'.$artifact;			

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


$content = '';

// find projects
$projects = project_list();
$versions = 0;
foreach ($projects as $project) {
	$project_info = parse_project($project);
	$versions += count($project_info['versions']);

	krsort($project_info['versions']);
	$psmarty = new Smarty;
	$psmarty->assign('app_name', $project_info['app_name']);
	$psmarty->assign('project', $project_info['project_name']);
	$psmarty->assign('project_id', $project);
	$psmarty->assign('versions', $project_info['versions']);
	$content .= $psmarty->fetch('templates/project.template');
}


$smarty = new Smarty;
$smarty->assign('title', "Project list");
$smarty->assign('content', $content);
$smarty->assign('footer', count($projects)." Projects and ".$versions." Versions");
$smarty->display('templates/page.template');
?>