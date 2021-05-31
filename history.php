<?php
    session_start();
    require_once('../config/Db_connect.php');
    require_once('../config/config_items.php');
    if( !isset($_SESSION['IdNumber']) ){
        header('LOCATION: ../index.php');
    }

    $date_from = date('Y-m-01');
    $date_to = date('Y-m-t');
    //initial data to be the current month's
    $where = "1";
    $is_searched = FALSE;
    
    $query = "SELECT  DateClaimed < DATE_SUB(NOW(), INTERVAL 1 month), d.DocumentTrackingNo, d.DateSubmitted, d.DocumentName, d.DocumentDetails, DocumentTypeName, d.ClaimedBy, d.Remarks, u1.ContactNo, CONCAT(u2.FName,' ',u2.LName, ' - ', u2.IdNumber) AS MName, CONCAT(u1.FName,' ',u1.LName, ' - ', u1.IdNumber) AS Name,  CONCAT(u3.FName,' ',u3.LName, ' - ', u3.IdNumber) AS RName, de.LocName, d.Status as DocumentStatus, d.PriorityLevel, (SELECT IF(dl.Location IS NOT NULL, dep.LocName, IF(LogDescription = 'Document Added', '', de.LocName) ) FROM documentdetailfile dl LEFT JOIN department dep ON dl.Location = dep.LocId WHERE dl.DocumentTrackingNo = d.DocumentTrackingNo ORDER BY LogUpdated DESC LIMIT 1) AS PreviousLocation, DateClaimed FROM documentheaderfile d LEFT JOIN document_type dt ON d.DocumentType = dt.DocumentTypeID LEFT JOIN users u1 ON d.SubmittedBy = u1.IdNumber LEFT JOIN users u2 ON d.ManagedBy = u2.IdNumber LEFT JOIN users u3 ON u3.IdNumber = d.ReceivedBy LEFT JOIN department de ON Location = LocId LEFT JOIN documentdetailfile dl2 ON dl2.DocumentTrackingNo = d.DocumentTrackingNo WHERE dl2.LoggedTextFormat LIKE '%ManagedByFrom---".$_SESSION['IdNumber']."%' OR dl2.LoggedTextFormat LIKE '%ManagedByTo---".$_SESSION['IdNumber']."%' GROUP BY d.DocumentTrackingNo";

    //echo $query;

    //$where AND IF(d.Status = 5, DateClaimed < DATE_SUB(NOW(), INTERVAL 1 month) != 1, DateClaimed IS NULL) ORDER BY DateSubmitted DESC";

    $q = $db->query($query, $connection);

    $documents = $db->get_assoc_for_data_source($q);
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
    <link href="../assets/css/datatable2.css" rel="stylesheet" />
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
        div.dataTables_wrapper div.dataTables_filter {
            margin-right: 50px;
        }
        .navbar-default {
            background-color: #00CA79; 
        }
        .navbar {
            border: none;
        }
        .jumbotron{
            padding-top: 20px !important;
        }
        .date_range input[type="text"]{
            margin-left: 0px !important;
            margin-right: 10px;
        }
        label{
            margin-top: 5px;
            margin-bottom: 0px;
        }

        .logs-content tbody tr:nth-child(odd){
            background: #00b358 !important;
            color: white;
        }
        .message{
            min-height: 300px;
        }
        .dataTable{
            width: auto !important;
        }

    </style>
