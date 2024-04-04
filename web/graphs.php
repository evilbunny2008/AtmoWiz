<?php
	$error = null;
	$timePeriod = "day";
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

	$query = "SELECT * FROM commands ORDER BY whentime DESC LIMIT 1";
	$res = mysqli_query($link, $query);
	$row = mysqli_fetch_assoc($res);
?>
<!DOCTYPE HTML>
<html>
<head>
<title>AtmoWiz Web UI</title>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" href="assets/favicon.svg">
<link rel="stylesheet" href="assets/clocklet.min.css">
<script src="assets/canvasjs.min.js"></script>
<script src="assets/clocklet.js"></script>
<link href="assets/time-pick.css" rel="stylesheet">
<script src="assets/time-pick.js"></script>
<script src="assets/intro.min.js"></script>
<link href="assets/introjs.min.css" rel="stylesheet">
<style>
*
{
  margin: 0;
  padding: 0;
  box-sizing: border-box;
  scrollbar-color: #888 #ddd;
  scrollbar-width: thin;
}

body
{
  font-family: Arial;
  font-size: 1rem;
}

::-webkit-scrollbar
{
  width: 20px;
}

::-webkit-scrollbar-thumb
{
  background-color: #888;
  border-radius: 10px;
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

#footer a
{
  color: #085f24;
}

.child
{
  position: absolute;
  z-index: 1;
  top: 5px;
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

.myInputs2, .myInputs5, .myInputs8
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

button:disabled
{
  opacity: 0.8;
  cursor: not-allowed;
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

#id07 .modal-content
{
  width: 800px;
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

.divLeft, .divRight
{
  margin: 0px;
  padding: 0px;
  width: 50%;
}

.divLeft
{
  float: left;
  padding: 20px;
}

.divRight
{
  float: right;
  padding: 20px;
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

.commandListOverflow
{
  cursor: pointer;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
  display: inline-block;
  width: 315px;
  //border: 1px solid black;
  font-size: <?=$CLfontSize?>;
}

.commandListDD
{
  float: right;
  width: 50%;
  padding: 12px 12px;
  margin: 8px 8px;
  border: 1px solid #ccc;
  box-sizing: border-box;
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
  <div style="display:flex;flex-direction:column;position:absolute;">
    <nav id="commandHeader" style='flex:0 0 auto;'>
    </nav>
    <nav id="commandList" style='flex:0 0 auto;overflow-x:hidden;overflow-y:scroll;'>
    </nav>
  </div>
  <div style="width:calc(100vw - 375px);float:right;">
    <div class="child" style='left:40%;'><img onClick="prevDay(); return false;" style='height:40px;' src='assets/left.png' /></div>
    <div class="child" style='right:20%;'><img onClick="nextDay(); return false;" style='height:40px;' src='assets/right.png' /></div>
    <div id="chartContainer" style="height: calc(100vh / 3 - 20px); width: 100%;"></div>
    <div id="chartContainer4" style="height: calc(100vh / 3 - 20px); width: 100%;"></div>
    <div style="height:calc(100vh / 3 + 5px); width:calc(100% - 25px); background:#fff;">
      <div id="costContainer" style="height:100%;width: calc(100% - 20px);"></div>
    </div>
<?php
	$disprssi = "display:none";
	if(isset($showRSSI) && $showRSSI == true)
		$disprssi = "";
?>
    <div style="height:calc(100vh / 3 - 20px); width:calc(100% - 25px); background:#fff;<?=$disprssi?>">
      <div id="rssiContainer" style="height:100%;width: calc(100% - 20px);"></div>
    </div>
  </div>
</section>
<div style='height: 32px;width: 100%'></div>
<footer id="footer">
  <div id="footer-content"><a target='_blank' href='https://AtmoWiz.com'>&copy; 2024 by </a><a target='_blank' href='https://evilbunny.org'>evilbunny</a></div>
</footer>
<?php
//	https://www.w3schools.com/howto/tryit.asp?filename=tryhow_css_login_form_modal
?>
<div id="id01" class="modal">
  <form class="modal-content animate" id="id01" action="graphForms.php" method="post">
    <div class="imgcontainer">
      <span onclick="document.getElementById('id01').style.display='none'" class="close">&times;</span>
    </div>
    <div class="container">
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
	<button type="submit" id="submitAddUpdate1">Update</button>
    </div>
  </form>
</div>
<script>
document.forms['id01'].addEventListener('submit', (event) =>
{
	event.preventDefault();
	document.getElementById("submitAddUpdate1").setAttribute('disabled', true);
	// TODO do something here to show user that form is being submitted
        var formData = new FormData(event.target);
	formData.append("podUID1", uid);

	fetch(event.target.action,
	{
		method: 'POST',
		body: formData
	}).then((response) => {
		if(!response.ok)
		{
			throw new Error(`HTTP error! Status: ${response.status} ${response.statusText} ${response.url}`);
		}

		timeSettings();
		document.getElementById("submitAddUpdate1").removeAttribute('disabled');
		modal1.style.display = 'none';
	}).catch((error) => {
		alert(error);
		document.getElementById("submitAddUpdate1").removeAttribute('disabled');
	});
});
</script>
<div id="id02" class="modal">
  <form class="modal-content animate" action="graphForms.php" method="post">
    <div class="imgcontainer">
      <span onclick="document.getElementById('id02').style.display='none'" class="close">&times;</span>
    </div>
    <div class="container">
	<h1>Climate Settings</h1>
	<br/>
	<table id="climateSettings">
	</table><br/><br/>
	<b onClick="newSetting(); return false;" style="cursor: pointer;color: #085f24;">Add Climate Setting</b>
    </div>
  </form>
</div>
<script>
function deleteSetting(created, uid)
{
	if(confirm("Are you sure you want to delete this setting?"))
	{
		const formData = new FormData();
		formData.append("action", "delete");
		formData.append("created", created);
		formData.append("podUID2", uid);

		const req = new Request("graphForms.php", { method: 'POST', body: formData });

		fetch(req)
		.then((response) =>
		{
			if(!response.ok)
			{
				throw new Error(`HTTP error! Status: ${response.status} ${response.statusText} ${response.url}`);
			}

			climateSettings();
		}).catch((error) => {
			climateSettings();
			alert(error);
		});
	}
}
</script>
<div id="id03" class="modal">
  <form class="modal-content animate" id="climateSettingsForm" action="graphForms.php" method="post">
    <div class="imgcontainer">
      <span onclick="cancelAddUpdate(); return false;" class="close">&times;</span>
    </div>
    <div class="container">
	<h1 style='text-align: center;'>Climate Settings</h1>
	<div style="text-align:center;" id="id03-device-title"></div><br/>

	<input id="created2" type="hidden" name="created2" />
	<div class="divLeft">
	<label for="name2"><b>Name:</b></label>
	<input class="myInputs2" id="name2" name="name">
	<label for="upperMode2"><b>Mode:</b></label>
	<select class="myInputs2" id="upperMode2" name="upperMode" onChange="populateULSelect('upper', this.value); return false;">
<?php
	$defmode = 'cool';
	$query = "SELECT mode FROM meta WHERE uid='${row['uid']}' GROUP BY mode";
	$dres = mysqli_query($link, $query);
	while($drow = mysqli_fetch_assoc($dres))
	{
		$v = $drow['mode'];
		echo "\t\t<option value='$v'>$v</option>\n";
	}
?>
	</select>
	<label for="upperTemperature2"><b>Upper Temperature:</b></label>
	<select class="myInputs2" id='upperTemperature2' name='upperTemperature'>
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
	<label for="upperTargetTemperature2"><b>Target Temperature:</b></label>
	<select class="myInputs2" id='upperTargetTemperature2' name='upperTargetTemperature'>
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
	<label for="upperTurnOnOff2"><b>Turn On/Off:</b></label>
	<select class="myInputs2" id="upperTurnOnOff2" name="upperTurnOnOff">
		<option value='On'>On</option>
		<option value='Off'>Off</option>
	</select>
	<label for="upperFanLevel2"><b>Fan Level:</b></label>
	<select class="myInputs2" id='upperFanLevel2' name="upperFanLevel">
<?php
	$defmode = 'cool';
	$query = "SELECT value FROM meta WHERE uid='${row['uid']}' AND mode='$defmode' AND keyval='fanLevels'";
	$dres = mysqli_query($link, $query);
	while($drow = mysqli_fetch_assoc($dres))
	{
		$v = $drow['value'];
		echo "\t\t<option value='$v'>$v</option>\n";
	}
?>
	</select>
	<label for="upperSwing2"><b>Swing:</b></label>
	<select class="myInputs2" id="upperSwing2" name="upperSwing">
<?php
	$defmode = 'cool';
	$query = "SELECT value FROM meta WHERE uid='${row['uid']}' AND mode='$defmode' AND keyval='swing'";
	$dres = mysqli_query($link, $query);
	while($drow = mysqli_fetch_assoc($dres))
	{
		$v = $drow['value'];
		echo "\t\t<option value='$v'>$v</option>\n";
	}
?>
	</select>
	<label for="upperHorizontalSwing2"><b>Horizontal Swing:</b></label>
	<select class="myInputs2" id="upperHorizontalSwing2" name="upperHorizontalSwing">
<?php
	$defmode = 'cool';
	$query = "SELECT value FROM meta WHERE uid='${row['uid']}' AND mode='$defmode' AND keyval='horizontalSwing'";
	$dres = mysqli_query($link, $query);
	while($drow = mysqli_fetch_assoc($dres))
	{
		$v = $drow['value'];
		echo "\t\t<option value='$v'>$v</option>\n";
	}
?>
	</select>
	</div>
	<div class="divRight">
	<label for="type2"><b>Type:</b></label>
	<select class="myInputs2" id="type2" name="type">
<?php
	$dquery = "SELECT DISTINCT SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING(COLUMN_TYPE, 7, LENGTH(COLUMN_TYPE) - 8), \"','\", 1 + units.i + tens.i * 10) , \"','\", -1) AS value FROM INFORMATION_SCHEMA.COLUMNS CROSS JOIN ".
			"(SELECT 0 AS i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) units CROSS JOIN (SELECT 0 AS i UNION SELECT 1 ".
			"UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) tens WHERE TABLE_NAME = 'settings' AND COLUMN_NAME = 'type'";
	$dres = mysqli_query($link, $dquery);
	while($drow = mysqli_fetch_assoc($dres))
	{
		$v = $drow['value'];
		echo "\t\t<option value='$v'>$v</option>\n";
	}
?>
	</select>
	<label for="lowerMode2"><b>Mode:</b></label>
	<select class="myInputs2" id="lowerMode2" name="lowerMode" onChange="populateULSelect('lower', this.value); return false;">
<?php
	$defmode = 'cool';
	$query = "SELECT mode FROM meta WHERE uid='${row['uid']}' GROUP BY mode";
	$dres = mysqli_query($link, $query);
	while($drow = mysqli_fetch_assoc($dres))
	{
		$v = $drow['mode'];
		echo "\t\t<option value='$v'>$v</option>\n";
	}
?>
	</select>
	<label for="lowerTemperature2"><b>Lower Temperature:</b></label>
	<select class="myInputs2" id='lowerTemperature2' name='lowerTemperature'>
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
	<label for="lowerTargetTemperature2"><b>Target Temperature:</b></label>
	<select class="myInputs2" id='lowerTargetTemperature2' name='lowerTargetTemperature'>
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
	<label for="lowerTurnOnOff2"><b>Turn On/Off:</b></label>
	<select class="myInputs2" id="lowerTurnOnOff2" name="lowerTurnOnOff">
		<option value='On'>On</option>
		<option value='Off'>Off</option>
	</select>
	<label for="lowerFanLevel2"><b>Fan Level:</b></label>
	<select class="myInputs2" id='lowerFanLevel2' name="lowerFanLevel">
<?php
	$defmode = 'cool';
	$query = "SELECT value FROM meta WHERE uid='${row['uid']}' AND mode='$defmode' AND keyval='fanLevels'";
	$dres = mysqli_query($link, $query);
	while($drow = mysqli_fetch_assoc($dres))
	{
		$v = $drow['value'];
		echo "\t\t<option value='$v'>$v</option>\n";
	}
?>
	</select>
	<label for="lowerSwing2"><b>Swing:</b></label>
	<select class="myInputs2" id="lowerSwing2" name="lowerSwing">
<?php
	$defmode = 'cool';
	$query = "SELECT value FROM meta WHERE uid='${row['uid']}' AND mode='$defmode' AND keyval='swing'";
	$dres = mysqli_query($link, $query);
	while($drow = mysqli_fetch_assoc($dres))
	{
		$v = $drow['value'];
		echo "\t\t<option value='$v'>$v</option>\n";
	}
?>
	</select>
	<label for="lowerHorizontalSwing2"><b>Horizontal Swing:</b></label>
	<select class="myInputs2" id="lowerHorizontalSwing2" name="lowerHorizontalSwing">
<?php
	$defmode = 'cool';
	$query = "SELECT value FROM meta WHERE uid='${row['uid']}' AND mode='$defmode' AND keyval='horizontalSwing'";
	$dres = mysqli_query($link, $query);
	while($drow = mysqli_fetch_assoc($dres))
	{
		$v = $drow['value'];
		echo "\t\t<option value='$v'>$v</option>\n";
	}
?>
	</select>
	</div>
	<label for="enabled2"><b>Enabled:</b></label><input style='text-align: left;' type="checkbox" id="enabled2" name="enabled" value="1" checked />
	<button id="submitAddUpdate2" type="submit">Add</button>
    </div>
  </form>
</div>
<script>
document.forms['climateSettingsForm'].addEventListener('submit', (event) =>
{
	event.preventDefault();
	document.getElementById("submitAddUpdate2").setAttribute('disabled', true);
	// TODO do something here to show user that form is being submitted
        var formData = new FormData(event.target);
	formData.append("podUID2", uid);

	fetch(event.target.action,
	{
		method: 'POST',
		body: formData
	}).then((response) => {
		if(!response.ok)
		{
			throw new Error(`HTTP error! Status: ${response.status} ${response.statusText} ${response.url}`);
		}

		climateSettings();
		modal3.style.display = 'none';
		modal2.style.display = 'block';
		document.getElementById("submitAddUpdate2").removeAttribute('disabled');
	}).catch((error) => {
		alert(error);
		document.getElementById("submitAddUpdate2").removeAttribute('disabled');
	});
});
</script>
<div id="id04" class="modal">
  <form class="modal-content animate" action="graphForms.php" method="post">
    <div class="imgcontainer">
      <span onclick="document.getElementById('id04').style.display='none'" class="close">&times;</span>
    </div>
    <div class="container">
	<h1>Time Based Settings</h1>
	<table id="timeSettings">
	</table><br/><br/>
	<b onClick="newTimeSetting(); return false;" style="cursor: pointer;color: #085f24;">Add Time Based Setting</b>
    </div>
  </form>
</div>
<script>
function deleteTimeSetting(created, uid)
{
	if(confirm("Are you sure you want to delete this setting?"))
	{
		const formData = new FormData();
		formData.append("action", "delete");
		formData.append("created", created);
		formData.append("podUID5", uid);

		const req = new Request("graphForms.php", { method: 'POST', body: formData });

		fetch(req)
		.then((response) =>
		{
			if(!response.ok)
			{
				throw new Error(`HTTP error! Status: ${response.status} ${response.statusText} ${response.url}`);
			}

			timeSettings();
		}).catch((error) => {
			timeSettings();
			alert(error);
		});
	}
}
</script>
<div id="id05" class="modal">
  <form class="modal-content animate" action="graphForms.php" id="id05" method="post">
    <div class="imgcontainer">
      <span onclick="cancelAddUpdateTime(); return false;" class="close">&times;</span>
    </div>
    <div class="container">
	<h1 style='text-align: center;'>Time Based Settings</h1>
	<div style="text-align:center;" id="id05-device-title"></div><br/>
	<input id="created5" type="hidden" name="created5" />
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
	<label for="startTime5"><b>Start Time:</b></label>
	<input id='startTime5' name='startTime' class="myInputs5" data-clocklet="class-name: clocklet-options-1; alignment: center;">
	<div class="divLeft">
	<label for="turnOnOff5"><b>Turn On/Off:</b></label>
	<select class="myInputs5" id="turnOnOff5" name="turnOnOff">
		<option value='On'>On</option>
		<option value='Off'>Off</option>
		<option value='Same'>Don't Change</option>
	</select>
	<label for="mode5"><b>Mode:</b></label>
	<select class="myInputs5" id="mode5" name="mode" onChange="populateSelect('5'); return false;">
<?php
	$defmode = 'cool';
	$query = "SELECT mode as value FROM meta WHERE uid='${row['uid']}' GROUP BY mode";
	$dres = mysqli_query($link, $query);
	while($drow = mysqli_fetch_assoc($dres))
	{
		$v = $drow['value'];
		echo "\t\t<option value='$v'>$v</option>\n";
	}
?>
	</select>
	<label for="targetTemperature5"><b>Target Temperature:</b></label>
	<select class="myInputs5" id='targetTemperature5' name='targetTemperature'>
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
	<label for="climateSetiings"><b>Climate Setting:</b></label>
	<select class="myInputs5" id='climateSetting5' name='climateSetting'>
	</select>
	</div>
	<div class="divRight">
	<label for="fanLevel5"><b>Fan Level:</b></label>
	<select class="myInputs5" id='fanLevel5' name="fanLevel">
<?php
	$defmode = 'cool';
	$query = "SELECT value FROM meta WHERE uid='${row['uid']}' AND mode='$defmode' AND keyval='fanLevel'";
	$dres = mysqli_query($link, $query);
	while($drow = mysqli_fetch_assoc($dres))
	{
		$v = $drow['value'];
		echo "\t\t<option value='$v'>$v</option>\n";
	}
?>
	</select>
	<label for="swing5"><b>Swing:</b></label>
	<select class="myInputs5" id="swing5" name="swing">
<?php
	$defmode = 'cool';
	$query = "SELECT value FROM meta WHERE uid='${row['uid']}' AND mode='$defmode' AND keyval='swing'";
	$dres = mysqli_query($link, $query);
	while($drow = mysqli_fetch_assoc($dres))
	{
		$v = $drow['value'];
		echo "\t\t<option value='$v'>$v</option>\n";
	}
?>
	</select>
	<label for="horizontalSwing5"><b>Horizontal Swing:</b></label>
	<select class="myInputs5" id="horizontalSwing5" name="horizontalSwing">
<?php
	$defmode = 'cool';
	$query = "SELECT value FROM meta WHERE uid='${row['uid']}' AND mode='$defmode' AND keyval='horizontalSwing'";
	$dres = mysqli_query($link, $query);
	while($drow = mysqli_fetch_assoc($dres))
	{
		$v = $drow['value'];
		echo "\t\t<option value='$v'>$v</option>\n";
	}
?>
	</select>
	<label for="enabled5"><b>Enabled:</b></label>
	<input class="myInputs5" type="checkbox" id="enabled5" name="enabled" value="1" checked />
	</div>
	<button id="submitAddUpdate5" type="submit">Add Setting</button>
    </div>
  </form>
</div>
<script>
document.forms['id05'].addEventListener('submit', (event) =>
{
	event.preventDefault();
	document.getElementById("submitAddUpdate5").setAttribute('disabled', true);
	// TODO do something here to show user that form is being submitted
        var formData = new FormData(event.target);
	formData.append("podUID5", uid);

	fetch(event.target.action,
	{
		method: 'POST',
		body: formData
	}).then((response) => {
		if(!response.ok)
		{
			throw new Error(`HTTP error! Status: ${response.status} ${response.statusText} ${response.url}`);
		}

		timeSettings();
		modal5.style.display = 'none';
		modal4.style.display = 'block';
		document.getElementById("submitAddUpdate5").removeAttribute('disabled');
	}).catch((error) => {
		alert(error);
		document.getElementById("submitAddUpdate5").removeAttribute('disabled');
	});
});
</script>
<div id="id06" class="modal">
  <form class="modal-content animate" action="graphForms.php" method="post">
    <div class="imgcontainer">
      <span onclick="document.getElementById('id06').style.display='none'" class="close">&times;</span>
    </div>
    <div class="container">
	<h1 style='text-align: center;'>Error!</h1>
	<b>
	<div id='errorMessage' style='text-align:center;color:red;font-size:xx-large;'>&nbsp;</div>
	</b>
    </div>
  </form>
</div>
<div id="id07" class="modal">
  <form class="modal-content animate" action="graphForms.php" method="post">
    <div class="imgcontainer">
      <span onclick="document.getElementById('id07').style.display='none'" class="close">&times;</span>
    </div>
    <div class="container">
	<h1 style='text-align: center;'>Timers</h1>
	<br/>
	<table id="timerTable">
	</table><br/><br/>
	<div style='text-align:center;'><b onClick="newTimer(); return false;" style="cursor: pointer;color: #085f24;">Add Timer</b></div>
    </div>
  </form>
</div>
<div id="id08" class="modal">
  <form class="modal-content animate" action="graphForms.php" id="AddTimer8" method="post">
    <div class="imgcontainer">
      <span onclick="cancelAddTimer(); return false;" class="close">&times;</span>
    </div>
    <div class="container">
	<h1 style="text-align:center;">New Timer</h1>
        <div style="text-align:center;" id="id08-device-title"></div><br/>
	<label for="timer8">Set a Timer:</label>
	<input type="text" id="timer8" name="timer" class="myInputs2" value="00:20" />
	<label for="turnOnOff8"><b>Turn On/Off:</b></label>
	<select class="myInputs8" id="turnOnOff8" name="turnOnOff">
		<option value='On'>On</option>
		<option value='Off'>Off</option>
	</select>
	<button type="submit" id="submit8">Add</button>
    </div>
  </form>
</div>
<script>

tp.attach({target:document.getElementById("timer8"),"24":true});

document.forms['AddTimer8'].addEventListener('submit', (event) =>
{
	event.preventDefault();
	document.getElementById("submit8").setAttribute('disabled', true);
	// TODO do something here to show user that form is being submitted
        var formData = new FormData(event.target);
	formData.append("podUID8", uid);

	fetch(event.target.action,
	{
		method: 'POST',
		body: formData
	}).then((response) => {
		if(!response.ok)
		{
			throw new Error(`HTTP error! Status: ${response.status} ${response.statusText} ${response.url}`);
		}

		timerTable();
		modal8.style.display = 'none';
		modal7.style.display = 'block';
		document.getElementById("submit8").removeAttribute('disabled');
	}).catch((error) => {
		alert(error);
		document.getElementById("submit8").removeAttribute('disabled');
	});
});

function deleteTimer(created, uid)
{
	if(confirm("Are you sure you want to delete this timer?"))
	{
		const formData = new FormData();
		formData.append("action", "delete");
		formData.append("whentime", created);
		formData.append("podUID8", uid);

		const req = new Request("graphForms.php", { method: 'POST', body: formData });

		fetch(req)
		.then((response) =>
		{
			if(!response.ok)
			{
				throw new Error(`HTTP error! Status: ${response.status} ${response.statusText} ${response.url}`);
			}

			timerTable();
		}).catch((error) => {
			timerTable();
			alert(error);
		});
	}
}

var corf = "C";
var timePeriod = "<?=$timePeriod?>";
var period = <?=$period?>;
var uid = "<?=$row['uid']?>";
var currtime = "";

var startTS = <?=$startTS?>;

var modal1 = document.getElementById('id01');
var modal2 = document.getElementById('id02');
var modal3 = document.getElementById('id03');
var modal4 = document.getElementById('id04');
var modal5 = document.getElementById('id05');
var modal6 = document.getElementById('id06');
var modal7 = document.getElementById('id07');
var modal8 = document.getElementById('id08');

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
				else if(entry.dataSeries.name == "Temperature [°F]")
					content += "<div style='color:<?=$tempColour?>'>" + entry.dataSeries.name + ": " +  entry.dataPoint.y + "°F</div>";
				else if(entry.dataSeries.name == "Humidity [%]")
					content += "<div style='color:<?=$humidColour?>'>" + entry.dataSeries.name + ": " +  entry.dataPoint.y + "%</div>";
				else if(entry.dataSeries.name == "Feels Like [°C]")
					content += "<div style='color:<?=$FLColour?>'>" + entry.dataSeries.name + ": " +  entry.dataPoint.y + "°C</div>";
				else if(entry.dataSeries.name == "Feels Like [°F]")
					content += "<div style='color:<?=$FLColour?>'>" + entry.dataSeries.name + ": " +  entry.dataPoint.y + "°F</div>";
				else if(entry.dataSeries.name == "Power Usage [W]")
					content += "<div style='color:<?=$powerColour?>'>" + entry.dataSeries.name + ": " +  entry.dataPoint.y + "W</div>";
				else if(entry.dataSeries.name == "Outdoor Temperature [°C]")
					content += "<div style='color:<?=$outTempColour?>'>" + entry.dataSeries.name + ": " +  entry.dataPoint.y + "°C</div>";
				else if(entry.dataSeries.name == "Outdoor Temperature [°F]")
					content += "<div style='color:<?=$outTempColour?>'>" + entry.dataSeries.name + ": " +  entry.dataPoint.y + "°F</div>";
				else if(entry.dataSeries.name == "Outdoor Humidity [%]")
					content += "<div style='color:<?=$outHumidColour?>'>" + entry.dataSeries.name + ": " +  entry.dataPoint.y + "%</div>";

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
		title: "Temperature [°"+corf+"]",
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
			name: "Feels Like [°"+corf+"]",
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
			name: "Temperature [°"+corf+"]",
			xValueType: "dateTime",
			markerSize: 0,
			showInLegend: true,
			color: "<?=$tempColour?>",
		},{
			type: "line",
			name: "Outdoor Temperature [°"+corf+"]",
			xValueType: "dateTime",
			markerSize: 0,
			showInLegend: true,
			color: "<?=$outTempColour?>",
		},{
			type: "line",
			axisYType: "secondary",
			name: "Outdoor Humidity [%]",
			xValueType: "dateTime",
			markerSize: 0,
			showInLegend: true,
			color: "<?=$outHumidColour?>",
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
		type: "line",
		name: "Signal Strength [dBm]",
		xValueType: "dateTime",
		markerSize: 0,
		showInLegend: true,
		color: "<?=$wifiColour?>",
       	}]
});
<?php
	$digits = "";
	for($i = 0; $i < $currency_values['frac_digits']; $i++)
		$digits .= "0";

	$fmtstr = "${currency_values['currency_symbol']}#${currency_values['mon_thousands_sep']}##0${currency_values['mon_decimal_point']}$digits";
?>
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
				content += "</br><div style='color:<?=$costColour?>'>" + entry.dataSeries.name + ": " +  CanvasJS.formatNumber(entry.dataPoint.y, "<?=$fmtstr?>") + "</div>";
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
		title: "Cost [<?=$currency_values['currency_symbol']?>]",
		includeZero: true,
		titleFontColor: "<?=$costColour?>",
		lineColor: "<?=$costColour?>",
		labelFontColor: "<?=$costColour?>",
		tickColor: "<?=$costColour?>",
		labelFormatter: function (e)
		{
			return CanvasJS.formatNumber(e.value, "<?=$fmtstr?>");
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
		name: "Cost [<?=$currency_values['currency_symbol']?>]",
		xValueType: "dateTime",
		markerSize: 0,
		showInLegend: true,
		color: "<?=$costColour?>",
		click: function(e)
		{
			if(timePeriod == "month")
			{
				timePeriod = "day";
				period = 86400000;
			} else if(timePeriod == "year") {
				timePeriod = "week";
				period = 604800000;
			} else {
				timePeriod = "day";
				period = 86400000;
			}

			startTS = e.dataPoint.x;
			startDataLoop(true)
		},
       	}],
});

