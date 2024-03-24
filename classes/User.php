<?php
/**
 * Пользователи
 */
class User
{
    /**
     * @const TOKEN_TIME время (в секундах) жизни токена аутентификации
     * @const TOKEN_REXP регулярка на md5
     */
    const TOKEN_TIME = 3600; # 1 час
    const TOKEN_REXP = '/^[a-f0-9]{32}$/i';

    /**
     * константы для записи кук
     * @const COOKIE_HOST   хост хранения кук
     * @const COOKIE_SECURE флаг секьюрности
     * @const COOKIE_ID     имя для хранения id
     * @const COOKIE_TOKEN  имя для хранения токена
     */
    const COOKIE_HOST   = 'andy.bezbozhny.com';
    const COOKIE_SECURE = false;
    const COOKIE_ID     = 'reportUser';
    const COOKIE_TOKEN  = 'reportToken';

    /**
     * Типы пользователей
     */
    const ADMINISTRATOR = 'administrator';  # администратор
    const MANAGER       = 'manager';        # менеджер

    static $types = [self::ADMINISTRATOR, self::MANAGER];

    /**
     * @var simpleMySQLi $sql объект simpleMySQLi
     */
    private $sql;

    /**
     * @var int     $id     id пользователя
     * @var string  $name   имя
     * @var string  $login  логин
     * @var string  $hashed md5 от пароля
     * @var string  $type   тип
     * @var int     $logged дата/время аутентификации
     * @var string  $token  токен аутентификации
     */
    private $id, $name, $type;

    public function __construct($sql)
    {
        $this->sql = $sql;
    }

    public function __get($key)
    {
        return in_array($key, ['id', 'name', 'type']) ? $this->$key : null;
    }

    public function __set($key, $value)
    {
        $this->$key = ('id' == $key and ($value = (int)$value) > 0) ? $value : null;
    }

    public function __debugInfo()
    {
        return [
            'id'   => $this->id,
            'name' => $this->name,
            'type' => $this->type
        ];
    }

    /**
     * Аутентификация пользователя
     * @param int    $id    id пользователя
     * @param string $token предъявляемый токен аутентификации
     * @return boolean флаг успешности аутентификации
     */
    public function auth(int $id = 0, string $token = '')
    {
        if ($id <= 0 or !preg_match(self::TOKEN_REXP, $token)) return false;

        $w   = [];
        $w[] = 'id = ' . $id;
        $w[] = 'token = BINARY ' . $this->sql->varchar($token);
        $w[] = 'logged > DATE_SUB(' . $this->sql->varchar(simpleMySQLi::_now()) . ', INTERVAL ' . self::TOKEN_TIME . ' SECOND)';

        $this->sql->str = 'SELECT * FROM reportUsers WHERE ' . simpleMySQLi::_and($w);
        $this->sql->execute();

        $user = $this->sql->rows ? $this->sql->assoc() : null;
        $this->sql->free();

        if ($user) {
            # токен валиден И время его жизни ещё не истекло
            # запись новой временной метки
            $data = ['logged' => $this->sql->varchar(simpleMySQLi::_now())];

            $result = true;
        } else {
            # предъявляемый токен НЕвалиден либо время его жизни УЖЕ истекло
            # сброс токена
            $data = ['logged' => 'NULL', 'token' => 'NULL'];

            $result = false;
        }

        if (false !== $this->sql->update('reportUsers', $data, ['id = ' . $id])) {

            if ($result) {
                $this->id   = (int)$user['id'];
                $this->name = $user['name'];
                $this->type = $user['type'];

                # перезапись id и токена в куки
                $this->_setCookie(self::COOKIE_ID,    $this->id, time() + self::TOKEN_TIME);
                $this->_setCookie(self::COOKIE_TOKEN, $token,    time() + self::TOKEN_TIME);
            } else {
                # удаление кук
                $this->_getCookie(self::COOKIE_ID,    true);
                $this->_getCookie(self::COOKIE_TOKEN, true);
            }
        } else {
            $result = false;
        }

        return $result;
    }

    /**
     * Логин
     * @param string $login логин
     * @param string $passw пароль
     * @return mixed токен аутентификации либо false в случае фейла
     */
    public function login(string $login, string $passw)
    {
        if (empty($login) or empty($passw)) return false;

        $w   = [];
        $w[] = 'login = BINARY ' . $this->sql->varchar($login);
        $w[] = 'hashed = BINARY ' . $this->sql->varchar(md5($passw));

        $this->sql->str = 'SELECT * FROM reportUsers WHERE ' . simpleMySQLi::_and($w);
        $this->sql->execute();

        $user = $this->sql->rows ? $this->sql->assoc() : null;
        $this->sql->free();

        $result = false;

        if ($user) {
            # пользователь найден

            # генерация токена аутентификации
            $user['token'] = md5(time() + rand(0, 10000000));

            # запись auth-данных
            $data = [
                'logged' => $this->sql->varchar(simpleMySQLi::_now()),
                'token'  => $this->sql->varchar($user['token'])
            ];

            if (false !== $this->sql->update('reportUsers', $data, ['id = ' . $user['id']])) {
                $this->id = (int)$user['id'];

                $result = $user['token'];
            }
        }

        return $result;
    }

    /**
     * Логаут пользователя
     * @return boolean флаг успешности логаута
     */
    public function logout()
    {
        if (empty($this->id)) return false;

        # удаление кук
        $this->_getCookie(self::COOKIE_ID,    true);
        $this->_getCookie(self::COOKIE_TOKEN, true);

        # сброс токена
        $data = ['logged' => 'NULL', 'token' => 'NULL'];

        return $this->sql->update('reportUsers', $data, ['id = ' . $this->id]);
    }

    /**
     * Список менеджеров
     * @return mixed массив данных либо false в случае фейла
     */
    public function managers()
    {
        $this->sql->str = 'SELECT id, name FROM reportUsers WHERE type = ' . $this->sql->varchar(self::MANAGER);
        $this->sql->execute();

        $data = $this->sql->err ? false : $this->sql->all();
        $this->sql->free();

        return $data ? array_column($data, 'name', 'id') : $data;
    }

    /**
     * получение значения куки
     * @param string $name   название
     * @param string $remove флаг удаления куки
     * @return string|null значение куки либо null в случае её отсутствия
     */
    private function _getCookie(string $name = '', $remove = false)
    {
        if ($name and array_key_exists($name, $_COOKIE)) {
            $value = $_COOKIE[$name];
            if ($remove) $this->_setCookie($name, '', time() - 60);
            return $value;
        }

        return null;
    }

    /**
     * запись значения куки
     * @param string $name   название
     * @param string $value  значение
     * @param int    $expire дата / время (unix) истечения куки
     */
    private function _setCookie(string $name = '', $value = '', $expire = 0)
    {
        setcookie($name, $value, $expire, '/', self::COOKIE_HOST, self::COOKIE_SECURE);
    }
}