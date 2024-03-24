<?php
    require 'config.php';

    $post = array_map(
        function($value) {
            return is_scalar($value) ? trim(strip_tags($value)) : $value;
        }, $_POST
    );

    $ret = new stdClass;
    $ret->success = false;
    $ret->error   = null;

    if (!isset($post['method'])) {
        $ret->error = 'Bad Request';
    } else if ('login' != $post['method']) {
        # проверка аутентификации
        if (isset($_COOKIE[User::COOKIE_ID]) and isset($_COOKIE[User::COOKIE_TOKEN])) {
            $sql  = new simpleMySQLi($db, realpath(__DIR__));
            $user = new User($sql);

            $ret->error = $user->auth($_COOKIE[User::COOKIE_ID], $_COOKIE[User::COOKIE_TOKEN]) ? null : 'Unauthorized';
        } else {
            $ret->error = 'Forbidden';
        }
    }

    if ($ret->error) goto endOfScript;

    switch ($post['method']) {

        case 'login': {
            $do = true;
            foreach (['login', 'passw'] as $key) {
                $do = isset($post[$key]) ? $do : false;
            }

            $ret->error = $do ? null : 'Invalid parameters';
            if ($ret->error) break;

            $user = new User(new simpleMySQLi($db, realpath(__DIR__)));

            if ($token = $user->login($post['login'], $post['passw'])) {
                if ($user->auth($user->id, $token)) {
                    $ret->success = true;
                } else {
                    $ret->error = 'Unauthorized';
                }
            } else {
                $ret->error = 'Forbidden';
            }
            break;
        }

        case 'logout': {
            if ($user->logout()) {
                $ret->success = true;
            } else {
                $ret->error = 'Internal Server Error';
            }
            break;
        }

        default: {
            $ret->error = 'Method Not Allowed';
            break;
        }
    }

    endOfScript:
    if ($ret->success) unset($ret->error);

    header('Content-Type: application/json');
    echo json_encode($ret, JSON_UNESCAPED_UNICODE);
