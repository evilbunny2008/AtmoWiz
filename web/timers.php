<?php
	$error = null;

	require_once('mariadb.php');

	if(!isset($_REQUEST['uid']) || empty($_REQUEST['uid']))
	{
		echo json_encode(array('status' => 'ERROR', 'error' => "Invalid UID."));
		exit;
	}

	$uid = mysqli_real_escape_string($link, $_REQUEST['uid']);

	$table = "<tr><th>Created</th><th>Second(s) Delay</th><th>Turn On/Off</th><th>Delete</th></tr>\n";

	$query = "SELECT * FROM timers WHERE uid='$uid'";
	$res = mysqli_query($link, $query);
	while($drow = mysqli_fetch_assoc($res))
	{
		$table .= "<tr>";
		$table .= "<td style='cursor: pointer;' title='".$drow['whentime']."'>".$drow['whentime']."</td>\n";

		$table .= "<td>".$drow['seconds']."</td>\n";
		$table .= "<td>".$drow['turnOnOff']."</td>\n";

		$table .= "<td onClick=\"deleteTimer('".$drow['whentime']."', '".$drow['uid']."'); return false;\" style=\"cursor: pointer;color: #085f24;\">Delete</td>\n";
		$table .= "</tr>\n";
	}

	echo json_encode(array('status' => 200, 'content' => $table));
