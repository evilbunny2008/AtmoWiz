<?php
	require_once('mariadb.php');

	$dataPoints1 = array();
	$dataPoints2 = array();
	$dataPoints3 = array();
	$dataPoints4 = array();
	$rc = 0;

	$query = "SELECT UNIX_TIMESTAMP(whentime) * 1000 as whentime,temperature,humidity,feelslike,rssi FROM fujitsu WHERE whentime >= now() - INTERVAL 2 DAY ORDER BY whentime ASC";
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
	title:{
		text: "Temperature Vs Humidity Vs Feels Like"
	},
	axisX:{
		title: "Time",
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
		type: "line",
		name: "Temperature",
		xValueType: "dateTime",
		markerSize: 0,
		toolTipContent: "Temperature: {y} °C",
		showInLegend: true,
		dataPoints: <?php echo json_encode($dataPoints1, JSON_NUMERIC_CHECK); ?>
	},{
		type: "line",
		axisYType: "secondary",
		name: "Humidity",
		xValueType: "dateTime",
		markerSize: 0,
		toolTipContent: "{name}: {y} %",
		showInLegend: true,
		dataPoints: <?php echo json_encode($dataPoints2, JSON_NUMERIC_CHECK); ?>
	},{
		type: "line",
		name: "Feels Like",
		xValueType: "dateTime",
		markerSize: 0,
		toolTipContent: "{name}: {y} °C",
		showInLegend: true,
		dataPoints: <?php echo json_encode($dataPoints3, JSON_NUMERIC_CHECK); ?>
        }]
});
chart.render();

var chart2 = new CanvasJS.Chart("rssiContainer", {
	animationEnabled: true,
	title:{
		text: "WIFI Signal Strength dBm"
	},
	axisX:{
		title: "Time",
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
		type: "line",
		name: "Signal Strength dBm",
		xValueType: "dateTime",
		markerSize: 0,
		toolTipContent: "{name}: {y} dBm",
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
