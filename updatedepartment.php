<?php
    session_start();
    require_once('../config/Db_connect.php');
    if( !isset($_SESSION) ){
        header('LOCATION: ../index.php');
    }

    //Create department
    if( isset( $_GET['depid'] ) ){
        $depid = $_GET['depid'];
        
        $query = "SELECT *FROM department WHERE LocId = $depid LIMIT 1";

        $q = $db->query($query, $connection);
        if( $q->num_rows ){
            $department = $db->get_assoc_for_data_source($q);
            
            $department = $department[0];
        }
        else{
            header('LOCATION: viewdepartment.php?found=false');
        }
    }

    if( isset($_POST['update_department']) ){
        $depid = $_GET['depid'];
        $locname = $_POST['depname'];
        $locbldg = $_POST['depbldg'];
        $locfloor = $_POST['depfloor'];
        $locroomno = $_POST['deproomno'];
        $locstatus = $_POST['depstatus'];

        $query = "UPDATE department SET LocName = '$locname', LocFloor = '$locfloor', LocBuilding = '$locbldg', LocRoomNo = '$locroomno', Status = '$locstatus' WHERE LocId = '$depid' ";

        $q = $db->query($query, $connection);
        echo "<script>alert('Department $locname Updated!');</script>";
        header('refresh: 0');
    }
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Where's My Docx?-Add Client</title>

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
    <link href="../assets/css/jquery.ui.css" rel="stylesheet" />
    <!-- GOOGLE FONTS-->
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
    <style type="text/css">
        .docDescription{
            height: 100px !important;
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
        <?php require_once('../includes/navbar.php'); ?>
        <div id="page-wrapper">
            <div id="page-inner">
                <div class="row">
                    <div class="col-md-12">
                        <h1 class="page-head-line">Add Document Type</h1>
                    </div>
                </div>
            <div class="ttrow">
                <div class="col-md-12">
                    <div class="jumbotron">
                    
                <div class="tibs">
                <form method="POST" action="">
                    <table>
                        <tr>
                            <td ><h4 style="color:black">Department ID:</h4>
                            <td >
                                <?php
                                    $disabled = '';
                                    $idD = '';
                                    if ( !isset($_GET['depid']) ){
                                        $disabled = 'disabled';
                                    }
                                    else{
                                        $idD = 'disabled';
                                    }
                                ?>
                                <input type="text" <?=$idD?> name='depid' value='<?=$department->LocId?>'>
                            </td>
                            <td style="float:right"><input type="button" class="btn btn-primary btn-lg search" role="button" value="Search"></td>
                        <tr/>
                        <tr>
                            <td ><h4 style="color:black">Department Name:</h4>
                            <td ><input type="text" <?=$disabled;?> required name='depname' value='<?=$department->LocName?>'></td>
                            <td style="float:right">
                        <tr/>
                        <tr>
                            <td ><h4 style="color:black">Building:</h4>
                            <td ><input type="text" <?=$disabled;?> required name='depbldg' value='<?=$department->LocBuilding?>'></td></td>
                            <td style="float:right">
                        <tr/>
                        <tr>
                            <td ><h4 style="color:black">Floor No.:</h4>
                            <td ><input type="text" <?=$disabled;?> required name='depfloor' value='<?=$department->LocFloor?>'></td></td>
                            <td style="float:right">
                        <tr/>
                        <tr>
                            <td ><h4 style="color:black">Room No.:</h4>
                            <td ><input type="text" <?=$disabled;?> required name='deproomno' value='<?=$department->LocRoomNo?>'></td></td>
                            <td style="float:right">
                        <tr/>
                        <tr>
                            <td ><h4 style="color:black">Status:</h4>
                            <td >
                                <select <?=$disabled;?> name='depstatus'>
                                    <?php
                                        $active = ( $department->Status  == 1 )? 'selected' : '';
                                        $inactive = ( $department->Status  == 0 )? 'selected' : '';
                                    ?>
                                    <option value='1' <?=$active?> > Active</option>
                                    <option value='0' <?=$inactive?> > Inactive</option>

                                </select>
                            </td>
                            <td style="float:right">
                        <tr/>
                        <tr>
                            <td>
                            <input type="submit" <?=$disabled;?> class="btn btn-primary" name='update_department' role="button" value="Update">
                            <a href='./updatedepartment.php'><input class="btn btn-primary" value="Clear" role="button"></a>
                            </td>
                        </tr>
                    </table>
                </form>
                </div>
                    </div>
                </div>
            </div>
                    <!-- /. ROW  -->
            </div>
            <!-- /. PAGE INNER  -->
        </div>
        <!-- /. PAGE WRAPPER  -->
    </disv>
    <!-- /. WRAPPER  -->
    <div id="footer-sec">
        &copy; 2017 Where's my Docx?| All Rights Reserved
    </div>
    <!-- /. FOOTER  -->
    <!-- SCRIPTS -AT THE BOTOM TO REDUCE THE LOAD TIME-->
    <!-- JQUERY SCRIPTS -->
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
        var active = 'updatedepartment';
    </script>

    <script src="../assets/js/nav.js"></script>
    <script type="text/javascript">
        $(document).ready(function(){
            $('.search').click(function(){
                var id = $('input[name="depid"]').val();

                if ( id != 0 && id.toString().length > 0 ){
                    window.location = 'updatedepartment.php?depid='+id;
                }
            })
        });
    </script>>

</body>
</html>