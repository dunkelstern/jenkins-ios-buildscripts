<?
require 'vendor/autoload.php';
require 'helper.php';

/* Error Codes:
 *
 * 1: Bad Request, no App-name or App-Version
 * 2: Bad Request, no Request-Type
 * 3: Bad Request, missing incident ID
 * 4: Bad Request, wrong method used
 * 5: Bad Request, unknown method
 * 6: Bad Request, invalid incident ID
 */

header("Content-Type: application/json");

$app_name     = @$_REQUEST['app_name'];
$app_version  = @$_REQUEST['app_version'];
$incident_id  = @$_REQUEST['incident_id'];
$request_type = @$_REQUEST['type'];

if ((strlen($app_name) < 1) || (strlen($app_version) < 1)) {
	header("HTTP/1.1 400 Bad Request", 400);
	die('{ "status" : "error", "code" : 1 }');
}

if (strlen($request_type) < 1) {
	header("HTTP/1.1 400 Bad Request", 400);
	die('{ "status" : "error", "code" : 2 }');
}

if (($request_type != 'getIncidentID') && (strlen($incident_id) < 1)) {
	header("HTTP/1.1 400 Bad Request", 400);
	die('{ "status" : "error", "code" : 3 }');
}

if ($request_type != 'getIncidentID') {
	if ($_SERVER['REQUEST_METHOD'] != 'POST') {
		header("HTTP/1.1 405 Method not allowed", 400);
		die('{ "status" : "error", "code" : 4 }');
	}
} else {
	if ($_SERVER['REQUEST_METHOD'] != 'GET') {
		header("HTTP/1.1 405 Method not allowed", 400);
		die('{ "status" : "error", "code" : 4 }');
	}	
}

$dir = 'crashreports/'.$app_name.'/'.$app_version.'/'.date('Ymd').'/';
switch ($request_type) {
	case 'getIncidentID':
		// prepare destination
		@mkdir('crashreports');
		@mkdir('crashreports/'.$app_name);
		@mkdir('crashreports/'.$app_name.'/'.$app_version);
		@mkdir($dir);

		// generate incident id
		$incident_id = substr(md5(time()),0,8);
		touch($dir.$incident_id.'.lock');
		die('{ "status" : "ok", "incident_id" : "'.$incident_id.'" }');
		break;
	
	case 'postCrashlog':
		$filename = $incident_id.'.plcrash';
		break;
	case 'postLogfile':
		$filename = $incident_id.'.nsloggerdata';
		break;
	case 'postMetadata':
		$filename = $incident_id.'.txt';
		break;
	default:
		header("HTTP/1.1 400 Bad Request", 400);
		die('{ "status" : "error", "code" : 5 }');
		break;
}

// die if unknown incient
if ((!is_dir($dir)) || (!file_exists($dir.$incident_id.'.lock'))) {
	header("HTTP/1.1 404 Not found", 400);
	die('{ "status" : "error", "code" : 6 }');	
}

// get post data
$post_data = file_get_contents('php://input');

// save to file
$fp = fopen($dir.$filename, 'wb');
fwrite($fp, $post_data);
fclose($fp);

header("HTTP/1.1 201 Created", 201);
echo '{ "status" : "ok" }';
?>