var chart4 = new CanvasJS.Chart("chartContainer4",
{
	animationEnabled: true,
	exportEnabled: true,
	zoomEnabled: true,
	title:
	{
		text: "Power Vs Ambient Temp"
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

				if(entry.dataSeries.name == "Indoor Temperature [°C]")
					content += "<div style='color:<?=$outTempColour?>'>" + entry.dataSeries.name + ": " +  entry.dataPoint.y + "°C</div>";
				else if(entry.dataSeries.name == "Indoor Temperature [°F]")
					content += "<div style='color:<?=$outTempColour?>'>" + entry.dataSeries.name + ": " +  entry.dataPoint.y + "°F</div>";
				else if(entry.dataSeries.name == "Power [kW]")
					content += "<div style='color:<?=$powerColour?>'>" + entry.dataSeries.name + ": " +  entry.dataPoint.y + "kW</div>";

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
		title: "Temperature [°"+corf+"]",
		titleFontColor: "<?=$outTempColour?>",
		lineColor: "<?=$outTempColour?>",
		labelFontColor: "<?=$outTempColour?>",
		tickColor: "<?=$outTempColour?>",
	},
	axisY2:
	{
		title: "Power [kW]",
		titleFontColor: "<?=$powerColour?>",
		lineColor: "<?=$powerColour?>",
		labelFontColor: "<?=$powerColour?>",
		tickColor: "<?=$powerColour?>",
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
			name: "Indoor Temperature [°"+corf+"]",
			xValueType: "dateTime",
			markerSize: 0,
			showInLegend: true,
			color: "<?=$outTempColour?>",
		},{
			type: "line",
			axisYType: "secondary",
			name: "Power [kW]",
			xValueType: "dateTime",
			markerSize: 0,
			showInLegend: true,
			color: "<?=$powerColour?>",
		}
	],
});

