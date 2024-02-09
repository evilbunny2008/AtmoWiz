
<?php
	$error = null;
	require_once('mariadb.php');

	if(!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] != true)
	{
		echo json_encode(array('status' => 403, 'error' => "You don't have access to this webpage"));
		exit;
	}

	function reportError($error)
	{
		if(!isset($_SESSION['shownError']) || $_SESSION['shownError'] < time())
		{
			$_SESSION['shownError'] = time() + 600;
			echo json_encode(array('status' => 403, 'error' => $error));
			exit;
		}

		$commands = "<li style='color:red;text-align:center'>" . $error . "</li>\n";
		$commands .= "<li style='text-align:right'><a href='graphs.php?logout=1'>Log Out</a></li>\n";
		$data = array('uid' => '', 'dataPoints1' => array(), 'dataPoints2' => array(), 'dataPoints3' => array(),
						'dataPoints4' => array(), 'commands' => $commands, 'currtime' => date("H:i"));
		echo json_encode(array('status' => 200, 'content' => $data));
		exit;
	}

	function getWho($reason)
	{
		if($reason == "ExternalIrCommand")
			return "Remote";
		else if($reason == "UserRequest")
			return "App";
		else if($reason == "UserAPI")
			return "API";
		else if($reason == "Trigger")
			return "Climate React";
		return "Unknown";
	}

	if($error != null)
		reportError($error);

	$dataPoints1 = array();
	$dataPoints2 = array();
	$dataPoints3 = array();
	$dataPoints4 = array();
	$dataPoints5 = array();

	$airconon = '';
	$uid = '';
	$ac = 'off';
	$currhumid = 0;
	$currtemp = 0.0;
	$currtime = "00:00";

	if(isset($_REQUEST['uid']) && !empty($_REQUEST['uid']))
		$uid = mysqli_real_escape_string($link, $_REQUEST['uid']);

	$query = "SELECT uid FROM devices";
	if($uid != '')
		$query .= " WHERE uid='$uid'";
	$query .= " LIMIT 1";
	$res = mysqli_query($link, $query);
	$uid = mysqli_fetch_assoc($res)['uid'];

	if(!isset($uid) || empty($uid))
	{
		$error = "Unable to get a UID, please check your database/configs and try again";
		reportError($error);
	}

	if(isset($_REQUEST['period']) && $_REQUEST['period'] > 0)
		$period = doubleval($_REQUEST['period']);
	else
		$period = 86400000;

	if(isset($_REQUEST['startTS']) && $_REQUEST['startTS'] > 0)
	{
		$startTS = doubleval($_REQUEST['startTS']);
	} else {
		$query = "SELECT *, UNIX_TIMESTAMP(whentime) * 1000 as whentimes FROM sensibo WHERE uid='$uid' ORDER BY whentime DESC LIMIT 1";
		$res = mysqli_query($link, $query);
		$row = mysqli_fetch_assoc($res);
		$startTS = $row['whentimes'] - $period;
	}

	$dataPoints1[] = array('x' => doubleval($startTS), 'y' => null);
	$dataPoints2[] = array('x' => doubleval($startTS), 'y' => null);
	$dataPoints3[] = array('x' => doubleval($startTS), 'y' => null);
	$dataPoints4[] = array('x' => doubleval($startTS), 'y' => null);
	$dataPoints5[] = array('x' => doubleval($startTS), 'y' => null);

	$query = "";

	if($period == 86400000)
	{
		$query = "SELECT whentime, UNIX_TIMESTAMP(whentime) * 1000 as whentimes,DATE_FORMAT(whentime, '%H:%i') as wttime, DATE_FORMAT(whentime, '%Y-%m-%d %H:%i') as wtdt, ".
				"temperature, humidity, feelslike, rssi, airconon FROM sensibo ".
				"WHERE uid='$uid' AND UNIX_TIMESTAMP(whentime) * 1000 >= $startTS AND UNIX_TIMESTAMP(whentime) * 1000 <= $startTS + $period ORDER BY whentime ASC";
	}

	if($period == 604800000)
	{
		$query = "SELECT UNIX_TIMESTAMP(whentime) * 1000 as whentimes,DATE_FORMAT(whentime, '%H:%i') as wttime, DATE_FORMAT(whentime, '%Y-%m-%d %H:%i') as wtdt, ".
				"temperature, humidity, feelslike, rssi, airconon FROM sensibo WHERE uid='$uid' AND UNIX_TIMESTAMP(whentime) * 1000 >= $startTS AND ".
				"UNIX_TIMESTAMP(whentime) * 1000 <= $startTS + $period ORDER BY whentime ASC";
	}

	if($period == 2592000000)
	{
		$query = "SELECT * FROM ( SELECT @row := @row +1 AS rownum, UNIX_TIMESTAMP(whentime) * 1000 as whentimes,DATE_FORMAT(whentime, '%H:%i') as wttime,".
				"DATE_FORMAT(whentime, '%Y-%m-%d %H:%i') as wtdt, temperature, humidity, feelslike, rssi, airconon FROM ( SELECT @row :=0) r, sensibo ".
				"WHERE uid='$uid' AND UNIX_TIMESTAMP(whentime) * 1000 >= $startTS AND UNIX_TIMESTAMP(whentime) * 1000 <= $startTS + $period ORDER BY whentime ASC".
				") ranked WHERE rownum % 15 = 0";
	}

	if($period == 31536000000)
	{
		$query = "SELECT DATE_FORMAT(whentime, '%Y-%m-%d') as wtdate FROM sensibo WHERE uid='$uid' AND UNIX_TIMESTAMP(whentime) * 1000 >= $startTS AND UNIX_TIMESTAMP(whentime) * 1000 <= $startTS + $period ".
				"GROUP BY DATE_FORMAT(whentime, '%Y-%m-%d') ORDER BY whentime ASC";
		$res = mysqli_query($link, $query);
		while($row = mysqli_fetch_assoc($res))
		{
			$query1 = "SELECT count(uid) as c FROM sensibo WHERE uid='$uid' and whentime LIKE '${row['wtdate']}%'";
			$res1 = mysqli_query($link, $query1);
			$rc = mysqli_fetch_assoc($res1)['c'];
			mysqli_free_result($res1);
			for($i = 0; $i <= $rc; $i += 64)
			{
				$query1 = "SELECT row.whentimes, row.airconon, ROUND(AVG(row.temperature), 1) AS temperature, ROUND(AVG(row.humidity), 1) AS humidity, ROUND(AVG(row.feelslike), 1) AS feelslike, ".
						" AVG(row.rssi) AS rssi FROM (SELECT whentime, UNIX_TIMESTAMP(whentime) * 1000 as whentimes, airconon, temperature, humidity, feelslike, rssi FROM sensibo ".
						"WHERE uid='$uid' and whentime LIKE '${row['wtdate']}%' LIMIT $i, 64) row";
				$res1 = mysqli_query($link, $query1);
				$row1 = mysqli_fetch_assoc($res1);
				mysqli_free_result($res1);

				if(doubleval($row1['whentimes']) > 0)
				{
					$dataPoints1[] = array('x' => doubleval($row1['whentimes']), 'y' => floatval($row1['temperature']));
					$dataPoints2[] = array('x' => doubleval($row1['whentimes']), 'y' => intval($row1['humidity']));
					$dataPoints3[] = array('x' => doubleval($row1['whentimes']), 'y' => round(floatval($row1['feelslike']) * 10.0) / 10.0);
					$dataPoints4[] = array('x' => doubleval($row1['whentimes']), 'y' => intval($row1['rssi']));
				}
			}
		}

		mysqli_free_result($res);

		$rc = $wt = $cost = 0;
		$query = "SELECT FLOOR(UNIX_TIMESTAMP(whentime) / 86400) * 86400000 as whentime, sum(cost) as cost FROM sensibo ".
				"WHERE uid='$uid' AND UNIX_TIMESTAMP(whentime) * 1000 >= $startTS AND UNIX_TIMESTAMP(whentime) * 1000 <= $startTS + $period GROUP BY DATE_FORMAT(whentime, '%Y-%m-%d') ORDER BY whentime ASC";
		$res = mysqli_query($link, $query);
		while($row = mysqli_fetch_assoc($res))
		{
			if(++$rc == 7)
			{
				if(doubleval($row['whentime']) > 0)
					$dataPoints5[] = array('x' => $wt, 'y' => $cost);
				$rc = $wt = $cost = 0;
			} else {
				if($wt == 0)
				{
					$wt = doubleval($row['whentime']);
					$wt = mktime(0, 0, 0, date("m", $wt / 1000), date("d", $wt / 1000), date("Y", $wt / 1000)) * 1000;
				}
				$cost += floatval($row['cost']);
			}
		}

		if($wt > 0)
			$dataPoints5[] = array('x' => $wt, 'y' => $cost);

		mysqli_free_result($res);
	} else {
		$res = mysqli_query($link, $query);
		while($row = mysqli_fetch_assoc($res))
		{
			if($period == 86400000)
			{
				$query = "SELECT * FROM commands WHERE uid='$uid' AND TIMESTAMPDIFF(SECOND, whentime, '${row['whentime']}') > -90 AND TIMESTAMPDIFF(SECOND, whentime, '${row['whentime']}') < 0 LIMIT 1";
				$dres = mysqli_query($link, $query);
				if(mysqli_num_rows($dres) > 0)
				{
					while($drow = mysqli_fetch_assoc($dres))
					{
						$ac = "";
						if(stripos($drow['changes'], "'on'"))
							if($drow['airconon'] == 1)
								$ac = "on";
							else
								$ac = "off";

						if($ac == "on")
							$dataPoints1[] = array('x' => doubleval($row['whentimes']), 'y' => floatval($row['temperature']),
									'inindexLabel' => $ac, 'markerType' => 'cross',  'markerSize' =>  20, 'markerColor' => 'green');
						else if($ac == "off")
							$dataPoints1[] = array('x' => doubleval($row['whentimes']), 'y' => floatval($row['temperature']),
									'inindexLabel' => $ac, 'markerType' => 'cross',  'markerSize' =>  20, 'markerColor' => 'tomato');
					}
				} else
					$dataPoints1[] = array('x' => doubleval($row['whentimes']), 'y' => floatval($row['temperature']));
			} else
				$dataPoints1[] = array('x' => doubleval($row['whentimes']), 'y' => floatval($row['temperature']));

			$dataPoints2[] = array('x' => doubleval($row['whentimes']), 'y' => intval($row['humidity']));
			$dataPoints3[] = array('x' => doubleval($row['whentimes']), 'y' => round(floatval($row['feelslike']) * 10.0) / 10.0);
			$dataPoints4[] = array('x' => doubleval($row['whentimes']), 'y' => intval($row['rssi']));
		}

		mysqli_free_result($res);
	}

	$ac = "off";
	$query = "SELECT *, DATE_FORMAT(whentime, '%H:%i') as wttime, UNIX_TIMESTAMP(whentime) * 1000 as startTS FROM ".
				"sensibo WHERE uid='$uid' ORDER BY whentime DESC LIMIT 1";
	$res = mysqli_query($link, $query);
	if(mysqli_num_rows($res) > 0)
	{
		$row = mysqli_fetch_assoc($res);
		mysqli_free_result($res);

		$currtemp = $row['temperature'];
		$currhumid = $row['humidity'];
		$currtime = $row['wttime'];

		if($row['airconon'] == 1)
			$ac = "on";
	} else {
		$currtemp = 0.0;
		$currhumid = 0;
		$currtime = "00:00";
	}


	if($period == 2592000000)
	{
		$query = "SELECT FLOOR(UNIX_TIMESTAMP(whentime) / 3600) * 3600000 as whentime, sum(cost) as cost FROM sensibo WHERE uid='$uid' AND UNIX_TIMESTAMP(whentime) * 1000 >= $startTS AND UNIX_TIMESTAMP(whentime) * 1000 <= $startTS + $period GROUP BY DATE_FORMAT(whentime, '%Y-%m-%d') ORDER BY whentime ASC";
		$res = mysqli_query($link, $query);
		while($row = mysqli_fetch_assoc($res))
		{
			if(doubleval($row['whentime']) > 0)
				$dataPoints5[] = array('x' => doubleval($row['whentime']), 'y' => floatval($row['cost']));
		}

		mysqli_free_result($res);
	} else if($period != 31536000000) {
		$query = "SELECT FLOOR(UNIX_TIMESTAMP(whentime) / 3600) * 3600000 as whentime, sum(cost) as cost FROM sensibo WHERE uid='$uid' AND UNIX_TIMESTAMP(whentime) * 1000 >= $startTS AND UNIX_TIMESTAMP(whentime) * 1000 <= $startTS + $period GROUP BY DATE_FORMAT(whentime, '%Y-%m-%d %H') ORDER BY whentime ASC";
		$res = mysqli_query($link, $query);
		while($row = mysqli_fetch_assoc($res))
		{
			if(doubleval($row['whentime']) > 0)
				$dataPoints5[] = array('x' => doubleval($row['whentime']), 'y' => floatval($row['cost']));
		}

		mysqli_free_result($res);

	}

	$commands = '';

	if(isset($_SESSION['rw']) && $_SESSION['rw'] == true)
	{
		$commands .= "<li style='text-align:center'>";
		$commands .= "<img style='width:50px;' onClick='showSettings(); return false;' src='wand.png' />\n";
		$commands .= "<img style='width:50px;' onClick='settings(); return false;' src='settings.png' />\n";

		if($ac == "on")
			$commands .= "<img id='onoff' style='width:50px;' onClick='toggleAC(); return false;' src='on.png' />\n";
		else
			$commands .= "<img id='onoff' style='width:50px;' onClick='toggleAC(); return false;' src='off.png' />\n";

		$commands .= "<img style='width:50px;' onClick='logout(); return false;' src='exit.png' />\n";

		$commands .= "</li>\n";
	} else {
		$commands .= "<li style='text-align:center'>";
		$commands .= "<img style='width:40px;' onClick='logout(); return false;' src='exit.png' />\n";
		$commands .= "</li>\n";
	}

	$query = "SELECT uid,name FROM devices ORDER BY name";
	$res = mysqli_query($link, $query);
	if(mysqli_num_rows($res) > 1)
	{
		$commands .= "<li><label for='devices'>Choose a Device:</label>\n";
		$commands .= "<select name='devices' id='devices' onChange='changeAC(this.value); return false;'>\n";

		while($row = mysqli_fetch_assoc($res))
		{
			$commands .= "<option value='".$row['uid']."'";
			if($uid == $row['uid'])
				$commands .= " selected";
			$commands .= ">".$row['name']."</option>\n";
		}
		$commands .= "</select></li>\n";

		mysqli_free_result($res);
	}

	$commands .= "<li><label for='timePeriod'>Time Period:</label>\n";
	$commands .= "<select name='devices' id='timePeriod' onChange='changeTP(this.value); return false;'>\n";
	$commands .= "<option value='day'";
	if($period == 86400000)
		$commands .= " selected";
	$commands .= ">Day</option>";
	$commands .= "<option value='week'";
	if($period == 604800000)
		$commands .= " selected";
	$commands .= ">Week</option>";
	$commands .= "<option value='month'";
	if($period == 2592000000)
		$commands .= " selected";
	$commands .= ">Month</option>";
	$commands .= "<option value='year'";
	if($period == 31536000000)
		$commands .= " selected";
	$commands .= ">Year</option>";
	$commands .= "</select></li>\n";

	$commands .= "<li>&nbsp;</li>\n";

	$commands .= "<li style='text-align:center;'><u><b>Current Conditions</b></u></li>\n";
	$commands .= "<li><b>".$currtime."</b> -- ".$currtemp."°C, ".$currhumid."%</li>\n";

	$date = $lastdate = '';

	$query = "SELECT *, DATE_FORMAT(whentime, '%a %d %b %Y') as wtdate, DATE_FORMAT(whentime, '%H:%i') as wttime FROM ".
				"commands WHERE uid='$uid' AND changes!='' AND changes!='[]' ORDER BY whentime DESC";
	$res = mysqli_query($link, $query);
	while($row = mysqli_fetch_assoc($res))
	{
		$date = $row["wtdate"];
		$who = $row['who'];
		$who2 = getWho($row["reason"]);

		if($who == '')
			$who = $who2;
		else if($who != $who2)
			$who = $who." (".$who2.")";

		if($date != $lastdate)
		{
			$commands .= "<li>&nbsp;</li>\n";
			$commands .= "<li style='text-align:center;'><u><b>$date</b></u></li>\n";
			$lastdate = $date;
		}

		if(stripos($row['changes'], "'targetTemperature'"))
			$commands .= "<li><b>".$row["wttime"]."</b> -- $who set temperature to ".$row["targetTemperature"]."</li>\n";

		if(stripos($row['changes'], "'mode'"))
			$commands .= "<li><b>".$row["wttime"]."</b> -- $who set mode to ".$row["mode"]."</li>\n";

		if(stripos($row['changes'], "'fanLevel'"))
			$commands .= "<li><b>".$row["wttime"]."</b> -- $who set fan to ".$row["fanLevel"]."</li>\n";

		if(stripos($row['changes'], "'swing'"))
			$commands .= "<li><b>".$row["wttime"]."</b> -- $who set vertical swing to ".$row["swing"]."</li>\n";

		if(stripos($row['changes'], "'horizontalSwing'"))
			$commands .= "<li><b>".$row["wttime"]."</b> -- $who set horizontonalswing to ".$row["horizontalSwing"]."</li>\n";

		if(stripos($row['changes'], "'on'"))
		{
			if($row["airconon"] == 1)
				$commands .= "<li><b>".$row["wttime"]."</b> -- $who set AC on</li>\n";
			else
				$commands .= "<li><b>".$row["wttime"]."</b> -- $who set AC off</li>\n";
		}
	}

	mysqli_free_result($res);

	$data = array('uid' => $uid, 'dataPoints1' => $dataPoints1, 'dataPoints2' => $dataPoints2, 'dataPoints3' => $dataPoints3,
					'dataPoints4' => $dataPoints4, 'dataPoints5' => $dataPoints5, 'commands' => $commands,
					'currtime' => $currtime, 'startTS' => $startTS);
	echo json_encode(array('status' => 200, 'content' => $data));
