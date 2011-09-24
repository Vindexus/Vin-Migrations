<?php
/*
This is a controller that can be used to run and manage migrations.
It is recommended that you take this logic and put it into your own system.
It is NOT secure here.
*/
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Controller for Vin Migrations</title>
</head>
<body>
<pre><?php
include('vinmigrate.php');
$vin_migrate = new Vinmigrate();
$action = isset($_GET['action']) ? $_GET['action'] : 'current_version';
$success = FALSE;
if($vin_migrate->init())
{
	if($action == 'current_version')
	{
		echo "Current version: " . $vin_migrate->get_current_version() . "\n";
	}
	elseif($action == 'install')
	{
		$success = $vin_migrate->install();
	}
	else
	{
		$success = $vin_migrate->$action($_GET['number']);
	}
}

echo join("\n", $vin_migrate->notices) . "\n";

if($success)
{
	echo $vin_migrate->success_message . "\n";
}
elseif(!$vin_migrate->no_errors())
{
	echo "ERRORS\n-------------------------------------------\n";
	echo join("\n", $vin_migrate->errors);
}
?>
</pre>
</body>
</html>