var charts = [];
charts.push(chart1);
charts.push(chart2);
charts.push(chart3);
charts.push(chart4);

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
		img.src = "assets/off.png";
	else
		img.src = "assets/on.png";

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
	timerTable();
	climateSettings();
	timeSettings();
}

async function timeSettings()
{
	try
	{
		var url = "timeSettings.php?time=" + new Date().getTime();
		url += "&uid=" + uid;

		const response = await fetch(url);
		const ret = await response.json();

		if(ret['status'] != 200)
		{
			alert(ret['error']);
			return;
		}

		content = ret['content'];
		document.getElementById("timeSettings").innerHTML = content;
	} catch (e) {
		console.log(e)
	}
}

async function timerTable()
{
	try
	{
		var url = "timers.php?time=" + new Date().getTime();
		url += "&uid=" + uid;
		const response = await fetch(url);
		const ret = await response.json();

		if(ret['status'] != 200)
		{
			alert(ret['error']);
			return;
		}

		content = ret['content'];
		document.getElementById("timerTable").innerHTML = content;
	} catch (e) {
		console.log(e)
	}
}

async function climateSettings()
{
	try
	{
		var url = "climateSettings.php?time=" + new Date().getTime();
		url += "&uid=" + uid;
		const response = await fetch(url);
		const ret = await response.json();

		if(ret['status'] != 200)
		{
			alert(ret['error']);
			return;
		}

		content = ret['content'];
		document.getElementById("climateSettings").innerHTML = content;
	} catch (e) {
		console.log(e)
	}
}

