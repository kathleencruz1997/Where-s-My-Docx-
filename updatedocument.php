<?php
    session_start();
    require_once('../config/Db_connect.php');
    require_once('../config/config_items.php');
    if( !isset($_SESSION['IdNumber']) ){
        header('LOCATION: ../index.php');
    }

    //check for notification
    // $query = "SELECT notificationDescription, trackingno FROM notification WHERE userID = ".$_SESSION['IdNumber']." AND notificationAcknowledged IS NULL";
    // echo $query;


    $hasTrack = FALSE;

    $success = '';

    $docs = (object)array(
                'DocumentTrackingNo' => '', 
                'DocumentName' => '', 
                'DocumentTypeName' => '', 
                'PriorityLevel' => '', 
                'SubmittedBy' => '',
                'ReceivedBy' => '',
                'DocumentDetails' => '', 
                'Remarks' => '',
                'DocumentType' => '',
                'Location' => '',
                'LocName' => '',
                'Status' => ''
            );

    $isClaimed = FALSE;
    
    if( !isset($_GET['trackingno']) ){
        $hasTrack = FALSE;
    }else{
        $hasTrack = TRUE;
        $trackingno = $_GET['trackingno'];

        $extraWhere = '';
        if( $_SESSION['UserType'] != 0 ){
            $extraWhere = " AND Location = ".$_SESSION['LocId'];
        }

        $query = "SELECT DocumentTrackingNo, DocumentName, DocumentTypeName, d.Status AS DocumentStatus, PriorityLevel, SubmittedBy, ReceivedBy, ManagedBy, DocumentDetails, Remarks, DocumentType, Location, LocName, d.Status, DATEDIFF(NOW(), DateClaimed) AS diffClaim, ClaimedBy, (SELECT IF(dl.Location IS NOT NULL, dep.LocId, IF(LogDescription = 'Document Added', '', de.LocId) ) FROM documentdetailfile dl LEFT JOIN department dep ON dl.Location = dep.LocId WHERE dl.DocumentTrackingNo = d.DocumentTrackingNo ORDER BY LogUpdated DESC LIMIT 1) AS PreviousLocation FROM documentheaderfile d LEFT JOIN department de ON d.Location = de.LocId LEFT JOIN document_type dt ON dt.DocumentTypeID = d.DocumentType WHERE DocumentTrackingNo = $trackingno $extraWhere";

        $q = $db->query($query, $connection);

        if( $q->num_rows ){
            $docs = $db->get_assoc_for_data_source($q);

            if( $docs[0]->DocumentStatus == 5 && $docs[0]->diffClaim > 7 ){
                $isClaimed = TRUE;
            }else{
                $docs = $docs[0];
            }
        }
        else{
            $success = '<div class="alert alert-danger" role="alert">Tracking Number:<strong> '.$_GET['trackingno'].'</strong> Not found or you do not have access!! </div>';
        }

         if( $isClaimed ){
            $success = '<div class="alert alert-danger" role="alert">Tracking Number:<strong> '.$_GET['trackingno'].'</strong> is already claimed. Cannot Update </div>';
        }
    }

    //Get all staff
    $query = "SELECT CONCAT(FName,' ', LName, ' - ', LocName) AS staff, IdNumber FROM users LEFT JOIN department ON LocId = Department WHERE UserType != 2";
    $q = $db->query( $query, $connection );

    $staff = $db->get_assoc_for_data_source($q);

    //get document types
    $query = "SELECT DocumentTypeID, DocumentTypeName FROM document_type";
    $q = $db->query($query, $connection);

    $documents = $db->get_assoc_for_data_source($q);
    //make an array
    $documentsArr = array();
    foreach ($documents as $key => $value) {
        $documentsArr[$value->DocumentTypeID] = $value->DocumentTypeName;
    }

    //get departments
    $query = "SELECT LocId, LocName FROM department WHERE status = 1";
    $q = $db->query($query, $connection);

    $departments = $db->get_assoc_for_data_source($q);

    //put department in array
    $departmentArr = array();
    foreach ($departments as $key => $value) {
        $departmentArr[$value->LocId] = $value->LocName;
    }

    //get all students
    $query = "SELECT IdNumber,CONCAT(FName, ' ', LName) AS Name, CONCAT( FName, ' ', LName, ' - ', IdNumber ) as value FROM users";

    $q = $db->query($query, $connection);
    $sourceData = json_encode( $db->get_assoc_for_data_source($q) );
    
    if( isset($_POST['submit']) ){
        //Compare data for update
        unset($_POST['submit']);

        $changes = '';
        $insertQueryColumn = '( ';
        $insertQueryValues = '( ';
        $query_set = '';
        $changeCount = 0;
        $sendsms = FALSE;
        $claimed = FALSE;
        $historyChangesArr = array();
        $historyAsText = '';
        $hasManagedBy = FALSE;
        $changedLocation = array();

        foreach ($_POST as $key => $value) {
            $kv = $docs->$key;
            if( $key == 'Location' ){
                $kv = $docs->PreviousLocation;
            }
            $historyAsText .= $key.'From---'.$kv.'---'.$key.'To---'.mysqli_real_escape_string( $connection, $value).'|||';
            // $historyChangesArr[$key.'From'] = $docs->$key;
            // $historyChangesArr[$key.'To'] = mysqli_real_escape_string( $connection, $value).'|||';

            if ( $docs->$key != $value){
                $changeCount++;

                if( $key == 'ManagedBy' ){
                    $hasManagedBy = TRUE;
                }

                $from = $docs->$key;
                $val = $value;
                if( $key == 'Location' ){
                    $from = $docs->LocName;
                    $val = $departmentArr[$value];

                    $changedLocation = array($value);
                }
                
                if( $key == 'Status'){
                    $from = $document_status[$docs->Status];
                    $val = $document_status[$value];
                }

                if( $key == 'DocumentType' ){
                    $from = $docs->DocumentTypeName;
                    $val = $documentsArr[$value];
                }

                if ($key == 'Status' && $value == 4){
                    $sendsms = TRUE;
                }
                //Update DateClaimed

                elseif ($key == 'Status' && $value == 5){
                    $query = "UPDATE documentheaderfile SET DateClaimed = NOW() WHERE DocumentTrackingNo = ".$_POST['DocumentTrackingNo'];

                    $q = $db->query($query, $connection);
                }


                //Text for logs
                $changes .= '<strong>Changed '.$key.' <br/>From: </strong><br/>'.$from.' <strong><br/>To</br/></strong> '.$val.'<hr/>';
            }

            $value = mysqli_real_escape_string( $connection, $value );
            //insert to document_logs
            $insertQueryColumn .= "$key, ";
            $insertQueryValues .= " '".$docs->$key."', ";
            $query_set .= $key.'="'.$value.'",';
            $docs->$key = $value;

        }

        if( $sendsms ){
            //get mobile number
            $query = "SELECT ContactNo, CONCAT(FName,' ', LName) AS studentName FROM users WHERE IdNumber = ".$docs->SubmittedBy;
            $q = $db->query($query, $connection);

            $pickUpLocation = (!empty($changedLocation)) ? $changedLocation[0] : $_SESSION['Department'];

            $result = $db->get_assoc_for_data_source($q)[0];
            $number = $result->ContactNo;
            $message = "Hello ".$result->studentName.", Your document, with Tracking Number ".$docs->DocumentTrackingNo.", is ready for pick up at ".$departmentArr[$pickUpLocation].".";
            
            require_once('../includes/functions.php');

            $sms = array('number' => $number, 'message' => $message );

            $smssent = sendsms($sms, $connection, $db);
        }

        if( (isset($smssent) && $smssent == 0 ) || $smssent == FALSE ){ 
            $query_set = trim($query_set,',') ;

            //$changes = mysqli_real_escape_string( $connection, $changes );
            $insertQueryColumn .= 'LoggedBy, LogUpdated,  LogDescription, LoggedTextFormat, ReceivedBy )';
            $insertQueryValues .= $_SESSION['IdNumber']." ,NOW(),  '$changes', '$historyAsText', ".$docs->ReceivedBy." )";

            $query = "UPDATE documentheaderfile SET $query_set WHERE DocumentTrackingNo = ".$_POST['DocumentTrackingNo'];

            $q = $db->query($query, $connection);
            $logID = 0;

            //insert into logs
            if ($changeCount){
                $query = "INSERT INTO documentdetailfile $insertQueryColumn VALUES $insertQueryValues";
                
                $q = $db->query($query, $connection);

                $logID = mysqli_insert_id($connection);

                //if location is changed empty the received by
                if( !empty($changedLocation) ){
                    //UPDATE the header file to remove the received by
                    $query = "UPDATE documentheaderfile SET ReceivedBy = 0 WHERE DocumentTrackingNo = ".$_POST['DocumentTrackingNo'];

                    $q = $db->query($query, $connection);

                    //Update the logs to remove the received by
                    $query = "UPDATE documentdetailfile SET ReceivedBy = 0 WHERE DocumentLogsID = $logID";

                    $q = $db->query($query, $connection);

                    //insert into notification to other department
                    $query = "INSERT INTO notification (notificationDescription, trackingno, userID ) SELECT 'New Incoming Document - Tracking Number #".$_POST['DocumentTrackingNo']."' as notificationDescription, ".$_POST['DocumentTrackingNo']." as trackingno, IdNumber FROM users WHERE Department = ".$changedLocation[0];

                    $q = $db->query($query, $connection);
                }
            }
            if( (isset($smssent) && $smssent == 0 ) ){
                //insert into logs for the client
                $query = "INSERT INTO notification (notificationDescription, trackingno, userID) VALUES ('Your document is ready for pick up', ".$_POST['DocumentTrackingNo'].", ".$docs->SubmittedBy.") ";

                $q = $db->query($query, $connection);

                $query = "UPDATE documentdetailfile SET Remarks = CONCAT(Remarks,' - ', 'Ready to claim SMS sent') WHERE DocumentLogsID = $logID";

                $q = $db->query($query, $connection);
            }
        }else{
            echo "<script>alert('Message was not sent, please update the file again!');</script>";
            header('Refresh: 0');
            exit();
        }
        
        //change manage by
        if( !$hasManagedBy ){
            $query = "UPDATE documentheaderfile SET ManagedBy = ".$_SESSION['IdNumber']." WHERE DocumentTrackingNo = ".$_POST['DocumentTrackingNo'];

            $q = $db->query($query, $connection);
        }

        header("LOCATION: monitordocument.php?trackingno=".$_POST['DocumentTrackingNo']."&success=true");
        //$success = '<div class="alert alert-success" role="alert">Document Updated. Tracking Number: <strong>'.$_POST['DocumentTrackingNo'].'</strong> <a href="monitordocument.php?trackingno='.$_POST['DocumentTrackingNo'].'">Click Here</a> to Monitor </div>';
    }

    if( isset($_POST['acknowledge']) ){
        //SELECT if the document is already acknowledged by someone

        $query = "SELECT ReceivedBy FROM documentheaderfile WHERE DocumentTrackingNo = ".$_GET['trackingno']." AND ReceivedBy != 0 ";
        $q = $db->query($query, $connection);

        if( !$q->num_rows ){
            $query = "UPDATE documentheaderfile SET ReceivedBy = ".$_SESSION['IdNumber']." WHERE DocumentTrackingNo = ".$_GET['trackingno'];

            $q = $db->query($query, $connection);

            $query = " INSERT INTO documentdetailfile VALUES(null, ".$_GET['trackingno'].", '".$docs->PriorityLevel."', '".$docs->DocumentType."', '".$docs->DocumentName."','".$docs->DocumentDetails."', '".$docs->Remarks."', ".$docs->SubmittedBy.", ".$_SESSION['IdNumber'].", ".$docs->ManagedBy.", 0, null, ".$docs->DocumentStatus.", ".$docs->PreviousLocation.", NOW(), ".$_SESSION['IdNumber'].", '', 'Document acknowledged by ".$_SESSION['IdNumber']."' )  ";

            $q = $db->query($query, $connection);

            //Update notification to remove the notification
            $query = "UPDATE notification SET notificationAcknowledged = NOW() WHERE trackingno = ".$_GET['trackingno'];
            $q = $db->query($query, $connection);

            header('Refresh: 0');
        }
        else{
            echo "<script>alert('Document has already been claimed');</script>";
        }
    }
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Where's My Docx?-Update Client</title>

    <!-- BOOTSTRAP STYLES-->
    <link href="../assets/css/bootstrap.css" rel="stylesheet" />
    <!-- FONTAWESOME STYLES-->
    <link href="../assets/css/font-awesome.css" rel="stylesheet" />
    <!--CUSTOM BASIC STYLES-->
    <link href="../assets/css/basic.css" rel="stylesheet" />
	<link href="../assets/css/searchbar.css" rel="stylesheet" />
	<link href="../assets/css/janice.css" rel="stylesheet" />
	<link href="../assets/css/table.css" rel="stylesheet" />
    <!--CUSTOM MAIN STYLES-->
    <link href="../assets/css/custom.css" rel="stylesheet" />
    <!-- GOOGLE FONTS-->
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />

    <link href="../assets/css/jquery.ui.css" rel="stylesheet" />
    <style type="text/css">
        .form-control{
            width: 100% !important;
            margin: 0px !important;
        }
        .row{
            margin: 0px;
        }
        .header_row{
            margin-left: 450px;
        }
    </style>
