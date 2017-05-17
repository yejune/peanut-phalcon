<?php
namespace Peanut\Generator\Model;

use Peanut\Phalcon\Db;

class Metadata
{
    public $tables  = [];
    public $indexes = [];
    public $connect;
    public static $instance;

    /**
     * @param  $name
     * @throws \PDOException|\Exception
     * @return \Pdo
     */
    public static function getInstance()
    {
        if (false === isset(self::$instance)) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    public function getTables($dbname)
    {
        $list   = $this->connect->gets('show table status from '.$dbname);
        $tables = [];
        foreach ($list as $table) {
            $tableName = $table['Name'];
            $tableDesc = $table['Comment'];
            $info      = $this->connect->gets('show full columns from '.$dbname.'.'.$tableName.'');

            $fields     = [];
            $allFields  = [];
            foreach ($info as $field) {
                if ($field['Key'] == 'PRI') {
                    $primary = [
                        'fieldName'       => $field['Field'],
                        'type'            => $field['Type'],
                        'isNull'          => $field['Null'] === 'NO' ? false : true,
                        'default'         => $field['Default'].($field['Extra'] == 'on update CURRENT_TIMESTAMP' ? ' '.'on update CURRENT_TIMESTAMP' : ''),
                        'isAutoIncrement' => $field['Extra'] == 'auto_increment' ? true : false,
                        'comment'         => $field['Comment'],
                    ];
                } else {
                    $fields[] = [
                        'fieldName'       => $field['Field'],
                        'type'            => $field['Type'],
                        'isNull'          => $field['Null'] === 'NO' ? false : true,
                        'default'         => $field['Default'].($field['Extra'] == 'on update CURRENT_TIMESTAMP' ? ' '.'on update CURRENT_TIMESTAMP' : ''),
                        'isAutoIncrement' => $field['Extra'] == 'auto_increment' ? true : false,
                        'comment'         => $field['Comment'],
                    ];
                }

                $allFields[] = [
                    'fieldName'       => $field['Field'],
                    'type'            => $field['Type'],
                    'isNull'          => $field['Null'] === 'NO' ? false : true,
                    'default'         => $field['Default'].($field['Extra'] == 'on update CURRENT_TIMESTAMP' ? ' '.'on update CURRENT_TIMESTAMP' : ''),
                    'isAutoIncrement' => $field['Extra'] == 'auto_increment' ? true : false,
                    'comment'         => $field['Comment'],
                ];
            }
            $tables[$tableName] = [
                'tableName'  => $tableName,
                'tableDesc'  => $tableDesc,
                'primary'    => $primary,
                'fields'     => $fields,
                'allFields'  => $allFields,
            ];
        }

        return $tables;
    }
    public function getIndexes($dbname)
    {
        $indexes = $this->connect->gets("
            SELECT
                *
            FROM
                information_schema.TABLE_CONSTRAINTS i
            LEFT JOIN
                information_schema.KEY_COLUMN_USAGE k
            ON
                i.CONSTRAINT_NAME = k.CONSTRAINT_NAME
            AND
                i.TABLE_SCHEMA = k.TABLE_SCHEMA
            AND
                i.TABLE_NAME = k.TABLE_NAME
            WHERE
                i.TABLE_SCHEMA = '{$dbname}'
            ORDER BY
                i.TABLE_NAME ASC, i.CONSTRAINT_TYPE = 'PRIMARY KEY' DESC
        ");

        $deleteMultiUnique = [];
        foreach ($indexes as $k => $v) {
            if ($v['CONSTRAINT_NAME'] != 'PRIMARY' && true === isset($deleteMultiUnique[$v['TABLE_NAME'].'_'.$v['CONSTRAINT_NAME']])) {
                unset($deleteMultiUnique[$v['TABLE_NAME'].'_'.$v['CONSTRAINT_NAME']]);
            } else {
                $deleteMultiUnique[$v['TABLE_NAME'].'_'.$v['CONSTRAINT_NAME']] = $v;
            }
        }

        $foreignKeys = [];
        foreach ($deleteMultiUnique as $index) {
            if (true === isset($foreignKeys[$index['TABLE_NAME']][$index['CONSTRAINT_TYPE']][$index['COLUMN_NAME']])) {
                //die($index['TABLE_NAME'].' '.$index['CONSTRAINT_TYPE'].' '.$index['COLUMN_NAME'].' duplication not support');
            }
            $foreignKeys[$index['TABLE_NAME']][$index['CONSTRAINT_TYPE']][$index['COLUMN_NAME']] = $index;
        }

        foreach ($foreignKeys as $tableName => &$indexes) {
            $ff = [];
            foreach ($this->tables[$tableName]['allFields'] as $i => $field) {
                if (true === isset($indexes['FOREIGN KEY'][$field['fieldName']])) {
                    $ff[$field['fieldName']] = $indexes['FOREIGN KEY'][$field['fieldName']];
                }
            }
            $indexes['FOREIGN KEY'] = $ff;
        }

        return $foreignKeys;
    }

    public function getRelations()
    {
        $relations = [];

        foreach ($this->indexes as $tableName => $indexes) {
            if (true === isset($indexes['FOREIGN KEY'])) {
                foreach ($indexes['FOREIGN KEY'] as $fieldName => $index) {
                    if (true === isset($indexes['UNIQUE'][$fieldName])) {
                        $relations[$index['REFERENCED_TABLE_NAME']]
                                  ['hasOne']
                                  [$index['REFERENCED_COLUMN_NAME']]
                                  [$index['TABLE_NAME']] = [
                                      'table'  => $index['TABLE_NAME'],
                                      'column' => $index['COLUMN_NAME'],
                                  ];

                        $relations[$index['TABLE_NAME']]
                                  ['belongTo']
                                  [$index['COLUMN_NAME']]
                                  [$index['REFERENCED_TABLE_NAME']] = [
                                     'table'  => $index['REFERENCED_TABLE_NAME'],
                                     'column' => $index['REFERENCED_COLUMN_NAME'],
                                 ];
                    } else {
                        $relations[$index['REFERENCED_TABLE_NAME']]
                                  ['hasMany']
                                  [$index['REFERENCED_COLUMN_NAME']]
                                  [$index['TABLE_NAME']] = [
                                      'table'  => $index['TABLE_NAME'],
                                      'column' => $index['COLUMN_NAME'],
                                  ];

                        $relations[$index['TABLE_NAME']]
                                  ['belongsTo']
                                  [$index['COLUMN_NAME']]
                                  [$index['REFERENCED_TABLE_NAME']] = [
                                     'table'  => $index['REFERENCED_TABLE_NAME'],
                                     'column' => $index['REFERENCED_COLUMN_NAME'],
                                 ];
                    }
                }
            }
        }
        $return = $relations;
        foreach ($relations as $tableName => $relation) {
            foreach ($relation as $relationName => $fields) {
                switch ($relationName) {
                    case 'belongsTo':

                        if (false === isset($relations[$tableName]['hasMany'])) {
                            $relations[$tableName]['hasMany'] = [];
                        }
                        if (false === isset($relations[$tableName]['hasOne'])) {
                            $relations[$tableName]['hasOne'] = [];
                        }
                        if (2 == count($relation[$relationName]) && (
                            0 === count($relations[$tableName]['hasMany'])
                            &&
                            0 === count($relations[$tableName]['hasOne'])
                        )) {
                            $is  = true;
                            $sss = [];
                            foreach ($fields as $fieldName => $s) {
                                if (false !== strpos($fieldName, 'parent_')) {
                                    $is = false;
                                    break;
                                }
                                $v        = reset($s);
                                $v['key'] = $fieldName;
                                $sss[]    = $v;
                                if (false === isset($relations[$v['table']]['hasMany'][$v['column']][$tableName])) {
                                    $is = false;
                                    break;
                                }
                            }
                            if ($is) {
                                // 단방향만 허용
                                $return[$sss[0]['table']]['hasManyToMany'][$sss[0]['column']][$tableName] = [
                                    'table'        => $tableName,
                                    'column'       => $sss[0]['key'],
                                    'destination'  => [
                                        $sss[1]['key'] => [
                                            $sss[1]['table'] => [
                                                'table'  => $sss[1]['table'],
                                                'column' => $sss[1]['column'],
                                            ],
                                        ],
                                    ],
                                ];
                                //unset($return[$sss[0]['table']]['hasMany'][$sss[0]['column']]);

                                /*
                                $return[$sss[1]['table']]['hasManyToMany'][$sss[1]['column']][$tableName] = [
                                    'table'        => $tableName,
                                    'column'       => $sss[1]['key'],
                                    'destination'  => [
                                        $sss[0]['key'] => [
                                            $sss[0]['table'] => [
                                                'table'  => $sss[0]['table'],
                                                'column' => $sss[0]['column'],
                                            ],
                                        ],
                                    ],
                                ];
                                unset($return[$sss[1]['table']]['hasMany'][$sss[1]['column']]);
                                */
                                //unset($relations[$tableName][$relationName]);
                            }
                        }
                    break;
                }
            }
        }

        return $return;
    }
    public function getDbInfomation($connect, $dbname)
    {
        $this->connect   = $connect;
        $this->tables    = $this->getTables($dbname);
        $this->indexes   = $this->getIndexes($dbname);
        $this->relations = $this->getRelations();
        foreach ($this->relations as $tableName => $relations) {
            $this->tables[$tableName]['relations'] = $relations;
        }

        return $this->tables;
    }
    public static function generate($connect, $dbname)
    {
        return static::getInstance()->getDbInfomation($connect, $dbname);
    }
}