function checkWindowSize()
{
	var chh = 190;

	if(document.getElementById('card-demo').classList.contains('card-demo1'))
		chh += 60;

	if(document.getElementById("device-chooser").style.display == "block")
		chh += 60;

	chh += 50;

	document.getElementById("commandHeader").style.height = chh + "px";

	chh += 32;

	document.getElementById("commandList").style.height = window.innerHeight - chh + "px";
}

window.onresize = function(event)
{
	checkWindowSize();
};

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

		document.getElementById("commandHeader").innerHTML = content['commandHeader'];
		document.getElementById("commandList").innerHTML = content['commands'];
		checkWindowSize();

		uid = content['uid'];

		if(timePeriod == "day")
		{
			chart1.options.axisX.intervalType = 'hour';
			chart2.options.axisX.intervalType = 'hour';
			chart3.options.axisX.intervalType = 'hour';
			chart3.options.dataPointWidth = 50;
			chart4.options.axisX.intervalType = 'hour';

			chart1.options.axisX.valueFormatString = 'D MMM, hTT';
			chart2.options.axisX.valueFormatString = 'D MMM, hTT';
			chart3.options.axisX.valueFormatString = 'D MMM, hTT';
			chart4.options.axisX.valueFormatString = 'D MMM, hTT';
		}

		if(timePeriod == "week")
		{
			chart1.options.axisX.intervalType = 'day';
			chart2.options.axisX.intervalType = 'day';
			chart3.options.axisX.intervalType = 'day';
			chart3.options.dataPointWidth = 7;
			chart4.options.axisX.intervalType = 'day';

			chart1.options.axisX.valueFormatString = 'D MMM';
			chart2.options.axisX.valueFormatString = 'D MMM';
			chart3.options.axisX.valueFormatString = 'D MMM';
			chart4.options.axisX.valueFormatString = 'D MMM';
		}

		if(timePeriod == "month")
		{
			chart1.options.axisX.intervalType = 'week';
			chart2.options.axisX.intervalType = 'week';
			chart3.options.axisX.intervalType = 'week';
			chart3.options.dataPointWidth = 40;
			chart4.options.axisX.intervalType = 'week';

			chart1.options.axisX.valueFormatString = 'D MMM YY';
			chart2.options.axisX.valueFormatString = 'D MMM YY';
			chart3.options.axisX.valueFormatString = 'D MMM YY';
			chart4.options.axisX.valueFormatString = 'D MMM YY';
		}

		if(timePeriod == "year")
		{
			chart1.options.axisX.intervalType = 'month';
			chart2.options.axisX.intervalType = 'month';
			chart3.options.axisX.intervalType = 'month';
			chart3.options.dataPointWidth = 20;
			chart4.options.axisX.intervalType = 'month';

			chart1.options.axisX.valueFormatString = 'MMM YYYY';
			chart2.options.axisX.valueFormatString = 'MMM YYYY';
			chart3.options.axisX.valueFormatString = 'MMM YYYY';
			chart4.options.axisX.valueFormatString = 'MMM YYYY';
		}

		chart1.options.data[0].dataPoints = content['dataPoints3'];
		chart1.options.data[1].dataPoints = content['dataPoints2'];
		chart1.options.data[2].dataPoints = content['dataPoints1'];
		chart2.options.data[0].dataPoints = content['dataPoints4'];
		chart3.options.data[0].dataPoints = content['dataPoints5'];
