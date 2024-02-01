<?php
	$error = null;

	require_once('mariadb.php');

	if(!isset($_REQUEST['uid']) || empty($_REQUEST['uid']))
	{
		echo json_encode(array('status' => 'ERROR', 'error' => "Invalid UID."));
		exit;
	}

	if(!isset($_REQUEST['mode']) || empty($_REQUEST['mode']))
	{
		echo json_encode(array('status' => 'ERROR', 'error' => "Invalid mode."));
		exit;
	}

	if(!isset($_REQUEST['keyval']) || empty($_REQUEST['keyval']))
	{
		echo json_encode(array('status' => 'ERROR', 'error' => "Invalid keyval."));
		exit;
	}

	$uid = mysqli_real_escape_string($link, $_REQUEST['uid']);
	$mode = mysqli_real_escape_string($link, $_REQUEST['mode']);
	$keyval = mysqli_real_escape_string($link, $_REQUEST['keyval']);

	$ret = [];
	$query = "SELECT * FROM meta WHERE uid='$uid' AND mode='$mode' AND keyval='$keyval'";
	$res = mysqli_query($link, $query);
	while($row = mysqli_fetch_assoc($res))
		$ret[] = $row['value'];

	$query = "SELECT targetTemperature,fanLevel,swing,horizontalSwing FROM commands ORDER BY whentime DESC LIMIT 1";
	$res = mysqli_query($link, $query);
	$row = mysqli_fetch_assoc($res);

	$output = array('status' => 200, 'content' => $ret);
	foreach($row as $k => $v)
		$output[$k] = $v;

	echo json_encode($output);
