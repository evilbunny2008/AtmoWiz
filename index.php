<?php
	require_once('mariadb.php');

	$dataPoints1 = array();
	$dataPoints2 = array();
	$dataPoints3 = array();
	$dataPoints4 = array();

	$airconon = -1;
	$query = "SELECT UNIX_TIMESTAMP(whentime) * 1000 as whentime,temperature,humidity,feelslike,rssi,airconon FROM sensibo WHERE whentime >= now() - INTERVAL 2 DAY ORDER BY whentime ASC";
	$res = mysqli_query($link, $query);
	while($row = mysqli_fetch_assoc($res))
	{
		if($row['airconon'] != $airconon)
		{
			$airconon = $row['airconon'];

			$ac = "off";
			if($airconon == 1)
				$ac = "on";
			$dataPoints1[] = array('x' => $row['whentime'], 'y' => $row['temperature'], 'inindexLabel' => $ac, 'markerType' => 'cross',  'markerSize' =>  20,'markerColor' => '#4F81BC');
		} else {
			$dataPoints1[] = array('x' => $row['whentime'], 'y' => $row['temperature']);
		}

		$dataPoints2[] = array('x' => $row['whentime'], 'y' => $row['humidity']);
		$dataPoints3[] = array('x' => $row['whentime'], 'y' => $row['feelslike']);
		$dataPoints4[] = array('x' => $row['whentime'], 'y' => $row['rssi']);
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
  min-height: 100vh;
}

/* Create two columns/boxes that floats next to each other */
nav {
  float: left;
  width: 350px;
  background: #ccc;
  padding: 20px;
}

/* Style the list inside the menu */
nav ul {
  list-style-type: none;
  padding: 0;
}

article {
  float: left;
  padding: 20px;
  background-color: #f1f1f1;
}

/* Clear floats after the columns */
section::after {
  content: "";
  display: table;
  clear: both;
}

/* Responsive layout - makes the two columns/boxes stack on top of each other instead of next to each other, on small screens */
@media (max-width: 600px) {
  nav, article {
    width: 100%;
    height: auto;
  }
}
</style>
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

				if(entry.dataPoint.markerType == 'cross')
					content += "<div>Aircon was turned " + entry.dataPoint.inindexLabel + "</div>";

				if(entry.dataSeries.name == "Temperature [°C]")
					content += "</br><div style='color:#4F81BC'>" + entry.dataSeries.name + ": " +  entry.dataPoint.y + "°C</div>";
				else if(entry.dataSeries.name == "Humidity [%]")
					content += "<div style='color:#C0504E'>" + entry.dataSeries.name + ": " +  entry.dataPoint.y + "%</div>";
				else
					content += "<div style='color:#9BBB58'>" + entry.dataSeries.name + ": " +  entry.dataPoint.y + "°C</div>";
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
		type: "line",
		markerSize: 12,
		name: "Temperature [°C]",
		xValueType: "dateTime",
		markerSize: 0,
		//toolTipContent: "Temperature: {y} °C",
		showInLegend: true,
		dataPoints: <?php echo json_encode($dataPoints1, JSON_NUMERIC_CHECK); ?>
	},{
		type: "line",
		markerSize: 12,
		axisYType: "secondary",
		name: "Humidity [%]",
		xValueType: "dateTime",
		markerSize: 0,
		//toolTipContent: "{name}: {y} %",
		showInLegend: true,
		dataPoints: <?php echo json_encode($dataPoints2, JSON_NUMERIC_CHECK); ?>
	},{
		type: "line",
		markerSize: 12,
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
		type: "line",
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
<section>
  <nav>
    <ul>
<?php
	$lastdate = '';
	$query = "SELECT *, DATE_FORMAT(whentime, '%a %d %b %Y') as wtdate, DATE_FORMAT(whentime, '%H:%i') as wttime FROM commands ORDER BY whentime DESC";
	$dres = mysqli_query($link, $query);
	while($drow = mysqli_fetch_assoc($dres))
	{

		$date = $drow['wtdate'];
		if($date != $lastdate)
		{
			echo "<li style='text-align:center;'><u><b>$date</b></u></li>\n";
			$lastdate = $date;
		}
?>
      <li><?php
        echo $drow['wttime'].' -- ';
	if($drow['reason'] == "ExternalIrCommand")
		echo "Remote Control turned AC ";
	else if($drow['reason'] == "UserAPI")
		echo "API turned AC ";
	else
		echo "Unknown turned AC ";
	if($drow['airconon'])
		echo "on";
	else
		echo "off";
?></li>
<?php
	}
?>
    </ul>
  </nav>
  <article style="width: calc(100% - 350px);">
    <div id="chartContainer" style="height: 370px; width: 100%;"></div>
    <div id="rssiContainer" style="height: 370px; width: calc(100% - 50px);"></div>
  </article>
</section>
<script src="https://cdn.canvasjs.com/canvasjs.min.js"></script>
</body>
</html>
