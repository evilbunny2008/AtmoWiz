<?php
	$error = null;
	require_once('mariadb.php');

	if(!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] != true)
	{
		header('Location: index.php');
		exit;
	}

	if(isset($_REQUEST['logout']) && $_REQUEST['logout'] == "1")
	{
		$_SESSION['authenticated'] = false;
		$_SESSION['rw'] = false;
		header('Location: index.php');
		exit;
	}

	function changeState($podUID, $changes)
	{
		global $apikey;

		if(!$_SESSION['rw'])
			return json_encode(array('ret' => "You don't have permission."));

		$url = "https://home.sensibo.com/api/v2/pods/$podUID/acStates?apiKey=".$apikey;
		$body = json_encode(array('acState' => $changes));

		$opts = array('http' => array('method' => "POST", 'header' => "Accept: application/json\r\nContent-Type: application/json\r\n", 'content' => $body, 'timeout' => 5));
		$context = stream_context_create($opts);
		$ret = @file_get_contents($url, false, $context);

		$statusheader = explode(" ", $http_response_header['0'], 3)['1'];
		if($statusheader == "200")
			return 200;
		else
			return json_encode(array('headers' => $http_response_header, 'ret' => $ret, 'url' => $url, 'body' => $body));
	}

	if(isset($_REQUEST['podUID1']) && !empty($_REQUEST['podUID1']) && $_SESSION['rw'])
	{
		$uid = mysqli_real_escape_string($link, $_REQUEST['podUID1']);

		$changes = array();

		if(isset($_REQUEST['mode']))
		{
			if($row['mode'] != $_REQUEST['mode'])
			{
				$row['mode'] = mysqli_real_escape_string($link, $_REQUEST['mode']);
				$changes['mode'] = $row['mode'];
			}
		}

		if(isset($_REQUEST['targetTemperature']))
		{
			if($row['targetTemperature'] != $_REQUEST['targetTemperature'])
			{
				$row['targetTemperature'] = intval($_REQUEST['targetTemperature']);
				$changes['targetTemperature'] = $row['targetTemperature'];
			}
		}

		if(isset($_REQUEST['fanLevel']))
		{
			if($row['fanLevel'] != $_REQUEST['fanLevel'])
			{
				$row['fanLevel'] = mysqli_real_escape_string($link, $_REQUEST['fanLevel']);
				$changes['fanLevel'] = $row['fanLevel'];
			}
		}

		if(isset($_REQUEST['swing']))
		{
			if($row['swing'] != $_REQUEST['swing'])
			{
				$row['swing'] = mysqli_real_escape_string($link, $_REQUEST['swing']);
				$changes['swing'] = $row['swing'];
			}
		}

		if(isset($_REQUEST['horizontalSwing']))
		{
			if($row['horizontalSwing'] != $_REQUEST['horizontalSwing'])
			{
				$row['horizontalSwing'] = mysqli_real_escape_string($link, $_REQUEST['horizontalSwing']);
				$changes['horizontalSwing'] = $row['horizontalSwing'];
			}
		}

		if($changes != array())
		{
			$ret = changeState($uid, $changes);
			if($ret != 200)
				echo $ret;
			else
				echo "OK";
			exit;
		}
	}

	if(isset($_REQUEST['podUID2']) && !empty($_REQUEST['podUID2']) && (!isset($_REQUEST['created2']) || empty($_REQUEST['created2'])) && $_SESSION['rw'] && (!isset($_REQUEST['action']) || empty($_REQUEST['action'])))
	{
		$uid = mysqli_real_escape_string($link, $_REQUEST['podUID2']);
		$onOff = mysqli_real_escape_string($link, $_REQUEST['onOff']);
		$targetType = mysqli_real_escape_string($link, $_REQUEST['targetType']);
		$targetOp = mysqli_real_escape_string($link, $_REQUEST['targetOp']);
		$targetValue = mysqli_real_escape_string($link, $_REQUEST['targetValue']);
		$startTime = mysqli_real_escape_string($link, $_REQUEST['startTime']).":00";
		$endTime = mysqli_real_escape_string($link, $_REQUEST['endTime']).":59";
		$turnOnOff = mysqli_real_escape_string($link, $_REQUEST['turnOnOff']);
		$targetTemperature = mysqli_real_escape_string($link, $_REQUEST['targetTemperature']);
		$mode = mysqli_real_escape_string($link, $_REQUEST['mode']);
		$fanLevel = mysqli_real_escape_string($link, $_REQUEST['fanLevel']);
		$swing = mysqli_real_escape_string($link, $_REQUEST['swing']);
		$horizontalSwing = mysqli_real_escape_string($link, $_REQUEST['horizontalSwing']);
		$enabled = 0;
		if(isset($_REQUEST['enabled']) && $_REQUEST['enabled'] == '1')
			$enabled = 1;

		$query = "INSERT INTO settings (uid, created, onOff, targetType, targetOp, targetValue, startTime, endTime, turnOnOff, targetTemperature, mode, fanLevel, swing, horizontalSwing, enabled) VALUES ".
			 "('$uid', NOW(), '$onOff', '$targetType', '$targetOp', '$targetValue', '$startTime', '$endTime', '$turnOnOff', '$targetTemperature', '$mode', '$fanLevel', '$swing', '$horizontalSwing', '$enabled')";
		if(!mysqli_query($link, $query))
			echo sprintf("Error message: %s\n", mysqli_error($link));
		else
			echo "OK";
		exit;
	}

	if(isset($_REQUEST['podUID2']) && !empty($_REQUEST['podUID2']) && isset($_REQUEST['created2']) && !empty($_REQUEST['created2']) && $_SESSION['rw'])
	{
		$created = mysqli_real_escape_string($link, $_REQUEST['created2']);
		$uid = mysqli_real_escape_string($link, $_REQUEST['podUID2']);
		$onOff = mysqli_real_escape_string($link, $_REQUEST['onOff']);
		$targetType = mysqli_real_escape_string($link, $_REQUEST['targetType']);
		$targetOp = mysqli_real_escape_string($link, $_REQUEST['targetOp']);
		$targetValue = mysqli_real_escape_string($link, $_REQUEST['targetValue']);
		$startTime = mysqli_real_escape_string($link, $_REQUEST['startTime']).":00";
		$endTime = mysqli_real_escape_string($link, $_REQUEST['endTime']).":59";
		$turnOnOff = mysqli_real_escape_string($link, $_REQUEST['turnOnOff']);
		$targetTemperature = mysqli_real_escape_string($link, $_REQUEST['targetTemperature']);
		$mode = mysqli_real_escape_string($link, $_REQUEST['mode']);
		$fanLevel = mysqli_real_escape_string($link, $_REQUEST['fanLevel']);
		$swing = mysqli_real_escape_string($link, $_REQUEST['swing']);
		$horizontalSwing = mysqli_real_escape_string($link, $_REQUEST['horizontalSwing']);
		$enabled = 0;
		if(isset($_REQUEST['enabled']) && $_REQUEST['enabled'] == '1')
			$enabled = 1;

		$query = "UPDATE settings SET onOff='$onOff', targetType='$targetType', targetOp='$targetOp', targetValue='$targetValue', startTime='$startTime', endTime='$endTime', turnOnOff='$turnOnOff', ".
			 "targetTemperature='$targetTemperature', mode='$mode', fanLevel='$fanLevel', swing='$swing', horizontalSwing='$horizontalSwing', enabled='$enabled' WHERE uid='$uid' AND created='$created'";
		if(!mysqli_query($link, $query))
			echo sprintf("Error message: %s\n", mysqli_error($link));
		else
			echo "OK";
		exit;
	}

	if(isset($_REQUEST['action']) && $_REQUEST['action'] == "delete" && isset($_REQUEST['podUID2']) && !empty($_REQUEST['podUID2']) && isset($_REQUEST['created']) && !empty($_REQUEST['created']) && $_SESSION['rw'])
	{
		$created = mysqli_real_escape_string($link, $_REQUEST['created']);
		$uid = mysqli_real_escape_string($link, $_REQUEST['podUID2']);

		$query = "DELETE FROM settings WHERE uid='$uid' AND created='$created'";
		if(!mysqli_query($link, $query))
			echo sprintf("Error message: %s\n", mysqli_error($link));
		else
			echo "OK";
		exit;
	}

	if(isset($_REQUEST['podUID5']) && !empty($_REQUEST['podUID5']) && isset($_REQUEST['created5']) && !empty($_REQUEST['created5']) && $_SESSION['rw'])
	{
		$created = mysqli_real_escape_string($link, $_REQUEST['created5']);
		$uid = mysqli_real_escape_string($link, $_REQUEST['podUID5']);
		$startTime = mysqli_real_escape_string($link, $_REQUEST['startTime']).":00";
		$endTime = mysqli_real_escape_string($link, $_REQUEST['endTime']).":59";
		$turnOnOff = mysqli_real_escape_string($link, $_REQUEST['turnOnOff']);
		$mode = mysqli_real_escape_string($link, $_REQUEST['mode']);
		$targetTemperature = mysqli_real_escape_string($link, $_REQUEST['targetTemperature']);
		$fanLevel = mysqli_real_escape_string($link, $_REQUEST['fanLevel']);
		$swing = mysqli_real_escape_string($link, $_REQUEST['swing']);
		$horizontalSwing = mysqli_real_escape_string($link, $_REQUEST['horizontalSwing']);

		$daysOfWeek = 0;
		foreach($_REQUEST['days'] as $k => $v)
			$daysOfWeek += 2 ** $v;

		if($daysOfWeek == 0 || $daysOfWeek > 127)
			$daysOfWeek = 127;

		$enabled = 0;
		if(isset($_REQUEST['enabled']) && $_REQUEST['enabled'] == '1')
			$enabled = 1;

		$query = "UPDATE timesettings SET daysOfWeek='$daysOfWeek', startTime='$startTime', endTime='$endTime', turnOnOff='$turnOnOff', mode='$mode', targetTemperature='$targetTemperature', fanLevel='$fanLevel', swing='$swing', horizontalSwing='$horizontalSwing', enabled='$enabled' WHERE uid='$uid' AND created='$created'";
		if(!mysqli_query($link, $query))
			echo sprintf("Error message: %s\n", mysqli_error($link));
		else
			echo "OK";
		exit;
	}

	if(isset($_REQUEST['podUID5']) && !empty($_REQUEST['podUID5']) && (!isset($_REQUEST['created5']) || empty($_REQUEST['created5'])) && $_SESSION['rw'] && (!isset($_REQUEST['action']) || empty($_REQUEST['action'])))
	{
		$uid = mysqli_real_escape_string($link, $_REQUEST['podUID5']);
		$startTime = mysqli_real_escape_string($link, $_REQUEST['startTime']).":00";
		$endTime = mysqli_real_escape_string($link, $_REQUEST['endTime']).":59";
		$turnOnOff = mysqli_real_escape_string($link, $_REQUEST['turnOnOff']);
		$mode = mysqli_real_escape_string($link, $_REQUEST['mode']);
		$targetTemperature = mysqli_real_escape_string($link, $_REQUEST['targetTemperature']);
		$fanLevel = mysqli_real_escape_string($link, $_REQUEST['fanLevel']);
		$swing = mysqli_real_escape_string($link, $_REQUEST['swing']);
		$horizontalSwing = mysqli_real_escape_string($link, $_REQUEST['horizontalSwing']);

		$daysOfWeek = 0;
		foreach($_REQUEST['days'] as $k => $v)
			$daysOfWeek += 2 ** $v;

		if($daysOfWeek == 0 || $daysOfWeek > 127)
			$daysOfWeek = 127;

		$enabled = 0;
		if(isset($_REQUEST['enabled']) && $_REQUEST['enabled'] == '1')
			$enabled = 1;

		$query = "INSERT INTO timesettings (uid, created, daysOfWeek, startTime, endTime, turnOnOff, mode, targetTemperature, fanLevel, swing, horizontalSwing, enabled) VALUES ".
			 "('$uid', NOW(), '$daysOfWeek', '$startTime', '$endTime', '$turnOnOff', '$mode', '$targetTemperature', '$fanLevel', '$swing', '$horizontalSwing', '$enabled')";
		if(!mysqli_query($link, $query))
			echo sprintf("Error message: %s\n", mysqli_error($link));
		else
			echo "OK";
		exit;
	}

	if(isset($_REQUEST['action']) && $_REQUEST['action'] == "delete" && isset($_REQUEST['podUID5']) && !empty($_REQUEST['podUID5']) && isset($_REQUEST['created']) && !empty($_REQUEST['created']) && $_SESSION['rw'])
	{
		$created = mysqli_real_escape_string($link, $_REQUEST['created']);
		$uid = mysqli_real_escape_string($link, $_REQUEST['podUID5']);

		$query = "DELETE FROM timesettings WHERE uid='$uid' AND created='$created'";
		if(!mysqli_query($link, $query))
			echo sprintf("Error message: %s\n", mysqli_error($link));
		else
			echo "OK";
		exit;
	}

	if(isset($_REQUEST['podUID8']) && !empty($_REQUEST['podUID8']) && $_SESSION['rw'] && (!isset($_REQUEST['action']) || empty($_REQUEST['action'])))
	{
		$uid = mysqli_real_escape_string($link, $_REQUEST['podUID8']);
		$turnOnOff = mysqli_real_escape_string($link, $_REQUEST['turnOnOff']);
		$seconds = substr($_REQUEST['timer'], 0, 2) * 3600 + substr($_REQUEST['timer'], 3, 2) * 60;

		$query = "INSERT INTO timers (uid, whentime, turnOnOff, seconds) VALUES ('$uid', NOW(), '$turnOnOff', '$seconds')";
		if(!mysqli_query($link, $query))
			echo sprintf("Error message: %s\n", mysqli_error($link));
		else
			echo "OK";
		exit;
	}

	if(isset($_REQUEST['action']) && $_REQUEST['action'] == "delete" && isset($_REQUEST['podUID8']) && !empty($_REQUEST['podUID8']) && isset($_REQUEST['whentime']) && !empty($_REQUEST['whentime']) && $_SESSION['rw'])
	{
		$whentime = mysqli_real_escape_string($link, $_REQUEST['whentime']);
		$uid = mysqli_real_escape_string($link, $_REQUEST['podUID8']);

		$query = "DELETE FROM timers WHERE uid='$uid' AND whentime='$whentime'";
		if(!mysqli_query($link, $query))
			echo sprintf("Error message: %s\n", mysqli_error($link));
		else
			echo "OK";
		exit;
	}
