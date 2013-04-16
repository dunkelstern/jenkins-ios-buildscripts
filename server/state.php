<?
require 'vendor/autoload.php';
require 'helper.php';
login_needed();

function save_ini_file($array, $file){
	$str="";
	foreach ($array as $k => $v){
    	if (is_array($v)){
    		foreach ($v as $k2 => $v2) {
	      		$str.=$k.'[] = '.$v2.PHP_EOL; 
    		}
    	} else {
      		$str.= $k.' = '.$v.PHP_EOL;
  		}
  	}
	return file_put_contents($file, $str);
}

$project = $_REQUEST['project'].'/project.ini';
$id      = $_REQUEST['id'];
$ini = parse_ini_file($project);

if (@isset($ini['bad'])) {
	foreach ($ini['bad'] as $key => $value) {
		if ($value == $id) {
			unset($ini['bad'][$key]);
		}
	}
}

if (@isset($ini['good'])) {
	foreach ($ini['good'] as $key => $value) {
		if ($value == $id) {
			unset($ini['good'][$key]);
		}
	}
}

if ($_REQUEST['status'] == 'bad') {
	$ini['bad'][] = $id;
}

if ($_REQUEST['status'] == 'good') {
	$ini['good'][] = $id;
}

save_ini_file($ini, $project);
header('Location: index.php');
?>