<?php
	$error = null;

	require_once('mariadb.php');

	if(!isset($_REQUEST['uid']) || empty($_REQUEST['uid']))
	{
		echo json_encode(array('status' => 'ERROR', 'error' => "Invalid UID."));
		exit;
	}

	$uid = mysqli_real_escape_string($link, $_REQUEST['uid']);

	$query = "SELECT mode FROM commands WHERE uid='$uid' ORDER BY whentime DESC LIMIT 1";
	$res = mysqli_query($link, $query);
	$row = mysqli_fetch_assoc($res);

	$output = array('status' => 200, 'content' => $row['mode']);

	echo json_encode($output);
