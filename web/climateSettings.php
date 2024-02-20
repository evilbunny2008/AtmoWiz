<?php
	$error = null;
	require_once('mariadb.php');

	if(!isset($_REQUEST['uid']) || empty($_REQUEST['uid']))
	{
		echo json_encode(array('status' => 'ERROR', 'error' => "Invalid UID."));
		exit;
	}

	$uid = mysqli_real_escape_string($link, $_REQUEST['uid']);

	$table = "<tr><th>Created</th><th>If On/Off</th><th>Target Type</th><th>Target Op</th><th>Target Value</th><th>Start Time</th><th>End Time</th><th>Turn On/Off</th><th>Mode</th><th>Target Temp</th><th>Enabled</th><th>Edit</th><th>Delete</th></tr>";

	$query = "SELECT * FROM settings WHERE uid='$uid'";
	$res = mysqli_query($link, $query);
	while($drow = mysqli_fetch_assoc($res))
	{
		$table .= "<tr>";
		$table .= "<td style='cursor: pointer;' title='".$drow['created']."'>".$drow['created']."</td>";
		$table .= "<td>".$drow['onOff']."</td>";
		$table .= "<td>".$drow['targetType']."</td>";
		$table .= "<td>".htmlentities($drow['targetOp'])."</td>";
		$table .= "<td>".$drow['targetValue']."</td>";
		$table .= "<td>".substr($drow['startTime'],0,5)."</td>";
		$table .= "<td>".substr($drow['endTime'],0,5)."</td>";
		$table .= "<td>".$drow['turnOnOff']."</td>";
		$table .= "<td>".$drow['mode']."</td>";
		$table .= "<td>".$drow['targetTemperature']."</td>";
		$table .= "<td>";
		if($drow['enabled'])
			$table .= "True";
		else
			$table .= "False";
		$table .= "</td>";

		$table .= "<td onClick=\"editSetting('".$drow['created']."', '".$drow['uid']."', '".$drow['onOff']."', '".$drow['targetType']."', '".$drow['targetOp']."', '".$drow['targetValue']."', '";
		$table .= substr($drow['startTime'],0,5)."', '".substr($drow['endTime'],0,5)."', '".$drow['turnOnOff']."', '".$drow['targetTemperature']."', '".$drow['mode']."', '".$drow['fanLevel']."', '".$drow['swing']."', '";
		$table .= $drow['horizontalSwing']."', '".$drow['enabled']."'";
		$table .= "); return false;\" style=\"cursor: pointer; color: #085f24;\">Edit</td>";
		$table .= "<td onClick=\"deleteSetting('".$drow['created']."', '".$drow['uid']."'); return false;\" style=\"cursor: pointer;color: #085f24;\">Delete</td>";
		$table .= "</tr>\n";
	}

	echo json_encode(array('status' => 200, 'content' => $table));
