<?php
	session_start();
	if( isset($sendsms) ){
		$true = TRUE;
	}
	elseif( !isset($_POST['method']) ){
		die('Access Denied!');
	}
	else{
		require_once('../config/Db_connect.php');
		$method = $_POST['method'];

		$method($_POST, $connection, $db);
	}

	function get_document_logs($data, $conn, $db){
		$tracking_no = $data['tracking_no'];

		$query = "SELECT IF(LoggedBy IS NULL, '', CONCAT(FName, ' ', LName) ) AS Logged, LogUpdated, LogDescription FROM documentdetailfile LEFT JOIN users ON IdNumber = LoggedBy WHERE DocumentTrackingNo = $tracking_no ORDER BY LogUpdated DESC";

		$q = $db->query($query, $conn);

		$row = $db->get_assoc_for_data_source($q);

		echo json_encode($row);
	}

	function sendsms($data, $conn, $db){
		$number = isset($_POST['number']) ? $_POST['number'] : $data['number'];
		$message = isset($_POST['message']) ? $_POST['message'] : $data['message'];
		$trackingno = isset($_POST['trackno']) ? $_POST['trackno'] : '';
		//$apicode = "TR-NENAM324735_WPK2B";
		$apicode = "TR-BRADL506214_IL5Y5";

		unset($_POST['trackno']);

		if( $trackingno != '' ){
			$query = "INSERT INTO documentdetailfile (DocumentTrackingNo, Remarks, LogDescription, LogUpdated, LoggedBy) VALUES ($trackingno, 'SMS Sent to document owner', 'SMS Sent', NOW(), ".$_SESSION['IdNumber']." ) ";

			$q = $db->query($query, $conn);
		}

		$url = 'https://www.itexmo.com/php_api/api.php';
		$itexmo = array('1' => $number, '2' => $message, '3' => $apicode);
		$param = array(
		    'http' => array(
		        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
		        'method'  => 'POST',
		        'content' => http_build_query($itexmo),
		    ),
		);
		$context  = stream_context_create($param);
		$response =  file_get_contents($url, false, $context);

		return $response;
	}
?>