<?php
    # глагне
    require 'config.php';

    if (isset($_COOKIE[User::COOKIE_ID]) and isset($_COOKIE[User::COOKIE_TOKEN])) {
        $user = new User(new simpleMySQLi($db, realpath(__DIR__)));

        if ($user->auth($_COOKIE[User::COOKIE_ID], $_COOKIE[User::COOKIE_TOKEN])) {
            header('Location: data.php');
            exit;
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>report</title>
    <link rel="stylesheet" href="bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="bootstrap/jquery.ui.min.css">
    <link rel="stylesheet" href="bootstrap/air.datepicker.min.css">
    <style>
        .run {
            display:    none;
            position:   fixed;
            z-index:    1000;
            top:        0;
            left:       0;
            height:     100%;
            width:      100%;
            background: rgba(255, 255, 255, .8) url('/bootstrap/run.gif') 50% 50% no-repeat;
        }
        body.loading { overflow: hidden; }
        body.loading .run { display: block; }
    </style>
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>
<body>
<div class="container">

    <div class="card border-info mt-lg-5">
        <div class="card-header">
            <h3>report</h3>
        </div>
        <div class="card-body">

            <div class="form-group row mt-2 mb-2">
                <label for="login" class="col-form-label col-1">login</label>
                <div class="col-3">
                    <input type="text" class="form-control" id="login">
                </div>
                <label for="passw" class="col-form-label col-1">passw</label>
                <div class="col-3">
                    <input type="password" class="form-control" id="passw">
                </div>
                <div class="col-3">
                    <a class="btn btn-block btn-outline-primary" href="javascript:void(login())">login</a>
                </div>
            </div>

        </div>
    </div>

</div>
<div class="modal run"></div>
<script src="bootstrap/jquery.min.js"></script>
<script src="bootstrap/jquery.ui.min.js"></script>
<script src="bootstrap/popper.min.js"></script>
<script src="bootstrap/bootstrap.min.js"></script>
<script src="bootstrap/moment.min.js"></script>
<script src="bootstrap/air.datepicker.min.js"></script>
<script src="report.js?<?php echo fileatime('report.js'); ?>"></script>
<script src="login.js?<?php echo fileatime('login.js'); ?>"></script>
</body>
</html>