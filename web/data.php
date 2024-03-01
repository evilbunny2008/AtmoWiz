<?php
	$error = null;
	$showChart4 = false;
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

		$commandHeader = "";
		$commands = "<li style='color:red;text-align:center'>" . $error . "</li>\n";
		$commands .= "<li style='text-align:right'><a href='graphs.php?logout=1'>Log Out</a></li>\n";
		$data = array('uid' => '', 'dataPoints1' => array(), 'dataPoints2' => array(), 'dataPoints3' => array(), 'dataPoints4' => array(),
			'dataPoints5' => array(), 'dataPoints6' => array(), 'dataPoints7' => array(),
			'commandHeader' => $commandHeader, 'commands' => $commands, 'currtime' => date("H:i"), 'showChart4' => $showChart4);
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
	$dataPoints6 = array();
	$dataPoints7 = array();

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

	$humidityOrWatts = 'humidity';
	$query = "SELECT TIMESTAMPDIFF(SECOND, UTC_TIMESTAMP(), NOW()) as tzoffset";
	$res = mysqli_query($link, $query);
	$tzoffset = mysqli_fetch_assoc($res)['tzoffset'];

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
		$dataPoints6[] = array('x' => mktime(0, 0, 0, date("m", $startTS / 1000), date("d", $startTS / 1000), date("Y", $startTS / 1000)) * 1000, 'y' => null);
		$dataPoints7[] = array('x' => mktime(0, 0, 0, date("m", $startTS / 1000), date("d", $startTS / 1000), date("Y", $startTS / 1000)) * 1000, 'y' => null);
	} else {
		$dataPoints1[] = array('x' => doubleval($startTS), 'y' => null);
		$dataPoints2[] = array('x' => doubleval($startTS), 'y' => null);
		$dataPoints3[] = array('x' => doubleval($startTS), 'y' => null);
		$dataPoints4[] = array('x' => doubleval($startTS), 'y' => null);
		$dataPoints5[] = array('x' => doubleval($startTS), 'y' => null);
		$dataPoints6[] = array('x' => doubleval($startTS), 'y' => null);
		$dataPoints7[] = array('x' => doubleval($startTS), 'y' => null);
	}

	$query = "";

	if($period == 86400000)
	{
		$query = "SELECT whentime, UNIX_TIMESTAMP(whentime) * 1000 as whentimes, DATE_FORMAT(whentime, '%H:%i') as wttime, DATE_FORMAT(whentime, '%Y-%m-%d %H:%i') as wtdt, ".
				"temperature, humidity, feelslike, rssi, airconon, watts FROM sensibo ".
				"WHERE uid='$uid' AND UNIX_TIMESTAMP(whentime) * 1000 >= $startTS AND UNIX_TIMESTAMP(whentime) * 1000 <= $startTS + $period ORDER BY whentime ASC";
	}

	if($period == 604800000)
	{
		$query = "SELECT UNIX_TIMESTAMP(whentime) * 1000 as whentimes,DATE_FORMAT(whentime, '%H:%i') as wttime, DATE_FORMAT(whentime, '%Y-%m-%d %H:%i') as wtdt, ".
				"temperature, humidity, feelslike, rssi, airconon, watts FROM sensibo WHERE uid='$uid' AND UNIX_TIMESTAMP(whentime) * 1000 >= $startTS AND ".
				"UNIX_TIMESTAMP(whentime) * 1000 <= $startTS + $period ORDER BY whentime ASC";
	}

	if($period == 2592000000)
	{
		$query = "SELECT * FROM ( SELECT @row := @row +1 AS rownum, UNIX_TIMESTAMP(whentime) * 1000 as whentimes,DATE_FORMAT(whentime, '%H:%i') as wttime,".
				"DATE_FORMAT(whentime, '%Y-%m-%d %H:%i') as wtdt, temperature, humidity, feelslike, rssi, airconon, watts FROM ( SELECT @row :=0) r, sensibo ".
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
			$dataPoints6 = $arr['4'];
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
					$redis->expire(md5($query1), 86400);
				}

				for($i = 0; $i <= $rc; $i += 64)
				{
					$query2 = "SELECT row.whentimes, row.airconon, ROUND(AVG(row.temperature), 1) AS temperature, ROUND(AVG(row.humidity), 1) AS humidity, ROUND(AVG(row.feelslike), 1) AS feelslike, ".
							" AVG(row.rssi) AS rssi, ROUND(AVG(row.watts), 1) as watts FROM (SELECT whentime, UNIX_TIMESTAMP(whentime) * 1000 as whentimes, airconon, temperature, humidity, ".
							"feelslike, rssi, watts FROM sensibo WHERE uid='$uid' and whentime LIKE '${row['wtdate']}%' LIMIT $i, 64) row";
					if($redis->exists(md5($query2)))
					{
						$row2 = unserialize($redis->get(md5($query2)));
					} else {
						$res2 = mysqli_query($link, $query2);
						$row2 = mysqli_fetch_assoc($res2);
						mysqli_free_result($res2);
						$redis->set(md5($query2), serialize($row2));
						$redis->expire(md5($query2), 86400);
					}

					if($row2 !== False && doubleval($row2['whentimes']) > 0)
					{
						$dataPoints1[] = array('x' => doubleval($row2['whentimes']), 'y' => floatval($row2['temperature']));
						$dataPoints2[] = array('x' => doubleval($row2['whentimes']), 'y' => intval($row2[$humidityOrWatts]));
						$dataPoints3[] = array('x' => doubleval($row2['whentimes']), 'y' => round(floatval($row2['feelslike']) * 10.0) / 10.0);
						$dataPoints4[] = array('x' => doubleval($row2['whentimes']), 'y' => intval($row2['rssi']));
						$dataPoints6[] = array('x' => doubleval($row2['whentimes']), 'y' => intval($row2['watts']));
					}
				}
			}

			mysqli_free_result($res);

			$redis->set(md5($query), serialize(array($dataPoints1, $dataPoints2, $dataPoints3, $dataPoints4, $dataPoints6)));
			$redis->expire(md5($query), 86400);
		}

		$query = "SELECT UNIX_TIMESTAMP(whentime) as whentime, round(sum(cost), 2) as cost FROM sensibo WHERE uid='$uid' AND UNIX_TIMESTAMP(whentime) * 1000 >= $startTS AND ".
				"UNIX_TIMESTAMP(whentime) * 1000 <= $startTS + $period GROUP BY DATE_FORMAT(whentime, '%Y-%v') ORDER BY whentime ASC";
		if($redis->exists(md5($query)))
		{
			$dataPoints5 = unserialize($redis->get(md5($query)));
		} else {
			$res = mysqli_query($link, $query);
			while($row = mysqli_fetch_assoc($res))
			{
				if($row !== False && doubleval($row['whentime']) > 0)
				{
					$wt = floor(doubleval($row['whentime']) / 604800) * 604800000;
					$wt = mktime(0, 0, 0, date("m", $wt / 1000), date("d", $wt / 1000), date("Y", $wt / 1000)) * 1000;
					$dataPoints5[] = array('x' => $wt, 'y' => floatval($row['cost']));
				}
			}

			$dataPoints5[] = array('x' => $startTS + $period, 'y' => null);

			mysqli_free_result($res);
			$redis->set(md5($query), serialize($dataPoints5));
			$redis->expire(md5($query), 86400);
		}

		$query = "SELECT UNIX_TIMESTAMP(whentime) * 1000 as whentimes, temperature FROM weather WHERE UNIX_TIMESTAMP(whentime) * 1000 >= $startTS AND UNIX_TIMESTAMP(whentime) * 1000 <= $startTS + $period ORDER BY whentime ASC";
		if($redis->exists(md5($query)))
		{
			$dataPoints7 = unserialize($redis->get(md5($query)));
		} else {
			$res = mysqli_query($link, $query);
			while($row = mysqli_fetch_assoc($res))
			{
				$dataPoints7[] = array('x' => doubleval($row['whentimes']), 'y' => floatval($row['temperature']));
			}

			mysqli_free_result($res);
			$redis->set(md5($query), serialize($dataPoints7));
			$redis->expire(md5($query), 86400);
		}
	} else {
		if($redis->exists(md5($query)))
		{
			$arr = unserialize($redis->get(md5($query)));
			$dataPoints1 = $arr['0'];
			$dataPoints2 = $arr['1'];
			$dataPoints3 = $arr['2'];
			$dataPoints4 = $arr['3'];
			$dataPoints6 = $arr['4'];
		} else {
			$res = mysqli_query($link, $query);
			while($row = mysqli_fetch_assoc($res))
			{
				if($period == 86400000)
				{
					$query1 = "SELECT * FROM commands WHERE uid='$uid' AND TIMESTAMPDIFF(SECOND, whentime, '${row['whentime']}') > -90 AND TIMESTAMPDIFF(SECOND, whentime, '${row['whentime']}') <= 2 AND changes LIKE \"%'on'%\" LIMIT 1";
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

				$dataPoints2[] = array('x' => doubleval($row['whentimes']), 'y' => intval($row[$humidityOrWatts]));
				$dataPoints3[] = array('x' => doubleval($row['whentimes']), 'y' => round(floatval($row['feelslike']) * 10.0) / 10.0);
				$dataPoints4[] = array('x' => doubleval($row['whentimes']), 'y' => intval($row['rssi']));
				$dataPoints6[] = array('x' => doubleval($row['whentimes']), 'y' => intval($row['watts']));
			}

			mysqli_free_result($res);

			$redis->set(md5($query), serialize(array($dataPoints1, $dataPoints2, $dataPoints3, $dataPoints4, $dataPoints6)));
			$redis->expire(md5($query), 86400);
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
		$query = "SELECT UNIX_TIMESTAMP(whentime) as whentime, sum(cost) as cost FROM sensibo WHERE uid='$uid' AND UNIX_TIMESTAMP(whentime) * 1000 >= $startTS AND ".
				"UNIX_TIMESTAMP(whentime) * 1000 <= $startTS + $period GROUP BY DATE_FORMAT(whentime, '%Y-%m-%d') ORDER BY whentime ASC LIMIT 500";
		if($redis->exists(md5($query)))
		{
			$dataPoints5 = unserialize($redis->get(md5($query)));
		} else {
			$res = mysqli_query($link, $query);
			while($row = mysqli_fetch_assoc($res))
			{
				$wt = doubleval($row['whentime']);
				$wt = mktime(0, 0, 0, date("m", $wt), date("d", $wt), date("Y", $wt)) * 1000;
				if($wt > 0)
					$dataPoints5[] = array('x' => $wt, 'y' => round(floatval($row['cost']), 2));
			}

			mysqli_free_result($res);
			$redis->set(md5($query), serialize($dataPoints5));
			$redis->expire(md5($query), 86400);
		}

		$query = "SELECT UNIX_TIMESTAMP(whentime) * 1000 as whentimes, temperature FROM weather WHERE UNIX_TIMESTAMP(whentime) * 1000 >= $startTS AND UNIX_TIMESTAMP(whentime) * 1000 <= $startTS + $period ORDER BY whentime ASC";
		if($redis->exists(md5($query)))
		{
			$dataPoints7 = unserialize($redis->get(md5($query)));
		} else {
			$res = mysqli_query($link, $query);
			while($row = mysqli_fetch_assoc($res))
			{
				$dataPoints7[] = array('x' => doubleval($row['whentimes']), 'y' => floatval($row['temperature']));
			}

			mysqli_free_result($res);
			$redis->set(md5($query), serialize($dataPoints7));
			$redis->expire(md5($query), 86400);
		}
	} else if($period != 31536000000) {
		$query = "SELECT FLOOR(UNIX_TIMESTAMP(whentime) / 3600) * 3600000 as whentime, sum(cost) as cost FROM sensibo WHERE uid='$uid' AND ".
				"UNIX_TIMESTAMP(whentime) * 1000 >= floor($startTS / 3600000) * 3600000 + 3600000 AND ".
				"UNIX_TIMESTAMP(whentime) * 1000 <= $startTS + $period + 3600000 GROUP BY DATE_FORMAT(whentime, '%Y-%m-%d %H') ORDER BY whentime ASC LIMIT 500";
		if($redis->exists(md5($query)))
		{
			$dataPoints5 = unserialize($redis->get(md5($query)));
		} else {
			$res = mysqli_query($link, $query);
			while($row = mysqli_fetch_assoc($res))
			{
				if($row !== False && doubleval($row['whentime']) > 0)
				{
					$wt = doubleval($row['whentime']);
					$dataPoints5[] = array('x' => $wt, 'y' => round(floatval($row['cost']), 2));
				}
			}

			mysqli_free_result($res);

//			$dataPoints5[] = array('x' => doubleval(round($startTS / $period) * $period + $period), 'y' => null);
			$redis->set(md5($query), serialize($dataPoints5));
			$redis->expire(md5($query), 86400);
		}

		$query = "SELECT UNIX_TIMESTAMP(whentime) * 1000 as whentimes, temperature FROM weather WHERE UNIX_TIMESTAMP(whentime) * 1000 >= $startTS AND UNIX_TIMESTAMP(whentime) * 1000 <= $startTS + $period ORDER BY whentime ASC";
		if($redis->exists(md5($query)))
		{
			$dataPoints7 = unserialize($redis->get(md5($query)));
		} else {
			$res = mysqli_query($link, $query);
			while($row = mysqli_fetch_assoc($res))
			{
				$dataPoints7[] = array('x' => doubleval($row['whentimes']), 'y' => floatval($row['temperature']));
			}

			mysqli_free_result($res);
			$redis->set(md5($query), serialize($dataPoints7));
			$redis->expire(md5($query), 86400);
		}
	}

	$commandHeader = '';

	if(isset($_SESSION['rw']) && $_SESSION['rw'] == true)
	{
		$commandHeader .= "<ul id='CommandHeader'>\n";
		$commandHeader .= "<li style='text-align:center'>\n";
		$commandHeader .= "<img style='width:40px;' onClick='showTimers(); return false;' src='assets/hourglass.png' title='Timer' class='card-demo1' />\n";
		$commandHeader .= "<img style='width:40px;' onClick='showSettings(); return false;' src='assets/wand.png' title='Show Climate Settings' class='card-demo2' />\n";
		$commandHeader .= "<img style='width:40px;' onClick='showTimeSettings(); return false;' src='assets/watch.png' title='Show Time Based Settings' class='card-demo3' />\n";
		$commandHeader .= "<img style='width:40px;' onClick='settings(); return false;' src='assets/settings.png' title='Show AirCon Settings' class='card-demo4' />\n";
		$commandHeader .= "</li>\n";

		$commandHeader .= "<li style='text-align:center'>";
		if($ac == "on")
			$commandHeader .= "<img id='onoff' style='width:40px;' onClick='toggleAC(); return false;' src='assets/on.png' title='Turn AirCon Off' class='card-demo5' />\n";
		else
			$commandHeader .= "<img id='onoff' style='width:40px;' onClick='toggleAC(); return false;' src='assets/off.png' title='Turn AirCon On' class='card-demo5' />\n";

		$commandHeader .= "<img style='width:40px;' onClick='showDay(\"".(time() * 1000 - 86400000)."\"); return false;' src='assets/tick.png' title='Jump to Now' class='card-demo6' />\n";
		$commandHeader .= "<img style='width:40px;' onClick='logout(); return false;' src='assets/exit.png' title='Logout' class='card-demo7' />\n";
		$commandHeader .= "<img style='width:40px;' onClick='help(); return false;' src='assets/question-mark.png' title='Get Help' class='card-demo8' />\n";

		$commandHeader .= "</li>\n";
	} else {
		$commandHeader .= "<ul id='CommandHeader'>\n";
		$commandHeader .= "<li style='text-align:center'>";
		$commandHeader .= "<img style='width:40px;' onClick='showDay(\"".(time() * 1000 - 86400000)."\"); return false;' src='assets/tick.png' title='Jump to Now' class='card-demo6' />\n";
		$commandHeader .= "<img style='width:40px;' onClick='logout(); return false;' src='assets/exit.png' title='Logout' class='card-demo7' />\n";
		$commandHeader .= "<img style='width:40px;' onClick='help(); return false;' src='assets/question-mark.png' title='Get Help' class='card-demo8' />\n";
		$commandHeader .= "</li>\n";
	}

	$query = "SELECT uid,name FROM devices ORDER BY name";
	$res = mysqli_query($link, $query);
	if(mysqli_num_rows($res) > 1)
	{
		$commandHeader .= "<li><label for='devices'>Choose a Device:</label>\n";
		$commandHeader .= "<select name='devices' id='devices' onChange='changeAC(this.value); return false;'>\n";

		while($row = mysqli_fetch_assoc($res))
		{
			$commandHeader .= "<option value='".$row['uid']."'";
			if($uid == $row['uid'])
				$commandHeader .= " selected";
		$commandHeader .= ">".$row['name']."</option>\n";
		}
		$commandHeader .= "</select></li>\n";

		mysqli_free_result($res);
	}

	$commandHeader .= "<li class='card-demo9'><label for='timePeriod'>Time Period:</label>\n";
	$commandHeader .= "<select name='timePeriod' id='timePeriod' onChange='changeTP(this.value); return false;'>\n";
	$commandHeader .= "<option value='day'";
	if($period == 86400000)
		$commandHeader .= " selected";
	$commandHeader .= ">Day</option>";
	$commandHeader .= "<option value='week'";
	if($period == 604800000)
		$commandHeader .= " selected";
	$commandHeader .= ">Week</option>";
	$commandHeader .= "<option value='month'";
	if($period == 2592000000)
		$commandHeader .= " selected";
	$commandHeader .= ">Month</option>";
	$commandHeader .= "<option value='year'";
	if($period == 31536000000)
		$commandHeader .= " selected";
	$commandHeader .= ">Year</option>";
	$commandHeader .= "</select></li>\n";

	$commandHeader .= "<li>&nbsp;</li>\n";

	$commandHeader .= "<li class='card-demo10' style='text-align:center;'><u><b>Current Conditions</b></u></li>\n";
	$commandHeader .= "<li class='card-demo10'><b>".$currtime."</b> -- ".$currtemp."°C, ".$currhumid."%</li>\n";
	$commandHeader .= "<li class='card-demo10'>&nbsp;</li>\n";

	$query = "SELECT *, DATE_FORMAT(whentime, '%H:%i') as wttime FROM weather ORDER BY whentime DESC LIMIT 1";
	$row = mysqli_fetch_assoc(mysqli_query($link, $query));
	if($row !== False)
	{
		$commandHeader .= "<li style='text-align:center;' class='card-demo11'><u><b>Closest Weather Station</b></u></li>\n";
		if($row['pressure'] >= 800)
			$pressure = $row['pressure'] . "hPa";
		else
			$pressure = $row['pressure'] . "in";

		$commandHeader .= "<li class='card-demo11'><b>".$row['wttime']."</b> -- ".$row['temperature']."°C, ".$row['humidity']."%, ".$pressure;

		if($row['aqi'] != -1)
			$commandHeader .= ", ".$row['aqi']." AQI</li>\n";
	}

	$query = "SELECT *, ROUND(UNIX_TIMESTAMP(whentime) / 86400) * 86400 as wtsec, DATE_FORMAT(whentime, '%a %d %b %Y') as wtdate, DATE_FORMAT(whentime, '%H:%i') as wttime FROM ".
				"commands WHERE uid='$uid' AND changes!='' AND changes!='[]' ORDER BY whentime DESC";
	$date = $lastdate = '';

	$commandHeader .= "</ul>\n";
	$commands = "<ul id='commands'>\n";

	$res = mysqli_query($link, $query);
	while($row = mysqli_fetch_assoc($res))
	{
		$date = $row["wtdate"];
		$wtsec = mktime(0, 0, 0, date("m", $row["wtsec"]), date("d", $row["wtsec"]), date("Y", $row["wtsec"])) * 1000;

		$who = $row['who'];
		$who2 = getWho($row["reason"]);

		if($who == '')
			$who = $who2;
		else if($who != $who2)
			$who = $who." (".$who2.")";

		if($date != $lastdate)
		{
			if($commands != "<ul id='commands'>\n")
				$commands .= "<li>&nbsp;</li>\n";

			$commands .= "<li style='text-align:center;cursor:pointer;' onClick='showDay(\"$wtsec\"); return false;'><u><b>$date</b></u></li>\n";
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

	$commands .= "</ul>\n";

	mysqli_free_result($res);

	for($i = 0; $i < sizeof($dataPoints6); $i++)
	{
		if($dataPoints6[$i]['y'] > 0)
		{
			$showChart4 = True;
			break;
		}
	}

	$data = array('uid' => $uid, 'dataPoints1' => $dataPoints1, 'dataPoints2' => $dataPoints2, 'dataPoints3' => $dataPoints3, 'dataPoints4' => $dataPoints4,
					'dataPoints5' => $dataPoints5, 'dataPoints6' => $dataPoints6, 'dataPoints7' => $dataPoints7,
					'commandHeader' => $commandHeader, 'commands' => $commands, 'currtime' => $currtime, 'startTS' => $startTS, 'showChart4' => $showChart4);
	echo json_encode(array('status' => 200, 'content' => $data));
