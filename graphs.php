<?php
	$error = null;
	$startTS = time() * 1000 - 86400000;

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

	function changeState($podUID, $what, $newValue)
	{
		global $apikey;

		$url = "https://home.sensibo.com/api/v2/pods/$podUID/acStates/$what?apiKey=".$apikey;
		$body = json_encode(array('newValue' => $newValue));

		$opts = array('http' => array('method'=>"PATCH", 'header' => "Accept: application/json\r\nContent-Type: application/json\r\n", 'content' => $body, 'timeout' => 5));
		$context = stream_context_create($opts);
		$ret = @file_get_contents($url, false, $context);

		$statusheader = explode(" ", $http_response_header['0'], 3)['1'];
		if($statusheader == "200")
			return 200;
		else
			return json_encode(array('headers' => $http_response_header, 'ret' => $ret));
	}

	$query = "SELECT * FROM commands ORDER BY whentime DESC LIMIT 1";
	$res = mysqli_query($link, $query);
	$row = mysqli_fetch_assoc($res);

	if(isset($_REQUEST['podUID']) && !empty($_REQUEST['podUID']))
	{
		$row['uid'] = mysqli_real_escape_string($link, $_REQUEST['podUID']);

		if(isset($_REQUEST['mode']))
		{
			if($row['mode'] != $_REQUEST['mode'])
			{
				$ret = changeState($row['uid'], 'mode', $_REQUEST['mode']);
				if($ret != 200)
				{
					echo $ret;
					die;
				}
				$row['mode'] = mysqli_real_escape_string($link, $_REQUEST['mode']);
			}
		}

		if(isset($_REQUEST['targetTemperature']))
		{
			if($row['targetTemperature'] != $_REQUEST['targetTemperature'])
			{
				$ret = changeState($row['uid'], 'targetTemperature', intval($_REQUEST['targetTemperature']));
				if($ret != 200)
				{
					echo $ret;
					die;
				}
				$row['targetTemperature'] = intval($_REQUEST['targetTemperature']);
			}
		}

		if(isset($_REQUEST['fanLevel']))
		{
			if($row['fanLevel'] != $_REQUEST['fanLevel'])
			{
				$ret = changeState($row['uid'], 'fanLevel', $_REQUEST['fanLevel']);
				if($ret != 200)
				{
					echo $ret;
					die;
				}
				$row['fanLevel'] = mysqli_real_escape_string($link, $_REQUEST['fanLevel']);
			}
		}

		if(isset($_REQUEST['swing']))
		{
			if($row['swing'] != $_REQUEST['swing'])
			{
				$ret = changeState($row['uid'], 'swing', $_REQUEST['swing']);
				if($ret != 200)
				{
					echo $ret;
					die;
				}
				$row['swing'] = mysqli_real_escape_string($link, $_REQUEST['swing']);
			}
		}

		if(isset($_REQUEST['horizontalSwing']))
		{
			if($row['horizontalSwing'] != $_REQUEST['horizontalSwing'])
			{
				$ret = changeState($row['uid'], 'horizontalSwing', $_REQUEST['horizontalSwing']);
				if($ret != 200)
				{
					echo $ret;
					die;
				}
				$row['horizontalSwing'] = mysqli_real_escape_string($link, $_REQUEST['horizontalSwing']);
			}
		}
	}

	if(isset($_REQUEST['startTS']) && !empty($_REQUEST['startTS']))
	{
		$startTS = doubleval($_REQUEST['startTS']);
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
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
  font-family: Arial, Helvetica, sans-serif;
  min-height: 750px;
  height: 750px;
}

nav {
  float: left;
  height: 780px;
  width: 350px;
  background: #ccc;
  padding: 20px;
}

nav ul {
  list-style-type: none;
  padding: 0;
}

article {
  float: left;
  padding: 20px;
  background-color: #f1f1f1;
}

section::after {
  content: "";
  display: table;
  clear: both;
}

#footer {
  margin-top:auto;
  width: 100%;
  background: #ccc;
  position: fixed;
  bottom: 0;
  left: 0;
  height: 32px;
  z-index: 1;
}

#footer-content {
  text-align: center;
  height: 32px;
  padding: 8px;
  width: 100%;
}

#footer a, #commands a {
  color: #085f24;
}

.child {
  position: absolute;
  z-index: 1;
  top: 370px;
}

body {font-family: Arial, Helvetica, sans-serif;}

#mode, #targetTemperature, #fanLevel, #swing, #horizontalSwing {
  width: 100%;
  padding: 12px 20px;
  margin: 8px 0;
  display: inline-block;
  border: 1px solid #ccc;
  box-sizing: border-box;
}

button {
  background-color: #04AA6D;
  color: white;
  padding: 14px 20px;
  margin: 8px 0;
  border: none;
  cursor: pointer;
  width: 100%;
}

button:hover {
  opacity: 0.8;
}

.cancelbtn {
  width: auto;
  padding: 10px 18px;
  background-color: #f44336;
}

