<?php
class WebinarSysteemDB extends wpdb {
    private static $instance = null;

    public function __construct() {
        parent::__construct(DB_USER, DB_PASSWORD, DB_NAME, DB_HOST);
    }

    public function insert_multiple($table, $data, $format = null) {
        $this->insert_id = 0;

        $formats = array();
        $values = array();

        foreach ($data as $index => $row) {
            $row = $this->process_fields($table, $row, $format);
            $row_formats = array();

            if ($row === false || array_keys($data[$index]) !== array_keys($data[0])) {
                continue;
            }

            foreach($row as $col => $value) {
                if (is_null($value['value'])) {
                    $row_formats[] = 'NULL';
                } else {
                    $row_formats[] = $value['format'];
                }

                $values[] = $value['value'];
            }

            $formats[] = '(' . implode(', ', $row_formats) . ')';
        }

        $fields  = '`' . implode('`, `', array_keys($data[0])) . '`';
        $formats = implode(', ', $formats);
        $sql = "INSERT INTO `$table` ($fields) VALUES $formats";

        $this->check_current_query = false;
        return $this->query($this->prepare($sql, $values));
    }

    public static function instance() {
        if (self::$instance == null) {
            self::$instance = new WebinarSysteemDB();
        }
        return self::$instance;
    }
}
