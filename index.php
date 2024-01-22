<?php
	require_once('mariadb.php');

	$dataPoints1 = array();
	$dataPoints2 = array();
	$rc = 0;

	$query = "SELECT UNIX_TIMESTAMP(whentime) * 1000 as whentime,temperature,humidity,feelslike FROM fujitsu WHERE whentime >= now() - INTERVAL 1 DAY ORDER BY whentime ASC";
	$res = mysqli_query($link, $query);
	while($row = mysqli_fetch_assoc($res))
	{
		$dataPoints1[] = array('x' => $row['whentime'], 'y' => $row['temperature']);
		$dataPoints2[] = array('x' => $row['whentime'], 'y' => $row['humidity']);
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
		text: "Temperature Vs Humidity"
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
	}]
});
chart.render();

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
<script src="https://cdn.canvasjs.com/canvasjs.min.js"></script>
</body>
</html>
