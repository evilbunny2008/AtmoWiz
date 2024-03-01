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

		$opts = array('http' => array('method' => "POST", 'header' => "Accept: application/json\r\nContent-Type: application/json\r\n", 'content' => $body, 'timeout' => 15));
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

		$row = mysqli_fetch_assoc(mysqli_query($link, "SELECT * FROM commands ORDER BY whentime DESC"));
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
		$name = mysqli_real_escape_string($link, $_REQUEST['name']);
		$upperTemperature = mysqli_real_escape_string($link, $_REQUEST['upperTemperature']);
		$lowerTemperature = mysqli_real_escape_string($link, $_REQUEST['lowerTemperature']);
		$targetTemperature = mysqli_real_escape_string($link, $_REQUEST['targetTemperature']);
		$turnOnOff = mysqli_real_escape_string($link, $_REQUEST['turnOnOff']);
		$mode = mysqli_real_escape_string($link, $_REQUEST['mode']);
		$fanLevel = mysqli_real_escape_string($link, $_REQUEST['fanLevel']);
		$swing = mysqli_real_escape_string($link, $_REQUEST['swing']);
		$horizontalSwing = mysqli_real_escape_string($link, $_REQUEST['horizontalSwing']);
		$enabled = 0;
		if(isset($_REQUEST['enabled']) && $_REQUEST['enabled'] == '1')
			$enabled = 1;

		$query = "INSERT INTO settings (uid, created, name, upperTemperature, lowerTemperature, targetTemperature, turnOnOff, mode, fanLevel, swing, horizontalSwing, enabled) VALUES ".
			 "('$uid', NOW(), '$name', '$upperTemperature', '$lowerTemperature', '$targetTemperature', '$turnOnOff', '$mode', '$fanLevel', '$swing', '$horizontalSwing', '$enabled')";
syslog(LOG_INFO, $query);
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
		$name = mysqli_real_escape_string($link, $_REQUEST['name']);
		$upperTemperature = mysqli_real_escape_string($link, $_REQUEST['upperTemperature']);
		$lowerTemperature = mysqli_real_escape_string($link, $_REQUEST['lowerTemperature']);
		$targetTemperature = mysqli_real_escape_string($link, $_REQUEST['targetTemperature']);
		$turnOnOff = mysqli_real_escape_string($link, $_REQUEST['turnOnOff']);
		$mode = mysqli_real_escape_string($link, $_REQUEST['mode']);
		$fanLevel = mysqli_real_escape_string($link, $_REQUEST['fanLevel']);
		$swing = mysqli_real_escape_string($link, $_REQUEST['swing']);
		$horizontalSwing = mysqli_real_escape_string($link, $_REQUEST['horizontalSwing']);
		$enabled = 0;
		if(isset($_REQUEST['enabled']) && $_REQUEST['enabled'] == '1')
			$enabled = 1;

		$query = "UPDATE settings SET name='$name', upperTemperature='$upperTemperature', lowerTemperature='$lowerTemperature', targetTemperature='$targetTemperature', turnOnOff='$turnOnOff', ".
			 "mode='$mode', fanLevel='$fanLevel', swing='$swing', horizontalSwing='$horizontalSwing', enabled='$enabled' WHERE uid='$uid' AND created='$created'";
syslog(LOG_INFO, $query);
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
syslog(LOG_INFO, $query);
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
		$turnOnOff = mysqli_real_escape_string($link, $_REQUEST['turnOnOff']);
		$mode = mysqli_real_escape_string($link, $_REQUEST['mode']);
		$targetTemperature = mysqli_real_escape_string($link, $_REQUEST['targetTemperature']);
		$fanLevel = mysqli_real_escape_string($link, $_REQUEST['fanLevel']);
		$swing = mysqli_real_escape_string($link, $_REQUEST['swing']);
		$horizontalSwing = mysqli_real_escape_string($link, $_REQUEST['horizontalSwing']);
		$climateSetting = mysqli_real_escape_string($link, $_REQUEST['climateSetting']);

		if($climateSetting == "none")
			$climateSetting = "NULL";
		else
			$climateSetting = "'$climateSetting'";

		$daysOfWeek = 0;
		foreach($_REQUEST['days'] as $k => $v)
			$daysOfWeek += 2 ** $v;

		if($daysOfWeek == 0 || $daysOfWeek > 127)
			$daysOfWeek = 127;

		$enabled = 0;
		if(isset($_REQUEST['enabled']) && $_REQUEST['enabled'] == '1')
			$enabled = 1;

		$query = "UPDATE timesettings SET daysOfWeek='$daysOfWeek', startTime='$startTime', turnOnOff='$turnOnOff', mode='$mode', targetTemperature='$targetTemperature', fanLevel='$fanLevel', swing='$swing', ".
			 "horizontalSwing='$horizontalSwing', climateSetting=$climateSetting, enabled='$enabled' WHERE uid='$uid' AND created='$created'";
syslog(LOG_INFO, $query);
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
		$turnOnOff = mysqli_real_escape_string($link, $_REQUEST['turnOnOff']);
		$mode = mysqli_real_escape_string($link, $_REQUEST['mode']);
		$targetTemperature = mysqli_real_escape_string($link, $_REQUEST['targetTemperature']);
		$fanLevel = mysqli_real_escape_string($link, $_REQUEST['fanLevel']);
		$swing = mysqli_real_escape_string($link, $_REQUEST['swing']);
		$horizontalSwing = mysqli_real_escape_string($link, $_REQUEST['horizontalSwing']);
		$climateSetting = mysqli_real_escape_string($link, $_REQUEST['climateSetting']);

		if($climateSetting == "none")
			$climateSetting = "NULL";
		else
			$climateSetting = "'$climateSetting'";

		$daysOfWeek = 0;
		foreach($_REQUEST['days'] as $k => $v)
			$daysOfWeek += 2 ** $v;

		if($daysOfWeek == 0 || $daysOfWeek > 127)
			$daysOfWeek = 127;

		$enabled = 0;
		if(isset($_REQUEST['enabled']) && $_REQUEST['enabled'] == '1')
			$enabled = 1;

		$query = "INSERT INTO timesettings (uid, created, daysOfWeek, startTime, turnOnOff, mode, targetTemperature, fanLevel, swing, horizontalSwing, climateSetting, enabled) VALUES ".
			 "('$uid', NOW(), '$daysOfWeek', '$startTime', '$turnOnOff', '$mode', '$targetTemperature', '$fanLevel', '$swing', '$horizontalSwing', $climateSetting, '$enabled')";
syslog(LOG_INFO, $query);
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
syslog(LOG_INFO, $query);
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
syslog(LOG_INFO, $query);
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
syslog(LOG_INFO, $query);
		if(!mysqli_query($link, $query))
			echo sprintf("Error message: %s\n", mysqli_error($link));
		else
			echo "OK";
		exit;
	}
