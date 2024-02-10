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
				echo $ret;
				die;
			}
		}
	}

	if(isset($_REQUEST['podUID2']) && !empty($_REQUEST['podUID2']) && (!isset($_REQUEST['created2']) || empty($_REQUEST['created2'])) && $_SESSION['rw'] && (!isset($_REQUEST['action']) || empty($_REQUEST['action'])))
	{
		$row['uid'] = mysqli_real_escape_string($link, $_REQUEST['podUID2']);
		$mode = mysqli_real_escape_string($link, $_REQUEST['mode']);
		$targetType = mysqli_real_escape_string($link, $_REQUEST['targetType']);
		$onValue = mysqli_real_escape_string($link, $_REQUEST['onValue']);
		$offValue = mysqli_real_escape_string($link, $_REQUEST['offValue']);
		$targetTemperature = mysqli_real_escape_string($link, $_REQUEST['targetTemperature']);
		$fanLevel = mysqli_real_escape_string($link, $_REQUEST['fanLevel']);
		$swing = mysqli_real_escape_string($link, $_REQUEST['swing']);
		$horizontalSwing = mysqli_real_escape_string($link, $_REQUEST['horizontalSwing']);
		$enabled = 0;
		if(isset($_REQUEST['enabled']) && $_REQUEST['enabled'] == '1')
			$enabled = 1;

		$query = "INSERT INTO settings (uid, created, mode, targetType, onValue, offValue, targetTemperature, fanLevel, swing, horizontalSwing, enabled) VALUES ".
			 "('${row['uid']}', NOW(), '$mode', '$targetType', '$onValue', '$offValue', '$targetTemperature', '$fanLevel', '$swing', '$horizontalSwing', $enabled)";
		mysqli_query($link, $query);
	}

	if(isset($_REQUEST['podUID2']) && !empty($_REQUEST['podUID2']) && isset($_REQUEST['created2']) && !empty($_REQUEST['created2']) && $_SESSION['rw'])
	{
		$created = mysqli_real_escape_string($link, $_REQUEST['created2']);
		$row['uid'] = mysqli_real_escape_string($link, $_REQUEST['podUID2']);
		$mode = mysqli_real_escape_string($link, $_REQUEST['mode']);
		$targetType = mysqli_real_escape_string($link, $_REQUEST['targetType']);
		$onValue = mysqli_real_escape_string($link, $_REQUEST['onValue']);
		$offValue = mysqli_real_escape_string($link, $_REQUEST['offValue']);
		$targetTemperature = mysqli_real_escape_string($link, $_REQUEST['targetTemperature']);
		$fanLevel = mysqli_real_escape_string($link, $_REQUEST['fanLevel']);
		$swing = mysqli_real_escape_string($link, $_REQUEST['swing']);
		$horizontalSwing = mysqli_real_escape_string($link, $_REQUEST['horizontalSwing']);
		$enabled = 0;
		if(isset($_REQUEST['enabled']) && $_REQUEST['enabled'] == '1')
			$enabled = 1;

		$query = "UPDATE settings SET mode='$mode', targetType='$targetType', onValue='$onValue', offValue='$offValue', targetTemperature='$targetTemperature', fanLevel='$fanLevel', ".
			 "swing='$swing', horizontalSwing='$horizontalSwing', enabled='$enabled' WHERE uid='${row['uid']}' AND created='$created'";
		mysqli_query($link, $query);
	}

	if(isset($_REQUEST['action']) && $_REQUEST['action'] == "delete" && isset($_REQUEST['podUID2']) && !empty($_REQUEST['podUID2']))
	{
		$created = mysqli_real_escape_string($link, $_REQUEST['created']);
		$row['uid'] = mysqli_real_escape_string($link, $_REQUEST['podUID2']);

		$query = "DELETE FROM settings WHERE uid='${row['uid']}' AND created='$created'";
		mysqli_query($link, $query);
	}
?>
<!DOCTYPE HTML>
<html>
<head>
<title>Sensibo Data Plotting</title>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" href="favicon.svg">
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

