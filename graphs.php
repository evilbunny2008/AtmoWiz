<?php
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
?>
<!DOCTYPE HTML>
<html>
<head>
<title>Sensibo Data Plotting</title>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
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
</style>
</head>
<body>
<section>
  <nav style='overflow-x:hidden;overflow-y:scroll;height:'>
    <ul id='commands'>
    </ul>
  </nav>
  <article style="width: calc(100% - 350px);">
    <div id="chartContainer" style="height: 370px; width: 100%;"></div>
    <div id="rssiContainer" style="height: 370px; width: calc(100% - 50px);"></div>
  </article>
</section>
<div style='height: 32px;width: 100%'></div>
<footer id="footer">
  <div id="footer-content"><a target='_blank' href='https://github.com/evilbunny2008/sensibo-python-sdk'>&copy; 2024 by </a><a target='_blank' href='https://evilbunny.org'>evilbunny</a></div>
</footer>
<script src="https://cdn.canvasjs.com/canvasjs.min.js"></script>
<script>

var uid = "";
var currtime = "";

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

var dataPoints = [];
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
			dataPoints: dataPoints
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
	const url='toggleAC.php?time=' + new Date().getTime() + '&uid='+uid;
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
	startDataLoop();
}

async function startDataLoop()
{
	try
	{
		var url = 'data.php?time=' + new Date().getTime();
		if(uid != '')
			url += "&uid=" + uid;

		const response = await fetch(url);
		const ret = await response.json();

		if(currtime == ret['currtime'])
			return;

		currtime = ret['currtime'];

		document.getElementById("commands").innerHTML = ret['commands'];
		uid = ret['uid'];

		chart.options.data[0].dataPoints = ret['dataPoints3'];
		chart.options.data[1].dataPoints = ret['dataPoints2'];
		chart.options.data[2].dataPoints = ret['dataPoints1'];
		chart.render();

		chart2.options.data[0].dataPoints = ret['dataPoints4'];
		chart2.render();
	} catch (e) {
		console.log(e)
	}
}

function changeAC(value)
{
	uid = value;
	startDataLoop();
}

DataLoop();
</script>
</body>
</html>
