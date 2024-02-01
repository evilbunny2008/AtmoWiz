#!/usr/bin/php
<?php
	// https://www.currentforce.com.au/compare-air-conditioners/
	// https://airconwa.com.au/air-conditioning-cost-to-run/
	// SELECT uid, sum(cost) as cost, count(uid) * 90 / 3600 as `count` FROM `sensibo` WHERE whentime LIKE '2024-02-01%' group by uid

	$EER = 3.38;
	$COP = 3.36;

	$airconWcool = 9.0;
	$airconWheat = 10.4;

	$peakkwHr = 0.389 * 1.1;
	$shoulderkwHr = 0.3318 * 1.1;
	$offpeakkwHr = 0.2518 * 1.1;

	$error = null;

	require_once('mariadb.php');

	$query = "SELECT whentime,uid,DAYOFWEEK(whentime) as dow,HOUR(whentime) as hod,mode FROM sensibo WHERE airconon=1 AND cost=0.0 AND mode='cool'";
	$res = mysqli_query($link, $query);
	while($row = mysqli_fetch_assoc($res))
	{
		if($row['dow'] == 1 || $row['dow'] == 7)
		{
			$cost = $airconWcool / $EER * $offpeakkwHr * 90.0 / 3600.0;
		} else {
			if($row['hod'] >= 0 && $row['hod'] < 7)
				$cost = $airconWcool / $EER * $offpeakkwHr * 90.0 / 3600.0;
			if($row['hod'] >= 7 && $row['hod'] < 9)
				$cost = $airconWcool / $EER * $peakkwHr * 90.0 / 3600.0;
			if($row['hod'] >= 9 && $row['hod'] < 17)
				$cost = $airconWcool / $EER * $shoulderkwHr * 90.0 / 3600.0;
			if($row['hod'] >= 17 && $row['hod'] < 20)
				$cost = $airconWcool / $EER * $peakkwHr * 90.0 / 3600.0;
			if($row['hod'] >= 20 && $row['hod'] < 22)
				$cost = $airconWcool / $EER * $shoulderkwHr * 90.0 / 3600.0;
			if($row['hod'] >= 22)
				$cost = $airconWcool / $EER * $offpeakkwHr * 90.0 / 3600.0;
		}

		$query = "UPDATE sensibo SET cost=$cost WHERE whentime='${row['whentime']}' AND uid='${row['uid']}'";
		mysqli_query($link, $query);
		echo $query."\n";
	}

	$query = "SELECT whentime,uid,DAYOFWEEK(whentime) as dow,HOUR(whentime) as hod,mode FROM sensibo WHERE airconon=1 AND cost=0.0 AND mode='heat'";
	$res = mysqli_query($link, $query);
	while($row = mysqli_fetch_assoc($res))
	{
		if($row['dow'] == 1 || $row['dow'] == 7)
		{
			$cost = $airconWheat / $COP * $offpeakkwHr * 90.0 / 3600.0;
		} else {
			if($row['hod'] >= 0 && $row['hod'] < 7)
				$cost = $airconWheat / $COP * $offpeakkwHr * 90.0 / 3600.0;
			if($row['hod'] >= 7 && $row['hod'] < 9)
				$cost = $airconWheat / $COP * $peakkwHr * 90.0 / 3600.0;
			if($row['hod'] >= 9 && $row['hod'] < 17)
				$cost = $airconWheat / $COP * $shoulderkwHr * 90.0 / 3600.0;
			if($row['hod'] >= 17 && $row['hod'] < 20)
				$cost = $airconWheat / $COP * $peakkwHr * 90.0 / 3600.0;
			if($row['hod'] >= 20 && $row['hod'] < 22)
				$cost = $airconWheat / $COP * $shoulderkwHr * 90.0 / 3600.0;
			if($row['hod'] >= 22)
				$cost = $airconWheat / $COP * $offpeakkwHr * 90.0 / 3600.0;
		}

		$query = "UPDATE sensibo SET cost=$cost WHERE whentime='${row['whentime']}' AND uid='${row['uid']}'";
		mysqli_query($link, $query);
		echo $query."\n";
	}
