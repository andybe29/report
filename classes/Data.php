<?php
/**
 * Данные
 */
class Data
{
    /**
     * @var simpleMySQLi $sql объект simpleMySQLi
     */
    private $sql;

    /**
     * @var int $id     id записи
     * @var int $date   дата
     * @var int $place  id точки
     * @var int $amount сумма (в копейках)
     */
    private $id, $place;

    public function __construct($sql)
    {
        $this->sql = $sql;
    }

    public function __get($key)
    {
        return in_array($key, ['id', 'place']) ? $this->$key : null;
    }

    public function __set($key, $value)
    {
        $this->$key = (in_array($key, ['id', 'place']) and ($value = (int)$value) > 0) ? $value : null;
    }

    /**
     * добавление записи в отчёт
     * @param int $date   дата в unixtime
     * @param int $amount сумма выручки
     * @return boolean результат операции
     */
    public function create(int $date = 0, int $amount = 0)
    {
        if (empty($this->place) or $date <= 0 or $date > time() or $amount <= 0) return false;

        $data = [];
        $data['date']   = $this->sql->varchar(date('Y-m-d', $date));
        $data['place']  = $this->place;
        $data['amount'] = $amount;

        if (false !== $this->sql->insert('reportData', $data)) {
            $this->id = $this->sql->id;
        }

        return ($this->id !== null);
    }

    /**
     * чтение записи
     * @param int $id id записи
     * @return mixed массив данных либо false в случае фейла
     */
    public function read(int $id = 0)
    {
        if ($id <= 0) return false;

        $this->sql->str = 'SELECT * FROM reportData WHERE id = ' . $id;
        $this->sql->execute();

        $result = $this->sql->rows ? $this->sql->assoc() : false;
        $this->sql->free();

        if ($result) {
            foreach ($result as $key => $value) {
                $result[$key] = ('date' == $key) ? strtotime($value) : (int)$value;
            }

            $this->id = $result['id'];

            unset($result['id']);
        }

        return $result;
    }

    /**
     * обновление записи
     * @param int $amount сумма выручки
     * @return boolean результат операции
     */
    public function update(int $amount = 0)
    {
        if (empty($this->id) or $amount <= 0) return false;

        return (false !== $this->sql->update('reportData', ['amount' => $amount], ['id = ' . $this->id]));
    }

    /**
     * список месяцев и годов записей
     * @param int $manager если требуется фильтр по менеджеру
     * @return mixed массив данных либо false в случае фейла
     */
    public function monYears(int $manager = 0)
    {
        if ($manager < 0) return false;

        $this->sql->str   = [];
        $this->sql->str[] = 'SELECT DISTINCT YEAR(date) AS year, MONTH(date) AS month FROM reportData';
        if ($manager) {
            $this->sql->str[] = 'WHERE place IN (SELECT id FROM reportPlaces WHERE manager = ' . $manager . ')';
        }
        $this->sql->str[] = 'ORDER BY year, month';
        $this->sql->execute();

        $data = $this->sql->err ? false : $this->sql->all();
        $this->sql->free();

        return $data;
    }

    /**
     * отчёт за месяц/год
     * @param int $year    год
     * @param int $month   номер месяца
     * @param int $manager если требуется фильтр по менеджеру
     * @return mixed массив данных либо false в случае фейла
     */
    public function data(int $year = 0, int $month = 0, int $manager = 0)
    {
        if ($year < 0 or $month < 0 or $month > 12 or $manager < 0) return false;

        $w   = [];
        $w[] = 'YEAR(reportData.date) = ' . $year;
        $w[] = 'MONTH(reportData.date) = ' . $month;
        $w[] = 'reportData.place = reportPlaces.id';
        if ($manager) {
            $w[] = 'reportPlaces.manager = ' . $manager;
        }

        $this->sql->str   = [];
        $this->sql->str[] = 'SELECT reportData.*, reportPlaces.manager FROM reportData, reportPlaces';
        $this->sql->str[] = 'WHERE ' . simpleMySQLi::_and($w) . ' ORDER BY reportData.date, reportData.place';
        $this->sql->execute();

        $data = $this->sql->err ? false : ($this->sql->rows ? $this->sql->all() : []);
        $this->sql->free();

        if ($data) {
            array_walk(
                $data, function(&$record) {
                    foreach ($record as $key => $value) {
                        $record[$key] = ('date' == $key) ? strtotime($value) : (int)$value;
                    }
                }
            );
        }

        return $data;
    }

    /**
     * сводный отчёт за месяц/год
     * @param int $year    год
     * @param int $month   номер месяца
     * @return mixed массив данных либо false в случае фейла
     */
    public function report(int $year = 0, int $month = 0)
    {
        if ($year < 0 or $month < 0 or $month > 12) return false;

        $w   = [];
        $w[] = 'YEAR(reportData.date) = ' . $year;
        $w[] = 'MONTH(reportData.date) = ' . $month;
        $w[] = 'reportData.place = reportPlaces.id';

        $this->sql->str   = [];
        $this->sql->str[] = 'SELECT reportPlaces.manager, SUM(reportData.amount) as amount FROM reportData, reportPlaces';
        $this->sql->str[] = 'WHERE ' . simpleMySQLi::_and($w) . ' GROUP BY reportPlaces.manager';
        $this->sql->execute();

        $data = $this->sql->err ? false : ($this->sql->rows ? $this->sql->all() : []);
        $this->sql->free();

        if ($data) {
            array_walk(
                $data, function(&$record) {
                    $record = array_map('intval', $record);
                }
            );
        }

        return $data;
    }

	public static function _monthes() {
		return [1 => 'январь', 'февраль', 'март', 'апрель', 'май', 'июнь', 'июль', 'август', 'сентябрь', 'октябрь', 'ноябрь', 'декабрь'];
	}
}