<?php
	$error = null;
	$period = 86400000;
	$startTS = time() * 1000 - $period;
	$row = array('uid' => '');

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

	$query = "SELECT * FROM commands ORDER BY whentime DESC LIMIT 1";
	$res = mysqli_query($link, $query);
	$row = mysqli_fetch_assoc($res);

	if(isset($_REQUEST['startTS']) && !empty($_REQUEST['startTS']))
		$startTS = doubleval($_REQUEST['startTS']);

	if(isset($_REQUEST['period']) && !empty($_REQUEST['period']))
		$period = doubleval($_REQUEST['period']);

	$timePeriod = "day";
	if($period == 604800000)
		$timePeriod = "week";
	if($period == 2592000000)
		$timePeriod = "month";
	if($period == 31536000000)
		$timePeriod = "year";

	if(isset($_REQUEST['podUID1']) && !empty($_REQUEST['podUID1']) && $_SESSION['rw'])
	{
		$row['uid'] = mysqli_real_escape_string($link, $_REQUEST['podUID1']);

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
			$ret = changeState($row['uid'], $changes);
			if($ret != 200)
			{
				$error = $ret;
			}
		}
	}

	if(isset($_REQUEST['podUID2']) && !empty($_REQUEST['podUID2']) && (!isset($_REQUEST['created2']) || empty($_REQUEST['created2'])) && $_SESSION['rw'] && (!isset($_REQUEST['action']) || empty($_REQUEST['action'])))
	{
		$row['uid'] = mysqli_real_escape_string($link, $_REQUEST['podUID2']);
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
			 "('${row['uid']}', NOW(), '$onOff', '$targetType', '$targetOp', '$targetValue', '$startTime', '$endTime', '$turnOnOff', '$targetTemperature', '$mode', '$fanLevel', '$swing', '$horizontalSwing', '$enabled')";
		if(!mysqli_query($link, $query))
			$error = sprintf("Error message: %s\n", mysqli_error($link));
	}

	if(isset($_REQUEST['podUID2']) && !empty($_REQUEST['podUID2']) && isset($_REQUEST['created2']) && !empty($_REQUEST['created2']) && $_SESSION['rw'])
	{
		$created = mysqli_real_escape_string($link, $_REQUEST['created2']);
		$row['uid'] = mysqli_real_escape_string($link, $_REQUEST['podUID2']);
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
			 "targetTemperature='$targetTemperature', mode='$mode', fanLevel='$fanLevel', swing='$swing', horizontalSwing='$horizontalSwing', enabled='$enabled' WHERE uid='${row['uid']}' AND created='$created'";
		if(!mysqli_query($link, $query))
			$error = sprintf("Error message: %s\n", mysqli_error($link));
	}

	if(isset($_REQUEST['action']) && $_REQUEST['action'] == "delete" && isset($_REQUEST['podUID2']) && !empty($_REQUEST['podUID2']) && isset($_REQUEST['created']) && !empty($_REQUEST['created']) && $_SESSION['rw'])
	{
		$created = mysqli_real_escape_string($link, $_REQUEST['created']);
		$row['uid'] = mysqli_real_escape_string($link, $_REQUEST['podUID2']);

		$query = "DELETE FROM settings WHERE uid='${row['uid']}' AND created='$created'";
		if(!mysqli_query($link, $query))
			$error = sprintf("Error message: %s\n", mysqli_error($link));
	}

	if(isset($_REQUEST['podUID5']) && !empty($_REQUEST['podUID5']) && isset($_REQUEST['created5']) && !empty($_REQUEST['created5']) && $_SESSION['rw'])
	{
		$created = mysqli_real_escape_string($link, $_REQUEST['created5']);
		$row['uid'] = mysqli_real_escape_string($link, $_REQUEST['podUID5']);
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

		$query = "UPDATE timesettings SET daysOfWeek='$daysOfWeek', startTime='$startTime', endTime='$endTime', turnOnOff='$turnOnOff', mode='$mode', targetTemperature='$targetTemperature', fanLevel='$fanLevel', swing='$swing', horizontalSwing='$horizontalSwing', enabled='$enabled' WHERE uid='${row['uid']}' AND created='$created'";
		if(!mysqli_query($link, $query))
			$error = sprintf("Error message: %s\n", mysqli_error($link));
	}

	if(isset($_REQUEST['podUID5']) && !empty($_REQUEST['podUID5']) && (!isset($_REQUEST['created5']) || empty($_REQUEST['created5'])) && $_SESSION['rw'] && (!isset($_REQUEST['action']) || empty($_REQUEST['action'])))
	{
		$row['uid'] = mysqli_real_escape_string($link, $_REQUEST['podUID5']);
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
			 "('${row['uid']}', NOW(), '$daysOfWeek', '$startTime', '$endTime', '$turnOnOff', '$mode', '$targetTemperature', '$fanLevel', '$swing', '$horizontalSwing', '$enabled')";
		if(!mysqli_query($link, $query))
			$error = sprintf("Error message: %s\n", mysqli_error($link));
	}

	if(isset($_REQUEST['action']) && $_REQUEST['action'] == "delete" && isset($_REQUEST['podUID5']) && !empty($_REQUEST['podUID5']) && isset($_REQUEST['created']) && !empty($_REQUEST['created']) && $_SESSION['rw'])
	{
		$created = mysqli_real_escape_string($link, $_REQUEST['created']);
		$row['uid'] = mysqli_real_escape_string($link, $_REQUEST['podUID5']);

		$query = "DELETE FROM timesettings WHERE uid='${row['uid']}' AND created='$created'";
		if(!mysqli_query($link, $query))
			$error = sprintf("Error message: %s\n", mysqli_error($link));
	}
