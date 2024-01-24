<?php
	require_once('mariadb.php');

	$dataPoints1 = array();
	$dataPoints2 = array();
	$dataPoints3 = array();
	$dataPoints4 = array();
	$rc = 0;

	$query = "SELECT UNIX_TIMESTAMP(whentime) * 1000 as whentime,temperature,humidity,feelslike,rssi FROM sensibo WHERE whentime >= now() - INTERVAL 2 DAY ORDER BY whentime ASC";
	$res = mysqli_query($link, $query);
	while($row = mysqli_fetch_assoc($res))
	{
		$dataPoints1[] = array('x' => $row['whentime'], 'y' => $row['temperature']);
		$dataPoints2[] = array('x' => $row['whentime'], 'y' => $row['humidity']);
		$dataPoints3[] = array('x' => $row['whentime'], 'y' => $row['feelslike']);
		$dataPoints4[] = array('x' => $row['whentime'], 'y' => $row['rssi']);
		$rc++;
	}

?>
<!DOCTYPE HTML>
<html>
<head>
<meta charset="UTF-8">
<script>
window.onload = function () {

var chart = new CanvasJS.Chart("chartContainer", {
	animationEnabled: true,
	zoomEnabled: true,
	title:{
		text: "Temperature Vs Humidity Vs Feels Like"
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
					content += "</br><div style='color:#4F81BC'>" + entry.dataSeries.name + ": " +  entry.dataPoint.y + "°C</div>";
				else if(entry.dataSeries.name == "Humidity [%]")
					content += "</br><div style='color:#C0504E'>" + entry.dataSeries.name + ": " +  entry.dataPoint.y + "%</div>";
				else
					content += "</br><div style='color:#9BBB58'>" + entry.dataSeries.name + ": " +  entry.dataPoint.y + "°C</div>";
			}

			return content;
		},
		shared: true,
	},
	axisX:{
		title: "Time",
		interval:2,
		intervalType: "hour",
		valueFormatString: "D MMM, hTT",
		labelAngle: -20,
	},
	axisY:{
		title: "Temperature [°C]",
		titleFontColor: "#4F81BC",
		lineColor: "#4F81BC",
		labelFontColor: "#4F81BC",
		tickColor: "#4F81BC"
	},
	axisY2:{
		title: "Humidity [%]",
		titleFontColor: "#C0504E",
		lineColor: "#C0504E",
		labelFontColor: "#C0504E",
		tickColor: "#C0504E"
	},
	legend:{
		cursor: "pointer",
		dockInsidePlotArea: true,
		itemclick: toggleDataSeries
	},
	data: [{
		type: "spline",
		name: "Temperature [°C]",
		xValueType: "dateTime",
		markerSize: 0,
		//toolTipContent: "Temperature: {y} °C",
		showInLegend: true,
		dataPoints: <?php echo json_encode($dataPoints1, JSON_NUMERIC_CHECK); ?>
	},{
		type: "spline",
		axisYType: "secondary",
		name: "Humidity [%]",
		xValueType: "dateTime",
		markerSize: 0,
		//toolTipContent: "{name}: {y} %",
		showInLegend: true,
		dataPoints: <?php echo json_encode($dataPoints2, JSON_NUMERIC_CHECK); ?>
	},{
		type: "spline",
		name: "Feels Like [°C]",
		xValueType: "dateTime",
		markerSize: 0,
		//toolTipContent: "{name}: {y} °C",
		showInLegend: true,
		dataPoints: <?php echo json_encode($dataPoints3, JSON_NUMERIC_CHECK); ?>
        }]
});
chart.render();

var chart2 = new CanvasJS.Chart("rssiContainer", {
	animationEnabled: true,
	zoomEnabled: true,
	title:{
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
	axisX:{
		title: "Time",
		interval:2,
		intervalType: "hour",
		valueFormatString: "D MMM, hTT",
		labelAngle: -20,
	},
	axisY:{
		title: "Signal Strength [dBm]",
		titleFontColor: "#4F81BC",
		lineColor: "#4F81BC",
		labelFontColor: "#4F81BC",
		tickColor: "#4F81BC"
	},
	legend:{
		cursor: "pointer",
		dockInsidePlotArea: true,
		itemclick: toggleDataSeries
	},
	data: [{
		type: "spline",
		name: "Signal Strength [dBm]",
		xValueType: "dateTime",
		markerSize: 0,
		//toolTipContent: "{name}: {y} dBm",
		showInLegend: true,
		dataPoints: <?php echo json_encode($dataPoints4, JSON_NUMERIC_CHECK); ?>
        }]
});
chart2.render();

function toggleDataSeries(e){
	if (typeof(e.dataSeries.visible) === "undefined" || e.dataSeries.visible) {
		e.dataSeries.visible = false;
	}
	else{
		e.dataSeries.visible = true;
	}
	chart.render();
}

}
</script>
</head>
<body>
<div id="chartContainer" style="height: 370px; width: 100%;"></div>
<div id="rssiContainer" style="height: 370px; width: calc(100% - 50px);"></div>
<script src="https://cdn.canvasjs.com/canvasjs.min.js"></script>
</body>
</html>
