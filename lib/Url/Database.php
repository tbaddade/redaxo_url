<?php

/**
 * This file is part of the Url package.
 *
 * @author (c) Thomas Blum <thomas@addoff.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Url;

class Database
{
    const DATABASE_TABLE_SEPARATOR = '_xxx_';

    public static function getAll()
    {
        $dbConfigs = \rex::getProperty('db');
        foreach ($dbConfigs as $DBID => $dbConfig) {
            if ($dbConfig['host'] . $dbConfig['login'] . $dbConfig['password'] . $dbConfig['name'] != '') {
                //$connection = \rex_sql::checkDbConnection(
                //    $dbConfig['host'],
                //    $dbConfig['login'],
                //    $dbConfig['password'],
                //    $dbConfig['name']
                //);
                $connection = true;
                if ($connection !== true) {
                    unset($dbConfigs[$DBID]);
                }
            } else {
                unset($dbConfigs[$DBID]);
            }
        }
        return $dbConfigs;
    }

    public static function getSupportedTables()
    {
        $dbConfigs = self::getAll();
        $supportedTables = [];
        foreach ($dbConfigs as $DBID => $dbConfig) {
            $tables = [];
            $sqlTables = \rex_sql::getTablesAndViews($DBID);
            foreach ($sqlTables as $sqlTable) {
                $tableColumns = [];
                $sqlColumns = \rex_sql::showColumns($sqlTable, $DBID);
                foreach ($sqlColumns as $sqlColumn) {
                    $tableColumns[] = ['name' => $sqlColumn['name']];
                }
                $tables[] = [
                    'name' => $sqlTable,
                    'name_unique' => self::merge($DBID, $sqlTable),
                    'columns' => $tableColumns,
                ];
            }

            $supportedTables[$DBID]['name'] = $dbConfig['name'];
            $supportedTables[$DBID]['tables'] = $tables;
        }
        return $supportedTables;
    }


    public static function getLogicalOperators()
    {
        return [
            'AND' => 'AND',
            'OR' => 'OR',
        ];
    }


    public static function getComparisonOperators()
    {
        return [
            '=' => '=',
            '>' => '>',
            '>=' => '>=',
            '<' => '<',
            '<=' => '<=',
            '!=' => '!=',
            'LIKE' => 'LIKE',
            'NOT LIKE' => 'NOT LIKE',
            'IN' => 'IN (...)',
            'NOT IN' => 'NOT IN (...)',
            'BETWEEN' => 'BETWEEN',
            'NOT BETWEEN' => 'NOT BETWEEN',
            'FIND_IN_SET' => 'FIND_IN_SET',
        ];
    }

    public static function merge($database, $table)
    {
        return implode(self::DATABASE_TABLE_SEPARATOR, [$database, $table]);
    }

    public static function split($value)
    {
        return explode(self::DATABASE_TABLE_SEPARATOR, $value);
    }
}
