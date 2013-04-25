<?
require 'vendor/autoload.php';

require 'helper.php';

$content = '';

// find projects
$info = array();
$projects = project_list();
$versions = 0;
foreach ($projects as $project) {
	$project_info = parse_project($project);
	$versions += count($project_info['versions']);
	krsort($project_info['versions']);
	$info[] = $project_info;
}

$projects = array();
foreach ($info as $project) {
	$data = array();
	$versions = array_values($project['versions']);
	$firstVersion = $versions[0];
	$data['current'] = $firstVersion['CFBundleVersion'];
	$data['url'] = $firstVersion['manifest_url'];

	$versions = array();
	foreach ($project['versions'] as $v) {
		if ($v['bad']) continue;
		$version['version']   = $v['CFBundleVersion'];
		$version['url']       = $v['manifest_url'];
		$version['changelog'] = $v['changelog'];
		$versions[] = $version;
	}

	$data['versions'] = $versions;

	$projects[$project['app_name']] = $data;
}
header("Content-Type: application/json");
echo json_encode($projects);

?>