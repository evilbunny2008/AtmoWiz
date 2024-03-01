<?php
	$error = null;
	require_once('mariadb.php');

	if(!isset($_REQUEST['uid']) || empty($_REQUEST['uid']))
	{
		echo json_encode(array('status' => 'ERROR', 'error' => "Invalid UID."));
		exit;
	}

	$uid = mysqli_real_escape_string($link, $_REQUEST['uid']);

	$table = "<tr><th>Name</th><th>Upper Temp.</th><th>Lower Temp.</th><th>Target Temp.</th><th>Turn On/Off</th><th>Mode</th><th>Fan Level</th><th>Swing</th><th>Hor. Swing</th><th>Enabled</th><th>Edit</th><th>Delete</th></tr>";

	$query = "SELECT * FROM settings WHERE uid='$uid'";
	$res = mysqli_query($link, $query);
	while($drow = mysqli_fetch_assoc($res))
	{
		$table .= "<tr>";
		$table .= "<td style='cursor: pointer;' title='".$drow['name']."'>".$drow['name']."</td>";
		$table .= "<td>".$drow['upperTemperature']."</td>";
		$table .= "<td>".$drow['lowerTemperature']."</td>";
		$table .= "<td>".$drow['targetTemperature']."</td>";
		$table .= "<td>".$drow['turnOnOff']."</td>";
		$table .= "<td>".$drow['mode']."</td>";
		$table .= "<td>".$drow['fanLevel']."</td>";
		$table .= "<td>".$drow['swing']."</td>";
		$table .= "<td>".$drow['horizontalSwing']."</td>";
		$table .= "<td>";
		if($drow['enabled'])
			$table .= "True";
		else
			$table .= "False";
		$table .= "</td>";

		$table .= "<td onClick=\"editSetting('".$drow['created']."', '".$drow['uid']."', '".$drow['name']."', '".$drow['upperTemperature']."', '".$drow['lowerTemperature']."', '".$drow['targetTemperature']."', '";
		$table .= $drow['turnOnOff']."', '".$drow['mode']."', '".$drow['fanLevel']."', '".$drow['swing']."', '".$drow['horizontalSwing']."', '".$drow['enabled']."'";
		$table .= "); return false;\" style=\"cursor: pointer; color: #085f24;\">Edit</td>";
		$table .= "<td onClick=\"deleteSetting('".$drow['created']."', '".$drow['uid']."'); return false;\" style=\"cursor: pointer;color: #085f24;\">Delete</td>";
		$table .= "</tr>\n";
	}

	echo json_encode(array('status' => 200, 'content' => $table));
