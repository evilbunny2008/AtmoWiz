<?php
	require_once('mariadb.php');

	if(!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] != true)
	{
		echo "You don't have permission to do this.";
		exit;
	}

	if(!isset($_SESSION['rw']) || $_SESSION['rw'] != true)
	{
		echo "You don't have permission to do this.";
		exit;
	}

	if(!isset($_REQUEST['uid']) || $_REQUEST['uid'] == '')
	{
		echo "Invalid UID or UID is blank.";
		exit;
	}

	$url = "https://home.sensibo.com/api/v2/pods/".$_REQUEST['uid']."/acStates?apiKey=".$apikey."&limit=1&fields=acState";
	$opts = array('http' => array('method'=>"GET", 'header' => "Accept: application/json\r\nContent-Type: application/json\r\n", 'timeout' => 5));
	$context = stream_context_create($opts);
	$ret = @file_get_contents($url, false, $context);

	$statusheader = explode(" ", $http_response_header['0'], 3)['1'];
	if($statusheader != "200")
	{
		var_dump($http_response_header);
		die;
	}

	$ret = json_decode($ret, true)['result']['0'];
	$on = !$ret['acState']['on'];
	$ac_state = json_encode($ret['acState']);

	$url = "https://home.sensibo.com/api/v2/pods/".$_REQUEST['uid']."/acStates/on?apiKey=".$apikey;

	$body = json_encode(['currentAcState' => $ac_state, 'newValue' => $on]);
	$opts = array('http' => array('method'=>"PATCH", 'header' => "Accept: application/json\r\nContent-Type: application/json\r\n", 'content' => $body, 'timeout' => 5));
	$context = stream_context_create($opts);
	$ret = file_get_contents($url, false, $context);

	$statusheader = explode(" ", $http_response_header['0'], 3)['1'];
	if($statusheader == "200")
		echo 200;
	else
		echo $ret;