.imgcontainer {
  text-align: center;
  margin: 24px 0 12px 0;
  position: relative;
}

.container {
  padding: 16px;
}

span.psw {
  float: right;
  padding-top: 16px;
}

.modal {
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

.modal-content {
  background-color: #fefefe;
  margin: 5% auto 15% auto;
  border: 1px solid #888;
  width: 20%;
}

.close {
  position: absolute;
  right: 25px;
  top: 0;
  color: #000;
  font-size: 35px;
  font-weight: bold;
}

.close:hover,
.close:focus {
  color: red;
  cursor: pointer;
}

.animate {
  -webkit-animation: animatezoom 0.6s;
  animation: animatezoom 0.6s
}

@-webkit-keyframes animatezoom {
  from {-webkit-transform: scale(0)}
  to {-webkit-transform: scale(1)}
}

@keyframes animatezoom {
  from {transform: scale(0)}
  to {transform: scale(1)}
}

@media screen and (max-width: 300px) {
  span.psw {
     display: block;
     float: none;
  }
  .cancelbtn {
     width: 100%;
  }
}
</style>
</head>
<body>
<section>
  <nav style='overflow-x:hidden;overflow-y:scroll;height:'>
    <ul id='commands'>
    </ul>
  </nav>
  <article style="width:calc(100% - 350px);">
    <div class="child"><img onClick="prevDay(); return false;" style='height:50px;' src='left.png' /></div>
    <div id="chartContainer" style="height: 370px; width: 100%;"></div>
    <div style="height:370px; width:100%; background:#fff;">
      <div id="rssiContainer" style="height: 370px; width: calc(100% - 50px);"></div>
    </div>
    <div class="child" style='right:20px;'><img onClick="nextDay(); return false;" style='height:50px;' src='right.png' /></div>
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
      <span onclick="document.getElementById('id01').style.display='none'" class="close" title="Close Modal">&times;</span>
    </div>
    <div class="container">
	<input id="startTS" type="hidden" name="startTS" value="<?=$startTS?>" />
	<input id="podUID" type="hidden" name="podUID" value="<?=$row['uid']?>" />

	<label for="mode"><b>Mode:</b></label>
	<select id='mode' name="mode">
<?php
	$modes = array('cool' => 'Cool', 'heat' => 'Heat', 'dry' => 'Dry', 'fan' => 'Fan');
	foreach($modes as $k => $v)
	{
		echo "\t\t<option value='$k'";
			if($row['mode'] == $k)
				echo ' selected';
		echo ">$v</option>\n";
	}
?>
	</select>
	<label for='targetTemperature'><b>Target Temperature:</b></label>
	<select id='targetTemperature' name='targetTemperature'>
<?php
	for($i = 16; $i <= 30; $i++)
	{
		echo "\t\t<option value='$i'";
		if($row['targetTemperature'] == $i)
			echo ' selected';
		echo ">$i</option>\n";
	}
?>
	</select>
	<label for="fanLevel"><b>Fan Level:</b></label>
	<select id='fanLevel' name="fanLevel">
<?php
	$fanlevels = array('auto' => 'Auto', 'quiet' => 'Quite', 'low' => 'Low', 'medium' => 'Medium', 'high' => 'High');
	foreach($fanlevels as $k => $v)
	{
		echo "\t\t<option value='$k'";
			if($row['fanLevel'] == $k)
				echo ' selected';
		echo ">$v</option>\n";
	}
?>
	</select>
	<label for="swing"><b>Swing:</b></label>
	<select id="swing" name="swing">
<?php
	$swings = array("stopped", "fixedTop", "fixedMiddleTop", "fixedMiddleBottom", "fixedBottom", "rangeFull");
	foreach($swings as $v)
	{
		echo "\t\t<option value='$v'";
			if($row['swing'] == $v)
				echo ' selected';
		echo ">$v</option>\n";
	}
?>
	</select>
	<label for="horizontalSwing"><b>Horizontal Swing:</b></label>
	<select id="horizontalSwing" name="horizontalSwing">
<?php
	$swings = array("stopped", "fixedLeft", "fixedCenterLeft", "fixedCenter", "fixedCenterRight", "fixedRight", "rangeFull");
	foreach($swings as $v)
	{
		echo "\t\t<option value='$v'";
			if($row['horizontalSwing'] == $v)
				echo ' selected';
		echo ">$v</option>\n";
	}
?>
	</select>
	<button type="submit">Update</button>
    </div>
    <div class="container" style="background-color:#f1f1f1">
      <button type="button" onclick="document.getElementById('id01').style.display='none'" class="cancelbtn">Cancel</button>
    </div>
  </form>
</div>
<script src="canvasjs.min.js"></script>
<script>

var uid = "";
var currtime = "";
var startTS = <?=$startTS?>;
document.getElementById("startTS").value = startTS;

var modal = document.getElementById('id01');

window.onclick = function(event)
{
	if(event.target == modal)
		modal.style.display = "none";
}

var chart = new CanvasJS.Chart("chartContainer",
{
	animationEnabled: true,
	zoomEnabled: true,
	title:
	{
		text: "Feels Like Vs Humidity Vs Temperature"
	},
	toolTip:
	{
		contentFormatter: function(e)
		{
			var content =  CanvasJS.formatDate(e.entries[0].dataPoint.x, "D MMM, h:mmTT") + "</br>------------";
			for(var i = 0; i < e.entries.length; i++)
			{
				var entry = e.entries[i];

				if(entry.dataSeries.name == "Temperature [°C]")
					content += "<div style='color:#9BBB58'>" + entry.dataSeries.name + ": " +  entry.dataPoint.y + "°C</div>";
				else if(entry.dataSeries.name == "Humidity [%]")
					content += "<div style='color:#C0504E'>" + entry.dataSeries.name + ": " +  entry.dataPoint.y + "%</div>";
				else if(entry.dataSeries.name == "Feels Like [°C]")
					content += "<div style='color:#4F81BC'>" + entry.dataSeries.name + ": " +  entry.dataPoint.y + "°C</div>";

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
		interval:2,
		intervalType: "hour",
		valueFormatString: "D MMM, hTT",
		labelAngle: -20,
	},
	axisY:
	{
		title: "Temperature [°C]",
		titleFontColor: "#9BBB58",
		lineColor: "#9BBB58",
		labelFontColor: "#9BBB58",
		tickColor: "#9BBB58"
	},
	axisY2:
	{
		title: "Humidity [%]",
		titleFontColor: "#C0504E",
		lineColor: "#C0504E",
		labelFontColor: "#C0504E",
		tickColor: "#C0504E"
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
			type: "line",
			name: "Feels Like [°C]",
			xValueType: "dateTime",
			markerSize: 0,
			showInLegend: true,
		},{
			type: "line",
			axisYType: "secondary",
			name: "Humidity [%]",
			xValueType: "dateTime",
			markerSize: 0,
			showInLegend: true,
		},{
			type: "line",
			name: "Temperature [°C]",
			xValueType: "dateTime",
			markerSize: 0,
			showInLegend: true,
		}
	]
});

var chart2 = new CanvasJS.Chart("rssiContainer",
{
	animationEnabled: true,
	zoomEnabled: true,
	title:
	{
		text: "WIFI Signal Strength dBm"
	},
	toolTip:
	{
		contentFormatter: function(e)
		{
			var content =  CanvasJS.formatDate(e.entries[0].dataPoint.x, "D MMM, h:mmTT") + "</br>------------";
			for(var i = 0; i < e.entries.length; i++)
			{
				var entry = e.entries[i];
				content += "</br><div style='color:#4F81BC'>" + entry.dataSeries.name + ": " +  entry.dataPoint.y + " dBm</div>";
			}
			return content;
		},
		shared: true,
	},
	axisX:
	{
		title: "Time",
		interval:2,
		intervalType: "hour",
		valueFormatString: "D MMM, hTT",
		labelAngle: -20,
	},
	axisY:
	{
		title: "Signal Strength [dBm]",
		titleFontColor: "#4F81BC",
		lineColor: "#4F81BC",
		labelFontColor: "#4F81BC",
		tickColor: "#4F81BC"
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
			type: "line",
			name: "Signal Strength [dBm]",
			xValueType: "dateTime",
			markerSize: 0,
			showInLegend: true,
       		}
	]
});

function toggleDataSeries(e)
{
	if (typeof(e.dataSeries.visible) === "undefined" || e.dataSeries.visible)
	{
		e.dataSeries.visible = false;
	} else {
		e.dataSeries.visible = true;
	}

	chart.render();
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
	setTimeout('DataLoop()', 5000);

	now = new Date().getTime();
	if(startTS >= now - 87300000 && startTS <= now - 85500000)
	        startTS = now - 86400000;
	document.getElementById("startTS").value = startTS;
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
		document.getElementById("startTS").value = startTS;

		document.getElementById("commands").innerHTML = content['commands'];
		uid = content['uid'];
		document.getElementById("podUID").value = content['uid'];

		chart.options.data[0].dataPoints = content['dataPoints3'];
		chart.options.data[1].dataPoints = content['dataPoints2'];
		chart.options.data[2].dataPoints = content['dataPoints1'];
		chart.render();

		chart2.options.data[0].dataPoints = content['dataPoints4'];
		chart2.render();
	} catch (e) {
		console.log(e)
	}
}

function changeAC(value)
{
	uid = value;
	startDataLoop(true);
}

function prevDay()
{
	startTS -= 86400000;
	document.getElementById("startTS").value = startTS;
	startDataLoop(true);
}

function nextDay()
{
	startTS += 86400000;
	document.getElementById("startTS").value = startTS;
	startDataLoop();
}

function logout()
{
	window.location = 'graphs.php?logout=1';
}

function settings()
{
	modal.style.display = "block";
}

DataLoop();
</script>
</body>
</html>
