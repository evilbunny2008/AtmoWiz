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
	$ret = file_get_contents($url);
	$ret = json_decode($ret, true)['result']['0'];
	$on = !$ret['acState']['on'];
	$ac_state = json_encode($ret['acState']);

	$url = "https://home.sensibo.com/api/v2/pods/".$_REQUEST['uid']."/acStates/on?apiKey=".$apikey;
	$fields = json_encode(['currentAcState' => $ac_state, 'newValue' => $on]);

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('accept: application/json', 'content-type: application/json'));
	curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);

	$head = curl_exec($ch);
	$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);

	echo $status;
	exit;
