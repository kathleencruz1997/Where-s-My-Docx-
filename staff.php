<?php
    session_start();
    //Check if there is a user logged in, if there is not. redirect to the main page
    if( !isset($_SESSION['IdNumber']) && !isset($_POST['submitNewPass']) ){
        header('LOCATION: index.php');
    }
    if( $_SESSION['UserType'] == 2 ){
        header('LOCATION: client.php');   
    }

    require_once('config/Db_connect.php');

    $IdNumber = isset($_SESSION['IdNumber']) ? $_SESSION['IdNumber'] :'';

    if( isset($_POST['submitNewPass']) ){
        $IdNumber = $_POST['IdNumber'];
        $currPassword = MD5( $_POST['currPassword'] );
        $newPassword = $_POST['newPassword'];
        $cnewPassword = $_POST['cnewPassword'];

        //check if the current password is correct
        $query = "SELECT IdNumber FROM users WHERE IdNumber = $IdNumber AND Password = '$currPassword' ";
        
        $q = $db->query($query, $connection);

        if($q->num_rows){
            $query = "UPDATE users SET Password = MD5('$newPassword'), changePass = 0 WHERE IdNumber = $IdNumber ";
            $q = $db->query($query, $connection);

            echo "<script>alert('Password Changed Successfully! Please Log In Again'); location.href='logout.php'; </script>";
        }
        else{
            echo "<script>alert('Wrong Password!')</script>";
        }
    }

    $query = "SELECT notificationID, notificationDescription, trackingno FROM notification WHERE userID = ".$_SESSION['IdNumber']." AND notificationAcknowledged IS NULL";
        
    $q = $db->query($query, $connection);

    $numNotif = $q->num_rows;
    $notif = $db->get_assoc_for_data_source($q);

    $notifList = '';
    if( $numNotif > 0 ){
        foreach ($notif as $key => $value) {
            $notifList .= "<li><a href='staff/monitordocument.php?trackingno=".$value->trackingno."&ref=notif&id=".$value->notificationID."'>".$value->notificationDescription."</a></li>";
        }
    }

    if( $_SESSION['changePass'] == 1 && !isset($_POST['submitNewPass']) ){
        unset( $_SESSION['IdNumber'] );
    }



?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Where's My Docx?</title>

    <!-- BOOTSTRAP STYLES-->
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <!-- FONTAWESOME STYLES-->
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <!--CUSTOM BASIC STYLES-->
    <link href="assets/css/basic.css" rel="stylesheet" />
	<link href="assets/css/table.css" rel="stylesheet" />
		<link href="assets/css/janice.css" rel="stylesheet" />

    <!--CUSTOM MAIN STYLES-->
    <link href="assets/css/custom.css" rel="stylesheet" />
    <!-- GOOGLE FONTS-->
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
	<link href='http://fontawesome.io/icons/' rel='stylesheet' type='text/css' />
    <link href="assets/css/jquery.ui.css" rel="stylesheet" />
	<style type="text/css">
    .row{
        margin: 10px 0px 0px 0px;
    }
    .form-control{
        width: 100%;
        margin: 0px !important;
    }
    .ui-dialog-titlebar-close{
        display: none;
    }
    .nav .inner-text a{
        text-decoration: none !important;
        color: white !important;
        font-weight: bold !important;
    }
    img{
        width: 100px;
    }
    </style>
	
