<?php
    //start new session or resume.
    //used for checking logged in users
    session_start();
    //Databse connection
    require_once('config/Db_connect.php');

    //checking if there is logged in user
    if( isset( $_SESSION['IdNumber'] ) ){
        if( $_SESSION['Usertype'] == 2 )
            header('LOCATION: client.php');
        else
            header('LOCATION: staff.php');
    }

    $search = 1;
    if( isset($_GET['search']) && $$_GET['search'] == false){
        $search = 0;
    }

    //Log in module
    if( isset($_POST['login']) ){
        $u = $_POST['studentid'];
        $p = md5($_POST['password']);

        //get the user based on username and password
        $query = "SELECT IdNumber, FName, LName, ContactNo, UserType, LocId, LocName, Department, changePass, picture FROM users u LEFT JOIN department d ON d.LocId = u.department WHERE IdNumber = '$u' AND Password = '$p' AND u.Status = 'Active' LIMIT 1 ";
       
        $result = $db->query($query, $connection);
        if( $result->num_rows ){

            $row = $result->fetch_assoc();
            
            //Store the result of the database query to session
            $_SESSION = $row;

            if( $_SESSION['UserType'] == 2 )
                header('LOCATION: client.php');
            else
                header('LOCATION: staff.php');
        }
        else{
            echo "<script>alert('Invalid Username / Password!');</script>";
        }
    }

    //view document module
    if( isset($_POST['viewDocument']) ){
        $trackingno = $_POST['trackingno'];

        $query = "SELECT DocumentTrackingNo, DocumentName, DocumentTypeName, u1.ContactNo,PriorityLevel, SubmittedBy, ReceivedBy, DocumentDetails, Remarks, DateSubmitted, DocumentType, Location, de.LocName, d.Status, CONCAT(u2.IdNumber, ' - ', u2.FName, ' ', u2.LName) AS ManagedBy FROM documentheaderfile d LEFT JOIN department de ON d.Location = de.LocId LEFT JOIN users u1 ON SubmittedBy = u1.IdNumber LEFT JOIN users u2 ON u2.IdNumber = d.ReceivedBy LEFT JOIN document_type dt ON dt.DocumentTypeID = d.DocumentType WHERE DocumentTrackingNo = $trackingno";

        $q = $db->query($query, $connection);

        if( $q && $q->num_rows ){
            $docs = $db->get_assoc_for_data_source($q);

            $docs = $docs[0];
        }
        else{
            header('LOCATION: index.php?search=false');
        }

        $query = "SELECT DocumentLogsId, LoggedTextFormat, DocumentTrackingNo, PriorityLevel, DocumentType, DocumentName, DocumentDetails, Remarks, SubmittedBy, DateSubmitted, dl.Status, Location , CONCAT(u1.FName, ' ', u1.LName) AS Logged, LogUpdated, LogDescription, LocName AS loggedLocation, CONCAT(u2.IdNumber, ' - ', u2.FName, ' ', u2.LName) AS ReceivedBy FROM documentdetailfile dl LEFT JOIN users u1 ON u1.IdNumber = LoggedBy LEFT JOIN users u2 ON u2.IdNumber = dl.ReceivedBy LEFT JOIN department de ON de.LocId = dl.Location WHERE DocumentTrackingNo = ".$_POST['trackingno']." ORDER BY LogUpdated DESC";

        $q = $db->query($query, $connection);

        $document_logs = $db->get_assoc_for_data_source($q);

        require_once('config/config_items.php');
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

        //insert into searchlogs table
        $ip = $_SERVER['REMOTE_ADDR'];

        $query = "INSERT INTO searchlogs (searchlogIP, searchlogDate, searchlogTrackingNo, searchLogIdNumber) VALUES ('$ip', NOW(), $trackingno, '".$docs->SubmittedBy."') ";

        $q = $db->query($query, $connection);
    }

    //Registration Module
    if( isset( $_POST['create_account'] ) ){
        $studentid = $_POST['studentid'];
        $firstname = $_POST['firstname'];
        $lastname = $_POST['lastname'];
        $password = $_POST['password'];
        $cpassword = $_POST['cpassword'];
        $contactnumber = $_POST['contactnumber'];
        $picture = 'assets/img/profile/user.png';

        //check student ID length
        if( strlen($studentid) < 4 || strlen($studentid) > 10 ){
            echo "<script>alert('ID Number must be 4 - 10 digits!');</script>";
        }
        //check password length
        elseif( strlen($password) < 8 || strlen($password) > 15 ){
            echo "<script>alert('Password lenght must be 8 - 15 characters long!');</script>";
        }
        //check phone number length
        elseif( strlen($contactnumber) < 11 || strlen($contactnumber) > 11){
            echo "<script>alert('Mobile Number must be 11 digits long');</script>";   
        }
        else{
            //check wether the id number already exist
            $checkStudent = "SELECT IdNumber FROM users WHERE IdNumber = '$studentid' ";
            
            $cs = $db->query($checkStudent, $connection);

            if( $db->count_row($cs) == 0 ){
                //check if password and confirm password matches
                if( $password == $cpassword ){
                    //insert to the database (MD5 encrypts the password)
                    $query = "INSERT INTO users VALUES ('$studentid', '$firstname', '$lastname', '$contactnumber', MD5('$password'), 1,2, 0, 'Active', '$picture' )";
                    
                    $q = $db->query( $query, $connection );

                    //Alert that the user is created
                    echo "<script>alert('Account: $studentid Created');</script>";
                

                }
                else{
                    echo "<script>alert('Password does not match!');</script>";
                }
            }else{
                echo "<script>alert('Account Already Exists!');</script>";
            }
        }
    }
    
    //get departments
    $query = "SELECT department_id, LocId, LocName FROM department";
    $q = $db->query($query, $connection);

    // $departments = $db->get_assoc_for_data_source($q);

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
      <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Where's My Docx?-Login</title>

    <!-- BOOTSTRAP STYLES-->
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <!-- FONTAWESOME STYLES-->
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <!-- GOOGLE FONTS-->
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
     <link href="assets/css/jquery.ui.css" rel="stylesheet" />
    <style type="text/css">
    .form-control{
        width: 100% !important;
    }
    .row{
        margin:0px !important;
    }
    </style>

