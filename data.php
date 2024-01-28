<?php
	require_once('mariadb.php');

	$dataPoints1 = array();
	$dataPoints2 = array();
	$dataPoints3 = array();
	$dataPoints4 = array();

	$airconon = '';
	$query = "SELECT uid,UNIX_TIMESTAMP(whentime) * 1000 as whentime,DATE_FORMAT(whentime, '%H:%i') as wttime,temperature,humidity,feelslike,rssi,airconon FROM sensibo WHERE whentime >= now() - INTERVAL 2 DAY ORDER BY whentime ASC";
	$res = mysqli_query($link, $query);
	while($row = mysqli_fetch_assoc($res))
	{
		if($row['airconon'] != $airconon && $airconon != '')
		{
			$airconon = $row['airconon'];

			$ac = "off";
			if($airconon == 1)
				$ac = "on";

			if($ac == "on")
				$dataPoints1[] = array('x' => intval($row['whentime']), 'y' => floatval($row['temperature']), 'inindexLabel' => $ac, 'markerType' => 'cross',  'markerSize' =>  20,'markerColor' => 'green');
			else
				$dataPoints1[] = array('x' => intval($row['whentime']), 'y' => floatval($row['temperature']), 'inindexLabel' => $ac, 'markerType' => 'cross',  'markerSize' =>  20,'markerColor' => 'tomato');
		} else {
			$dataPoints1[] = array('x' => intval($row['whentime']), 'y' => floatval($row['temperature']));
			$airconon = $row['airconon'];
		}

		$dataPoints2[] = array('x' => intval($row['whentime']), 'y' => floatval($row['humidity']));
		$dataPoints3[] = array('x' => intval($row['whentime']), 'y' => floatval($row['feelslike']));
		$dataPoints4[] = array('x' => intval($row['whentime']), 'y' => floatval($row['rssi']));

		$currtemp = $row['temperature'];
		$currhumid = $row['humidity'];
		$currfl = $row['feelslike'];
		$curruid = $row['uid'];
		$currtime = $row['wttime'];
	}

	$negac = "on";
	if($ac == "on")
		$negac = "off";

	$line1 = $currtime." -- ".$currtemp."°C, ".$currhumid."% <a href='#' onClick='toggleAC(); return false;'>Turn AC $negac</a>";

	$lastdate = '';
	$commands = "<li style='text-align:center;'><u><b>Current Conditions</b></u></li>\n";
	$commands .= "<li>$line1</li>\n";
	$query = "SELECT *, DATE_FORMAT(whentime, '%a %d %b %Y') as wtdate, DATE_FORMAT(whentime, '%H:%i') as wttime FROM commands ORDER BY whentime DESC";
	$dres = mysqli_query($link, $query);
	while($drow = mysqli_fetch_assoc($dres))
	{

		$date = $drow['wtdate'];
		if($date != $lastdate)
		{
			$commands .= "<li style='text-align:center;'><u><b>$date</b></u></li>\n";
			$lastdate = $date;
		}

	        $commands .= "<li>".$drow['wttime'].' -- ';
		if($drow['reason'] == "ExternalIrCommand")
			$commands .= "Remote Control turned AC ";
		else if($drow['reason'] == "UserAPI")
			$commands .= "API turned AC ";
		else
			$commands .= "Unknown turned AC ";
		if($drow['airconon'])
			$commands .= "on";
		else
			$commands .= "off";
		$commands .= "</li>\n";
	}

	$data = array('uid' => $curruid, 'ac' => $ac, 'dataPoints1' => $dataPoints1, 'dataPoints2' => $dataPoints2, 'dataPoints3' => $dataPoints3, 'dataPoints4' => $dataPoints4, 'commands' => $commands, 'line1' => $line1);
	echo json_encode($data);
