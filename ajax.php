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
            # вход
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
            # выход
            if ($user->logout()) {
                $ret->success = true;
            } else {
                $ret->error = 'Internal Server Error';
            }
            break;
        }

        case 'createData': {
            # добавление записи в отчёт
            $post = array_map('intval', $post);

            $do = (User::MANAGER == $user->type);

            foreach (['amount', 'date', 'place'] as $key) {
                if (false === ($do = isset($post[$key]) ? $do : false)) continue;
                if (false === ($do = ($post[$key] > 0)  ? $do : false)) continue;

                if ('amount' == $key) {
                } else if ('date' == $key) {
                    $do = ($post[$key] <= time());
                } else if ('place' == $key) {
                    $place  = new Place($sql);
                    $places = $place->lista($user->id);
                    $places = empty($places) ? [] : array_column($places, 'id');

                    if (in_array($post[$key], $places)) {
                        # ok
                        # если данный менеджер имеет право добавлять запись для данной точки
                    } else {
                        $do = false;
                        $ret->error = 'Internal Server Error';
                    }
                }
            }

            $ret->error = $do ? null : ($ret->error ? $ret->error : 'Invalid parameters');
            if ($ret->error) break;

            $data = new Data($sql);
            $data->place = $post['place'];

            if ($ret->success = $data->create($post['date'], $post['amount'])) {
                $ret->id = $data->id;
            } else {
                $ret->error = 'Internal Server Error';
            }

            break;
        }

        case 'readData': {
            # чтение записи
            $do = (User::MANAGER == $user->type);
            $do = (isset($post['id']) and ($post['id'] = (int)$post['id']) > 0) ? $do : false;

            $ret->error = $do ? null : 'Invalid parameters';
            if ($ret->error) break;

            $data = new Data($sql);
            $value = $data->read($post['id']);

            $ret->error = empty($value) ? 'Internal Server Error' : null;
            if ($ret->error) break;

            $ret = (object)$value;
            $ret->success = true;

            break;
        }

        case 'updateData': {
            # обновление записи в отчёте
            $post = array_map('intval', $post);

            $do = (User::MANAGER == $user->type);
            foreach (['amount', 'id'] as $key) {
                if (false === ($do = isset($post[$key]) ? $do : false)) continue;
                if (false === ($do = ($post[$key] > 0)  ? $do : false)) continue;

                if ('amount' == $key) {
                } else if ('date' == $key) {
                    $do = ($post[$key] <= time());
                } else if ('id' == $key) {
                } else if ('place' == $key) {
                    $place  = new Place($sql);
                    $places = $place->lista($user->id);
                    $places = empty($places) ? [] : array_column($places, 'id');

                    if (in_array($post[$key], $places)) {
                        # ok
                        # если данный менеджер имеет право добавлять запись для данной точки
                    } else {
                        $do = false;
                        $ret->error = 'Internal Server Error';
                    }
                }
            }

            $ret->error = $do ? null : ($ret->error ? $ret->error : 'Invalid parameters');
            if ($ret->error) break;

            $data = new Data($sql);
            $data->id = $post['id'];

            if ($ret->success = $data->update($post['amount'])) {
                $ret->id = $data->id;
            } else {
                $ret->error = 'Internal Server Error';
            }

            break;
        }

        case 'showData': {
            # вывод отчёта
            $post = array_map('intval', $post);

            $do   = true;
            $keys = (User::ADMINISTRATOR == $user->type) ? ['manager', 'month', 'year'] : ['month', 'year'];

            foreach ($keys as $key) {
                if (false === ($do = isset($post[$key]) ? $do : false)) continue;

                if ('manager' == $key) {
                    $do = ($post[$key] >= 0);
                } else if ('month' == $key) {
                    $do = ($post[$key] > 0 and $post[$key] <= 12);
                } else if ('year' == $key) {
                    $do = ($post[$key] > 0);
                }
            }

            $ret->error = $do ? null : 'Invalid parameters';
            if ($ret->error) break;

            $post['manager'] = (User::ADMINISTRATOR == $user->type) ? $post['manager'] : $user->id;

            $data = new Data($sql);
            $values = $data->report($post['year'], $post['month'], $post['manager']);

            if (false === $values) {
                $ret->error = 'Internal Server Error';
            } else if (empty($values)) {
                $ret->success = true;
            }
            if ($ret->error or $ret->success) break;

            $place = new Place($sql);
            $places = $place->lista($post['manager']);

            $ret->error = empty($places) ? 'Internal Server Error' : null;
            if ($ret->error) break;

            if (User::ADMINISTRATOR == $user->type) {
                $managers = $user->managers();

                $ret->error = empty($managers) ? 'Internal Server Error' : null;
            } else {
                array_walk($values, function(&$value) { unset($value['manager']); });
            }
            if ($ret->error) break;

            $ret->success = true;
            $ret->values  = $values;
            $ret->places  = array_combine(array_column($places, 'id'), array_column($places, 'address'));
            if (User::ADMINISTRATOR == $user->type) {
                $ret->managers = $managers;
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
