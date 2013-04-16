<?
require 'vendor/autoload.php';
require 'helper.php';

if ($_REQUEST['username']) {
	// try to log in
	$users = parse_ini_file('.htusers', true);
	foreach ($users as $username => $settings) {
		if ($_REQUEST['username'] == $username) {
			$hash = md5($_REQUEST['password'].$settings['salt']);
			if ($hash == $settings['password']) {
				$_SESSION['logged_in'] = true;
				header("Location: index.php");
				exit;
			}
		}
	}
}
login_needed();
?>