</head>
<body style="background-color: #E2E2E2;">
    <div class="container">
        <div class="trow text-center " style="padding-top:100px;">
            <div class="col-md-12">
                <img src="assets/img/logo.png" />
            </div>
        </div>
         <div class="trow ">
                <!-- <div class="col-md-4 col-md-offset-4 col-sm-6 col-sm-offset-3 col-xs-10 col-xs-offset-1"> -->
                <div class='col-md-4 col-sm-4 col-xs-4 col-md-offset-1'>
                    <div class="panel-body">
                        <form role="form" method='POST' action='./index.php' class='search-form'>
                            <hr />
                            <h5>Search for your File</h5>
                               <br />
                             <div class="form-group input-group">
                                <span class="input-group-addon"><i class="fa fa-tag"  ></i></span>
                                <input type="text" name='trackingno' class="form-control" placeholder="Please Enter Tracking # " />
                            </div>
                            
                             
                            <center><input type='submit' value='Go!' class='btn btn-primary' name='viewDocument'></center>
                            <hr/>
                            </form>
                        </div>
                </div>
                <div class='col-md-4 col-sm-4 col-xs-4 col-md-offset-2'>
                    <div class="panel-body">
                        <form role="form" method="POST" class='login-form'>
                            <hr />
                            <h5>Enter Details to Login</h5>
                               <br />
                             <div class="form-group input-group">
                                    <span class="input-group-addon"><i class="fa fa-tag"  ></i></span>
                                    <input type="text" class="form-control logstudentid" name='studentid' value='<?php echo ( isset($_POST['studentid']) )? $_POST['studentid'] : ""; ?>' placeholder="ID No." data-placement='right' required data-content='Invalid ID Number!' data-container='body' />
                                </div>
                                                                      <div class="form-group input-group">
                                    <span class="input-group-addon"><i class="fa fa-lock"  ></i></span>
                                    <input type="password" class="form-control" name='password'  placeholder="Your Password" />
                                </div>
                            
                             
                             <center><input type='submit' class='btn btn-primary' name='login' value="Login Now" /> <input type='button' class='btn btn-success register' data-toggle='modal' data-target='#registermodal' value="Register Here" /> </center>

                            <hr/>
                        </form>
                    </div>
                           
            </div>

            <?php 
                if( isset($docs) ){
            ?>
            <div class='doc-dialog'>
                <div class="row">
                    <div class="col-md-12">
                        <div class="jumbotron">
                            <div class="tibs">
                                <table class='table'>
                                    <!-- <tr>
                                        <td colspan="4"><?=$error;?></td>
                                    </tr> -->
                                    <tr>
                                        <td> <strong> Tracking No: </strong></td>
                                        <td><?=$docs->DocumentTrackingNo?>
                                        </td>
                                    </tr>
                                        <tr>
                                            <td> <strong> Document Name: </strong></td>
                                            <!-- <td><input type="text" required name="DocumentName" class='form-control' placeholder="Document Name" value='<?=$docs->DocumentName?>' /></td> -->
                                            <td><?=$docs->DocumentName?></td>
                                            
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
                                        <tr>
                                            <td><strong>Location</strong></td>
                                            <td colspan="3">
                                                <?php
                                                    foreach ($departments as $key => $value) {
                                                        if( $value->LocId == $docs->Location)
                                                            echo $value->LocName;
                                                    }
                                                ?>
                                                <!-- <select name='Location'>
                                                    <?php
                                                        foreach ($departments as $key => $value) {
                                                            $selected = '';
                                                            if( $value->LocId == $docs->Location)
                                                                $selected = 'selected';

                                                            echo '<option '.$selected.' value="'.$value->LocId.'" >'.$value->LocName.'</option>';
                                                        }
                                                    ?>
                                                </select> -->
                                            </td>
                                        </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
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
                                    <th>Location From</th>
                                    <th>Location To</th>
                                    <th>Status</th>
                                    <th>Received By</th>
                                    <th>Managed By</th>
                                    <th>Claimed By</th>
                                    <th>Date Updated</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                    $logsArr = array();
                                    foreach ($document_logs as $key => $value) {
                                        //if( $value->LogDescription != 'Document Added'){
                                            $logs = $value->LoggedTextFormat;
                                            $eLogs = explode('|||', $logs);

                                            $eRemarks = explode( '---', $eLogs[6] );
                                            $eLoc = explode( '---', $eLogs[10] );
                                            $eStatus = explode( '---', $eLogs[8] );
                                            $eClaimed = explode( '---', $eLogs[9] );

                                            //if( $value->Location == $_SESSION['Department'] || $_SESSION['UserType'] == 0 || $eLoc[3] == $_SESSION['Department'] ){
                                            $logsArr[$key] = $value;
                                            echo '<tr  style="background: #ffe0c6">';
                                            echo '<td>'.$eRemarks[3].'</td>';

                                            if( $value->LogDescription != 'Document Added'){
                                                echo '<td>'.$departmentArr[$eLoc[1]].'</td>';
                                                echo '<td>'.$departmentArr[$eLoc[3]].'</td>';
                                                echo '<td>';
                                                echo $document_status[$eStatus[3]];
                                                echo '</td>';
                                            }
                                            else{
                                                echo '<td></td>';
                                                echo '<td>'.$departmentArr[$value->Location].'</td>';
                                                echo '<td>Processing</td>';
                                            }
                                            
                                            echo '<td>'.$docs->ReceivedBy.'</td>';
                                            echo '<td>'.$value->Logged.'</td>';
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
            </div>
            <?php } ?>

            <!-- Register fields -->
            <div id='registermodal' class='modal fade' tabindex="-1" role="dialog" aria-labelledby='modallabel'>
                <div class='modal-dialog' role='document'>
                    <div class='modal-content'>
                        <form method="post" class="signin" action="#">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                <h4 class='modal-title' id='modallabel'>Registration</h4>
                            </div>
                            <div class="modal-body">
                                <div class='form-group'>
                                    <label for='student_id'>Student ID: </label>
                                    <input type='text' name='studentid' id='student_id' placeholder='Student ID' class='form-control' data-container='body' data-placement='right' data-content="ID must be 8 characters long" />
                                </div>

                                <div class='form-group'>
                                    <label for='firstname'>Firstname: </label>
                                    <input type='text' name='firstname' id='firstname' required placeholder='Firstname' class='form-control' />
                                </div>

                                <div class='form-group'>
                                    <label for='lastname'>Lastname: </label>
                                    <input type='text' name='lastname' id='lastname' required placeholder='Lastname' class='form-control' />
                                </div>

                                <div class='form-group'>
                                    <label for='password'>Password: </label>
                                    <input type='password' name='password' id='password' placeholder='Password' class='form-control' data-content='Password lenght must be 8 - 15 characters long!' data-container='body' data-placement='right' />
                                </div>

                                <div class='form-group'>
                                    <label for='cpassword'>Confirm Password: </label>
                                    <input type='password' name='cpassword' id='cpassword' placeholder='Confirm Password' class='form-control' data-container='body' data-placement='right'  data-content='Password did not match!' />
                                </div>

                                <div class='form-group'>
                                    <label for='contactnumber'>Contact Number: </label>
                                    <input type='text' required name='contactnumber' id='contactnumber' placeholder='Contact Number' class='form-control' data-container='body'  data-content=' Mobile Number must be 11 digits!' data-placement='right' />
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                <button type="submit" name='create_account' class="btn btn-primary">Register</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
                
                
        </div>
    </div>

</body>
<script type="text/javascript" src='assets/js/jquery-1.10.2.js'></script>
   <!-- JQUERY UI SCRIPTS -->
    <script src="assets/js/jquery.ui.js"></script>
<script type="text/javascript" src='assets/js/bootstrap.js'></script>
<script src="assets/js/jquery.ui.js"></script>

<script type="text/javascript">
    $(document).ready(function(){
        var id_number = '';

        if( !<?=$search?> ){
            alert('Document Does not Exist!');
        }

        //Check length of ID number if less than 8 and if numeric
        $('#student_id').on('keyup',function(){
            id_number = $(this).val();
            //check if the input is a number using $.isNumeric()
            if( !$.isNumeric(id_number) ){
                //remove the last inputted value which is the non numeric char
                id_number = id_number.slice(0, -1);
                //update the text box
                $('#student_id').val(id_number);
            }
        });

        $('.doc-dialog').dialog({
            title: 'View Document',
            height: 800,
            width: 900,
            modal: true
        });

        $('.table-logs tr a').click(function(e){
            e.preventDefault();
            var logid = $(this).attr('data-logid');

            $('#logsid-'+logid).toggleClass('hidden');

        });

        //check for errors before submitting
        $('#registermodal form').on('submit', function(){
            //call function reset field
            resetfields();
            var haserror = false;
            //check for correct ID lenght
            var id = $('#student_id').val();
            var password = $('#password').val();
            var cpassword = $('#cpassword').val();
            var contactnumber = $('#contactnumber').val();

            if( id.length < 4 || id.length > 10 ){
                haserror = true;
                $('#student_id').parent().addClass('has-error');
                $('#student_id').popover('show');
            }

            if( password.length < 8 || password.length > 15 ){
                haserror = true;
                $('#password').parent().addClass('has-error');
                $('#password').popover('show');
            }

            if( password != cpassword ){
                haserror = true;
                $('#cpassword').parent().addClass('has-error');
                $('#cpassword').popover('show');   
            }

            if( contactnumber.length < 11 || contactnumber.length > 11 ){
                haserror = true;
                $('#contactnumber').parent().addClass('has-error');
                $('#contactnumber').popover('show');
            }

            if( haserror ){
                $('#registermodal').effect('shake');
            }

            console.log( haserror );
            return !haserror;
        });

        $('.login-form').on('submit', function(e){
            var idnum = $('.logstudentid').val();

            var hasError = false;
            if( !$.isNumeric(idnum) ){
                hasError = true;
                $('.logstudentid').popover('show');
            }

            //return false;
            return !hasError;
        });

        $('.search-form').on('submit',function(){
            var trackingno = $('input[name="trackingno"]').val();

            if( trackingno != '' )
                return true;
            else{
                alert('Please Enter Tracking #');
                return false;
            }
        });

        //on modal close, reset popovers
        $('#registermodal').on('hide.bs.modal',function(){
            $('#cpassword, #contactnumber, #student_id, #password').popover('hide');
        })

        function resetfields(){
            //reset fields to not default state. this is for checking the error again and avoid being red
            //even if the field is already correct.
            $('#cpassword, #contactnumber, #student_id, #password').parent().removeClass('has-error');
        }
    }); 
</script>>

</html>
