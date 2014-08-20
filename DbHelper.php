<?php
/**
 * DbHelper class file.
 * @license http://www.opensource.org/licenses/bsd-license.php
 */

/**
 * Helper for migration commands.
 *
 * @author Pavel Bariev <bariew@yandex.ru>
 */

namespace bariew\moduleMigration;
use Yii;
use yii\db\Command;

class DbHelper
{
    /**
     * Disables foreign key check. Use it before table migration operations.
     * @return int whether check is disabled.
     */
    public static function foreignKeysOff()
    {
        return Yii::$app->db->createCommand("SET foreign_key_checks = 0")->execute();
    }

    /**
     * Enables foreign key check. Use it after table migration operations.
     * @return int whether check is enabled.
     */
    public static function foreignKeysOn()
    {
        return Yii::$app->db->createCommand("SET foreign_key_checks = 1")->execute();
    }

    /**
     * Inserts new data into table or updates on duplicate key.
     * @param string $tableName db table name
     * @param array $columns db column names
     * @param array $data data to insert
     * @param string $db application connection name
     * @return Command the DB command
     */
    public static function insertUpdate($tableName, $columns, $data, $db = 'db')
    {
        if (!$data) {
            return false;
        }
        foreach ($data as $key => $row) {
            $data[$key] = array_values($row);
        }
        $sql = \Yii::$app->$db->createCommand()->batchInsert($tableName, $columns, $data)->rawSql;
        $sql .= 'ON DUPLICATE KEY UPDATE ';
        $values = [];
        foreach ($columns as $column) {
            $values[] = "{$column} = VALUES({$column})";
        }
        $sql .= implode($values, ', ');
        return \Yii::$app->$db->createCommand($sql);
    }

    /**
     * Extracts column names from table data.
     * @param array $data table data.
     * @return array column names.
     */
    public static function dataColumns($data)
    {
        $row = reset($data);
        return array_keys($row);
    }

} 