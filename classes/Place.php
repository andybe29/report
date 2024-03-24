<?php
/**
 * Торговые точки
 */
class Place
{
    /**
     * @var simpleMySQLi $sql объект simpleMySQLi
     */
    private $sql;

    /**
     * @var int     $id      id точки
     * @var string  $address адрес
     * @var string  $manager ответственный менеджер
     */
    private $id, $manager;

    public function __construct($sql)
    {
        $this->sql = $sql;
    }

    public function __get($key)
    {
        return in_array($key, ['id', 'manager']) ? $this->$key : null;
    }

    public function __set($key, $value)
    {
        $this->$key = ('id' == $key and ($value = (int)$value) > 0) ? $value : null;
    }

    public function __debugInfo()
    {
        return [
            'id'      => $this->id,
            'manager' => $this->manager
        ];
    }

    /**
     * Список точек
     * @param int $manager если требуется фильтр по менеджеру
     * @return mixed массив данных либо false в случае фейла
     */
    public function lista(int $manager = 0)
    {
        if ($manager < 0) return false;

        $this->sql->str   = [];
        $this->sql->str[] = 'SELECT * FROM reportPlaces';
        if ($manager) {
            $this->sql->str[] = 'WHERE manager = ' . $manager;
        }
        $this->sql->execute();

        $data = $this->sql->err ? false : $this->sql->all();
        $this->sql->free();

        return $data;
    }
}