//		chart4.options.data[0].dataPoints = content['dataPoints7'];
		chart4.options.data[0].dataPoints = content['dataPoints1'];
		chart4.options.data[1].dataPoints = content['dataPoints6'];
		chart1.options.data[3].dataPoints = content['dataPoints7'];
		chart1.options.data[4].dataPoints = content['dataPoints8'];

//		if(content['showChart4'])
//			document.getElementById("chartContainer4").style.display = 'block';
//		else
//			document.getElementById("chartContainer4").style.display = 'none';

		corf = content['corf'];

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
	const response = await fetch(url);
	const ret = await response.json();

	if(ret['status'] == 200)
		popSelect(document.getElementById(contentType + fieldID), ret['content'], ret[contentType]);
	else
		console.log(ret);
}

async function popSelect2(dropdown, content, current)
{
	dropdown.innerHTML = '';
	var opt = document.createElement("option");
	opt.value = "None";
	opt.innerHTML = "None";
	dropdown.appendChild(opt);

	for(let i = 0; i < content.length; i++)
	{
		var opt = document.createElement('option');
		opt.value = content[i]['created'];
		opt.innerHTML = content[i]['name'];
		if(opt.value == current)
			opt.selected = true;
		dropdown.appendChild(opt);
	}
}

async function populateClimateSetting(def)
{
	try
	{
		var url = "getClimateNames.php?time=" + new Date().getTime() + '&uid=' + uid;
		console.log(url);
		const response = await fetch(url);
		const ret = await response.json();

		if(ret['status'] == 200)
		{
			popSelect2(document.getElementById("climateSetting5"), ret['content'], def);
		} else {
			console.log(ret);
		}
	} catch (e) {
		console.log(e);
	}
}