#mode2, #targetTemperature2, #fanLevel2, #swing2, #horizontalSwing2, #targetType2, #onValue, #offValue
{
  width: 100%;
  padding: 12px 20px;
  margin: 8px 0;
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

#id03 .modal-content
{
  width: 1300px;
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
	<select id='mode1' name="mode" onChange='populateSelect(this.value); return false;'>
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
	<input id="created2" type="hidden" name="created2" />
	<input id="startTS2" type="hidden" name="startTS" />
	<input id="podUID2" type="hidden" name="podUID2" />
	<div class="wrapper">
	<div id="leftDiv">
		<label for="mode2"><b>Mode:</b></label>
		<select id='mode2' name="mode" onChange='populateSettings(this.value); return false;'>
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
		<label for='targetType2'><b>Target Type:</b></label>
		<select id='targetType2' name='targetType'>
<?php
	$query = "SELECT DISTINCT SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING(COLUMN_TYPE, 7, LENGTH(COLUMN_TYPE) - 8), \"','\", 1 + units.i + tens.i * 10) , \"','\", -1) AS value FROM INFORMATION_SCHEMA.COLUMNS CROSS JOIN ".
			"(SELECT 0 AS i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) units CROSS JOIN (SELECT 0 AS i UNION SELECT 1 ".
			"UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) tens WHERE TABLE_NAME = 'settings' AND COLUMN_NAME = 'targetType'";
	$dres = mysqli_query($link, $query);
	while($drow = mysqli_fetch_assoc($dres))
	{
		$v = $drow['value'];
		echo "\t\t<option value='$v'>$v</option>\n";
	}
?>
		</select>
		<label for="onValue"><b>On Value:</b></label>
		<input type="text" id="onValue" name="onValue" value="28" />

		<label for="offValue"><b>Off Value:</b></label>
		<input type="text" id="offValue" name="offValue" value="26.1" />
	</div>
	<div id="rightDiv">
		<label for='targetTemperature2'><b>Target Temperature:</b></label>
		<select id='targetTemperature2' name='targetTemperature'>
<?php
	$query = "SELECT value FROM meta WHERE uid='${row['uid']}' AND mode='$defmode' AND keyval='temperatures'";
	$dres = mysqli_query($link, $query);
	while($drow = mysqli_fetch_assoc($dres))
	{
		$v = $drow['value'];
		echo "\t\t<option value='$v'>$v</option>\n";
	}
?>
		</select>
		<label for="fanLevel2"><b>Fan Level:</b></label>
		<select id='fanLevel2' name="fanLevel">
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
		<label for="swing2"><b>Swing:</b></label>
		<select id="swing2" name="swing">
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
		<label for="horizontalSwing2"><b>Horizontal Swing:</b></label>
		<select id="horizontalSwing2" name="horizontalSwing">
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
	<label for="enabled2"><b>Enabled</b></label>
	<input type="checkbox" id="enabled2" name="enabled" value="1" checked />

	<button id="submitAddUpdate" type="submit">Add</button>
    </div>
  </form>
</div>
<div id="id03" class="modal" style="text-align: center;">
  <form class="modal-content animate" action="graphs.php" method="post">
    <div class="imgcontainer">
      <span onclick="document.getElementById('id03').style.display='none'" class="close">&times;</span>
    </div>
    <div class="container">
	<h1>Climate Settings</h1>
	<table>
	<tr>
		<th>Created</th>
		<th>Mode</th>
		<th>Target Type</th>
		<th>On Value</th>
		<th>Off Value</th>
		<th>Target Temperature</th>
		<th>Fan Level</th>
		<th>Swing</th>
		<th>Horizontal Swing</th>
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
		echo "<td>".$drow['mode']."</td>";
		echo "<td>".$drow['targetType']."</td>";
		echo "<td>".$drow['onValue']."</td>";
		echo "<td>".$drow['offValue']."</td>";
		echo "<td>".$drow['targetTemperature']."</td>";
		echo "<td>".$drow['fanLevel']."</td>";
		echo "<td>".$drow['swing']."</td>";
		echo "<td>".$drow['horizontalSwing']."</td>";
		echo "<td>";
		if($drow['enabled'])
			echo "True";
		else
			echo "False";
		echo "</td>";

		echo "<td onClick=\"editSetting('".$drow['created']."', '".$drow['uid']."', '".$drow['mode']."', '".$drow['targetType']."', '".$drow['onValue']."', '".$drow['offValue']."', '";
		echo $drow['targetTemperature']."', '".$drow['fanLevel']."', '".$drow['swing']."', '".$drow['horizontalSwing']."', '".$drow['enabled'];
		echo "'); return false;\" style=\"cursor: pointer; color: #085f24;\">Edit</td>";
		echo "<td onClick=\"deleteSetting('".$drow['created']."', '".$drow['uid']."'); return false;\" style=\"cursor: pointer;color: #085f24;\">Delete</td>";
		echo "</tr>\n";
	}
?>
	</table><br/><br/>
	<b onClick="newSetting(); return false;" style="cursor: pointer;color: #085f24;">Add Climate Setting</b>
    </div>
  </form>
</div>
<script src="canvasjs.min.js"></script>
<script>

var timePeriod = "day";
var period = 86400000;
var uid = "<?=$row['uid']?>";
var currtime = "";
document.getElementById("podUID1").value = uid;
document.getElementById("podUID2").value = uid;

var startTS = <?=$startTS?>;
document.getElementById("startTS1").value = startTS;
document.getElementById("startTS2").value = startTS;

var modal1 = document.getElementById('id01');
var modal2 = document.getElementById('id02');
var modal3 = document.getElementById('id03');

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

		if(!force && currtime == content['currtime'])
			return;

		currtime = content['currtime'];
		startTS = content['startTS'];
		document.getElementById("startTS1").value = startTS;
		document.getElementById("startTS2").value = startTS;

		document.getElementById("commands").innerHTML = content['commands'];
		uid = content['uid'];
		document.getElementById("podUID1").value = uid;
		document.getElementById("podUID2").value = uid;

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

		populateSelect();
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

async function doPop(mode, val, contentType)
{
	var url = 'modes.php?time=' + new Date().getTime() + '&uid=' + uid + '&mode=' + mode + '&keyval=' + val;
console.log(url);
	const response = await fetch(url);
	const ret = await response.json();

	if(ret['status'] == 200)
		popSelect(document.getElementById(contentType+"1"), ret['content'], ret[contentType]);
	else
		console.log(ret);
}

async function populateSelect()
{
	try
	{
		var e = document.getElementById("mode1");
		var value = e.options[e.selectedIndex].value;

		doPop(value, 'temperatures', 'targetTemperature');
		doPop(value, 'fanLevels', 'fanLevel');
		doPop(value, 'swing', 'swing');
		doPop(value, 'horizontalSwing', 'horizontalSwing');
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
	modal3.style.display = "block";
}

function newSetting()
{
	document.getElementById("created2").value = "";
	document.getElementById("podUID2").value = uid;

	document.getElementById("mode2").options[0].selected = 'selected';
	document.getElementById("targetType2").options[0].selected = 'selected';

	document.getElementById("onValue").value = "";
	document.getElementById("offValue").value = "";

	document.getElementById("targetTemperature2").options[0].selected = 'selected';
	document.getElementById("fanLevel2").options[0].selected = 'selected';
	document.getElementById("swing2").options[0].selected = 'selected';
	document.getElementById("horizontalSwing2").options[0].selected = 'selected';

	document.getElementById("enabled2").checked = true;
	document.getElementById("submitAddUpdate").innerHTML = "Add Setting";

	modal3.style.display = "none";
	modal2.style.display = "block";
}

function editSetting(created, uid, mode, targetType, onValue, offValue, targetTemperature, fanLevel, swing, horizontalSwing, enabled)
{
	document.getElementById("created2").value = created;
	document.getElementById("podUID2").value = uid;

	for(let i = 0; i < document.getElementById("mode2").options.length; i++)
	{
		if(document.getElementById("mode2").options[i].value == mode)
			document.getElementById("mode2").options[i].selected = 'selected';
	}

	for(let i = 0; i < document.getElementById("targetType2").options.length; i++)
	{
		if(document.getElementById("targetType2").options[i].value == targetType)
			document.getElementById("targetType2").options[i].selected = 'selected';
	}

	document.getElementById("onValue").value = onValue;
	document.getElementById("offValue").value = offValue;

	for(let i = 0; i < document.getElementById("targetTemperature2").options.length; i++)
	{
		if(document.getElementById("targetTemperature2").options[i].value == targetTemperature)
			document.getElementById("targetTemperature2").options[i].selected = 'selected';
	}

	for(let i = 0; i < document.getElementById("fanLevel2").options.length; i++)
	{
		if(document.getElementById("fanLevel2").options[i].value == fanLevel)
			document.getElementById("fanLevel2").options[i].selected = 'selected';
	}

	for(let i = 0; i < document.getElementById("swing2").options.length; i++)
	{
		if(document.getElementById("swing2").options[i].value == swing)
			document.getElementById("swing2").options[i].selected = 'selected';
	}

	for(let i = 0; i < document.getElementById("horizontalSwing2").options.length; i++)
	{
		if(document.getElementById("horizontalSwing2").options[i].value == horizontalSwing)
			document.getElementById("horizontalSwing2").options[i].selected = 'selected';
	}

	if(enabled == 1)
		document.getElementById("enabled2").checked = true;
	else
		document.getElementById("enabled2").checked = false;

	document.getElementById("submitAddUpdate").innerHTML = "Update";

	modal3.style.display = "none";
	modal2.style.display = "block";
}

function deleteSetting(created, uid)
{
	if(confirm("Are you sure you want to delete this setting?"))
		window.location = 'graphs.php?action=delete&created=' + created + '&podUID2=' + uid;
}

DataLoop();

<?php
	if(isset($_REQUEST['podUID2']) && !empty($_REQUEST['podUID2']) && $_SESSION['rw'])
	{
		echo "alert('Your climate settings were accepted.');\n";
	}
?>
</script>
</body>
</html>
