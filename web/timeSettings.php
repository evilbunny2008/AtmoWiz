<?php
	$error = null;
	$period = 86400000;
	$startTS = time() * 1000 - $period;
	$row = array('uid' => '');

	require_once('mariadb.php');

	if(!isset($_REQUEST['uid']) || empty($_REQUEST['uid']))
	{
		echo json_encode(array('status' => 'ERROR', 'error' => "Invalid UID."));
		exit;
	}

	$uid = mysqli_real_escape_string($link, $_REQUEST['uid']);

	$table = "<tr><th>Created</th><th>Day(s)</th><th>Start Time</th><th>Turn On/Off</th><th>Mode</th><th>Target Temp</th><th>Fan Level</th><th>Swing</th><th>Hor. Swing</th><th>Climate Setting</th><th>Enabled</th><th>Edit</th><th>Delete</th></tr>";

	$query = "SELECT * FROM timesettings WHERE uid='$uid'";
	$res = mysqli_query($link, $query);
	while($drow = mysqli_fetch_assoc($res))
	{
		$table .= "<tr>";
		$table .= "<td style='cursor: pointer;' title='".$drow['created']."'>".$drow['created']."</td>\n";

		$days = "";
		if($drow['daysOfWeek'] == 31)
		{
			$days = "Mon-Fri";
		} else if($drow['daysOfWeek'] == 127) {
			$days = "Mon-Sun";
		} else if($drow['daysOfWeek'] == 96) {
			$days = "Sat-Sun";
		} else if($drow['daysOfWeek'] == 79) {
			$days = "Mon-Thu, Sun";
		} else if($drow['daysOfWeek'] == 48) {
			$days = "Fri-Sat";
		} else {
			for($v = 0; $v < 7; $v++)
			{
				if($drow['daysOfWeek'] & 2 ** $v)
				{
					if($days != "")
						$days .= ", ";
					$days .= date("D", mktime(0, 0, 0, 0, $v + 6, 0));
				}
			}
		}

		$table .= "<td style='cursor: pointer;' title='$days'>$days</td>\n";
		$table .= "<td>".substr($drow['startTime'],0,5)."</td>\n";
		$table .= "<td>".$drow['turnOnOff']."</td>\n";
		$table .= "<td>".$drow['mode']."</td>\n";
		$table .= "<td>".$drow['targetTemperature']."</td>\n";
		$table .= "<td>".$drow['fanLevel']."</td>\n";
		$table .= "<td>".$drow['swing']."</td>\n";
		$table .= "<td>".$drow['horizontalSwing']."</td>\n";
		$table .= "<td>";
		if($drow['climateSetting'] == null)
		{
			$table .= "N/A";
		} else {
			$query = "SELECT * FROM settings WHERE uid='$uid' AND created='${drow['climateSetting']}'";
			$ddrow = mysqli_fetch_assoc(mysqli_query($link, $query));
			$table .= $ddrow['name'];
		}
		$table .= "</td>\n";
		$table .= "<td>";
		if($drow['enabled'])
			$table .= "True";
		else
			$table .= "False";
		$table .= "</td>\n";

		$table .= "<td onClick=\"editTimeSetting('".$drow['created']."', '".$drow['uid']."', '".$drow['daysOfWeek']."', '";
		$table .= substr($drow['startTime'],0,5)."', '".$drow['turnOnOff']."', '".$drow['mode']."', '".$drow['targetTemperature']."', '";
		$table .= $drow['fanLevel']."', '".$drow['swing']."', '".$drow['horizontalSwing']."', '".$drow['climateSetting']."', '".$drow['enabled']."'";
		$table .= "); return false;\" style=\"cursor: pointer; color: #085f24;\">Edit</td>\n";
		$table .= "<td onClick=\"deleteTimeSetting('".$drow['created']."', '".$drow['uid']."'); return false;\" style=\"cursor: pointer;color: #085f24;\">Delete</td>\n";
		$table .= "</tr>\n";
	}

	echo json_encode(array('status' => 200, 'content' => $table));