</head>
<body>
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
                        <h1 class="page-head-line">Managed Documents</h1>
                    </div>
                </div>
                <!-- /. ROW  -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="jumbotron">
                            <div class="tibs">
                                <!-- <div class='date_range'>
                                    <form method='POST' action=''>
                                    <div class='row'>
                                        <div class='form-group'>
                                            <label for="from" class='col-sm-1'>From:</label>
                                            <div class='col-sm-2'>
                                                <input type="text" class='form-control' id="from" name="date_search_from">
                                            </div>
                                            <label for="to" class='col-sm-1'>to:</label>
                                            <div class='col-sm-2'>
                                              <input type="text" class='form-control' id="to" name="date_search_to">
                                            </div>
                                        </div>
                                    </div>
                                    <div class='row'>
                                        <div class='form-group'>

                                            <label for='trackingno' class='col-sm-1'>Tracking No. </label>
                                            <div class='col-sm-2'>
                                                <input type="text" class='form-control' id="trackingno" name='trackingno_search' />
                                            </div>
                                            <label for='studentid' class='col-sm-1'>Student ID Number. </label>
                                            <div class='col-sm-2'>
                                                <input type="text" class='form-control' id="studentid" name='studentid' />
                                            </div>
                                            <input type='submit' class='btn btn-sm btn-success'  value="Search" />
                                        </div>
                                    </div>
                                    </form>
                                </div> -->
                                <br/>
                                <table class='table table-bordered table-responsive' id='document_table'>
                                    <thead>
                                        <tr>
                                            <th>Tracking No.</th>
                                            <th>Document Name</th>
                                            <th>Document Type</th>
                                            <th>Submitted By</th>
                                            <th>Received By</th>
                                            <th>Currently Managed By</th>
                                            <th>Priority Level</th>
                                            <th>Date Submitted</th>
                                            <th>Previous Location</th>
                                            <th>Current Location</th>
                                            <th>Status</th>
                                            <th>Claimed By</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                            $docsArr = array();
                                            $prioArr = array();
                                            $claimedArr = array();
                                            $currDate = date_create(date('Y-m-d H:i:s'));

                                            foreach ($documents as $key => $value) {
                                                if( $value->DocumentStatus == 5 ){
                                                    $claimedArr[] = $value;
                                                }
                                                elseif( $value->PriorityLevel == 'High' ){
                                                    $prioArr[] = $value;
                                                }
                                                
                                                else{
                                                    $docsArr[] = $value;
                                                }
                                            }

                                            $newDocs = array_merge($prioArr, $docsArr, $claimedArr);

                                            foreach ($newDocs as $key => $value) {

                                                $pClass = '';
                                                if( $value->PriorityLevel == 'High' ){
                                                    $pClass = 'class="bg-danger"';
                                                }
                                                elseif( $value->PriorityLevel == 'Normal' ){
                                                    $pClass = 'class="bg-success"';
                                                }
                                                elseif( $value->PriorityLevel == 'Low' ){
                                                    $pClass = 'class="bg-warning"';
                                                }

                                                $claimedDate = date_create($value->DateClaimed);

                                                $diff = date_diff($currDate, $claimedDate);

                                                $logdate = $diff->format('%a');

                                                //if( $value->DocumentStatus != 5 || ( $value->DocumentStatus == 5 && $logdate < 8 || $is_searched ) ){
                                                    echo "<tr $pClass>";
                                                    echo "<td><a href='monitordocument.php?trackingno=".$value->DocumentTrackingNo."'>".$value->DocumentTrackingNo."</a></td>";
                                                    echo "<td>".$value->DocumentName."</td>";
                                                    echo "<td>".$value->DocumentTypeName."</td>";
                                                    echo "<td>".$value->Name."</td>";
                                                    echo "<td>".$value->RName."</td>";
                                                    echo "<td>".$value->MName."</td>";
                                                    echo "<td>".$value->PriorityLevel."</td>";
                                                    echo "<td>".$value->DateSubmitted."</td>";
                                                    echo "<td>".$value->PreviousLocation."</td>";
                                                    echo "<td>".$value->LocName."</td>";
                                                    echo "<td>".$document_status[$value->DocumentStatus]."</td>";
                                                    echo "<td>".$value->ClaimedBy."</td>";
                                                    echo "<td><input type='button' value='View Details' date-documentid='".$value->DocumentTrackingNo."' class='btn btn-sm btn-warning open-dialog' />";

                                                    // if( $_SESSION['UserType'] != 0 ){
                                                    //     echo "<input type='button' value='Send SMS' date-documentid='".$value->DocumentTrackingNo."' class='btn btn-sm btn-primary open-sms' />";

                                                    //     $date1 = date_create(date('Y-m-d H:i:s'));
                                                    //     $date2 = date_create($value->DateClaimed);
                                                    //     $datediff = date_diff($date1, $date2);
                                                    //     $diff = $datediff->format('%a');

                                                    //     if( $value->DocumentStatus != 5 || $diff < 8 ){
                                                    //         echo "<a href='updatedocument.php?trackingno=".$value->DocumentTrackingNo."'><input type='button' value='Update' class='btn btn-sm btn-success' /></a>";
                                                    //     }
                                                    // }

                                                    echo "</td>";
                                                    echo "</tr>";
                                                    echo '<div class="dialog-message document'.$value->DocumentTrackingNo.'" title="View Details"><fieldset><legend>Details</legend>'
                                                        .str_replace("\n", "<br/>", $value->DocumentDetails).
                                                        '</fieldset><br/>
                                                        <fieldset>
                                                            <legend>Remarks</legend>
                                                        '.str_replace("\n", "<br/>", $value->Remarks).'
                                                        </fieldset>
                                                        </div>';
                                                    echo "<div class='sms-sender' id='sms-".$value->DocumentTrackingNo."'>
                                                            <div class='form-group'>
                                                                <div class='row'>
                                                                    <label for='recepient' class='col-sm-2'>Recepient:</label>
                                                                    <div class='col-sm-4'>
                                                                        <input type='text' class='form-control recepient' value='".$value->ContactNo."' />
                                                                    </div>
                                                                </div>
                                                                <div class='row'>
                                                                    <label for='message' class='col-sm-2'>Message: </label>
                                                                    <textarea class='form-control message'></textarea>
                                                                </div>
                                                                <br/>
                                                                <div class='row'>
                                                                    <div class='pull-right'>
                                                                        <input type='button' value='Send' data-formid='sms-".$value->DocumentTrackingNo."' class='btn btn-sm btn-success sendsms' />
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>";
                                                //}
                                            }
                                        ?>
                                    </tbody>     
                                </table>
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
        var active = 'history';
    </script>

    <script src="../assets/js/nav.js"></script>
    <script src="../assets/js/datatable2.js"></script>
    <script type="text/javascript">
        $(document).ready(function(){
            $('#document_table').DataTable({ responsive: true, "aaSorting": [] });

            //Empty the search fields of date and student id to search only tracking no
            $("#trackingno").keyup(function(){
                $('#to, #from').val('');
                $('#studentid').val('');
            });

            //empty the search field of tracking number and student id to search
            //only from the date range
            $('#from, #to').datepicker().on('change', function(){
                $('#trackingno').val('');
                $('#studentid').val('');
            });

            //Search only the student id
            $('#studentid').keyup(function(){
                $('#trackingno').val('');
                $('#to, #from').val('');
            });

            //Initiate pop up for the view details
            $('.dialog-message, .sms-sender').dialog({
                autoOpen: false,
                modal: true,
                width: 800,
                height: 500,
                maxHeight: 800,
            });

            $(document).on('click','.open-sms',function(){
                var tracking = $(this).attr('date-documentid');
                $('#sms-'+tracking).dialog('open');
                $('#sms-'+tracking).dialog('option', 'title', 'Send SMS');
            });

            $('.sms-sender').on('click','.sendsms',function(){
                var id = $(this).attr('data-formid');
                var number = $('#'+id+' .recepient').val();
                var message = $('#'+id+' .message').val();

                var url = '../includes/functions.php';
                var data = {'method': 'sendsms', 'number': number, 'message': message};

                ajax_call(url, data, function data_handler(data){
                    if ( data == 0 ){
                        alert('Message Sent!');
                    }

                    $("#"+id).dialog('close');
                });
            });

            //View details of documents
            $('#document_table').on('click','.open-dialog', function(){
                var tracking = $(this).attr('date-documentid');
                var url = '../includes/functions.php';
                var data = {'method': 'get_document_logs', 'tracking_no': tracking};
                $('.document'+tracking).dialog('open');
                $('.document'+tracking).dialog('option', 'title', 'Document Details');

                //Get history of data from documentlogs
                $('.logs-content').empty();
            });
        });
    </script>


