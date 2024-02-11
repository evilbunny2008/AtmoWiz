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
						'dataPoints4' => array(), 'dataPoints5' => array(), 'commands' => $commands, 'currtime' => date("H:i"));
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

	if($period == 31536000000 || $period == 2592000000)
	{
		$dataPoints1[] = array('x' => mktime(0, 0, 0, date("m", $startTS / 1000), date("d", $startTS / 1000), date("Y", $startTS / 1000)) * 1000, 'y' => null);
		$dataPoints2[] = array('x' => mktime(0, 0, 0, date("m", $startTS / 1000), date("d", $startTS / 1000), date("Y", $startTS / 1000)) * 1000, 'y' => null);
		$dataPoints3[] = array('x' => mktime(0, 0, 0, date("m", $startTS / 1000), date("d", $startTS / 1000), date("Y", $startTS / 1000)) * 1000, 'y' => null);
		$dataPoints4[] = array('x' => mktime(0, 0, 0, date("m", $startTS / 1000), date("d", $startTS / 1000), date("Y", $startTS / 1000)) * 1000, 'y' => null);
		$dataPoints5[] = array('x' => mktime(0, 0, 0, date("m", $startTS / 1000), date("d", $startTS / 1000), date("Y", $startTS / 1000)) * 1000, 'y' => null);
	} else {
		$dataPoints1[] = array('x' => doubleval($startTS), 'y' => null);
		$dataPoints2[] = array('x' => doubleval($startTS), 'y' => null);
		$dataPoints3[] = array('x' => doubleval($startTS), 'y' => null);
		$dataPoints4[] = array('x' => doubleval($startTS), 'y' => null);
		$dataPoints5[] = array('x' => doubleval($startTS), 'y' => null);
	}

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
		$query = "SELECT DATE_FORMAT(whentime, '%Y-%m-%d') as wtdate, count(uid) as c FROM sensibo WHERE uid='$uid' AND UNIX_TIMESTAMP(whentime) * 1000 >= $startTS AND UNIX_TIMESTAMP(whentime) * 1000 <= $startTS + $period ".
				"GROUP BY DATE_FORMAT(whentime, '%Y-%m-%d') ORDER BY whentime ASC";
		if($redis->exists(md5($query)))
		{
			$arr = unserialize($redis->get(md5($query)));
			$dataPoints1 = $arr['0'];
			$dataPoints2 = $arr['1'];
			$dataPoints3 = $arr['2'];
			$dataPoints4 = $arr['3'];
		} else {
			$res = mysqli_query($link, $query);
			while($row = mysqli_fetch_assoc($res))
			{
				$query1 = "SELECT count(uid) as c FROM sensibo WHERE uid='$uid' and whentime LIKE '${row['wtdate']}%'";
				if($redis->exists(md5($query1)))
				{
					$rc = $redis->get(md5($query1));
				} else {
					$res1 = mysqli_query($link, $query1);
					$rc = mysqli_fetch_assoc($res1)['c'];
					$redis->set(md5($query1), $rc);
				}

				for($i = 0; $i <= $rc; $i += 64)
				{
					$query2 = "SELECT row.whentimes, row.airconon, ROUND(AVG(row.temperature), 1) AS temperature, ROUND(AVG(row.humidity), 1) AS humidity, ROUND(AVG(row.feelslike), 1) AS feelslike, ".
							" AVG(row.rssi) AS rssi FROM (SELECT whentime, UNIX_TIMESTAMP(whentime) * 1000 as whentimes, airconon, temperature, humidity, feelslike, rssi FROM sensibo ".
							"WHERE uid='$uid' and whentime LIKE '${row['wtdate']}%' LIMIT $i, 64) row";
					if($redis->exists(md5($query2)))
					{
						$row2 = unserialize($redis->get(md5($query2)));
					} else {
						$res2 = mysqli_query($link, $query2);
						$row2 = mysqli_fetch_assoc($res2);
						mysqli_free_result($res2);
						$redis->set(md5($query2), serialize($row2));
					}

					if($row2 !== False && doubleval($row2['whentimes']) > 0)
					{
						$dataPoints1[] = array('x' => doubleval($row2['whentimes']), 'y' => floatval($row2['temperature']));
						$dataPoints2[] = array('x' => doubleval($row2['whentimes']), 'y' => intval($row2['humidity']));
						$dataPoints3[] = array('x' => doubleval($row2['whentimes']), 'y' => round(floatval($row2['feelslike']) * 10.0) / 10.0);
						$dataPoints4[] = array('x' => doubleval($row2['whentimes']), 'y' => intval($row2['rssi']));
					}
				}
			}

			mysqli_free_result($res);

			$redis->set(md5($query), serialize(array($dataPoints1, $dataPoints2, $dataPoints3, $dataPoints4)));
		}

		$rc = $wt = $cost = 0;
		$query = "SELECT FLOOR(UNIX_TIMESTAMP(whentime) / 86400) * 86400000 as whentime, sum(cost) as cost FROM sensibo ".
				"WHERE uid='$uid' AND UNIX_TIMESTAMP(whentime) * 1000 >= $startTS AND UNIX_TIMESTAMP(whentime) * 1000 <= $startTS + $period GROUP BY DATE_FORMAT(whentime, '%Y-%m-%d') ORDER BY whentime ASC";
		if($redis->exists(md5($query)))
		{
			$dataPoints5 = unserialize($redis->get(md5($query)));
		} else {
			$res = mysqli_query($link, $query);
			while($row = mysqli_fetch_assoc($res))
			{
				if(++$rc == 7)
				{
					if($wt > 0)
						$dataPoints5[] = array('x' => $wt, 'y' => $cost);
					$rc = $wt = $cost = 0;
				} else {
					if($row !== False && doubleval($row['whentime']) > 0)
					{
						if($wt == 0)
						{
							$wt = doubleval($row['whentime']);
							$wt = mktime(0, 0, 0, date("m", $wt / 1000), date("d", $wt / 1000), date("Y", $wt / 1000)) * 1000;
						}

						$cost += floatval($row['cost']);
					}
				}
			}

			if($wt > 0)
				$dataPoints5[] = array('x' => $wt, 'y' => $cost);

			$dataPoints5[] = array('x' => $startTS + $period, 'y' => null);

			mysqli_free_result($res);
			$redis->set(md5($query), serialize($dataPoints5));
		}
	} else {
		if($redis->exists(md5($query)))
		{
			$arr = unserialize($redis->get(md5($query)));
			$dataPoints1 = $arr['0'];
			$dataPoints2 = $arr['1'];
			$dataPoints3 = $arr['2'];
			$dataPoints4 = $arr['3'];
		} else {
			$res = mysqli_query($link, $query);
			while($row = mysqli_fetch_assoc($res))
			{
				if($period == 86400000)
				{
					$query1 = "SELECT * FROM commands WHERE uid='$uid' AND TIMESTAMPDIFF(SECOND, whentime, '${row['whentime']}') > -90 AND TIMESTAMPDIFF(SECOND, whentime, '${row['whentime']}') < 0 LIMIT 1";
					$dres = mysqli_query($link, $query1);
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

			$redis->set(md5($query), serialize(array($dataPoints1, $dataPoints2, $dataPoints3, $dataPoints4)));
		}
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
		$query = "SELECT FLOOR(UNIX_TIMESTAMP(whentime) / 3600) * 3600000 as whentime, sum(cost) as cost FROM sensibo WHERE uid='$uid' AND UNIX_TIMESTAMP(whentime) * 1000 >= $startTS AND ".
				"UNIX_TIMESTAMP(whentime) * 1000 <= $startTS + $period GROUP BY DATE_FORMAT(whentime, '%Y-%m-%d') ORDER BY whentime ASC";
		if($redis->exists(md5($query)))
		{
			$dataPoints5 = unserialize($redis->get(md5($query)));
		} else {
			$res = mysqli_query($link, $query);
			while($row = mysqli_fetch_assoc($res))
			{
				$wt = round(doubleval($row['whentime']) / 1000);
				$wt = mktime(0, 0, 0, date("m", $wt), date("d", $wt), date("Y", $wt)) * 1000;
				if($wt > 0)
					$dataPoints5[] = array('x' => $wt, 'y' => floatval($row['cost']));
			}

			mysqli_free_result($res);
			$redis->set(md5($query), serialize($dataPoints5));
		}
	} else if($period != 31536000000) {
		$query = "SELECT FLOOR(UNIX_TIMESTAMP(whentime) / 3600) * 3600000 as whentime, sum(cost) as cost FROM sensibo WHERE uid='$uid' AND ".
				"UNIX_TIMESTAMP(whentime) * 1000 >= $startTS + 3600000 AND UNIX_TIMESTAMP(whentime) * 1000 <= $startTS + $period + 3600000 GROUP BY DATE_FORMAT(whentime, '%Y-%m-%d %H') ORDER BY whentime ASC";
		if($redis->exists(md5($query)))
		{
			$dataPoints5 = unserialize($redis->get(md5($query)));
		} else {
			$res = mysqli_query($link, $query);
			while($row = mysqli_fetch_assoc($res))
			{
				if($row !== False && doubleval($row['whentime']) > 0)
					$dataPoints5[] = array('x' => doubleval($row['whentime']), 'y' => floatval($row['cost']));
			}

			mysqli_free_result($res);

			$dataPoints5[] = array('x' => doubleval($startTS + $period), 'y' => null);
			$redis->set(md5($query), serialize($dataPoints5));
		}
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
	$commands .= "<li>&nbsp;</li>\n";

	$query = "SELECT *, DATE_FORMAT(whentime, '%H:%i') as wttime FROM weather ORDER BY whentime DESC LIMIT 1";
	$row = mysqlI_fetch_assoc(mysqli_query($link, $query));
	if($row !== False)
	{
		$commands .= "<li style='text-align:center;'><u><b>Closest Weather Station</b></u></li>\n";
		if($row['pressure'] >= 900)
			$pressure = $row['pressure'] . "hPa";
		else
			$pressure = $row['pressure'] . "in";

		$commands .= "<li><b>".$row['wttime']."</b> -- ".$row['temperature']."°C, ".$row['humidity']."%, ".$pressure.", ".$row['aq']." AQI</li>\n";
	}

	$query = "SELECT *, DATE_FORMAT(whentime, '%a %d %b %Y') as wtdate, DATE_FORMAT(whentime, '%H:%i') as wttime FROM ".
				"commands WHERE uid='$uid' AND changes!='' AND changes!='[]' ORDER BY whentime DESC";
	$date = $lastdate = '';

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