async function populateULSelect(UL, mode)
{
	try
	{
		doPop(mode, 'temperatures', UL + 'TargetTemperature', "2");
		doPop(mode, 'temperatures', UL + 'Temperature', "2");
	} catch (e) {
		console.log(e);
	}
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
		console.log(e);
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
	startDataLoop(true);
}

function showDay(timestamp)
{
	timePeriod = "day";
	period = 86400000;
	startTS = timestamp;
	startDataLoop(true);
}

function prevDay()
{
	startTS -= period;
	startDataLoop(true);
}

function nextDay()
{
	startTS += period;
	startDataLoop(true);
}

function logout()
{
	window.location = 'graphForms.php?logout=1';
}

function settings()
{
	populateSelect('1');
	modal1.style.display = "block";
}

function showSettings()
{
	modal2.style.display = "block";
}

function newSetting()
{
	document.getElementById("created2").value = "";
	document.getElementById("name2").value = "Climate Setting #1";

	document.getElementById("type2").options[0].selected = 'selected';

	document.getElementById("upperTargetTemperature2").options[8].selected = 'selected';
	document.getElementById("upperTemperature2").options[10].selected = 'selected';

	document.getElementById("upperTurnOnOff2").options[0].selected = 'selected';

	document.getElementById("upperMode2").options[1].selected = 'selected';

	document.getElementById("upperFanLevel2").options[2].selected = 'selected';
	document.getElementById("upperSwing2").options[1].selected = 'selected';
	document.getElementById("upperHorizontalSwing2").options[3].selected = 'selected';

	document.getElementById("lowerTargetTemperature2").options[8].selected = 'selected';
	document.getElementById("lowerTemperature2").options[6].selected = 'selected';

	document.getElementById("lowerTurnOnOff2").options[0].selected = 'selected';

	document.getElementById("lowerMode2").options[1].selected = 'selected';

	document.getElementById("lowerFanLevel2").options[2].selected = 'selected';
	document.getElementById("lowerSwing2").options[1].selected = 'selected';
	document.getElementById("lowerHorizontalSwing2").options[3].selected = 'selected';

	document.getElementById("enabled2").checked = true;
	document.getElementById("submitAddUpdate2").innerHTML = "Add Climate Setting";

	dd = document.getElementById("devices");
	document.getElementById("id03-device-title").innerHTML = dd.options[dd.selectedIndex].text;
	modal2.style.display = "none";
	modal3.style.display = "block";
}

