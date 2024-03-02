<?php
	$error = null;
	require_once('mariadb.php');

	if(!isset($_REQUEST['uid']) || empty($_REQUEST['uid']))
	{
		echo json_encode(array('status' => 'ERROR', 'error' => "Invalid UID."));
		exit;
	}

	$uid = mysqli_real_escape_string($link, $_REQUEST['uid']);

	$table  = "<tr><th>Name</th>";
	$table .= "<th>Upper Temp.</th><th>Target Temp.</th><th>Turn On/Off</th><th>Mode</th><th>Fan Level</th>";
	$table .= "<th>Lower Temp.</th><th>Target Temp.</th><th>Turn On/Off</th><th>Mode</th><th>Fan Level</th>";
	$table .= "<th>Enabled</th><th>Edit</th><th>Delete</th></tr>";

	$query = "SELECT * FROM settings WHERE uid='$uid'";
	$res = mysqli_query($link, $query);
	while($drow = mysqli_fetch_assoc($res))
	{
		$table .= "<tr>";
		$table .= "<td style='cursor: pointer;' title='".$drow['name']."'>".$drow['name']."</td>";
		$table .= "<td>".$drow['upperTemperature']."</td>";
		$table .= "<td>".$drow['upperTargetTemperature']."</td>";
		$table .= "<td>".$drow['upperTurnOnOff']."</td>";
		$table .= "<td>".$drow['upperMode']."</td>";
		$table .= "<td>".$drow['upperFanLevel']."</td>";
		$table .= "<td>".$drow['lowerTemperature']."</td>";
		$table .= "<td>".$drow['lowerTargetTemperature']."</td>";
		$table .= "<td>".$drow['lowerTurnOnOff']."</td>";
		$table .= "<td>".$drow['lowerMode']."</td>";
		$table .= "<td>".$drow['lowerFanLevel']."</td>";
		$table .= "<td>";
		if($drow['enabled'])
			$table .= "True";
		else
			$table .= "False";
		$table .= "</td>";

		$table .= "<td onClick=\"editSetting('".$drow['created']."', '".$drow['uid']."', '".$drow['name']."', '".$drow['type']."', '";
		$table .= $drow['upperTemperature']."', '".$drow['upperTargetTemperature']."', '".$drow['upperTurnOnOff']."', '".$drow['upperMode']."', '".$drow['upperFanLevel']."', '".$drow['upperSwing']."', '".$drow['upperHorizontalSwing']."', '";
		$table .= $drow['lowerTemperature']."', '".$drow['lowerTargetTemperature']."', '".$drow['lowerTurnOnOff']."', '".$drow['lowerMode']."', '".$drow['lowerFanLevel']."', '".$drow['lowerSwing']."', '".$drow['lowerHorizontalSwing']."', '";
		$table .= $drow['enabled']."'";
		$table .= "); return false;\" style=\"cursor: pointer; color: #085f24;\">Edit</td>";
		$table .= "<td onClick=\"deleteSetting('".$drow['created']."', '".$drow['uid']."'); return false;\" style=\"cursor: pointer;color: #085f24;\">Delete</td>";
		$table .= "</tr>\n";
	}

	echo json_encode(array('status' => 200, 'content' => $table));
