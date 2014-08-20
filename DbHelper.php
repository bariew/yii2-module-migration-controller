<?php
/**
 * Created by PhpStorm.
 * User: pt
 * Date: 8/20/14
 * Time: 11:20 AM
 */

namespace bariew\moduleMigration;
use Yii;
use yii\db\Command;

class DbHelper
{
    public static function foreignKeysOff()
    {
        return Yii::$app->db->createCommand("SET foreign_key_checks = 0")->execute();
    }
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

    public static function dataColumns($data)
    {
        $row = reset($data);
        return array_keys($row);
    }

} 