function updateSelected(def, dd)
{
	for(let i = 0; i < dd.options.length; i++)
	{
		if(dd.options[i].value == def)
			dd.options[i].selected = 'selected';
	}
}

function editSetting(created, uid, name, type, upperTemperature, upperTargetTemperature, upperTurnOnOff, upperMode, upperFanLevel, upperSwing, upperHorizontalSwing,
					       lowerTemperature, lowerTargetTemperature, lowerTurnOnOff, lowerMode, lowerFanLevel, lowerSwing, lowerHorizontalSwing, enabled)
{
	document.getElementById("created2").value = created;

	var def = null;
	var dd = null;

	document.getElementById("name2").value = name;

	updateSelected(type, document.getElementById("type2"));
	updateSelected(upperTemperature, document.getElementById("upperTemperature2"));
	updateSelected(upperTargetTemperature, document.getElementById("upperTargetTemperature2"));
	updateSelected(upperTurnOnOff, document.getElementById("upperTurnOnOff2"));
	updateSelected(upperMode, document.getElementById("upperMode2"));
	updateSelected(upperFanLevel, document.getElementById("upperFanLevel2"));
	updateSelected(upperSwing, document.getElementById("upperSwing2"));
	updateSelected(upperHorizontalSwing, document.getElementById("upperHorizontalSwing2"));
	updateSelected(lowerTemperature, document.getElementById("lowerTemperature2"));
	updateSelected(lowerTargetTemperature, document.getElementById("lowerTargetTemperature2"));
	updateSelected(lowerTurnOnOff, document.getElementById("lowerTurnOnOff2"));
	updateSelected(lowerMode, document.getElementById("lowerMode2"));
	updateSelected(lowerFanLevel, document.getElementById("lowerFanLevel2"));
	updateSelected(lowerSwing, document.getElementById("lowerSwing2"));
	updateSelected(lowerHorizontalSwing, document.getElementById("lowerHorizontalSwing2"));

	if(enabled == 1)
		document.getElementById("enabled2").checked = true;
	else
		document.getElementById("enabled2").checked = false;

	document.getElementById("submitAddUpdate2").innerHTML = "Update";

	dd = document.getElementById("devices");
	document.getElementById("id03-device-title").innerHTML = dd.options[dd.selectedIndex].text;
	modal2.style.display = "none";
	modal3.style.display = "block";
}

