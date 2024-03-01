<?php
	$error = null;

	require_once('mariadb.php');

	$rows = array();

	$query = "SELECT created, name FROM settings";
	$res = mysqli_query($link, $query);
	while($row = mysqli_fetch_assoc($res))
		$rows[] = array('name' => $row['name'], 'created' => $row['created']);

	$output = array('status' => 200, 'content' => $rows);

	echo json_encode($output);