</head>
<body>

    <?php
        //SELECT if this document has no receiveby
        $locked = false;
        if( isset($_GET['trackingno']) && ( !$docs->ReceivedBy && ($docs->Location == $_SESSION['Department'] || $_SESSION['UserType'] == 0) ) ){
            $locked = true;
    ?>
            <div class='ack-dialog'>
                <form method='POST'>
                    <p>In order to manage the document it should be acknowledged.<br/></p>
                    <div class='text-right'>
                        <input type="submit" name="acknowledge" class='btn btn-success btn-sm' value="Accept" />
                        <input type="button" class='btn btn-danger btn-sm cancel-ack' value="Cancel" />
                    </div>
                </form>
            </div>
    <?php
        }
    ?>

    <div id="wrapper">
        <nav class="navbar navbar-default navbar-cls-top " role="navigation" style="margin-bottom: 0">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".sidebar-collapse">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="../staff.php"><img src="../assets/img/logo.png" class="go"/></img></a>
            </div>

            <div class="header-right">

              
                <?php require_once('../includes/topbar.php') ?>
                <a href="../logout.php" class="btn btn-danger" title="Logout"><i class="fa fa-exclamation-circle fa-2x"></i></a>


            </div>
        </nav>
        <!-- /. NAV TOP  -->
        <?php require_once('../includes/navbar.php') ?>
        <!-- /. NAV SIDE  -->
        <div id="page-wrapper">
            <div id="page-inner">
                <div class="row header_row">
                    <div class="col-md-12">
                        <h1 class="page-head-line">Update Document</h1>
                    </div>
                </div>
                <!-- /. ROW  -->
				<div class="row">
                    <div class="col-md-12">
                        <div class="jumbotron">
                			<div class="tibs">
                				<form method="post" action="">
                					<table class='table'>
                                        <tr>
                                            <td colspan="4"><?=$success;?></td>
                                        </tr>
                                        <tr>
                                            <td> <strong> Tracking No: </strong></td>
                                            <td>
                                                <?php 
                                                    $disabled = '';
                                                    if( $docs->DocumentTrackingNo ){
                                                        $disabled = 'disabled';
                                                    }
                                                ?>
                                                <input type='hidden' name='DocumentTrackingNo' value='<?=$docs->DocumentTrackingNo?>'>
                                                <input type="number" <?=$disabled?> required class='form-control tracking-no' placeholder="Tracking No." value='<?=$docs->DocumentTrackingNo?>' />
                                            </td>
                                            <td>
                                                <input valign='center' type='button' value='Search' class='btn btn-sm btn-success search-tracking' />
                                            </td>
                                        </tr>
                                        <?php if( isset($_GET['trackingno']) && $success == '' ) {?>
                                        <tr>
                                            <td> <strong> Document Name: </strong></td>
                                            <td><input type="text" required name="DocumentName" class='form-control' required placeholder="Document Name" value='<?=$docs->DocumentName?>' /></td>
                                            <td> <strong> Priority Level: </strong></td>
                                            <td>
                                                <?php
                                                    $high = ( strtolower($docs->PriorityLevel) == 'high' )? 'selected' : '';
                                                    $normal = ( strtolower($docs->PriorityLevel) == 'normal' )? 'selected' : '';
                                                    $low = ( strtolower($docs->PriorityLevel) == 'low' )? 'selected' : '';
                                                ?>
                                                <select name='PriorityLevel'>
                                                    <option <?=$high?> value='High'>High</option>
                                                    <option <?=$normal?> value='Normal'>Normal</option>
                                                    <option <?=$low?> value='Low'>Low</option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td> <strong> Submitted By: </strong></td>
                                            <td><input type="text" required name="SubmittedBy" id='search_user' required class='form-control' value='<?=$docs->SubmittedBy?>' placeholder="Enter Student ID" data-container='body' data-placement='right' data-content="Invalid ID Number" /></td>
                                        </tr>
                                        <tr class='hidden'>
                                            <td> <strong> Assign to: </strong></td>
                                            <td>
                                                <select name='ManagedBy'>
                                                    <?php

                                                        if( !$docs->ManagedBy ){
                                                            $docs->ManagedBy = $_SESSION['IdNumber'];
                                                        }
                                                        foreach ($staff as $key => $value) {
                                                            $selected = '';
                                                            if( $docs->ManagedBy == $value->IdNumber )
                                                                $selected = 'selected';

                                                            echo '<option '.$selected.' value="'.$value->IdNumber.'">'.$value->staff.'</option>';
                                                        }
                                                    ?>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Document Details</strong></td>
                                            <td colspan="3"><textarea required style='height: 200px;' required class='form-control' name='DocumentDetails'><?=$docs->DocumentDetails?></textarea></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Remarks</strong></td>
                                            <td colspan="3"><textarea style='height: 100px;' class='form-control' name='Remarks'><?=$docs->Remarks?></textarea></td>
                                        </tr>
                                         <tr>
                                            <td><strong>Document Type</strong></td>
                                            <td colspan="3">
                                                <select name='DocumentType'>
                                                    <?php
                                                        foreach ($documents as $key => $value) {
                                                            $selected = '';
                                                            if( $value->DocumentTypeID == $docs->DocumentType)
                                                                $selected = 'selected';
                                                            echo '<option '.$selected.' value="'.$value->DocumentTypeID.'" >'.$value->DocumentTypeName.'</option>';
                                                        }
                                                    ?>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Status</strong></td>
                                            <td colspan="3">
                                                <select name='Status'>
                                                    <?php
                                                        foreach ($document_status as $key => $value) {
                                                            $selected = '';
                                                            if( $key == $docs->Status)
                                                                $selected = 'selected';

                                                            echo '<option '.$selected.' value="'.$key.'" >'.$value
                                                            .'</option>';
                                                        }
                                                    ?>
                                                </select>
                                            </td>
                                        </tr>
                                        <?php
                                            $hidden = 'hidden';
                                            if( $docs->Status == 5 ){
                                                $hidden = '';
                                            }
                                        ?>
                                        <tr class="cbclass <?=$hidden?>"><td><strong>Claimed By</strong></td><td><input type="text" name="ClaimedBy" value='<?=$docs->ClaimedBy?>'></td></tr>
                                        <tr>
                                            <td><strong>Change Location</strong></td>
                                            <td colspan="3">
                                                <select name='Location'>
                                                    <?php
                                                        foreach ($departments as $key => $value) {
                                                            $selected = '';
                                                            if( $value->LocId == $docs->Location)
                                                                $selected = 'selected';

                                                            echo '<option '.$selected.' value="'.$value->LocId.'" >'.$value->LocName.'</option>';
                                                        }
                                                    ?>
                                                </select>
                                            </td>
                                        </tr>
                                        <?php if( !$locked || $_SESSION['UserType'] == 0 ){ ?>
                                        <tr>
                                            <td><input type="submit" class='btn btn-small btn-success' name="submit" value='Submit'/></td>
                                        </tr>
                                        <?php }}?>
                                    </table>
                				</form>
                			</div>
                        </div>
    	            </div>
                </div>

            </div>
            <!-- /. PAGE INNER  -->
        </div>
        <!-- /. PAGE WRAPPER  -->
    </div>
    <!-- /. WRAPPER  -->
    <div id="footer-sec">
        &copy; 2017 Where's my Docx? | All Rights Reserved
    </div>
    <!-- /. FOOTER  -->
    <!-- SCRIPTS -AT THE BOTOM TO REDUCE THE LOAD TIME-->
     <script src="../assets/js/jquery-1.10.2.js"></script>
    <!-- JQUERY UI SCRIPTS -->
    <script src="../assets/js/jquery.ui.js"></script>
    <!-- BOOTSTRAP SCRIPTS -->
    <script src="../assets/js/bootstrap.js"></script>
    <!-- METISMENU SCRIPTS -->
    <script src="../assets/js/jquery.metisMenu.js"></script>
    <!-- CUSTOM SCRIPTS -->
    <script src="../assets/js/custom.js"></script>
    <script type="text/javascript">
        var active = 'updatedocument';
    </script>

    <script src="../assets/js/nav.js"></script>
    <script type="text/javascript">
        $(document).ready(function(){
            console.log(<?=$sourceData;?>);
            $('#search_user').autocomplete({
                source: <?=$sourceData;?>,
                select: function (e, u){
                    var id = u.item.IdNumber;
                    $("#search_user").val(id);
                    return false;
                }
            });
            
            $('input[name="ClaimedBy"]').autocomplete({
                source: <?=$sourceData;?>,
                select: function (e, u){
                    var val = u.item.Name;
                    $(this).val(val);
                    return false;
                }
            });

            $('.ack-dialog').dialog({
                modal: true,
            });

            $('.cancel-ack').on('click', function(){
                $('.ack-dialog').dialog('close');
            });

            $('.search-tracking').click(function(){
                var trackingno = $('.tracking-no').val();
                if( trackingno != '' ){
                    location.href = 'updatedocument.php?trackingno='+trackingno;
                }
            });

            $('form').on('submit', function(e){
                var idnum = $('input[name="SubmittedBy"]').val();

                var hasError = false;
                if( !$.isNumeric(idnum) ){
                    hasError = true;
                    $('input[name="SubmittedBy"]').popover('show');
                }

                //return false;
                return !hasError;
            });

            $('select[name="Status"]').on('change', function(){
                var status = $(this).val();
                if( status == 5 ){
                    $('.cbclass').removeClass('hidden');
                    $('.cbclass input[name="ClaimedBy"]').attr('required','required');
                }
                else{
                    $('.cbclass').addClass('hidden');
                    $('.cbclass input[name="ClaimedBy"]').removeAttr('required','required');
                }
            });
        });
    </script>
</body>
</html>
