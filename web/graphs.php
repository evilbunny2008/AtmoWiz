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

	if(isset($_REQUEST['podUID']) && !empty($_REQUEST['podUID']))
	{
		$row['uid'] = mysqli_real_escape_string($link, $_REQUEST['podUID']);

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
  top: 5px;
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
  <nav style='overflow-x:hidden;overflow-y:scroll;height:calc(100vh - 32px);'>
    <ul id='commands'>
    </ul>
  </nav>
  <article style="width:calc(100% - 350px);">
    <div class="child" style='right:53%;'><img onClick="prevDay(); return false;" style='height:50px;' src='left.png' /></div>
    <div id="chartContainer" style="height: calc(100vh / 3 - 20px); width: 100%;"></div>
    <div style="height:calc(100vh / 3 * 2 - 52px); width:100%; background:#fff;">
      <div id="rssiContainer" style="height: calc(100% / 2); width: calc(100% - 50px);"></div>
      <div id="costContainer" style="height: calc(100% / 2); width: calc(100% - 50px);"></div>
    </div>
    <div class="child" style='right:26%;'><img onClick="nextDay(); return false;" style='height:50px;' src='right.png' /></div>
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
	<input id="startTS" type="hidden" name="startTS" />
	<input id="podUID" type="hidden" name="podUID" value="<?=$row['uid']?>" />
	<label for="mode"><b>Mode:</b></label>
	<select id='mode' name="mode" onChange='populateSelect(this.value); return false;'>
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
	<label for='targetTemperature'><b>Target Temperature:</b></label>
	<select id='targetTemperature' name='targetTemperature'>
	</select>
	<label for="fanLevel"><b>Fan Level:</b></label>
	<select id='fanLevel' name="fanLevel">
	</select>
	<label for="swing"><b>Swing:</b></label>
	<select id="swing" name="swing">
	</select>
	<label for="horizontalSwing"><b>Horizontal Swing:</b></label>
	<select id="horizontalSwing" name="horizontalSwing">
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

var timePeriod = "day";
var period = 86400000;
var uid = "<?=$row['uid']?>";
var currtime = "";
var startTS = <?=$startTS?>;
document.getElementById("startTS").value = startTS;

var modal = document.getElementById('id01');

window.onclick = function(event)
{
	if(event.target == modal)
		modal.style.display = "none";
}

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
			var content =  CanvasJS.formatDate(e.entries[0].dataPoint.x, "D MMM, h:mmTT") + "</br>------------";
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
		interval:2,
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
			type: "line",
			name: "Feels Like [°C]",
			xValueType: "dateTime",
			markerSize: 0,
			showInLegend: true,
			color: "<?=$FLColour?>",
		},{
			type: "line",
			axisYType: "secondary",
			name: "Humidity [%]",
			xValueType: "dateTime",
			markerSize: 0,
			showInLegend: true,
			color: "<?=$humidColour?>",
		},{
			type: "line",
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
			var content =  CanvasJS.formatDate(e.entries[0].dataPoint.x, "D MMM, h:mmTT") + "</br>------------";
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
		interval:2,
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
		type: "line",
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
		text: "Cost per hour"
	},
	toolTip:
	{
		contentFormatter: function(e)
		{
			var content =  CanvasJS.formatDate(e.entries[0].dataPoint.x, "D MMM, h:mmTT") + "</br>------------";
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
		interval:2,
		intervalType: "hour",
		valueFormatString: "D MMM, hTT",
		labelAngle: -20,
	},
	axisY:
	{
		title: "Cost per Hour [$]",
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

	if(!this.onCrosshairUpdated)
	{
		this.onCrosshairUpdated = function(e)
		{
			for(var j = 0; j < charts.length; j++)
				if(charts[j] != e.chart)
					charts[j].axisX[0].crosshair.showAt(e.value);
		}
	}

	if(!this.onCrosshairHidden)
	{
		this.onCrosshairHidden =  function(e)
		{
			for(var j = 0; j < charts.length; j++)
				if(charts[j] != e.chart)
					charts[j].axisX[0].crosshair.hide();
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

		if(syncCrosshair)
		{
			if(!charts[i].options.axisX)
				charts[i].options.axisX = { crosshair: { enabled: true }};

//			charts[i].options.axisX.crosshair.updated = this.onCrosshairUpdated;
//			charts[i].options.axisX.crosshair.hidden = this.onCrosshairHidden;
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
	        startTS = now - period;

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
		document.getElementById("startTS").value = startTS;

		document.getElementById("commands").innerHTML = content['commands'];
		uid = content['uid'];
		document.getElementById("podUID").value = content['uid'];

		if(period == 86400000)
		{
			chart1.options.axisX.intervalType = 'hour';
			chart2.options.axisX.intervalType = 'hour';
			chart3.options.axisX.intervalType = 'hour';
		}

		if(period == 604800000)
		{
			chart1.options.axisX.intervalType = 'day';
			chart2.options.axisX.intervalType = 'day';
			chart3.options.axisX.intervalType = 'day';
		}

		if(period == 2592000000)
		{
			chart1.options.axisX.intervalType = 'week';
			chart2.options.axisX.intervalType = 'week';
			chart3.options.axisX.intervalType = 'week';
		}

		if(period == 31536000000)
		{
			chart1.options.axisX.intervalType = 'month';
			chart2.options.axisX.intervalType = 'month';
			chart3.options.axisX.intervalType = 'month';
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

async function doPop(mode, val, type)
{
	var url = 'modes.php?time=' + new Date().getTime() + '&uid=' + uid + '&mode=' + mode + '&keyval=' + val;
console.log(url);
	const response = await fetch(url);
	const ret = await response.json();

	if(ret['status'] == 200)
		popSelect(document.getElementById(type), ret['content'], ret[type]);
	else
		console.log(ret);
}

async function populateSelect()
{
	try
	{
		var e = document.getElementById("mode");
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
		for(let i = 0; i < document.getElementById("mode").options.length; i++)
		{
			if(document.getElementById("mode").options[i].value == ret['content'])
			{
				document.getElementById("mode").options[i].selected = 'selected';
			}
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
	startDataLoop(true);
}

function prevDay()
{
	startTS -= period;
	document.getElementById("startTS").value = startTS;
	startDataLoop(true);
}

function nextDay()
{
	startTS += period;
	document.getElementById("startTS").value = startTS;
	startDataLoop(true);
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