?>
<!DOCTYPE HTML>
<html>
<head>
<title>Sensibo Data Plotting</title>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" href="favicon.svg">
<link rel="stylesheet" href="clocklet.min.css">
<script src="canvasjs.min.js"></script>
<script src="clocklet.js"></script>
<style>
*
{
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body
{
  font-family: Arial, Helvetica, sans-serif;
  min-height: 750px;
  height: 750px;
}

nav
{
  float: left;
  width: 350px;
  background: #ccc;
  padding: 20px;
}

nav ul
{
  list-style-type: none;
  padding: 0;
}

article
{
  float: left;
  padding: 20px;
  background-color: #f1f1f1;
}

section::after
{
  content: "";
  display: table;
  clear: both;
}

#footer
{
  margin-top:auto;
  width: 100%;
  background: #ccc;
  position: fixed;
  bottom: 0;
  left: 0;
  height: 32px;
  z-index: 1;
}

#footer-content
{
  text-align: center;
  height: 32px;
  padding: 8px;
  width: 100%;
}

#footer a, #commands a
{
  color: #085f24;
}

.child
{
  position: absolute;
  z-index: 1;
  top: 5px;
}

body
{
  font-family: Arial, Helvetica, sans-serif;
}

#mode1, #targetTemperature1, #fanLevel1, #swing1, #horizontalSwing1
{
  width: 100%;
  padding: 12px 20px;
  margin: 8px 0;
  display: inline-block;
  border: 1px solid #ccc;
  box-sizing: border-box;
}

.myInputs2
{
  width: 100%;
  padding: 12px 12px;
  margin: 8px 8px;
  display: inline-block;
  border: 1px solid #ccc;
  box-sizing: border-box;
}

.myInputs5
{
  width: 100%;
  padding: 12px 12px;
  margin: 8px 8px;
  display: inline-block;
  border: 1px solid #ccc;
  box-sizing: border-box;
}

button
{
  background-color: #04AA6D;
  color: white;
  padding: 14px 20px;
  margin: 8px 0;
  border: none;
  cursor: pointer;
  width: 100%;
}

button:hover
{
  opacity: 0.8;
}

.imgcontainer
{
  text-align: center;
  margin: 24px 0 12px 0;
  position: relative;
}

.container
{
  padding: 16px;
}

span.psw
{
  float: right;
  padding-top: 16px;
}

.modal
{
  display: none;
  position: fixed;
  z-index: 1;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  overflow: auto;
  background-color: rgb(0,0,0);
  background-color: rgba(0,0,0,0.4);
  padding-top: 60px;
}

.modal-content
{
  background-color: #fefefe;
  margin: 5% auto 15% auto;
  border: 1px solid #888;
  width: 20%;
}

#id02 .modal-content
{
  width: 1700px;
  text-align: center;
}

#id03 .modal-content
{
  width: 500px;
  padding-top: 0px;
  margin-top: 0px;
  padding-bottom: 50px;
  margin-bottom: 50px;
}

#id04 .modal-content
{
  width: 1700px;
  text-align: center;
}

#id05 .modal-content
{
  width: 500px;
  padding-top: 0px;
  margin-top: 0px;
  padding-bottom: 50px;
  margin-bottom: 50px;
}

#id06 .modal-content
{
  width: 500px;
  padding-top: 0px;
  margin-top: 0px;
  padding-bottom: 50px;
  margin-bottom: 50px;
}

.close
{
  position: absolute;
  right: 25px;
  top: 0;
  color: #000;
  font-size: 35px;
  font-weight: bold;
}

.close:hover,
.close:focus
{
  color: red;
  cursor: pointer;
}

#divLeft, #divRight
{
  margin: 0px;
  padding: 0px;
  width: 50%;
}

#divLeft
{
  float: left;
  padding: 20px;
}

#divRight
{
  float: right;
  padding: 20px;
}

.wrapper
{
  display: flex;
  padding-top: 30px;
}

.wrapper > div
{
  flex: 1;
}

table, th, td
{
  border: 1px solid black;
  border-collapse: collapse;
}

table
{
  width: 100%;
  table-layout: fixed;
}

td
{
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
  text-align: center;
}

.animate
{
  -webkit-animation: animatezoom 0.6s;
  animation: animatezoom 0.6s
}

@-webkit-keyframes animatezoom
{
  from {-webkit-transform: scale(0)}
  to {-webkit-transform: scale(1)}
}

@keyframes animatezoom
{
  from {transform: scale(0)}
  to {transform: scale(1)}
}

@media screen and (max-width: 300px)
{
  span.psw
  {
     display: block;
     float: none;
  }
  .cancelbtn
  {
     width: 100%;
  }
}
</style>
</head>
<body>
<section>
  <nav style='overflow-x:hidden;overflow-y:scroll;height:calc(100vh - 32px);'>
    <ul id='commands'>
    </ul>
  </nav>
  <article style="width:calc(100% - 350px);">
    <div class="child" style='left:40%;'><img onClick="prevDay(); return false;" style='height:40px;' src='left.png' /></div>
    <div id="chartContainer" style="height: calc(100vh / 3 - 20px); width: 100%;"></div>
    <div style="height:calc(100vh / 3 * 2 - 52px); width:100%; background:#fff;">
      <div id="rssiContainer" style="height: calc(100% / 2); width: calc(100% - 50px);"></div>
      <div id="costContainer" style="height: calc(100% / 2); width: calc(100% - 50px);"></div>
    </div>
    <div class="child" style='right:20%;'><img onClick="nextDay(); return false;" style='height:40px;' src='right.png' /></div>
  </article>
</section>
<div style='height: 32px;width: 100%'></div>
<footer id="footer">
  <div id="footer-content"><a target='_blank' href='https://github.com/evilbunny2008/sensibo-python-sdk'>&copy; 2024 by </a><a target='_blank' href='https://evilbunny.org'>evilbunny</a></div>
</footer>

<?php
//	https://www.w3schools.com/howto/tryit.asp?filename=tryhow_css_login_form_modal
?>
<div id="id01" class="modal">
  <form class="modal-content animate" action="graphs.php" method="post">
    <div class="imgcontainer">
      <span onclick="document.getElementById('id01').style.display='none'" class="close">&times;</span>
    </div>
    <div class="container">
	<input id="startTS1" type="hidden" name="startTS" />
	<input id="podUID1" type="hidden" name="podUID1" value="<?=$row['uid']?>" />
	<label for="mode1"><b>Mode:</b></label>
	<select id="mode1" name="mode" onChange="populateSelect('1'); return false;">
<?php
	$dquery = "SELECT mode FROM meta GROUP BY mode";
	$dres = mysqli_query($link, $dquery);
	while($drow = mysqli_fetch_assoc($dres))
	{
		$v = $drow['mode'];
		echo "\t\t<option value='$v'";
			if($row['mode'] == $v)
				echo ' selected';
		echo ">$v</option>\n";
	}
?>
	</select>
	<label for='targetTemperature1'><b>Target Temperature:</b></label>
	<select id='targetTemperature1' name='targetTemperature'>
	</select>
	<label for="fanLevel1"><b>Fan Level:</b></label>
	<select id='fanLevel1' name="fanLevel">
	</select>
	<label for="swing1"><b>Swing:</b></label>
	<select id="swing1" name="swing">
	</select>
	<label for="horizontalSwing1"><b>Horizontal Swing:</b></label>
	<select id="horizontalSwing1" name="horizontalSwing">
	</select>
	<button type="submit">Update</button>
    </div>
  </form>
</div>
<div id="id02" class="modal">
  <form class="modal-content animate" action="graphs.php" method="post">
    <div class="imgcontainer">
      <span onclick="document.getElementById('id02').style.display='none'" class="close">&times;</span>
    </div>
    <div class="container">
	<h1>Climate Settings</h1>
	<br/>
	<table>
	<tr>
		<th>Created</th>
		<th>If On/Off</th>
		<th>Target Type</th>
		<th>Target Op</th>
		<th>Target Value</th>
		<th>Start Time</th>
		<th>End Time</th>
		<th>Turn On/Off</th>
		<th>Mode</th>
		<th>Target Temp</th>
		<th>Enabled</th>
		<th>Edit</th>
		<th>Delete</th>
	</tr>
<?php
	$query = "SELECT * FROM settings WHERE uid='${row['uid']}'";
	$res = mysqli_query($link, $query);
	while($drow = mysqli_fetch_assoc($res))
	{
		echo "<tr>";
		echo "<td style='cursor: pointer;' title='".$drow['created']."'>".$drow['created']."</td>";
		echo "<td>".$drow['onOff']."</td>";
		echo "<td>".$drow['targetType']."</td>";
		echo "<td>".htmlentities($drow['targetOp'])."</td>";
		echo "<td>".$drow['targetValue']."</td>";
		echo "<td>".substr($drow['startTime'],0,5)."</td>";
		echo "<td>".substr($drow['endTime'],0,5)."</td>";
		echo "<td>".$drow['turnOnOff']."</td>";
		echo "<td>".$drow['mode']."</td>";
		echo "<td>".$drow['targetTemperature']."</td>";
		echo "<td>";
		if($drow['enabled'])
			echo "True";
		else
			echo "False";
		echo "</td>";

		echo "<td onClick=\"editSetting('".$drow['created']."', '".$drow['uid']."', '".$drow['onOff']."', '".$drow['targetType']."', '".$drow['targetOp']."', '".$drow['targetValue']."', '";
		echo substr($drow['startTime'],0,5)."', '".substr($drow['endTime'],0,5)."', '".$drow['turnOnOff']."', '".$drow['targetTemperature']."', '".$drow['mode']."', '".$drow['fanLevel']."', '".$drow['swing']."', '";
		echo $drow['horizontalSwing']."', '".$drow['enabled']."'";
		echo "); return false;\" style=\"cursor: pointer; color: #085f24;\">Edit</td>";
		echo "<td onClick=\"deleteSetting('".$drow['created']."', '".$drow['uid']."'); return false;\" style=\"cursor: pointer;color: #085f24;\">Delete</td>";
		echo "</tr>\n";
	}
?>
	</table><br/><br/>
	<b onClick="newSetting(); return false;" style="cursor: pointer;color: #085f24;">Add Climate Setting</b>
    </div>
  </form>
</div>
<div id="id03" class="modal">
  <form class="modal-content animate" action="graphs.php" method="post">
    <div class="imgcontainer">
      <span onclick="cancelAddUpdate(); return false;" class="close">&times;</span>
    </div>
    <div class="container">
	<h1 style='text-align: center;'>Climate Settings</h1><br/>
	<input id="created2" type="hidden" name="created2" />
	<input id="startTS2" type="hidden" name="startTS" />
	<input id="podUID2" type="hidden" name="podUID2" />
	<label for="onOff2" style="width:200px;"><b>If On/Off:</b></label>
	<select class="myInputs2" style='width:300px;right:0px;float:right;' id="onOff2" name="onOff">
<?php
	$dquery = "SELECT DISTINCT SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING(COLUMN_TYPE, 7, LENGTH(COLUMN_TYPE) - 8), \"','\", 1 + units.i + tens.i * 10) , \"','\", -1) AS value FROM INFORMATION_SCHEMA.COLUMNS CROSS JOIN ".
			"(SELECT 0 AS i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) units CROSS JOIN (SELECT 0 AS i UNION SELECT 1 ".
			"UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) tens WHERE TABLE_NAME = 'settings' AND COLUMN_NAME = 'onOff'";
	$dres = mysqli_query($link, $dquery);
	while($drow = mysqli_fetch_assoc($dres))
	{
		$v = $drow['value'];
		echo "\t\t<option value='$v'>$v</option>\n";
	}
?>
	</select>
	<br/><br/><br/><br/>
	<label for="targetType2" style="width:200px;"><b>Target Type:</b></label>
	<select class="myInputs2" style='width:300px;right:0px;float:right;' id="targetType2" name="targetType">
<?php
	$dquery = "SELECT DISTINCT SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING(COLUMN_TYPE, 7, LENGTH(COLUMN_TYPE) - 8), \"','\", 1 + units.i + tens.i * 10) , \"','\", -1) AS value FROM INFORMATION_SCHEMA.COLUMNS CROSS JOIN ".
			"(SELECT 0 AS i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) units CROSS JOIN (SELECT 0 AS i UNION SELECT 1 ".
			"UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) tens WHERE TABLE_NAME = 'settings' AND COLUMN_NAME = 'targetType'";
	$dres = mysqli_query($link, $dquery);
	while($drow = mysqli_fetch_assoc($dres))
	{
		$v = $drow['value'];
		echo "\t\t<option value='$v'>$v</option>\n";
	}
?>
	</select>
	<br/><br/><br/><br/>
	<label for="targetOp2" style="width:200px;"><b>Target Operation:</b></label>
	<select class="myInputs2" style='width:300px;right:0px;float:right;' id="targetOp2" name="targetOp">
<?php
	$dquery = "SELECT DISTINCT SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING(COLUMN_TYPE, 7, LENGTH(COLUMN_TYPE) - 8), \"','\", 1 + units.i + tens.i * 10) , \"','\", -1) AS value FROM INFORMATION_SCHEMA.COLUMNS CROSS JOIN ".
			"(SELECT 0 AS i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) units CROSS JOIN (SELECT 0 AS i UNION SELECT 1 ".
			"UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) tens WHERE TABLE_NAME = 'settings' AND COLUMN_NAME = 'targetOp'";
	$dres = mysqli_query($link, $dquery);
	while($drow = mysqli_fetch_assoc($dres))
	{
		$v = htmlentities($drow['value']);
		echo "\t\t<option value='$v'>$v</option>\n";
	}
?>
	</select>
	<br/><br/><br/><br/>
	<label for="targetValue2" style="width:200px;"><b>Target Value:</b></label>
	<input class="myInputs2" style='width:300px;right:0px;float:right;' id="targetValue2" name="targetValue" type="number" min="0.0" step="0.1" max="40.0" value="30" />
	<br/><br/><br/><br/>
	<div id="wrapper">
		<div id="divLeft">
	<label for="startTime2"><b>Start Time:</b></label>
	<input id='startTime2' name='startTime' class="myInputs2" data-clocklet="class-name: clocklet-options-1; alignment: center;">
	<label for="turnOnOff2"><b>Turn On/Off:</b></label><select class="myInputs2" id="turnOnOff2" name="turnOnOff">
<?php
	$dquery = "SELECT DISTINCT SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING(COLUMN_TYPE, 7, LENGTH(COLUMN_TYPE) - 8), \"','\", 1 + units.i + tens.i * 10) , \"','\", -1) AS value FROM INFORMATION_SCHEMA.COLUMNS CROSS JOIN ".
			"(SELECT 0 AS i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) units CROSS JOIN (SELECT 0 AS i UNION SELECT 1 ".
			"UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) tens WHERE TABLE_NAME = 'settings' AND COLUMN_NAME = 'turnOnOff'";
	$dres = mysqli_query($link, $dquery);
	while($drow = mysqli_fetch_assoc($dres))
	{
		$v = $drow['value'];
		echo "\t\t<option value='$v'>$v</option>\n";
	}
?>
	</select>
	<label for="mode2"><b>Mode:</b></label><select class="myInputs2" id="mode2" name="mode" onChange="populateSelect('2'); return false;">
<?php
	$defmode = "";
	$dquery = "SELECT DISTINCT SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING(COLUMN_TYPE, 7, LENGTH(COLUMN_TYPE) - 8), \"','\", 1 + units.i + tens.i * 10) , \"','\", -1) AS value FROM INFORMATION_SCHEMA.COLUMNS CROSS JOIN ".
			"(SELECT 0 AS i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) units CROSS JOIN (SELECT 0 AS i UNION SELECT 1 ".
			"UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) tens WHERE TABLE_NAME = 'settings' AND COLUMN_NAME = 'mode'";
	$dres = mysqli_query($link, $dquery);
	while($drow = mysqli_fetch_assoc($dres))
	{
		$v = $drow['value'];
		echo "\t\t<option value='$v'>$v</option>\n";
		if($defmode == "")
			$defmode = $v;
	}
?>
	</select>
	<label for="targetTemperature2"><b>Target Temperature:</b></label><select class="myInputs2" id='targetTemperature2' name='targetTemperature'>
<?php
	$defmode = 'cool';
	$query = "SELECT value FROM meta WHERE uid='${row['uid']}' AND mode='$defmode' AND keyval='temperatures'";
	$dres = mysqli_query($link, $query);
	while($drow = mysqli_fetch_assoc($dres))
	{
		$v = $drow['value'];
		echo "\t\t<option value='$v'>$v</option>\n";
	}
?>
	</select>
		</div>
		<div id="divRight">
	<label for="endTime2"><b>Start Time:</b></label>
	<input id='endTime2' name='endTime' class="myInputs2" data-clocklet="class-name: clocklet-options-1; alignment: center;">
	<label for="fanLevel2"><b>Fan Level:</b></label><select class="myInputs2" id='fanLevel2' name="fanLevel">
<?php
	$query = "SELECT DISTINCT SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING(COLUMN_TYPE, 7, LENGTH(COLUMN_TYPE) - 8), \"','\", 1 + units.i + tens.i * 10) , \"','\", -1) AS value FROM INFORMATION_SCHEMA.COLUMNS CROSS JOIN ".
			"(SELECT 0 AS i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) units CROSS JOIN (SELECT 0 AS i UNION SELECT 1 ".
			"UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) tens WHERE TABLE_NAME = 'settings' AND COLUMN_NAME = 'fanLevel'";
	$dres = mysqli_query($link, $query);
	while($drow = mysqli_fetch_assoc($dres))
	{
		$v = $drow['value'];
		echo "\t\t<option value='$v'>$v</option>\n";
	}
?>
		</select>
	<label for="swing2"><b>Swing:</b></label><select class="myInputs2" id="swing2" name="swing">
<?php
	$query = "SELECT DISTINCT SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING(COLUMN_TYPE, 7, LENGTH(COLUMN_TYPE) - 8), \"','\", 1 + units.i + tens.i * 10) , \"','\", -1) AS value FROM INFORMATION_SCHEMA.COLUMNS CROSS JOIN ".
			"(SELECT 0 AS i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) units CROSS JOIN (SELECT 0 AS i UNION SELECT 1 ".
			"UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) tens WHERE TABLE_NAME = 'settings' AND COLUMN_NAME = 'swing'";
	$dres = mysqli_query($link, $query);
	while($drow = mysqli_fetch_assoc($dres))
	{
		$v = $drow['value'];
		echo "\t\t<option value='$v'>$v</option>\n";
	}
?>
		</select>
	<label for="horizontalSwing2"><b>Horizontal Swing:</b></label><select class="myInputs2" id="horizontalSwing2" name="horizontalSwing">
<?php
	$query = "SELECT DISTINCT SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING(COLUMN_TYPE, 7, LENGTH(COLUMN_TYPE) - 8), \"','\", 1 + units.i + tens.i * 10) , \"','\", -1) AS value FROM INFORMATION_SCHEMA.COLUMNS CROSS JOIN ".
			"(SELECT 0 AS i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) units CROSS JOIN (SELECT 0 AS i UNION SELECT 1 ".
			"UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) tens WHERE TABLE_NAME = 'settings' AND COLUMN_NAME = 'horizontalSwing'";
	$dres = mysqli_query($link, $query);
	while($drow = mysqli_fetch_assoc($dres))
	{
		$v = $drow['value'];
		echo "\t\t<option value='$v'>$v</option>\n";
	}
?>
	</select>
		</div>
	</div>
	<label for="enabled2"><b>Enabled:</b></label><input style='text-align: left;' type="checkbox" id="enabled2" name="enabled" value="1" checked />
	<button id="submitAddUpdate2" type="submit">Add</button>
    </div>
  </form>
</div>
<div id="id04" class="modal">
  <form class="modal-content animate" action="graphs.php" method="post">
    <div class="imgcontainer">
      <span onclick="document.getElementById('id04').style.display='none'" class="close">&times;</span>
    </div>
    <div class="container">
	<h1>Climate Settings</h1>
	<br/>
	<table>
	<tr>
		<th>Created</th>
		<th>Day(s)</th>
		<th>Start Time</th>
		<th>End Time</th>
		<th>Turn On/Off</th>
		<th>Mode</th>
		<th>Target Temp</th>
		<th>Fan Level</th>
		<th>Swing</th>
		<th>Hor. Swing</th>
		<th>Enabled</th>
		<th>Edit</th>
		<th>Delete</th>
	</tr>
<?php
	$query = "SELECT * FROM timesettings WHERE uid='${row['uid']}'";
	$res = mysqli_query($link, $query);
	while($drow = mysqli_fetch_assoc($res))
	{
		echo "<tr>";
		echo "<td style='cursor: pointer;' title='".$drow['created']."'>".$drow['created']."</td>\n";

		$days = "";
		if($drow['daysOfWeek'] == 31)
		{
			$days = "Mon-Fri";
		} else if($drow['daysOfWeek'] == 127) {
			$days = "Mon-Sun";
		} else if($drow['daysOfWeek'] == 96) {
			$days = "Sat-Sun";
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

		echo "<td style='cursor: pointer;' title='$days'>$days</td>\n";
		echo "<td>".substr($drow['startTime'],0,5)."</td>\n";
		echo "<td>".substr($drow['endTime'],0,5)."</td>\n";
		echo "<td>".$drow['turnOnOff']."</td>\n";
		echo "<td>".$drow['mode']."</td>\n";
		echo "<td>".$drow['targetTemperature']."</td>\n";
		echo "<td>".$drow['fanLevel']."</td>\n";
		echo "<td>".$drow['swing']."</td>\n";
		echo "<td>".$drow['horizontalSwing']."</td>\n";
		echo "<td>";
		if($drow['enabled'])
			echo "True";
		else
			echo "False";
		echo "</td>\n";

		echo "<td onClick=\"editTimeSetting('".$drow['created']."', '".$drow['uid']."', '".$drow['daysOfWeek']."', '";
		echo substr($drow['startTime'],0,5)."', '".substr($drow['endTime'],0,5)."', '".$drow['turnOnOff']."', '".$drow['mode']."', '".$drow['targetTemperature']."', '";
		echo $drow['fanLevel']."', '".$drow['swing']."', '".$drow['horizontalSwing']."', '".$drow['enabled']."'";
		echo "); return false;\" style=\"cursor: pointer; color: #085f24;\">Edit</td>\n";
		echo "<td onClick=\"deleteTimeSetting('".$drow['created']."', '".$drow['uid']."'); return false;\" style=\"cursor: pointer;color: #085f24;\">Delete</td>\n";
		echo "</tr>\n";
	}
?>
	</table><br/><br/>
	<b onClick="newTimeSetting(); return false;" style="cursor: pointer;color: #085f24;">Add Climate Setting</b>
    </div>
  </form>
</div>
<div id="id05" class="modal">
  <form class="modal-content animate" action="graphs.php" method="post">
    <div class="imgcontainer">
      <span onclick="cancelAddUpdateTime(); return false;" class="close">&times;</span>
    </div>
    <div class="container">
	<h1 style='text-align: center;'>Climate Settings</h1><br/>
	<input id="created5" type="hidden" name="created5" />
	<input id="startTS5" type="hidden" name="startTS" />
	<input id="podUID5" type="hidden" name="podUID5" />
	<label for="dayOfWeek5"><b>Day(s) of the Week:</b></label><br/>
	<select class="myInputs5" id="days5" name="days[]" size="7" multiple="multiple" required>
<?php
	for($i = 0; $i < 7; $i++)
	{
		$day = date('l', mktime(0, 0, 0, 0, $i + 6, 0));
		echo "<option value='$i' selected>$day</option>\n";
	}
?>
	</select>
	<div id="wrapper">
		<div id="divLeft">
	<label for="startTime5"><b>Start Time:</b></label>
	<input id='startTime5' name='startTime' class="myInputs5" data-clocklet="class-name: clocklet-options-1; alignment: center;">
	<label for="turnOnOff5"><b>Turn On/Off:</b></label><select class="myInputs5" id="turnOnOff5" name="turnOnOff">
<?php
	$dquery = "SELECT DISTINCT SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING(COLUMN_TYPE, 7, LENGTH(COLUMN_TYPE) - 8), \"','\", 1 + units.i + tens.i * 10) , \"','\", -1) AS value FROM INFORMATION_SCHEMA.COLUMNS CROSS JOIN ".
			"(SELECT 0 AS i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) units CROSS JOIN (SELECT 0 AS i UNION SELECT 1 ".
			"UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) tens WHERE TABLE_NAME = 'timesettings' AND COLUMN_NAME = 'turnOnOff'";
	$dres = mysqli_query($link, $dquery);
	while($drow = mysqli_fetch_assoc($dres))
	{
		$v = $drow['value'];
		echo "\t\t<option value='$v'>$v</option>\n";
	}
?>
	</select>
	<label for="mode5"><b>Mode:</b></label><select class="myInputs5" id="mode5" name="mode" onChange="populateSelect('5'); return false;">
<?php
	$defmode = "";
	$dquery = "SELECT DISTINCT SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING(COLUMN_TYPE, 7, LENGTH(COLUMN_TYPE) - 8), \"','\", 1 + units.i + tens.i * 10) , \"','\", -1) AS value FROM INFORMATION_SCHEMA.COLUMNS CROSS JOIN ".
			"(SELECT 0 AS i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) units CROSS JOIN (SELECT 0 AS i UNION SELECT 1 ".
			"UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) tens WHERE TABLE_NAME = 'timesettings' AND COLUMN_NAME = 'mode'";
	$dres = mysqli_query($link, $dquery);
	while($drow = mysqli_fetch_assoc($dres))
	{
		$v = $drow['value'];
		echo "\t\t<option value='$v'>$v</option>\n";
		if($defmode == "")
			$defmode = $v;
	}
?>
	</select>
	<label for="targetTemperature5"><b>Target Temperature:</b></label><select class="myInputs5" id='targetTemperature5' name='targetTemperature'>
<?php
	$defmode = 'cool';
	$query = "SELECT value FROM meta WHERE uid='${row['uid']}' AND mode='$defmode' AND keyval='temperatures'";
	$dres = mysqli_query($link, $query);
	while($drow = mysqli_fetch_assoc($dres))
	{
		$v = $drow['value'];
		echo "\t\t<option value='$v'>$v</option>\n";
	}
?>
	</select>
		</div>
		<div id="divRight">
	<label for="endTime5"><b>End Time:</b></label>
	<input id='endTime5' name='endTime' class="myInputs5" data-clocklet="class-name: clocklet-options-1; alignment: center;">
	<label for="fanLevel5"><b>Fan Level:</b></label><select class="myInputs5" id='fanLevel5' name="fanLevel">
<?php
	$query = "SELECT DISTINCT SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING(COLUMN_TYPE, 7, LENGTH(COLUMN_TYPE) - 8), \"','\", 1 + units.i + tens.i * 10) , \"','\", -1) AS value FROM INFORMATION_SCHEMA.COLUMNS CROSS JOIN ".
			"(SELECT 0 AS i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) units CROSS JOIN (SELECT 0 AS i UNION SELECT 1 ".
			"UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) tens WHERE TABLE_NAME = 'timesettings' AND COLUMN_NAME = 'fanLevel'";
	$dres = mysqli_query($link, $query);
	while($drow = mysqli_fetch_assoc($dres))
	{
		$v = $drow['value'];
		echo "\t\t<option value='$v'>$v</option>\n";
	}
?>
		</select>
	<label for="swing5"><b>Swing:</b></label><select class="myInputs5" id="swing5" name="swing">
<?php
	$query = "SELECT DISTINCT SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING(COLUMN_TYPE, 7, LENGTH(COLUMN_TYPE) - 8), \"','\", 1 + units.i + tens.i * 10) , \"','\", -1) AS value FROM INFORMATION_SCHEMA.COLUMNS CROSS JOIN ".
			"(SELECT 0 AS i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) units CROSS JOIN (SELECT 0 AS i UNION SELECT 1 ".
			"UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) tens WHERE TABLE_NAME = 'timesettings' AND COLUMN_NAME = 'swing'";
	$dres = mysqli_query($link, $query);
	while($drow = mysqli_fetch_assoc($dres))
	{
		$v = $drow['value'];
		echo "\t\t<option value='$v'>$v</option>\n";
	}
?>
		</select>
	<label for="horizontalSwing5"><b>Horizontal Swing:</b></label><select class="myInputs5" id="horizontalSwing5" name="horizontalSwing">
<?php
	$query = "SELECT DISTINCT SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING(COLUMN_TYPE, 7, LENGTH(COLUMN_TYPE) - 8), \"','\", 1 + units.i + tens.i * 10) , \"','\", -1) AS value FROM INFORMATION_SCHEMA.COLUMNS CROSS JOIN ".
			"(SELECT 0 AS i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) units CROSS JOIN (SELECT 0 AS i UNION SELECT 1 ".
			"UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) tens WHERE TABLE_NAME = 'timesettings' AND COLUMN_NAME = 'horizontalSwing'";
	$dres = mysqli_query($link, $query);
	while($drow = mysqli_fetch_assoc($dres))
	{
		$v = $drow['value'];
		echo "\t\t<option value='$v'>$v</option>\n";
	}
?>
	</select>
		</div>
	</div>
	<label for="enabled5"><b>Enabled:</b></label><input style='text-align: left;' type="checkbox" id="enabled5" name="enabled" value="1" checked />
	<button id="submitAddUpdate5" type="submit">Add</button>
    </div>
  </form>
</div>
<div id="id06" class="modal">
  <form class="modal-content animate" action="graphs.php" method="post">
    <div class="imgcontainer">
      <span onclick="document.getElementById('id06').style.display='none'" class="close">&times;</span>
    </div>
    <div class="container">
	<h1>Error!</h1>
	<b>
	<div id='errorMessage' style='text-align:center;color:red;font-size:xx-large;'>&nbsp;</div>
	</b>
    </div>
  </form>
</div>
<script>

var timePeriod = "<?=$timePeriod?>";
var period = <?=$period?>;
var uid = "<?=$row['uid']?>";
var currtime = "";
document.getElementById("podUID1").value = uid;
document.getElementById("podUID2").value = uid;
document.getElementById("podUID5").value = uid;

var startTS = <?=$startTS?>;
document.getElementById("startTS1").value = startTS;
document.getElementById("startTS2").value = startTS;
document.getElementById("startTS5").value = startTS;

var modal1 = document.getElementById('id01');
var modal2 = document.getElementById('id02');
var modal3 = document.getElementById('id03');
var modal4 = document.getElementById('id04');
var modal5 = document.getElementById('id05');
var modal6 = document.getElementById('id06');

var chart1 = new CanvasJS.Chart("chartContainer",
{
	animationEnabled: true,
	exportEnabled: true,
	zoomEnabled: true,
	title:
	{
		text: "Feels Like Vs Humidity Vs Temperature"
	},
	toolTip:
	{
		contentFormatter: function(e)
		{
			var content = "";
			if(timePeriod == "year" || timePeriod == "month")
				content += CanvasJS.formatDate(e.entries[0].dataPoint.x, "D MMM") + "</br>------------";
			else
				content += CanvasJS.formatDate(e.entries[0].dataPoint.x, "D MMM, h:mmTT") + "</br>------------";

			for(var i = 0; i < e.entries.length; i++)
			{
				var entry = e.entries[i];

				if(entry.dataSeries.name == "Temperature [°C]")
					content += "<div style='color:<?=$tempColour?>'>" + entry.dataSeries.name + ": " +  entry.dataPoint.y + "°C</div>";
				else if(entry.dataSeries.name == "Humidity [%]")
					content += "<div style='color:<?=$humidColour?>'>" + entry.dataSeries.name + ": " +  entry.dataPoint.y + "%</div>";
				else if(entry.dataSeries.name == "Feels Like [°C]")
					content += "<div style='color:<?=$FLColour?>'>" + entry.dataSeries.name + ": " +  entry.dataPoint.y + "°C</div>";

				if(entry.dataPoint.markerType == 'cross')
					content += "<br/><div>Aircon was turned " + entry.dataPoint.inindexLabel + "</div>";
			}
			return content;
		},
		shared: true,
	},
	axisX:
	{
		title: "Time",
		interval: 1,
		intervalType: "hour",
		valueFormatString: "D MMM, hTT",
		labelAngle: -20,
	},
	axisY:
	{
		title: "Temperature [°C]",
		titleFontColor: "<?=$tempColour?>",
		lineColor: "<?=$tempColour?>",
		labelFontColor: "<?=$tempColour?>",
		tickColor: "<?=$tempColour?>",
	},
	axisY2:
	{
		title: "Humidity [%]",
		titleFontColor: "<?=$humidColour?>",
		lineColor: "<?=$humidColour?>",
		labelFontColor: "<?=$humidColour?>",
		tickColor: "<?=$humidColour?>",
	},
	legend:
	{
		cursor: "pointer",
		dockInsidePlotArea: true,
		itemclick: toggleDataSeries
	},
	data:
	[
		{
			type: "spline",
			name: "Feels Like [°C]",
			xValueType: "dateTime",
			markerSize: 0,
			showInLegend: true,
			color: "<?=$FLColour?>",
		},{
			type: "spline",
			axisYType: "secondary",
			name: "Humidity [%]",
			xValueType: "dateTime",
			markerSize: 0,
			showInLegend: true,
			color: "<?=$humidColour?>",
		},{
			type: "spline",
			name: "Temperature [°C]",
			xValueType: "dateTime",
			markerSize: 0,
			showInLegend: true,
			color: "<?=$tempColour?>",
		}
	],
});

var chart2 = new CanvasJS.Chart("rssiContainer",
{
	animationEnabled: true,
	exportEnabled: true,
	zoomEnabled: true,
	title:
	{
		text: "WIFI Signal Strength dBm"
	},
	toolTip:
	{
		contentFormatter: function(e)
		{
			var content = "";
			if(timePeriod == "year" || timePeriod == "month")
				content += CanvasJS.formatDate(e.entries[0].dataPoint.x, "D MMM") + "</br>------------";
			else
				content += CanvasJS.formatDate(e.entries[0].dataPoint.x, "D MMM, h:mmTT") + "</br>------------";

			for(var i = 0; i < e.entries.length; i++)
			{
				var entry = e.entries[i];
				content += "</br><div style='color:<?=$wifiColour?>'>" + entry.dataSeries.name + ": " +  entry.dataPoint.y + " dBm</div>";
			}
			return content;
		},
		shared: true,
	},
	axisX:
	{
		title: "Time",
		interval: 1,
		intervalType: "hour",
		valueFormatString: "D MMM, hTT",
		labelAngle: -20,
	},
	axisY:
	{
		title: "Signal Strength [dBm]",
		titleFontColor: "<?=$wifiColour?>",
		lineColor: "<?=$wifiColour?>",
		labelFontColor: "<?=$wifiColour?>",
		tickColor: "<?=$wifiColour?>",
	},
	legend:
	{
		cursor: "pointer",
		dockInsidePlotArea: true,
		itemclick: toggleDataSeries
	},
	data:
	[{
		type: "spline",
		name: "Signal Strength [dBm]",
		xValueType: "dateTime",
		markerSize: 0,
		showInLegend: true,
		color: "<?=$wifiColour?>",
       	}]
});

var chart3 = new CanvasJS.Chart("costContainer",
{
	animationEnabled: true,
	exportEnabled: true,
	zoomEnabled: true,
	title:
	{
		text: "Cost of Running"
	},
	toolTip:
	{
		contentFormatter: function(e)
		{
			var content = "";
			if(timePeriod == "year")
			{
				var isoWeek = getISOWeekNumber(e.entries[0].dataPoint.x);
				content += "Week " + isoWeek + ", " + CanvasJS.formatDate(e.entries[0].dataPoint.x, "MMM YYYY") + "</br>------------";
			} else if(timePeriod == "month") {
				content += CanvasJS.formatDate(e.entries[0].dataPoint.x, "D MMM") + "</br>------------";
			} else {
				content += CanvasJS.formatDate(e.entries[0].dataPoint.x, "D MMM, h:mmTT") + "</br>------------";
			}

			for(var i = 0; i < e.entries.length; i++)
			{
				var entry = e.entries[i];
				content += "</br><div style='color:<?=$costColour?>'>" + entry.dataSeries.name + ": " +  CanvasJS.formatNumber(entry.dataPoint.y, "$#,##0.00") + "</div>";
			}
			return content;
		},
		shared: true,
	},
	axisX:
	{
		title: "Time",
		interval: 1,
		intervalType: "hour",
		valueFormatString: "D MMM, hTT",
		labelAngle: -20,
	},
	axisY:
	{
		title: "Cost [$]",
		includeZero: true,
		titleFontColor: "<?=$costColour?>",
		lineColor: "<?=$costColour?>",
		labelFontColor: "<?=$costColour?>",
		tickColor: "<?=$costColour?>",
		labelFormatter: function (e)
		{
			return CanvasJS.formatNumber(e.value, "$#,##0.00");
		},
	},
	legend:
	{
		cursor: "pointer",
		dockInsidePlotArea: true,
		itemclick: toggleDataSeries
	},
	dataPointWidth: 50,
	data:
	[{
		type: "column",
		name: "Cost [$]",
		xValueType: "dateTime",
		markerSize: 0,
		showInLegend: true,
		color: "<?=$costColour?>",
		click: function(e)
		{
			if(timePeriod == "month")
				tperiod = 86400000;
			if(timePeriod == "year")
				tperiod = 604800000;

			window.location = 'graphs.php?startTS=' + e.dataPoint.x + '&period=' + tperiod + '&uid=' + uid;
		},
       	}],
});

var charts = [];
charts.push(chart1);
charts.push(chart2);
charts.push(chart3);

syncCharts(charts, true, true, true);

function syncCharts(charts, syncToolTip, syncCrosshair, syncAxisXRange)
{
	if(!this.onToolTipUpdated)
	{
		this.onToolTipUpdated = function(e)
		{
			for(var j = 0; j < charts.length; j++)
				if(charts[j] != e.chart)
					charts[j].toolTip.showAtX(e.entries[0].xValue);
		}
	}

	if(!this.onToolTipHidden)
	{
		this.onToolTipHidden = function(e)
		{
			for(var j = 0; j < charts.length; j++)
				if(charts[j] != e.chart)
					charts[j].toolTip.hide();
		}
	}

	if(!this.onRangeChanged)
	{
		this.onRangeChanged = function(e)
		{
			for(var j = 0; j < charts.length; j++)
			{
				if(e.trigger === "reset")
				{
					charts[j].options.axisX.viewportMinimum = charts[j].options.axisX.viewportMaximum = null;
					charts[j].options.axisY.viewportMinimum = charts[j].options.axisY.viewportMaximum = null;
					charts[j].render();
				} else if (charts[j] !== e.chart) {
					charts[j].options.axisX.viewportMinimum = e.axisX[0].viewportMinimum;
					charts[j].options.axisX.viewportMaximum = e.axisX[0].viewportMaximum;
					charts[j].render();
				}
			}
		}
	}

	for(var i = 0; i < charts.length; i++)
	{
		if(syncToolTip)
		{
			if(!charts[i].options.toolTip)
				charts[i].options.toolTip = {};

			charts[i].options.toolTip.updated = this.onToolTipUpdated;
			charts[i].options.toolTip.hidden = this.onToolTipHidden;
		}

		if(syncAxisXRange)
		{
			charts[i].options.zoomEnabled = true;
			charts[i].options.rangeChanged = this.onRangeChanged;
		}
	}
}

function toggleDataSeries(e)
{
	if (typeof(e.dataSeries.visible) === "undefined" || e.dataSeries.visible)
	{
		e.dataSeries.visible = false;
	} else {
		e.dataSeries.visible = true;
	}

	chart1.render();
}

function getISOWeekNumber(dt)
{
	dt = new Date(dt);
	var tdt = new Date(dt.valueOf());
	var dayn = (dt.getDay() + 6) % 7;
	tdt.setDate(tdt.getDate() - dayn + 3);
	var firstThursday = tdt.valueOf();
	tdt.setMonth(0, 1);

	if (tdt.getDay() !== 4)
		tdt.setMonth(0, 1 + ((4 - tdt.getDay()) + 7) % 7);

	isoWeek = 1 + Math.ceil((firstThursday - tdt) / 604800000);

	return isoWeek;
}

async function toggleAC()
{
<?php
	if(!isset($apikey) || $apikey == "apikey" || $apikey == "<insert APIkey here>" || $apikey == "")
		echo "\talert('API key isn\'t set in mariadb.php, can\'t toggle your AC');\n\treturn;\n\n";
?>

	const img = document.getElementById("onoff");
	const imgsrc = img.src.split('/');
	if(imgsrc[imgsrc.length - 1] == 'on.png')
		img.src = "off.png";
	else
		img.src = "on.png";

	const url = 'toggleAC.php?time=' + new Date().getTime() + '&uid=' + uid;
	const response = await fetch(url);
	const ret = await response.text();
	if(ret != 200)
	{
		alert("There was a problem with your request, " + ret);
		return;
	}
}

async function DataLoop()
{
	setTimeout('DataLoop()', 15000);

	now = new Date().getTime();
	if(startTS >= now - 87300000 && startTS <= now - 85500000)
	{
		startTS = now - period;
		document.getElementById("startTS1").value = startTS;
		document.getElementById("startTS2").value = startTS;
	}

	startDataLoop(false);
}

async function startDataLoop(force)
{
	try
	{
		var url = 'data.php?time=' + new Date().getTime();
		if(uid != '')
			url += "&uid=" + uid;
		url += "&startTS=" + startTS;
		url += "&period=" + period;
console.log(url);

		const response = await fetch(url);
		const ret = await response.json();

		if(ret['status'] != 200)
		{
			alert(ret['error']);
			return;
		}

		content = ret['content'];
console.log(currtime);
console.log(content['currtime']);
		if(!force && currtime == content['currtime'])
			return;
console.log("Update should have happened.");

		currtime = content['currtime'];
		startTS = content['startTS'];
		document.getElementById("startTS1").value = startTS;
		document.getElementById("startTS2").value = startTS;

		document.getElementById("commands").innerHTML = content['commands'];
		uid = content['uid'];
		document.getElementById("podUID1").value = uid;
		document.getElementById("podUID2").value = uid;
		document.getElementById("podUID5").value = uid;

		if(timePeriod == "day")
		{
			chart1.options.axisX.intervalType = 'hour';
			chart2.options.axisX.intervalType = 'hour';
			chart3.options.axisX.intervalType = 'hour';
			chart3.options.dataPointWidth = 50;

			chart1.options.axisX.valueFormatString = 'D MMM, hTT';
			chart2.options.axisX.valueFormatString = 'D MMM, hTT';
			chart3.options.axisX.valueFormatString = 'D MMM, hTT';
		}

		if(timePeriod == "week")
		{
			chart1.options.axisX.intervalType = 'day';
			chart2.options.axisX.intervalType = 'day';
			chart3.options.axisX.intervalType = 'day';
			chart3.options.dataPointWidth = 7;

			chart1.options.axisX.valueFormatString = 'D MMM';
			chart2.options.axisX.valueFormatString = 'D MMM';
			chart3.options.axisX.valueFormatString = 'D MMM';
		}

		if(timePeriod == "month")
		{
			chart1.options.axisX.intervalType = 'week';
			chart2.options.axisX.intervalType = 'week';
			chart3.options.axisX.intervalType = 'week';
			chart3.options.dataPointWidth = 40;

			chart1.options.axisX.valueFormatString = 'D MMM YY';
			chart2.options.axisX.valueFormatString = 'D MMM YY';
			chart3.options.axisX.valueFormatString = 'D MMM YY';
		}

		if(timePeriod == "year")
		{
			chart1.options.axisX.intervalType = 'month';
			chart2.options.axisX.intervalType = 'month';
			chart3.options.axisX.intervalType = 'month';
			chart3.options.dataPointWidth = 20;

			chart1.options.axisX.valueFormatString = 'MMM YYYY';
			chart2.options.axisX.valueFormatString = 'MMM YYYY';
			chart3.options.axisX.valueFormatString = 'MMM YYYY';
		}

		chart1.options.data[0].dataPoints = content['dataPoints3'];
		chart1.options.data[1].dataPoints = content['dataPoints2'];
		chart1.options.data[2].dataPoints = content['dataPoints1'];
		chart2.options.data[0].dataPoints = content['dataPoints4'];
		chart3.options.data[0].dataPoints = content['dataPoints5'];

		for(var i = 0; i < charts.length; i++)
			charts[i].render();
	} catch (e) {
		console.log(e)
	}
}

async function popSelect(dropdown, content, current)
{
	dropdown.innerHTML = '';
	for(let i = 0; i < content.length; i++)
	{
		var opt = document.createElement('option');
		opt.value = content[i];
		opt.innerHTML = content[i];
		if(content[i] == current)
			opt.selected = true;
		dropdown.appendChild(opt);
	}
}

async function doPop(mode, val, contentType, fieldID)
{
	var url = 'modes.php?time=' + new Date().getTime() + '&uid=' + uid + '&mode=' + mode + '&keyval=' + val;
console.log(url);
	const response = await fetch(url);
	const ret = await response.json();

	if(ret['status'] == 200)
		popSelect(document.getElementById(contentType + fieldID), ret['content'], ret[contentType]);
	else
		console.log(ret);
}

async function populateSelect(fieldID)
{
	try
	{
		var e = document.getElementById("mode" + fieldID);
		var value = e.options[e.selectedIndex].value;

		doPop(value, 'temperatures', 'targetTemperature', fieldID);
		doPop(value, 'fanLevels', 'fanLevel', fieldID);
		doPop(value, 'swing', 'swing', fieldID);
		doPop(value, 'horizontalSwing', 'horizontalSwing', fieldID);
	} catch (e) {
		console.log(e)
	}
}

async function changeAC(value)
{
	uid = value;
	var url = 'getDefaultMode.php?time=' + new Date().getTime() + '&uid=' + uid;

	const response = await fetch(url);
	const ret = await response.json();

	if(ret['status'] == 200)
	{
		for(let i = 0; i < document.getElementById("mode1").options.length; i++)
		{
			if(document.getElementById("mode1").options[i].value == ret['content'])
				document.getElementById("mode1").options[i].selected = 'selected';
		}
	}

	startDataLoop(true);
}

async function changeTP(value)
{
	timePeriod = value;

	if(value == 'day')
		period = 86400000;

	if(value == 'week')
		period = 604800000;

	if(value == 'month')
		period = 2592000000;

	if(value == 'year')
		period = 31536000000;

	startTS = new Date().getTime() - period;
	document.getElementById("startTS1").value = startTS;
	document.getElementById("startTS2").value = startTS;
	startDataLoop(true);
}

function prevDay()
{
	startTS -= period;
	document.getElementById("startTS1").value = startTS;
	document.getElementById("startTS2").value = startTS;
	startDataLoop(true);
}

function nextDay()
{
	startTS += period;
	document.getElementById("startTS1").value = startTS;
	document.getElementById("startTS2").value = startTS;
	startDataLoop(true);
}

function logout()
{
	window.location = 'graphs.php?logout=1';
}

function settings()
{
	modal1.style.display = "block";
}

function showSettings()
{
	modal2.style.display = "block";
}

function newSetting()
{
	document.getElementById("created2").value = "";
	document.getElementById("onOff2").options[1].selected = 'selected';
	document.getElementById("targetType2").options[0].selected = 'selected';
	document.getElementById("targetOp2").options[0].selected = 'selected';
	document.getElementById("targetValue2").value = "30";

	document.getElementById("startTime2").value = "00:00";
	document.getElementById("endTime2").value = "23:59";

	document.getElementById("turnOnOff2").options[0].selected = 'selected';
	document.getElementById("targetTemperature2").options[8].selected = 'selected';

	document.getElementById("mode2").options[0].selected = 'selected';

	document.getElementById("fanLevel2").options[2].selected = 'selected';
	document.getElementById("swing2").options[1].selected = 'selected';
	document.getElementById("horizontalSwing2").options[3].selected = 'selected';

	document.getElementById("enabled2").checked = true;
	document.getElementById("submitAddUpdate2").innerHTML = "Add Setting";

	modal2.style.display = "none";
	modal3.style.display = "block";
}

function editSetting(created, uid, onOff, targetType, targetOp, targetValue, startTime, endTime, turnOnOff, targetTemperature, mode, fanLevel, swing, horizontalSwing, enabled)
{
	document.getElementById("created2").value = created;

	var def = null;
	var dd = null;

	def = onOff;
	dd = document.getElementById("onOff2");
	for(let i = 0; i < dd.options.length; i++)
	{
		if(dd.options[i].value == def)
			dd.options[i].selected = 'selected';
	}

	def = targetType;
	dd = document.getElementById("targetType2");
	for(let i = 0; i < dd.options.length; i++)
	{
		if(dd.options[i].value == def)
			dd.options[i].selected = 'selected';
	}

	def = targetOp;
	dd = document.getElementById("targetOp2");
	for(let i = 0; i < dd.options.length; i++)
	{
		if(dd.options[i].value == def)
			dd.options[i].selected = 'selected';
	}

	dd = document.getElementById("targetValue2").value = parseInt(targetValue);
	dd = document.getElementById("startTime2").value = startTime;
	dd = document.getElementById("endTime2").value = endTime;

	def = turnOnOff;
	dd = document.getElementById("turnOnOff2");
	for(let i = 0; i < dd.options.length; i++)
	{
		if(dd.options[i].value == def)
			dd.options[i].selected = 'selected';
	}

	def = targetTemperature;
	dd = document.getElementById("targetTemperature2");
	for(let i = 0; i < dd.options.length; i++)
	{
		if(dd.options[i].value == def)
			dd.options[i].selected = 'selected';
	}

	def = mode;
	dd = document.getElementById("mode2");
	for(let i = 0; i < dd.options.length; i++)
	{
		if(dd.options[i].value == def)
			dd.options[i].selected = 'selected';
	}

	def = fanLevel;
	dd = document.getElementById("fanLevel2");
	for(let i = 0; i < dd.options.length; i++)
	{
		if(dd.options[i].value == def)
			dd.options[i].selected = 'selected';
	}

	def = swing;
	dd = document.getElementById("swing2");
	for(let i = 0; i < dd.options.length; i++)
	{
		if(dd.options[i].value == def)
			dd.options[i].selected = 'selected';
	}

	def = horizontalSwing;
	dd = document.getElementById("horizontalSwing2");
	for(let i = 0; i < dd.options.length; i++)
	{
		if(dd.options[i].value == def)
			dd.options[i].selected = 'selected';
	}

	if(enabled == 1)
		document.getElementById("enabled2").checked = true;
	else
		document.getElementById("enabled2").checked = false;

	document.getElementById("submitAddUpdate2").innerHTML = "Update";

	modal2.style.display = "none";
	modal3.style.display = "block";
}

function editTimeSetting(created, uid, daysOfWeek, startTime, endTime, turnOnOff, mode, targetTemperature, fanLevel, swing, horizontalSwing, enabled)
{
	document.getElementById("created5").value = created;

	var def = null;
	var dd = null;

	def = daysOfWeek;
	dd = document.getElementById("days5");
	for(let i = 0; i < dd.options.length; i++)
	{
		if(def & 2 ** i)
			dd.options[i].selected = 'selected';
		else
			dd.options[i].selected = '';
	}

	dd = document.getElementById("startTime5").value = startTime;
	dd = document.getElementById("endTime5").value = endTime;

	def = turnOnOff;
	dd = document.getElementById("turnOnOff5");
	for(let i = 0; i < dd.options.length; i++)
	{
		if(dd.options[i].value == def)
			dd.options[i].selected = 'selected';
	}

	def = mode;
	dd = document.getElementById("mode5");
	for(let i = 0; i < dd.options.length; i++)
	{
		if(dd.options[i].value == def)
			dd.options[i].selected = 'selected';
	}

	def = targetTemperature;
	dd = document.getElementById("targetTemperature5");
	for(let i = 0; i < dd.options.length; i++)
	{
		if(dd.options[i].value == def)
			dd.options[i].selected = 'selected';
	}

	def = fanLevel;
	dd = document.getElementById("fanLevel5");
	for(let i = 0; i < dd.options.length; i++)
	{
		if(dd.options[i].value == def)
			dd.options[i].selected = 'selected';
	}

	def = swing;
	dd = document.getElementById("swing5");
	for(let i = 0; i < dd.options.length; i++)
	{
		if(dd.options[i].value == def)
			dd.options[i].selected = 'selected';
	}

	def = horizontalSwing;
	dd = document.getElementById("horizontalSwing5");
	for(let i = 0; i < dd.options.length; i++)
	{
		if(dd.options[i].value == def)
			dd.options[i].selected = 'selected';
	}

	if(enabled == 1)
		document.getElementById("enabled5").checked = true;
	else
		document.getElementById("enabled5").checked = false;

	document.getElementById("submitAddUpdate5").innerHTML = "Update";

	modal4.style.display = "none";
	modal5.style.display = "block";
}

function cancelAddUpdate()
{
	modal3.style.display = "none";
	modal2.style.display = "block";
}

function cancelAddUpdateTime()
{
	modal5.style.display = "none";
	modal4.style.display = "block";
}

function deleteSetting(created, uid)
{
	if(confirm("Are you sure you want to delete this setting?"))
		window.location = 'graphs.php?action=delete&created=' + created + '&podUID2=' + uid;
}

function deleteTimeSetting(created, uid)
{
	if(confirm("Are you sure you want to delete this setting?"))
		window.location = 'graphs.php?action=delete&created=' + created + '&podUID5=' + uid;
}

function newTimeSetting()
{
	document.getElementById("created5").value = "";

	document.getElementById("startTime5").value = "00:00";
	document.getElementById("endTime5").value = "23:59";

	document.getElementById("turnOnOff5").options[0].selected = 'selected';
	document.getElementById("mode5").options[0].selected = 'selected';
	document.getElementById("targetTemperature5").options[8].selected = 'selected';

	document.getElementById("fanLevel5").options[2].selected = 'selected';
	document.getElementById("swing5").options[1].selected = 'selected';
	document.getElementById("horizontalSwing5").options[3].selected = 'selected';

	document.getElementById("enabled5").checked = true;
	document.getElementById("submitAddUpdate5").innerHTML = "Add Setting";

	modal4.style.display = "none";
	modal5.style.display = "block";
}

function showTimeSettings()
{
	modal4.style.display = "block";
}

DataLoop();
populateSelect("1");
populateSelect("2");
populateSelect("5");

<?php
	if(isset($_REQUEST['podUID2']) && !empty($_REQUEST['podUID2']) && $_SESSION['rw'])
	{
		echo "function delayLoading()\n";
		echo "{\n";
		echo "\tmodal2.style.display = 'block';\n";
		echo "}\n\n";
		echo "setTimeout('delayLoading()', 250);\n";
	} else if(isset($_REQUEST['podUID1']) && !empty($_REQUEST['podUID1']) && $_SESSION['rw']) {
		echo "function delayLoading()\n";
		echo "{\n";
		echo "\tmodal1.style.display = 'block';\n";
		echo "}\n\n";
		echo "setTimeout('delayLoading()', 250);\n";
	} else if(isset($_REQUEST['podUID5']) && !empty($_REQUEST['podUID5']) && $_SESSION['rw']) {
		echo "function delayLoading()\n";
		echo "{\n";
		echo "\tmodal4.style.display = 'block';\n";
		echo "}\n\n";
		echo "setTimeout('delayLoading()', 250);\n";
	}

	if($error != null)
	{
		echo "function errorLoading()\n";
		echo "{\n";
		echo "\tmodal6.style.display = 'block';\n";
		echo "\tdocument.getElementById('errorMessage').innerHTML = \"$error\";\n";
		echo "}\n\n";
		echo "errorLoading();\n";
	}
?>
</script>
</body>
</html>