</head>
<body>
    <div id="wrapper">
        <nav class="navbar navbar-default navbar-cls-top " role="navigation" style="margin-bottom: 0">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="sidebar-collapse">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="staff.php"><img src="assets/img/logo.png" class="go"/></img></a>
            </div>

            <div class="header-right">
                <?php if( $_SESSION['UserType'] != 0 ){ ?>
                <div class="btn-group">
                    <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <b><?=$numNotif?> </b><i class="fa fa-bars fa-2x"></i></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-right">
                          <?=$notifList;?>
                    </ul>
                </div>
                <?php }?>
                <a href="logout.php" class="btn btn-danger" title="Logout"><i class="fa fa-exclamation-circle fa-2x"></i></a>

            </div>
        </nav>
        <!-- /. NAV TOP  -->
        <nav class="navbar-default navbar-side" role="navigation">
            <div class="sidebar-collapse">
                <ul class="nav" id="main-menu">
                    <li>
                        <div class="user-img-div">
                            <div class="inner-text">
                             <table>
                                <tr><td rowspan="3"><img class='img-responsive' src='<?=$_SESSION["picture"]?>'></td><td><a href='staff/viewclient.php?staffID=<?=$IdNumber?>'><?=ucfirst($_SESSION['FName']).' '.ucfirst($_SESSION['LName']);?></a></td></tr>
                                <tr><td><?=$_SESSION['LocName']?></small></td></tr>
                                <tr><td>
                                    <small><?php
                                    if( $_SESSION['UserType'] == 0){
                                        echo "System Admin";
                                    }
                                    elseif( $_SESSION['UserType'] == 1){
                                        echo "Staff";
                                    }
                                    elseif( $_SESSION['UserType'] == 2){
                                        echo "Client";
                                    }
                                ?></small>
                                </td></tr>
                            </table>
                            <!-- Display the name and department using session -->
                                <!-- <a href='staff/viewclient.php?staffID=<?=$IdNumber?>'><?=ucfirst($_SESSION['FName']).' '.ucfirst($_SESSION['LName']);?></a>
                            <br />
                                <small><?=$_SESSION['LocName']?></small><br/>
                                <small><?php
                                    if( $_SESSION['UserType'] == 0){
                                        echo "System Admin";
                                    }
                                    elseif( $_SESSION['UserType'] == 1){
                                        echo "Staff";
                                    }
                                    elseif( $_SESSION['UserType'] == 2){
                                        echo "Client";
                                    }
                                ?></small>
                             </div> -->
                        </div>

                    </li>
                    
                    <li>
                        <a href="#"><i class="fa fa-desktop "></i>Manage Users <span class="fa arrow"></span></a>
                            <ul class="nav nav-second-level">
                            <li>
                                <a href="staff/addclient.php"><i class="fa fa-user"></i>Add Client</a>
                            </li>
                            <li>
                                <a href="staff/searchclient.php"><i class="fa fa-search"></i>Search Client</a>
                            </li>
                             <li>
                                <a href="staff/updateclient.php"><i class="fa fa-pencil-square-o"></i>Update Client</a>
                            </li>
                        </ul>
                    </li>
                    <?php if( $_SESSION['UserType'] != 0 ){?>
                     <li>
                        <a href="staff/adddocument.php"><i class="fa fa-file-text-o"></i>Add Document</a>
                    </li>
                    <?php }?>
					<li>
                        <a href="#"><i class="fa fa-cogs"></i>Managed Documents<span class="fa arrow"></span></a>
                         <ul class="nav nav-second-level">
                            <li>
                                <a href="staff/viewdocument.php"><i class="fa fa-eye"></i>View</a>
                            </li>
                            <?php if( $_SESSION['UserType'] != 0 ){?>
                                <li>
                                    <a href="staff/updatedocument.php"><i class="fa fa-pencil-square-o"></i>Update</a>
                                </li>
                             <?php }?>
                             <li>
                                <a href="staff/monitordocument.php"><i class="fa fa-desktop"></i>Monitor</a>
                            </li>
                            
							 <!-- <li>
                                <a href="staff/form.php"><i class="fa fa-desktop"></i>Add Location</a>
                            </li> -->
                        </ul>
                    </li>
                    <?php if( $_SESSION['UserType'] == 0 ){?>
                        <li >
                            <a href="#" id='documenttype'><i class="fa fa-file-text-o"></i>Document Type <span class="fa arrow"></a>
                             <ul class="nav nav-second-level">
                                <li class='adddocumenttype' data-parentmenu='documenttype'>
                                    <a href="staff/adddocumenttype.php"><i class="fa fa-plus-square"></i>Add</a>
                                </li>
                                <li>
                                    <a href="staff/viewdocumenttype.php"><i class="fa fa-eye"></i>View</a>
                                </li>
                                <li>
                                    <a href="staff/updatedocumenttype.php"><i class="fa fa-level-up"></i>Update</a>
                                </li>
                            </ul>
                        </li>
                        <li>
                            <a href="#" id='documenttype'><i class="fa fa-bank"></i>Department <span class="fa arrow"></a>
                             <ul class="nav nav-second-level">
                                <li class='adddocumenttype' data-parentmenu='documenttype'>
                                    <a href="staff/adddepartment.php"><i class="fa fa-plus-square"></i>Add</a>
                                </li>
                                <li>
                                    <a href="staff/viewdepartment.php"><i class="fa fa-eye"></i>View</a>
                                </li>
                                <li>
                                    <a href="staff/updatedepartment.php"><i class="fa fa-level-up"></i>Update</a>
                                </li>
                            </ul>
                        </li>
                    <?php }?>
                </ul>

            </div>

        </nav>
        <!-- /. NAV SIDE  -->
        <div id="page-wrapper">
            <div id="page-inner">
                <div class="row">
                   
                </div>
                <br/>               
						<img src="assets/img/home1.png" class="gora"/></img>
				
                <!--/.ROW-->

            </div>
            <!-- /. PAGE INNER  -->
        </div>
        <!-- /. PAGE WRAPPER  -->
    </div>
    <!-- /. WRAPPER  -->
    <?php if($_SESSION['changePass']) { ?>
    <div class='changePass-dialog' title="View Details" data-changepass='<?=$_SESSION["changePass"]?>' >
        <form method="POST">
            <input type='hidden' value='<?=$IdNumber?>' name='IdNumber'/ >
            <div class='row'>
                <label class='col-sm-5'>Current Password: </label>
                <div class='col-sm-7'>
                    <input type="password" required id='currPassword' name='currPassword' class="form-control" data-content='Password lenght must be 8 - 15 characters long!' data-container='body' data-placement='right'/>
                </div>
            </div>
            <div class='row'>
                <label class='col-sm-5'>New Password: </label>
                <div class='col-sm-7'>
                    <input type="password" required id='newPassword' name='newPassword' class="form-control" data-content='Password lenght must be 8 - 15 characters long!' data-container='body' data-placement='right' />
                </div>
            </div>
            <div class='row'>
                <label class='col-sm-5'>Confirm New Password: </label>
                <div class='col-sm-7'>
                    <input type="password" required id='cnewPassword' name='cnewPassword' class="form-control" data-content='Password does not match!!' data-container='body' data-placement='right' />
                </div>
            </div>
            <div class='row'>
                <div class='col-sm-7'>
                    <input type="submit" name='submitNewPass' value='Change Password'  class="btn btn-sm btn-primary" />
                </div>
            </div>
        </form>
    </div>
    <?php } ?>

    <div id="footer-sec">
        &copy; 2017 Where's my Docx? | All Rights Reserved
    </div>
    <!-- /. FOOTER  -->
    <!-- SCRIPTS -AT THE BOTOM TO REDUCE THE LOAD TIME-->
    <!-- JQUERY SCRIPTS -->
    <script src="assets/js/jquery-1.10.2.js"></script>
    <!-- JQUERY UI SCRIPTS -->
    <script src="assets/js/jquery.ui.js"></script>
    <!-- BOOTSTRAP SCRIPTS -->
    <script src="assets/js/bootstrap.js"></script>
    <!-- METISMENU SCRIPTS -->
    <script src="assets/js/jquery.metisMenu.js"></script>
       <!-- CUSTOM SCRIPTS -->
    <script src="assets/js/custom.js"></script>
    
	<script src="assets/js/sparkline-chart.js"></script>    
	<script src="assets/js/zabuto_calendar.js"></script>	

	
    <script type="text/javascript">
 //        $(document).ready(function () {
 //        var unique_id = $.gritter.add({
 //            // (string | mandatory) the heading of the notification
 //            title: 'Welcome to Wheres My DocX!',
 //            // (string | mandatory) the text inside the notification
 //            text: 'Hover me to enable the Close Button. You can hide the left sidebar clicking on the button next to the logo. Free version for <a href="http://blacktie.co" target="_blank" style="color:#ffd777">BlackTie.co</a>.',
 //            // (string | optional) the image to display on the left
 //            image: 'assets/img/ui-sam.jpg',
 //            // (bool | optional) if you want it to fade out on its own or just sit there
 //            sticky: true,
 //            // (int | optional) the time you want it to be alive for before fading out
 //            time: '',
 //            // (string | optional) the class name you want to apply to that specific message
 //            class_name: 'my-sticky-class'
 //        });

 //        return false;
 //        });
	// </script>
	<script type="application/javascript">
        $(document).ready(function () {
            $('.changePass-dialog').dialog({
                height: 300,
                width: 700,
                autoOpen: true,
                modal: true,
                title: 'Change Password Required'
            });

            $('form').on('submit',function(){
                var haserror = false;
                var currPassword = $('#currPassword').val();
                var newPassword = $('#newPassword').val();
                var cnewPassword = $('#cnewPassword').val();

                if( currPassword.length < 8 || currPassword.length > 15 ){
                    haserror = true;
                    $('#currPassword').parent().addClass('has-error');
                    $('#currPassword').popover('show');
                }

                if( newPassword.length < 8 || newPassword.length > 15 ){
                    haserror = true;
                    $('#newPassword').parent().addClass('has-error');
                    $('#newPassword').popover('show');
                }

                if( newPassword != cnewPassword ){
                    haserror = true;
                    $('#cnewPassword').parent().addClass('has-error');
                    $('#cnewPassword').popover('show');   
                }

                return !haserror;
            });

            $('.changePass-dialog').dialog('open');

            $("#date-popover").popover({html: true, trigger: "manual"});
            $("#date-popover").hide();
            $("#date-popover").click(function (e) {
                $(this).hide();
            });
        
            $("#my-calendar").zabuto_calendar({
                action: function () {
                    return myDateFunction(this.id, false);
                },
                action_nav: function () {
                    return myNavFunction(this.id);
                },
                ajax: {
                    url: "show_data.php?action=1",
                    modal: true
                },
                legend: [
                    {type: "text", label: "Special event", badge: "00"},
                    {type: "block", label: "Regular event", }
                ]
            });
        });
        
        
        function myNavFunction(id) {
            $("#date-popover").hide();
            var nav = $("#" + id).data("navigation");
            var to = $("#" + id).data("to");
            console.log('nav ' + nav + ' to: ' + to.month + '/' + to.year);
        }
    </script>
  

</body>
</html>
