<?php

namespace app\service {

    class R extends \RedBeanPHP\R
    {

        public static $RX_CONNECTED = false;

        public static function rx_setup()
        {
            $config = \Config::getSection("REDBEAN_CONFIG");
            if (!self::$RX_CONNECTED) {
                self::setup('mysql:host=' . $config['host'] . ';dbname=' . $config['dbname'], $config['username'], $config['password']);
                R::ext('xdispense', function ($type) {
                    return R::getRedBean()->dispense($type);
                });
                self::$RX_CONNECTED = true;
            }
        }

        public static function dump_backup()
        {
            Header("Content-type: application/octet-stream");
            Header("Content-Disposition: attachment; filename='me.sql'");

            $alltables = R::getAll("SHOW TABLES");
            $content = "SET FOREIGN_KEY_CHECKS = 0;";
            foreach ($alltables as $table) {
                foreach ($table as $db => $tablename) {
                    $content .= "TRUNCATE `" . $tablename . "`;";
                }
            }
            foreach ($alltables as $table) {
                foreach ($table as $db => $tablename) {
                    $content .= self::dump_backup_datadump($tablename);
                }
            }
            $content .= "SET FOREIGN_KEY_CHECKS = 1;";
            return $content;
        }

        public function dump_backup_datadump($table)
        {
            $result = "";
            $result .= "# Dump of $table \r\n";
            $result .= "# Dump DATE : " . date("d-M-Y") . "\r\n\r\n";
            $tabledata = R::getAll("SELECT * FROM $table");
            foreach ($tabledata as $t) {
                $result .= "INSERT INTO " . $table . " ";
                $row = array();
                foreach ($t AS $field => $value) {
                    // $value = \escapestring($value);
                    $value = ereg_replace("\n", "\\n", $value);
                    if (isset($value)) $row[$field] = "'$value'";
                    else $row[$field] = "''";
                }
                $result .= "VALUES(" . implode(", ", $row) . ");\r\n";
            }
            return $result . "\r\n\r\n\r\n";
        }

    }

    R::rx_setup();
}


