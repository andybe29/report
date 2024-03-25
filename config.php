<?php
    mb_internal_encoding('utf-8');

    # доступ к БД
    $db = [
        'username' => '',
        'passwd'   => '',
        'dbname'   => '',
        'host'     => ''
    ];

    function __autoload($classname)
    {
        require_once realpath(__DIR__) . '/classes/' . $classname . '.php';
    }
