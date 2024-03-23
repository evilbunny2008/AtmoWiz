<?php
	session_start();

	mysqli_report(MYSQLI_REPORT_OFF);
	$link = @mysqli_connect('localhost', 'atmowiz', $dbpass, 'atmowiz');
	if(!isset($link))
		$error = "DB error occured, check your settings in mariadb.php and try again";

	if($error == null && mysqli_connect_errno())
		$error = "DB error: " . mysqli_connect_error();

	$redis = new Redis();
	$redis->connect('127.0.0.1');

	$systemTz = trim(file_get_contents("/etc/timezone"));
	if($systemTz == 'Etc/UTC')
		$systemTz = 'UTC';
	date_default_timezone_set($systemTz);

	$currency_values = array();
	$open = false;
	$file = "/usr/share/i18n/locales/".$currency_code;
	$fp = fopen($file, "r");
	while(!feof($fp))
	{
		$line = trim(fgets($fp));
		if($line == "LC_MONETARY")
		{
			$open = true;
			continue;
		}

		if($line == "END LC_MONETARY")
			break;

		if($open != true)
			continue;

		list($v, $k) = explode(" ", $line, 2);
		$v = trim($v);
		$k = trim($k);

		if(substr($k, 0, 1) == '"')
			$k = substr($k, 1, -1);

		if(substr($k, 0, 1) == "<" && substr($k, -1) == ">")
		{
			$htmlEntity = "&#x".substr($k, 2, -1).";";
			$k = html_entity_decode($htmlEntity, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, 'UTF-8');
		}

		$currency_values[$v] = $k;
	}
	fclose($fp);
