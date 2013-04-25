<?
require 'vendor/autoload.php';

require 'helper.php';
login_needed();

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