function editTimeSetting(created, uid, daysOfWeek, startTime, turnOnOff, mode, targetTemperature, fanLevel, swing, horizontalSwing, climateSetting, enabled)
{
	populateClimateSetting(climateSetting);

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

	dd = document.getElementById("devices");
	document.getElementById("id05-device-title").innerHTML = dd.options[dd.selectedIndex].text;
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

function cancelAddTimer()
{
	modal8.style.display = "none";
	modal7.style.display = "block";
}

function newTimeSetting()
{
	populateClimateSetting('none');

	document.getElementById("created5").value = "";

	document.getElementById("startTime5").value = "00:00";

	dd = document.getElementById("days5");
	for(let i = 0; i < dd.options.length; i++)
		dd.options[i].selected = 'selected';

	document.getElementById("turnOnOff5").options[0].selected = 'selected';
	document.getElementById("mode5").options[1].selected = 'selected';
	document.getElementById("targetTemperature5").options[8].selected = 'selected';

	document.getElementById("fanLevel5").options[2].selected = 'selected';
	document.getElementById("swing5").options[1].selected = 'selected';
	document.getElementById("horizontalSwing5").options[3].selected = 'selected';

	document.getElementById("enabled5").checked = true;
	document.getElementById("submitAddUpdate5").innerHTML = "Add Setting";

	dd = document.getElementById("devices");
	document.getElementById("id05-device-title").innerHTML = dd.options[dd.selectedIndex].text;
	modal4.style.display = "none";
	modal5.style.display = "block";
}

function showTimeSettings()
{
	modal4.style.display = "block";
}

function showTimers()
{
	modal7.style.display = "block";
}

function newTimer()
{
	dd = document.getElementById("devices");
	document.getElementById("id08-device-title").innerHTML = dd.options[dd.selectedIndex].text;
	modal7.style.display = "none";
	modal8.style.display = "block";
}

function help()
{
	window.open("https://github.com/evilbunny2008/AtmoWiz/wiki/Using-AtmoWiz", "_blank").focus();;
}

DataLoop();
populateSelect("1");
populateSelect("5");
populateClimateSetting('none');
populateULSelect('upper', 'cool');
populateULSelect('lower', 'cool');

function delayLoading()
{
	try
	{
		introJs().setOptions(
		{
			showBullets: false,
			dontShowAgain: true,
			steps: [
<?php
	if($_SESSION['rw'])
	{
?>
			{
				element: document.querySelector('.card-demo1'),
				intro: '<div style="overflow:hidden;width:250px"><img src="assets/home-settings.png" style="float:left;margin-right:20px;" width="50px"><p>Air Conditioner Settings screen allows you to adjust the mode, the preferred temperature, the fan speed and swing settings.</p></div>',
				position: 'bottom'
			},
			{
				element: document.querySelector('.card-demo2'),
				intro: '<div style="overflow:hidden;width:250px"><img src="assets/wand.png" style="float:left;margin-right:20px;" width="50px"><p>Our Climate Settings screen allows you to take charge based on real-time conditions</p></div>',
				position: 'bottom'
			},
			{
				element: document.querySelector('.card-demo3'),
				intro: '<div style="overflow:hidden;width:250px"><img src="assets/watch.png" style="float:left;margin-right:20px;" width="50px"><p>Similar to the Climate Settings screen, but focused on time intervals</p></div>',
				position: 'bottom'
			},
			{
				element: document.querySelector('.card-demo4'),
				intro: '<div style="overflow:hidden;width:250px"><img src="assets/hourglass.png" style="float:left;margin-right:20px;" width="50px"><p>This lets you set a simple timer to turn your aircon on or off</p></div>',
				position: 'bottom'
			},
			{
				element: document.querySelector('.card-demo5'),
				intro: '<div style="overflow:hidden;width:250px"><img src="assets/on.png" style="float:left;margin-right:20px;" width="50px"><p>This lets you turn your aircon on and off</p></div>',
				position: 'bottom'
			},
<?php } ?>
			{
				element: document.querySelector('.card-demo6'),
				intro: '<div style="overflow:hidden;width:250px"><img src="assets/tick.png" style="float:left;margin-right:20px;" width="50px"><p>This jumps the display to the current date/time</p></div>',
				position: 'bottom'
			},
			{
				element: document.querySelector('.card-demo7'),
				intro: '<div style="overflow:hidden;width:250px"><img src="assets/exit.png" style="float:left;margin-right:20px;" width="50px"><p>This lets you logout of the AtmoWiz interface</p></div>',
				position: 'bottom'
			},
			{
				element: document.querySelector('.card-demo8'),
				intro: '<div style="overflow:hidden;width:250px"><img src="assets/question-mark.png" style="float:left;margin-right:20px;" width="50px"><p>Click this icon to open more indepth help.</p></div>',
				position: 'bottom'
			},
			{
				element: document.querySelector('.card-demo9'),
				intro: '<div style="overflow:hidden;width:250px"><p>You can jump between time periods using this drop down.</p></div>',
				position: 'bottom'
			},
			{
				element: document.querySelector('.card-demo10'),
				intro: '<div style="overflow:hidden;width:250px"><p>Current conditions shows values based on information reported by your Sensibo.</p></div>',
				position: 'bottom'
			},
			{
				element: document.querySelector('.card-demo11'),
				intro: '<div style="overflow:hidden;width:250px"><p>Whether rain or shine, our Outdoor Conditions section ensures you\'re prepared for whatever nature has in store. With a glance, you\'ll have the insights you need.</p></div>',
				position: 'bottom'
			},
		]
		}).start();
	} catch (e) {
		console.log(e)
	}
}

setTimeout(delayLoading, 1500);

<?php
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
