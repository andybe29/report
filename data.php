<?php
    require 'config.php';

    if (isset($_COOKIE[User::COOKIE_ID]) and isset($_COOKIE[User::COOKIE_TOKEN])) {
        $sql  = new simpleMySQLi($db, realpath(__DIR__));
        $user = new User($sql);

        if ($user->auth($_COOKIE[User::COOKIE_ID], $_COOKIE[User::COOKIE_TOKEN])) {
            # ok
        } else {
            header('Location: login.php');
            exit;
        }
    } else {
        header('Location: login.php');
        exit;
    }

    $data  = new Data($sql);
    $place = new Place($sql);
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
            background: rgba(255, 255, 255, .8) url('bootstrap/run.gif') 50% 50% no-repeat;
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
<input type="hidden" rel="settings" data-key="ADMINISTRATOR" data-value="<?php echo User::ADMINISTRATOR; ?>">
<input type="hidden" rel="settings" data-key="MANAGER"       data-value="<?php echo User::MANAGER; ?>">
<input type="hidden" rel="settings" data-key="type" data-value="<?php echo $user->type; ?>">

<nav class="navbar navbar-light bg-light sticky-top">
    <div class="container">
        <span class="navbar-brand">report</span>
        <span class="navbar-text"><?php echo $user->name; ?> (<?php echo $user->type; ?>)</span>
<?php
    if (User::MANAGER == $user->type) {
        $monYears = $data->monYears($user->id);
        $places   = $place->lista($user->id);
?>
        <a class="nav-link" href="javascript:void(popupData(0))">добавить запись в отчёт</a>
<?php
    }

    $monthes = Data::_monthes();
?>
        <a class="nav-link" href="javascript:void(logout())">выход</a>
    </div>
</nav>

<div class="container">

<?php
    if (User::MANAGER == $user->type) {
?>
    <div class="card mb-2 mt-2" id="data-period">
        <div class="card-body">

            <div class="form-group row mb-2">
                <label for="data-period-month-year" class="col-form-label col-form-label-sm col-1">период:</label>

                <div class="col-3">
            <?php
                if ($monYears) {
            ?>
                    <select id="data-period-month-year" class="custom-select custom-select-sm">
            <?php
                    foreach ($monYears as $value) {
            ?>
                        <option data-year="<?php echo $value['year']; ?>" data-month="<?php echo $value['month']; ?>">
                            <?php echo $monthes[$value['month']]; ?> <?php echo $value['year']; ?>
                        </option>
            <?php
                    }
            ?>
                    </select>
            <?php
                } else if (false == $monYears) {
            ?>
                    <div class="alert alert-danger">Internal Server Error</div>
            <?php
                } else if (empty($monYears)) {
            ?>
                    <div class="alert alert-warning">нет заполненных отчётов</div>
            <?php
                }
            ?>
                </div>

                <div class="col-2">
                    <a class="btn btn-block btn-outline-primary btn-sm" href="javascript:void(showData())">показать</a>
                </div>

            </div>

        </div>
    </div>

    <table id="data-table" class="table table-bordered table-hover table-sm table-striped">
        <thead>
            <tr class="table-primary">
                <th>#</th>
                <th>дата</th>
                <th>точка</th>
                <th class="text-right">выручка</th>
            </tr>
        </thead>
        <tbody id="data-table-body"></tbody>
        <tfoot id="data-table-foot"></tfoot>
    </table>

    <div class="modal fade" id="data-popup" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">добавить запись в отчёт</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group row mb-2">
                        <label for="data-popup-place" class="col-form-label col-form-label-sm col-2">точка</label>
                        <div class="col-10">
                    <?php

                        if ($places) {
                    ?>
                            <select id="data-popup-place" class="custom-select custom-select-sm">
                    <?php
                            foreach ($places as $value) {
                    ?>
                                <option value="<?php echo $value['id']; ?>"><?php echo $value['address']; ?></option>
                    <?php
                            }
                    ?>
                            </select>
                    <?php
                        } else if (false === $places) {
                    ?>
                            <div class="alert alert-danger">Internal Server Error</div>
                    <?php
                        } else if (empty($places)) {
                    ?>
                            <div class="alert alert-warning">у вас нет точек</div>
                    <?php
                        }
                    ?>
                        </div>
                    </div>

                    <div class="form-group row mb-2">
                        <label for="data-popup-date" class="col-form-label col-form-label-sm col-2">дата</label>
                        <div class="col-4">
                            <input disabled type="text" class="form-control form-control-sm text-center" id="data-popup-date" rel="datepicker" autocomplete="off" value="<?php echo date('d.m.Y'); ?>">
                        </div>

                        <label for="data-popup-amount" class="col-form-label col-form-label-sm col-2">сумма</label>
                        <div class="col-4">
                            <input type="text" class="form-control form-control-sm text-right" id="data-popup-amount">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a class="btn btn-outline-primary" href="javascript:void(saveData())">сохранить</a>
                </div>
            </div>
        </div>
    </div>
<?php
    }

    $script = 'data.' . $user->type . '.js';
?>

</div>
<div class="modal run"></div>
<script src="bootstrap/jquery.min.js"></script>
<script src="bootstrap/jquery.ui.min.js"></script>
<script src="bootstrap/popper.min.js"></script>
<script src="bootstrap/bootstrap.min.js"></script>
<script src="bootstrap/moment.min.js"></script>
<script src="bootstrap/air.datepicker.min.js"></script>
<script src="report.js?<?php echo fileatime('report.js'); ?>"></script>
<script src="<?php echo $script; ?>?<?php echo fileatime($script); ?>"></script>
</body>
</html>