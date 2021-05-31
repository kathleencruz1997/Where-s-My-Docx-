<?php
    session_start();
    require_once('../config/Db_connect.php');
    require_once('../config/config_items.php');
    if( !isset($_SESSION['IdNumber']) ){
        header('LOCATION: ../index.php');
    }

    $hasTrack = FALSE;

    $error = '';

    $docs = (object)array(
                'DocumentTrackingNo' => '', 
                'DocumentName' => '', 
                'DocumentTypeName' => '', 
                'PriorityLevel' => '', 
                'SubmittedBy' => '', 
                'DocumentDetails' => '', 
                'Remarks' => '',
                'DocumentType' => '',
                'Location' => '',
                'LocName' => '',
                'Status' => ''
            );
    
    if( !isset($_GET['trackingno']) ){
        $hasTrack = FALSE;
    }else{
        $hasTrack = TRUE;
        $trackingno = $_GET['trackingno'];

        $extraWhere = '';
        // 
        //     $extraWhere = " AND Location = ".$_SESSION['LocId'];
        // }

        $query = "SELECT DocumentTrackingNo, DocumentName, d.Status AS DocumentStatus, DocumentTypeName, u1.ContactNo, PriorityLevel,ClaimedBy, SubmittedBy, CONCAT(u3.FName, ' ', u3.LName) AS ReceivedBy, ManagedBy AS mby, DocumentDetails, Remarks, DateSubmitted, DocumentType, Location, de.LocName, d.Status, CONCAT(u2.IdNumber, ' - ', u2.FName, ' ', u2.LName) AS ManagedBy, DATEDIFF(NOW(), DateClaimed) AS diffClaim,
            (SELECT IF(dl.Location IS NOT NULL, dep.LocId, IF(LogDescription = 'Document Added', '', de.LocId) ) FROM documentdetailfile dl LEFT JOIN department dep ON dl.Location = dep.LocId WHERE dl.DocumentTrackingNo = d.DocumentTrackingNo ORDER BY LogUpdated DESC LIMIT 1) AS PreviousLocation
            , ReceivedBy AS rby
            FROM documentheaderfile d 
            LEFT JOIN department de ON d.Location = de.LocId 
            LEFT JOIN users u1 ON SubmittedBy = u1.IdNumber 
            LEFT JOIN users u2 ON u2.IdNumber = d.ManagedBy 
            LEFT JOIN users u3 ON u3.IdNumber = d.ReceivedBy 
            LEFT JOIN document_type dt ON dt.DocumentTypeID = d.DocumentType 
            WHERE DocumentTrackingNo = $trackingno $extraWhere";

        $q = $db->query($query, $connection);

        if( $q->num_rows ){
            $docs = $db->get_assoc_for_data_source($q);

            $docs = $docs[0];
        }
        else{
            $error = '<div class="alert alert-danger" role="alert">Tracking Number:<strong> '.$_GET['trackingno'].'</strong> Not found or you do not have access!! </div>';
        }

        if( $_SESSION['UserType'] != 0 && $docs->Location != $_SESSION['Department']){
               $error = '<div class="alert alert-danger" role="alert">You do not have access to Tracking Number:<strong> '.$_GET['trackingno'].'</strong></div>';
        }
    }

    if( isset($_POST['acknowledge']) ){
        //SELECT if the document is already acknowledged by someone

        $query = "SELECT ReceivedBy FROM documentheaderfile WHERE DocumentTrackingNo = ".$_GET['trackingno']." AND ReceivedBy != 0 ";
        $q = $db->query($query, $connection);

        if( !$q->num_rows ){
            $query = "UPDATE documentheaderfile SET ReceivedBy = ".$_SESSION['IdNumber']." WHERE DocumentTrackingNo = ".$_GET['trackingno'];

            $q = $db->query($query, $connection);

            $query = " INSERT INTO documentdetailfile VALUES(null, ".$_GET['trackingno'].", '".$docs->PriorityLevel."', '".$docs->DocumentType."', '".$docs->DocumentName."','".$docs->DocumentDetails."', '".$docs->Remarks."', ".$docs->SubmittedBy.", ".$_SESSION['IdNumber'].", ".$docs->mby.", 0, null, ".$docs->DocumentStatus.", ".$docs->PreviousLocation.", NOW(), ".$_SESSION['IdNumber'].", '', 'Document acknowledged by ".$_SESSION['IdNumber']."' )  ";

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

    //get departments
    $query = "SELECT DocumentTypeID, DocumentTypeName FROM document_type";
    $q = $db->query($query, $connection);

    $documents = $db->get_assoc_for_data_source($q);
    //make an array
    $documentsArr = array();
    foreach ($documents as $key => $value) {
        $documentsArr[$value->DocumentTypeID] = $value->DocumentTypeName;
    }

    //get document types
    $query = "SELECT LocId, LocName FROM department WHERE Status = 1";
    $q = $db->query($query, $connection);

    $departments = $db->get_assoc_for_data_source($q);

    //put department in array
    $departmentArr = array();
    foreach ($departments as $key => $value) {
        $departmentArr[$value->LocId] = $value->LocName;
    }

    //get all students
    $query = "SELECT IdNumber, CONCAT( FName, ' ', LName, ' - ', IdNumber ) as value FROM users";

    $q = $db->query($query, $connection);
    $sourceData = json_encode( $db->get_assoc_for_data_source($q) );

    //get document logs
    if(isset($_GET['trackingno'])){
        $query = "SELECT DocumentLogsId, LoggedTextFormat, DocumentTrackingNo, PriorityLevel, DocumentType, DocumentName, DocumentDetails, Remarks, SubmittedBy, DateSubmitted, dl.Status, Location , CONCAT(u1.FName, ' ', u1.LName) AS Logged, LogUpdated, LogDescription, LocName AS loggedLocation, CONCAT(u2.IdNumber, ' - ', u2.FName, ' ', u2.LName) AS ReceivedBy FROM documentdetailfile dl LEFT JOIN users u1 ON u1.IdNumber = LoggedBy LEFT JOIN users u2 ON u2.IdNumber = dl.ReceivedBy LEFT JOIN department de ON de.LocId = dl.Location WHERE DocumentTrackingNo = ".$_GET['trackingno']." ORDER BY LogUpdated DESC";

        $q = $db->query($query, $connection);

        $document_logs = $db->get_assoc_for_data_source($q);
    }

    //if addremarks
    if( isset( $_POST['addRemarks'] ) ){
        $rtrack = $_POST['DocumentTrackingNo'];
        $rmark = mysqli_real_escape_string( $connection, $_POST['remarks']);
        $query = "UPDATE documentheaderfile SET Remarks = '$rmark', ManagedBy = '".$_SESSION['IdNumber']."' WHERE DocumentTrackingNo = $rtrack";

        $q = $db->query($query, $connection);

        $updateRmark = mysqli_real_escape_string( $connection, $_POST['remarks'] );
        $changes = mysqli_real_escape_string( $connection, "<strong>Add Remarks</strong><br/>$rmark<hr/>");
        //$historyAsText .= 'RemarksFrom---'.$docs->Remarks.'---RemarksTo---'.mysqli_real_escape_string( $connection, $rmark).'|||';
        $historyAsText = 'DocumentTrackingNoFrom---1---DocumentTrackingNoTo---1|||DocumentNameFrom---Test Document 1---DocumentNameTo---Test Document 1|||PriorityLevelFrom---Normal---PriorityLevelTo---Normal|||SubmittedByFrom---1234---SubmittedByTo---1234|||ReceivedByFrom---8305765---ReceivedByTo---8305765|||DocumentDetailsFrom---Test Document 1---DocumentDetailsTo---Test Document 1|||RemarksFrom---'.$docs->Remarks.'---RemarksTo---'.$_POST['remarks'].'|||DocumentTypeFrom---2---DocumentTypeTo---2|||StatusFrom---'.$docs->Status.'---StatusTo---'.$docs->Status.'|||ClaimedByFrom------ClaimedByTo---'.$docs->ClaimedBy.'|||LocationFrom---'.$docs->Location.'---LocationTo---'.$docs->Location.'|||';
        // $historyChangesArr = array(
        //     'RemarksFrom' => $docs->Remarks,
        //     'RemarksTo' => $_POST['remarks'],
        //     'LocationFrom' => $docs->Location,
        //     'LocationTo' => $docs->Location,
        //     'StatusFrom' => $docs->Status,
        //     'StatusTo' => $docs->Status,
        // );

        // $historyAsText = json_encode($historyChangesArr);

        //insert into logs
        $query = "INSERT INTO documentdetailfile (DocumentTrackingNo, PriorityLevel, DocumentType, DocumentName, DocumentDetails,Remarks, SubmittedBy, DateSubmitted, Status, Location, LogUpdated,LogDescription, LoggedTextFormat, LoggedBy, ManagedBy, ClaimedBy, ReceivedBy) VALUES ($rtrack, '".$docs->PriorityLevel."', '".$docs->DocumentType."', '".$docs->DocumentName."', '".$docs->DocumentDetails."', '$updateRmark', '".$docs->SubmittedBy."', '".$docs->DateSubmitted."', '".$docs->Status."', '".$docs->Location."', NOW(), '$changes', '$historyAsText' ,".$_SESSION['IdNumber'].", '".$_SESSION['IdNumber']."', '".$docs->ClaimedBy."', '".$docs->rby."') ";

        $q = $db->query($query, $connection);

        header('Refresh:0');
    }
    if( isset($_GET['success']) ){
        echo "<script>alert('Document Updated');</script>";
        header('LOCATION:monitordocument.php?trackingno='.$_GET['trackingno']);
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
        .table-logs a, .table-logs a:visited{
            color: #428bca;
        }

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
        .logs-content table tr:nth-child(odd){
            background: #00b358 !important;
            color: white;
        }
        .message{
            min-height: 300px;
        }
    </style>
</head>
<body>
    <?php
        //SELECT if this document has no receiveby
        $locked = false;
        if( isset($_GET['trackingno']) && ( !$docs->ReceivedBy && ($docs->Location == $_SESSION['Department']) ) ){
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
              
                <!-- <a href="message-task.php" class="btn btn-primary" title="New Task"><b><?=$numNotif?> </b><i class="fa fa-bars fa-2x"></i></a> -->
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
                        <h1 class="page-head-line">Monitor Document</h1>
                    </div>
                </div>
                <!-- /. ROW  -->
				<div class="row">
                    <div class="col-md-12">
                        <div class="jumbotron">
                			<div class="tibs">
            					<table class='table'>
                                    <tr>
                                        <td colspan="4"><?=$error;?></td>
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
                                        <tr>
                                            <td> <strong> Document Name: </strong></td>
                                            <!-- <td><input type="text" required name="DocumentName" class='form-control' placeholder="Document Name" value='<?=$docs->DocumentName?>' /></td> -->
                                            <td><?=$docs->DocumentName?></td>
                                            <td> <strong> Priority Level: </strong></td>
                                            <td>
                                                <?=$docs->PriorityLevel?>
                                                <!--
                                                <?php
                                                    $high = ( strtolower($docs->PriorityLevel) == 'high' )? 'selected' : '';
                                                    $normal = ( strtolower($docs->PriorityLevel) == 'normal' )? 'selected' : '';
                                                    $low = ( strtolower($docs->PriorityLevel) == 'now' )? 'selected' : '';
                                                ?>
                                                <select name='PriorityLevel'>
                                                    <option <?=$high?> value='High'>High</option>
                                                    <option <?=$normal?> value='Normal'>Normal</option>
                                                    <option <?=$low?> value='Low'>Low</option>
                                                </select>-->
                                            </td>
                                        </tr>
                                        <tr>
                                            <td> <strong> Assigned To: </strong></td>
                                            <td><?=$docs->ManagedBy?></td>
                                        </tr>
                                        <tr>
                                            <td> <strong> Submitted By: </strong></td>
                                            <!-- <td><input type="text" required name="SubmittedBy" id='search_user' class='form-control' value='<?=$docs->SubmittedBy?>' placeholder="Enter Student ID" /></td> -->
                                            <td><?=$docs->SubmittedBy?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Document Details</strong></td>
                                            <!-- <td colspan="3"><textarea required style='height: 300px;' class='form-control' name='DocumentDetails'><?=$docs->DocumentDetails?></textarea></td> -->
                                            <td colspan="3"><?= str_replace("\n", "<br/>", $docs->DocumentDetails );?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Remarks</strong></td>
                                            <!-- <td colspan="3"><textarea style='height: 100px;' class='form-control' name='Remarks'><?=$docs->Remarks?></textarea></td> -->
                                            <td colspan="3"><?= str_replace("\n", "<br/>", $docs->Remarks);?></td>
                                        </tr>
                                         <tr>
                                            <td><strong>Document Type</strong></td>
                                            <td colspan="3">
                                                <?php
                                                    foreach ($documents as $key => $value) {
                                                        if( $value->DocumentTypeID == $docs->DocumentType)
                                                            echo $value->DocumentTypeName;
                                                    }
                                                ?>
                                               <!--  <select name='DocumentType'>
                                                    <?php
                                                        foreach ($documents as $key => $value) {
                                                            $selected = '';
                                                            if( $value->DocumentTypeID == $docs->DocumentType)
                                                                $selected = 'selected';
                                                            echo '<option '.$selected.' value="'.$value->DocumentTypeID.'" >'.$value->DocumentTypeName.'</option>';
                                                        }
                                                    ?>
                                                </select> -->
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Status</strong></td>
                                            <td colspan="3">
                                                 <?php
                                                    foreach ($document_status as $key => $value) {
                                                      
                                                        if( $key == $docs->Status)
                                                            echo $value;
                                                    }
                                                ?>
                                                <!-- <select name='Status'>
                                                    <?php
                                                        foreach ($document_status as $key => $value) {
                                                            $selected = '';
                                                            if( $key == $docs->Status)
                                                                $selected = 'selected';

                                                            echo '<option '.$selected.' value="'.$key.'" >'.$value
                                                            .'</option>';
                                                        }
                                                    ?>
                                                </select> -->
                                            </td>
                                        </tr>
                                        <?php if($docs->Status == 5){ ?>
                                        <tr>
                                            <td><strong>Claimed By</strong></td>
                                            <td colspan="3">
                                                 <?=$docs->ClaimedBy?>
                                            </td>
                                        </tr>
                                        <?php }?>
                                        <tr>
                                            <td><strong>Location</strong></td>
                                            <td colspan="3">
                                                <?php
                                                    foreach ($departments as $key => $value) {
                                                        if( $value->LocId == $docs->Location)
                                                            echo $value->LocName;
                                                    }
                                                ?>
                                            </td>
                                        </tr>
                                        <?php if( $hasTrack && $error == '' ){ ?>
                                        <?php if( !$locked ){ ?>
                                        <tr>
                                            <td>
                                                <?php if( $_SESSION['UserType'] != 0 && ($docs->DocumentStatus != 5 || $docs->diffClaim < 8 ) ){?>
                                                <a href='updatedocument.php?trackingno=<?=$docs->DocumentTrackingNo?>'><input type='button' class='btn btn-sm btn-success' value='Update'/></a>
                                                <?php }?>
                                                <?php if( $_SESSION['UserType'] != 0 ){ ?>
                                                <input type='button' value='Send SMS' date-documentid='<?=$docs->DocumentTrackingNo?>' class='btn btn-sm btn-primary open-sms' />
                                                <?php }?>
                                            </td>
                                        </tr>
                                        
                                        <tr>
                                            <?php
                                                $disabled = '';
                                                if( $docs->DocumentStatus == 5 && $docs->diffClaim > 7 ){
                                                    $disabled = 'disabled';
                                                }
                                            ?>
                                            <?php if( $_SESSION['UserType'] != 0 ){ ?>
                                                <form method="POST">
                                                    <td><strong>Remarks: </strong><td><input <?=$disabled;?> type='hidden' name='DocumentTrackingNo' value='<?=$docs->DocumentTrackingNo?>'><textarea <?=$disabled;?> required class='form-control' name='remarks'></textarea> <input type='submit' name='addRemarks' <?=$disabled;?> class='btn btn-sm btn-primary' Value='Add'></td></td>
                                                </form>
                                            <?php }?>
                                        </tr>
                                        <?php } }?>
                                        <div class='sms-sender' data-trackno='<?=$docs->DocumentTrackingNo?>' id='sms-<?=$docs->DocumentTrackingNo?>'>
                                            <div class='form-group'>
                                                <div class='row'>
                                                    <label for='recepient' class='col-sm-2'>Recepient:</label>
                                                    <div class='col-sm-4'>
                                                        <input type='text' class='form-control recepient' value='<?=$docs->ContactNo?>' />
                                                    </div>
                                                </div>
                                                <div class='row'>
                                                    <label for='message' class='col-sm-2'>Message: </label>
                                                    <textarea class='form-control message'></textarea>
                                                </div>
                                                <br/>
                                                <div class='row'>
                                                    <div class='pull-right'>
                                                        <input type='button' value='Send' data-formid='sms-<?=$docs->DocumentTrackingNo?>' class='btn btn-sm btn-success sendsms' />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                </table>
                			</div>
                        </div>
    	            </div>
                </div>

                <?php if( isset($document_logs) ){ 
                ?>
                    <div class="row header_row">
                        <div class="col-md-12">
                            <h1 class="page-head-line">History</h1>
                        </div>
                    </div>


                    <div class='row'>
                        <table class='table-logs table table-bordered'>
                            <thead style="background: #00b358; color: white">
                                <tr>
                                    <th>Remarks</th>
                                    <th>Previous Location</th>
                                    <th>Current Location</th>
                                    <th>Status</th>
                                    <th>Managed By</th>
                                    <th>Received By</th>
                                    <th>Claimed By</th>
                                    <th>Date Updated</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                    $logsArr = array();
                                    $rtemp = '';

                                    foreach ($document_logs as $key => $value) {
                                        //if( $value->LogDescription != 'Document Added'){
                                            $logs = $value->LoggedTextFormat;
                                            $eLogs = explode('|||', $logs);

                                            $eRemarks = explode( '---', $eLogs[6] );
                                            $eLoc = explode( '---', $eLogs[10] );
                                            $eStatus = explode( '---', $eLogs[8] );
                                            $eClaimed = explode( '---', $eLogs[9] );

                                            $logsArr[$key] = $value;

                                            echo '<tr  style="background: #ffe0c6">';
                                            if( $value->LogDescription == 'SMS Sent' ){
                                                echo '<td>SMS Sent</td>';
                                                echo '<td></td>';
                                                echo '<td></td>';
                                                echo '<td></td>';
                                                echo '<td></td>';
                                            }
                                            elseif( $value->LogDescription != 'Document Added' && strpos($value->LogDescription, 'Document acknowledged') === FALSE ){
                                                $extra = '';
                                                if( strpos($value->Remarks, 'Ready to claim SMS sent') )
                                                    $extra = ' - Ready to claim SMS sent';

                                                echo '<td>'.$eRemarks[3].$extra.'</td>';
                                                if( ( $value->Location != $eLoc[3] && $value->Location !=  $eLoc[1]) || strpos($value->LogDescription, 'Changed Location') !== FALSE ){
                                                    echo '<td>'.$departmentArr[$value->Location].'</td>';
                                                }
                                                else{
                                                    echo '<td></td>';
                                                }
                                                echo '<td>'.$departmentArr[$eLoc[3]].'</td>';
                                                echo '<td>';
                                                echo $document_status[$eStatus[3]];
                                                echo '</td>';
                                                echo '<td>'.$value->Logged.'</td>';
                                            }
                                            else{
                                                echo '<td>'.$value->Remarks.'</td>';
                                                // echo strpos($value->LogDescription, 'Document acknowledged');
                                                if( strpos($value->LogDescription, 'Document acknowledged') === FALSE ){
                                                    echo '<td></td>';
                                                }
                                                else{
                                                    echo '<td>'.$departmentArr[$value->Location].'</td>';
                                                }
                                                echo '<td>'.$departmentArr[$docs->Location].'</td>';
                                                echo '<td>Processing</td>';
                                                echo '<td></td>';
                                            }
                                            echo '<td>'.$value->ReceivedBy.'</td>';
                                           
                                            
                                            echo '<td>'.$eClaimed[3].'</td>';
                                            echo '<td><a href="#" data-logid="'.$value->DocumentLogsId.'">'.$value->LogUpdated.'</td>';
                                            echo '</tr>';
                                            //}
                                            echo '<tr class="hidden" id="logsid-'.$value->DocumentLogsId.'">';
                                            echo '<td colspan="8">'.str_replace("\n", "<br/>", $value->LogDescription ).'</td>';
                                            echo '</tr>';
                                        //}else{
                                        //    $logsArr[$key] = $value;
                                        //}
                                    }
                                ?>
                            </tbody>
                        </table>
                    </div>

                <!-- <div class="row header_row">
                    <div class="col-md-12">
                        <h1 class="page-head-line">Document Logs</h1>
                    </div>
                </div>
                <div class='row logs-content'>
                    <table class='table-logs table'>
                        <?php
                            foreach ($logsArr as $key => $value) {
                                // if( $value->Location == $_SESSION['Department'] || $_SESSION['UserType'] == 0 ){
                                    echo '<tr>';
                                    echo '<td><strong>'.date('F d, Y H:i:s', strtotime($value->LogUpdated)).'</strong> <i>by</i> <strong>'.$value->Logged.'</strong></td>';
                                    echo '</tr>';
                                    echo '<tr>';
                                    echo '<td>'.str_replace("\n", "<br/>", $value->LogDescription ).'</td>';
                                    echo '</tr>';
                                // }
                            }
                        ?>
                    </table>
                </div> -->
                <?php }?>
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
        var active = 'monitordocument';
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

            $('.sms-sender').dialog({
                autoOpen: false,
                modal: true,
                width: 800,
                height: 500,
                maxHeight: 800,
            });

            $('.ack-dialog').dialog({
                modal: true,
            });

            $('.cancel-ack').on('click', function(){
                $('.ack-dialog').dialog('close');
            });

            $('.open-sms').click(function(){
                $('.sms-sender').dialog('open');
            });

            $('.table-logs tr a').click(function(e){
                e.preventDefault();
                var logid = $(this).attr('data-logid');

                $('#logsid-'+logid).toggleClass('hidden');

            });

            $('.sms-sender').on('click','.sendsms',function(){
                var id = $(this).attr('data-formid');
                var number = $('#'+id+' .recepient').val();
                var message = $('#'+id+' .message').val();
                var trackno = $('#'+id).attr('data-trackno');

                var url = '../includes/functions.php';
                var data = {'method': 'sendsms', 'number': number, 'message': message, 'trackno': trackno};

                ajax_call(url, data, function data_handler(data){
                    console.log(data);
                    if ( data == 0 ){
                        alert('Message Sent!');
                        location.reload();
                    }

                    $("#"+id).dialog('close');
                });
            });

            $('.search-tracking').click(function(){
                var trackingno = $('.tracking-no').val();
                if( trackingno != '' ){
                    location.href = 'monitordocument.php?trackingno='+trackingno;
                }
            });
        });
    </script>
</body>
</html>

