<?php
namespace Peanut\Generator;

use Sb\Generator\Object as CreateClass;
use Sb\Generator\Object\Property as CreateField;
use Sb\Generator\Object\Method as CreateMethod;
use Sb\Generator\Object\Method\Param as CreateMethodParam;

class Model
{
    public $namespace   = '\\App\\Models\\Databases\\Default';
    public $foreignKey  = false;
    public $reusable    = false;
    public $cascade     = false;
    public $isMetaData  = true;
    public $extendModel = '\\Peanut\\Phalcon\\Mvc\\Model';
    public $dbName      = 'WORKSPACE';
    public $dbConn;
    public $targetFolder;

    public function addDatabaseConnection($conn)
    {
        $this->dbConn = $conn;
    }
    public function addDatabaseName($name)
    {
        $this->dbName = $name;
    }
    public function addNamespace($namespace)
    {
        $this->namespace = $namespace;
    }
    public function addTargetFolder($folder)
    {
        $this->targetFolder = $folder;
    }
    public function execute()
    {
        //$files = $this->getFiles('../erd');
        $models = \Peanut\Generator\Model\Metadata::generate($this->dbConn, $this->dbName);

        foreach ($models as $tableName => $config) {
            $modelName    = \Peanut\Text::camelize($tableName);

            $workClass = new CreateClass($modelName);
            $workClass->setExtends('Properties\\'.$modelName);
            $workClass->setNamespace(trim($this->namespace, '\\'));

            $propertyClass = new CreateClass($modelName);
            $propertyClass->addUse('Phalcon\Mvc\Model\MetaData');
            $propertyClass->addUse('Phalcon\Db\Column');
            $propertyClass->setExtends($this->extendModel);
            $propertyClass->setNamespace(trim($this->namespace.'\\Properties', '\\'));

            foreach ($config['allFields'] as $field) {
                $fieldField   = new CreateField($field['fieldName']);
                $fieldField->addDescription($field['comment']);
                $fieldField->setScope('protected');
                $fieldField->setType($field['type']);
                $propertyClass->addField($fieldField);
            }

            // get source method
            $propertyClass->addMethod($this->getSourceMethod($tableName));
            // setter getter
            $propertyClass->generateSettersAndGetters();
            // initialize method
            $propertyClass->addMethod($this->getInitializeMethod($config));

            //has many
            $relations = isset($config['relations']['hasMany']) ? $config['relations']['hasMany'] : [];
            foreach ($relations as $fieldName => $tables) {
                foreach ($tables as $tableName => $value) {
                    $refTableName      = $tableName;
                    $refFieldName      = $value['column'];
                    $refModelName      = \Peanut\Text::camelize($refTableName);
                    $refLcModelName    = \Peanut\Text::lcfirstCamelize($refTableName);
                    $refPropertyName   = \Peanut\Text::camelize($refFieldName);
                    if (1 === preg_match('#parent_#', $fieldName)) {
                        continue;
                    }

                    $aliasModelName = \Peanut\Text::pluralize($refModelName);

                    $getMethod       = new CreateMethod('get'.$aliasModelName);
                    //$getMethod->addDescription('test');
                    $getMethod->addContentLine("return \$this->getRelated('{$aliasModelName}', \$parameters);");
                    $getMethod->setReturn("\Phalcon\Mvc\Model\Resultset\Simple");
                    $getMethodParam1 = new CreateMethodParam('parameters');
                    $getMethodParam1->setType('array');
                    $getMethodParam1->setDefaultValue('[]');
                    $getMethod->addParam($getMethodParam1);
                    $propertyClass->addMethod($getMethod);

                    $setMethod       = new CreateMethod('add'.$refModelName);
                    $setMethod->addContentLine("\$this->setRelated('{$aliasModelName}', \${$refLcModelName});");
                    $setMethod->addContentLine('');
                    $setMethod->addContentLine('return $this;');
                    $setMethodParam1 = new CreateMethodParam($refLcModelName);
                    $setMethodParam1->setType("{$this->namespace}\\{$refModelName}");
                    $setMethod->addParam($setMethodParam1);
                    $setMethod->setReturn('$this');
                    $propertyClass->addMethod($setMethod);

                    $propertiesName = lcfirst($aliasModelName);
                    $propertyName   = lcfirst($refModelName);

                    $setsMethod       = new CreateMethod('add'.$aliasModelName);
                    $setsMethod->addContentLine("foreach (\${$propertiesName} as \${$propertyName}) {");
                    $setsMethod->addContentLine('    $this->'.'add'.$refModelName.'($'.$propertyName.');');

                    $setsMethod->addContentLine('}');
                    $setsMethod->addContentLine('');
                    $setsMethod->addContentLine('return $this;');
                    $setsMethodParam1 = new CreateMethodParam($propertiesName);
                    $setsMethodParam1->setType('array');
                    $setsMethod->addParam($setsMethodParam1);
                    $setsMethod->setReturn('$this');
                    $propertyClass->addMethod($setsMethod);
                }
            }

            //has one
            $relations = isset($config['relations']['hasOne']) ? $config['relations']['hasOne'] : [];
            foreach ($relations as $fieldName => $tables) {
                foreach ($tables as $tableName => $value) {
                    $refTableName      = $tableName;
                    $refFieldName      = $value['column'];
                    $refModelName      = \Peanut\Text::camelize($refTableName);
                    $refLcModelName    = \Peanut\Text::lcfirstCamelize($refTableName);
                    $refPropertyName   = \Peanut\Text::camelize($refFieldName);
                    if (1 === preg_match('#parent_#', $fieldName)) {
                        continue;
                    }

                    $aliasModelName = $refModelName;

                    $getMethod       = new CreateMethod('get'.$refModelName);
                    $getMethod->addContentLine("return \$this->getRelated('{$refModelName}', \$parameters) ?: null;");
                    $getMethod->setReturn("{$this->namespace}\\{$refModelName}");
                    $getMethodParam1 = new CreateMethodParam('parameters');
                    $getMethodParam1->setType('array');
                    $getMethodParam1->setDefaultValue('[]');
                    $getMethod->addReturnNull();
                    $getMethod->addParam($getMethodParam1);
                    $propertyClass->addMethod($getMethod);

                    $setMethod       = new CreateMethod('add'.$refModelName);
                    $setMethod->addContentLine("\$this->setRelated('{$aliasModelName}', \${$refLcModelName});");
                    //$setMethod->addContentLine("\$this->{$fieldName} = \${$refLcModelName} ? \${$refLcModelName}->get{$refPropertyName}() : null;");
                    $setMethod->addContentLine('');
                    $setMethod->addContentLine('return $this;');

                    $setMethodParam1 = new CreateMethodParam($refLcModelName);
                    $setMethodParam1->setType("{$this->namespace}\\{$refModelName}");
                    //$setMethodParam1->setDefaultValue('null');
                    $setMethod->addParam($setMethodParam1);
                    $setMethod->setReturn('$this');

                    $propertyClass->addMethod($setMethod);
                }
            }
            // belongs to
            $relationses = [
                'many' => isset($config['relations']['belongsTo']) ? $config['relations']['belongsTo'] : [],
                'one'  => isset($config['relations']['belongTo']) ? $config['relations']['belongTo'] : [],
            ];
            foreach ($relationses as $relations) {
                foreach ($relations as $fieldName => $tables) {
                    foreach ($tables as $tableName => $value) {
                        $refTableName      = $tableName;

                        $refFieldName      = $value['column'];
                        $refModelName      = \Peanut\Text::camelize($refTableName);
                        $refLcModelName    = \Peanut\Text::lcfirstCamelize($refTableName);
                        $refPropertyName   = \Peanut\Text::camelize($refFieldName);
                        if (1 === preg_match('#parent_#', $fieldName)) {
                            continue;
                        }

                        $aliasModelName = $refModelName;

                        $getMethod       = new CreateMethod('get'.$refModelName);
                        $getMethod->addContentLine("return \$this->getRelated('{$refModelName}', \$parameters) ?: null;");
                        $getMethod->setReturn("{$this->namespace}\\{$refModelName}");
                        $getMethodParam1 = new CreateMethodParam('parameters');
                        $getMethodParam1->setType('array');
                        $getMethodParam1->setDefaultValue('[]');
                        $getMethod->addReturnNull();
                        $getMethod->addParam($getMethodParam1);
                        $propertyClass->addMethod($getMethod);

                        $setMethod       = new CreateMethod('add'.$refModelName);
                        $setMethodParam1 = new CreateMethodParam($refLcModelName);
                        $setMethodParam1->setType("{$this->namespace}\\{$refModelName}");
                        //$setMethodParam1->setDefaultValue('null');
                        $setMethod->addParam($setMethodParam1);

                        //$setMethod->addContentLine("\$this->setRelated('{$aliasModelName}', \${$refLcModelName});");
                        $setMethod->addContentLine("\$this->{$refModelName} = \${$refLcModelName};");

                        $setMethod->addContentLine('');
                        $setMethod->addContentLine('return $this;');
                        $setMethod->setReturn('$this');

                        $propertyClass->addMethod($setMethod);

                        $setMethod       = new CreateMethod('set'.$refModelName);
                        $setMethodParam1 = new CreateMethodParam($refLcModelName);
                        $setMethodParam1->setType("{$this->namespace}\\{$refModelName}");
                        //$setMethodParam1->setDefaultValue('null');
                        $setMethod->addParam($setMethodParam1);

                        //$setMethod->addContentLine("\$this->{$fieldName} = \${$refLcModelName} ? \${$refLcModelName}->get{$refPropertyName}() : null;");
                        $setMethod->addContentLine("\$this->{$fieldName} = \${$refLcModelName}->get{$refPropertyName}();");

                        $setMethod->addContentLine('');
                        $setMethod->addContentLine('return $this;');
                        $setMethod->setReturn('$this');

                        $propertyClass->addMethod($setMethod);
                    }
                }
            }

            $relations = isset($config['relations']['hasManyToMany']) ? $config['relations']['hasManyToMany'] : [];
            foreach ($relations as $fieldName => $tables) {
                foreach ($tables as $tableName => $value) {
                    $refTableName      = $tableName;
                    $refFieldName      = $value['column'];
                    $refModelName      = \Peanut\Text::camelize($refTableName);
                    $refLcModelName    = \Peanut\Text::lcfirstCamelize($refTableName);
                    $refPropertyName   = \Peanut\Text::camelize($refFieldName);

                    if (1 === preg_match('#parent_#', $fieldName)) {
                        continue;
                    }
                    //$varNameMany       = SbUtils::getNameMany(lcfirst($refModelName));
                    foreach ($value['destination'] as $destinationKey => $destination) {
                        foreach ($destination as $destinationTable => $dest) {
                            $desModelName = \Peanut\Text::camelize($dest['table']);

                            //$aliasModelName = ''.\Peanut\Text::pluralize($refModelName);
                            $aliasModelName = 'Through'.\Peanut\Text::pluralize($desModelName);

                            $getMethod       = new CreateMethod('get'.''.$aliasModelName);
                            $getMethod->addContentLine("return \$this->getRelated('{$aliasModelName}', \$parameters);");
                            $getMethod->setReturn("\Phalcon\Mvc\Model\Resultset\Simple");
                            $getMethodParam1 = new CreateMethodParam('parameters');
                            $getMethodParam1->setType('array');
                            $getMethodParam1->setDefaultValue('[]');
                            $getMethod->addParam($getMethodParam1);
                            $propertyClass->addMethod($getMethod);

                            $setMethod       = new CreateMethod('add'.'Through'.$desModelName);
                            $setMethod->addContentLine("\$this->setRelated('{$aliasModelName}', \${$refLcModelName});");
                            $setMethod->addContentLine('');
                            $setMethod->addContentLine('return $this;');
                            $setMethodParam1 = new CreateMethodParam($refLcModelName);
                            $setMethodParam1->setType("{$this->namespace}\\{$refModelName}");
                            $setMethod->addParam($setMethodParam1);
                            $setMethod->setReturn('$this');
                            $propertyClass->addMethod($setMethod);

                            /*
                            $deleteMethod = new CreateMethod('delete'.$refModelName);
                            $deleteMethod->addContentLine("\$this->{\$intermediateModel}->delete(function(\$object) use (\${$refLcModelName}) {");
                            $deleteMethod->addContentLine("    /** @var \\{$this->namespace}\\{\$intermediateModel} \$object *"."/");
                            $deleteMethod->addContentLine("    return \$object->get{$refPropertyName}() === \${$refLcModelName}->getId();");
                            $deleteMethod->addContentLine('});');
                            $deleteMethod->addContentLine('return $this;');
                            $deleteMethodParam1 = new CreateMethodParam($refLcModelName);
                            $deleteMethodParam1->setType("{$this->namespace}\\{$refModelName}");
                            $deleteMethod->addParam($deleteMethodParam1);
                            $deleteMethod->setReturn('$this');
                            $propertyClass->addMethod($deleteMethod);
                            */
                        }
                    }
                }
            }

            if ($this->isMetaData) {
                $propertyClass->addMethod($this->getMetaData($config));
            }

            $propertyFilename = $this->targetFolder.'/Properties/'.$modelName.'.php';
            $this->createFile($propertyFilename, (string)$propertyClass);
            echo 'created file '.$propertyFilename.PHP_EOL;

            $filename = $this->targetFolder.'/'.$modelName.'.php';
            if (false === file_exists($filename)) {
                $this->createFile($filename, (string)$workClass);
                echo 'created file '.$filename.PHP_EOL;
            }
        }
    }
    public function getSourceMethod($tableName)
    {
        $getSourceMethod = new CreateMethod('getSource');
        $getSourceMethod->setScope('public');
        $getSourceMethod->addDescription('table name');
        $getSourceMethod->addContentLine("return '{$tableName}';");

        return $getSourceMethod;
    }
    public function getMetaData($config)
    {
        $metaDataMethod = new CreateMethod('metaData');

        $metaDataMethod->addContentLine('return [');
        //Every column in the mapped table
        $metaDataMethod->addContentLine('    MetaData::MODELS_ATTRIBUTES => [');
        $max = $this->getMax($config['allFields'], 'fieldName');

        foreach ($config['allFields'] as $field) {
            $padStr = str_pad("'".$field['fieldName']."'", $max + 2, ' ', STR_PAD_RIGHT);
            $metaDataMethod->addContentLine("        '".$field['fieldName']."',");
        }
        $metaDataMethod->addContentLine('    ],');

        //Every column part of the primary key
        $metaDataMethod->addContentLine('    MetaData::MODELS_PRIMARY_KEY => [');
        $metaDataMethod->addContentLine("        '".$config['primary']['fieldName']."',");
        $metaDataMethod->addContentLine('    ],');

        //Every column that isn't part of the primary key
        $metaDataMethod->addContentLine('    MetaData::MODELS_NON_PRIMARY_KEY => [');
        foreach ($config['fields'] as $field) {
            $metaDataMethod->addContentLine("        '".$field['fieldName']."',");
        }
        $metaDataMethod->addContentLine('    ],');

        //Every column that doesn't allows null values
        $metaDataMethod->addContentLine('    MetaData::MODELS_NOT_NULL => [');
        foreach ($config['allFields'] as $field) {
            if (false === $field['isNull']) {
                $metaDataMethod->addContentLine("        '".$field['fieldName']."',");
            }
        }
        $metaDataMethod->addContentLine('    ],');

        //Every column and their data types
        $metaDataMethod->addContentLine('    MetaData::MODELS_DATA_TYPES => [');
        $max = $this->getMax($config['allFields'], 'fieldName');
        foreach ($config['allFields'] as $field) {
            $padStr = str_pad("'".$field['fieldName']."'", $max + 2, ' ', STR_PAD_RIGHT);
            $metaDataMethod->addContentLine('        '.$padStr.' => '.$this->getDataType($field).',');
        }
        $metaDataMethod->addContentLine('    ],');

        //The columns that have numeric data types
        $max = $this->getMax($config['allFields'], 'fieldName', function ($field) {
            $type   = $this->getType($field['type']);
            switch ($type) {
                case 'decimal':
                case 'float':
                case 'double':
                case 'integer':
                case 'biginteger':
                    return true;
                    break;
                default:
                    break;
            }

            return false;
        });
        $metaDataMethod->addContentLine('    MetaData::MODELS_DATA_TYPES_NUMERIC => [');
        foreach ($config['allFields'] as $field) {
            $padStr = str_pad("'".$field['fieldName']."'", $max + 2, ' ', STR_PAD_RIGHT);
            $type   = $this->getType($field['type']);
            switch ($type) {
                case 'decimal':
                case 'float':
                case 'double':
                case 'integer':
                case 'biginteger':
                    $metaDataMethod->addContentLine('        '.$padStr.' => true,');
                    break;
                default:
                    break;
            }
        }
        $metaDataMethod->addContentLine('    ],');

        //The identity column, use boolean false if the model doesn't have
        //an identity column
        if (true === isset($config['primary']['fieldName'])) {
            $metaDataMethod->addContentLine("    MetaData::MODELS_IDENTITY_COLUMN => '".$config['primary']['fieldName']."',");
        } else {
            $metaDataMethod->addContentLine('    MetaData::MODELS_IDENTITY_COLUMN => false,');
        }

        //How every column must be bound/casted
        $metaDataMethod->addContentLine('    MetaData::MODELS_DATA_TYPES_BIND => [');
        $max = $this->getMax($config['allFields'], 'fieldName');
        foreach ($config['allFields'] as $field) {
            $padStr = str_pad("'".$field['fieldName']."'", $max + 2, ' ', STR_PAD_RIGHT);
            $metaDataMethod->addContentLine('        '.$padStr.' => '.$this->getBindType($field).',');
        }
        $metaDataMethod->addContentLine('    ],');

        //Fields that must be ignored from INSERT SQL statements
        $metaDataMethod->addContentLine('    MetaData::MODELS_AUTOMATIC_DEFAULT_INSERT => [');
        //$metaDataMethod->addContentLine("        'id' => true,");
        $metaDataMethod->addContentLine('    ],');

        //Fields that must be ignored from UPDATE SQL statements
        $metaDataMethod->addContentLine('    MetaData::MODELS_AUTOMATIC_DEFAULT_UPDATE => [');
        //$metaDataMethod->addContentLine("        'id' => true,");
        $metaDataMethod->addContentLine('    ],');
        // 열의 기본값
        $metaDataMethod->addContentLine('    MetaData::MODELS_DEFAULT_VALUES => [');
        $max = $this->getMax($config['allFields'], 'fieldName', function ($field) {
            return strlen($field['default']);
        });
        foreach ($config['allFields'] as $field) {
            if (strlen($field['default'])) {
                $padStr = str_pad("'".$field['fieldName']."'", $max + 2, ' ', STR_PAD_RIGHT);
                $metaDataMethod->addContentLine('        '.$padStr." => '".$field['default']."',");
            }
        }
        $metaDataMethod->addContentLine('    ],');

        // 빈 문자열을 허용하는 필드
        $metaDataMethod->addContentLine('    MetaData::MODELS_EMPTY_STRING_VALUES => [');
        $metaDataMethod->addContentLine('    ],');
        $metaDataMethod->addContentLine('];');
        $metaDataMethod->setReturn('array');

        return $metaDataMethod;
    }
    public function getMax($arr, $key, $func=null)
    {
        $max = 0;
        foreach ($arr as $i => $value) {
            if ($func) {
                $func->bindTo($this);
                if ($func($value)) {
                    $current = strlen($value[$key]);
                } else {
                    $current = 0;
                }
            } else {
                $current = strlen($value[$key]);
            }
            if ($max < $current) {
                $max = $current;
            }
        }

        return $max;
    }
    public function getType($format)
    {
        $tmp  = explode('(', $format);
        $type = $tmp[0];

        switch ($type) {
            case 'boolean':
            case 'timestamp':
            case 'float':
            case 'varchar':
            case 'char':
            case 'text':
            case 'tinyblob':
            case 'blob':
            case 'mediumblob':
            case 'longblob':
            case 'date':
                break;
            case 'double':
            case 'real':
                $type = 'double';
                break;
            case 'dec':
            case 'numeric':
            case 'fixed':
                $type = 'decimal';
                break;
            case 'integer':
            case 'int':
            case 'bit':
            case 'tinyint':
            case 'smallint':
            case 'mediumint':
                $type = 'integer';
                break;
            case 'datetime':
            case 'datetimez':
                $type = 'datetime';
                break;
            case 'time':
            case 'mediumtext':
            case 'longtext':
            case 'binary':
            case 'varbinary':
            case 'tinytext':
            case 'enum':
            case 'set':
                $type = 'text';
                break;
            case 'bigint':
                $type = 'biginteger';
                break;
            default:
                break;
        }

        return $type;
    }
    public function getDataType($field)
    {
        $type = $this->getType($field['type']);
        switch ($type) {
            case 'integer':
                $dataType = 'Column::TYPE_INTEGER';
                break;
            case 'date':
                $dataType = 'Column::TYPE_DATE';
                break;
            case 'varchar':
                $dataType = 'Column::TYPE_VARCHAR';
                break;
            case 'decimal':
                $dataType = 'Column::TYPE_DECIMAL';
                break;
            case 'timestamp':
                $dataType = 'Column::TYPE_TIMESTAMP';
                break;
            case 'datetime':
                $dataType = 'Column::TYPE_DATETIME';
                break;
            case 'char':
                $dataType = 'Column::TYPE_CHAR';
                break;
            case 'float':
                $dataType = 'Column::TYPE_FLOAT';
                break;
            case 'boolean':
                $dataType = 'Column::TYPE_BOOLEAN';
                break;
            case 'double':
                $dataType = 'Column::TYPE_DOUBLE';
                break;
            case 'tinyblob':
                $dataType = 'Column::TYPE_TINYBLOB';
                break;
            case 'blob':
                $dataType = 'Column::TYPE_BLOB';
                break;
            case 'mediumblob':
                $dataType = 'Column::TYPE_MEDIUMBLOB';
                break;
            case 'longblob':
                $dataType = 'Column::TYPE_LONGBLOB';
                break;
            case 'biginteger':
                $dataType = 'Column::TYPE_BIGINTEGER';
                break;
            case 'text':
                $dataType = 'Column::TYPE_TEXT';
                break;
        }

        return $dataType;
    }
    public function getBindType($field)
    {
        $type = $this->getType($field['type']);
        switch ($type) {
            case 'integer':
                $dataType = 'Column::BIND_PARAM_INT';
                break;
            case 'date':
                $dataType = 'Column::BIND_PARAM_STR';
                break;
            case 'varchar':
                $dataType = 'Column::BIND_PARAM_STR';
                break;
            case 'decimal':
                $dataType = 'Column::BIND_PARAM_DECIMAL';
                break;
            case 'timestamp':
                $dataType = 'Column::BIND_PARAM_STR';
                break;
            case 'datetime':
                $dataType = 'Column::BIND_PARAM_STR';
                break;
            case 'char':
                $dataType = 'Column::BIND_PARAM_STR';
                break;
            case 'float':
                $dataType = 'Column::BIND_SKIP';
                break;
            case 'boolean':
                $dataType = 'Column::BIND_PARAM_BOOL';
                break;
            case 'double':
                $dataType = 'Column::BIND_SKIP';
                break;
            case 'tinyblob':
                $dataType = 'Column::BIND_SKIP';
                break;
            case 'blob':
                $dataType = 'Column::BIND_SKIP';
                break;
            case 'mediumblob':
                $dataType = 'Column::BIND_SKIP';
                break;
            case 'longblob':
                $dataType = 'Column::BIND_SKIP';
                break;
            case 'biginteger':
                $dataType = 'Column::BIND_PARAM_INT';
                break;
            case 'text':
                $dataType = 'Column::BIND_PARAM_STR';
                break;
        }

        return $dataType;
    }
    public function getInitializeMethod($config)
    {
        $initializeMethod = new CreateMethod('initialize');
        $initializeMethod->addContentLine('$this->useDynamicUpdate(true);');
        $initializeMethod->addContentLine('$this->setWriteConnectionService(\'master\');');
        $initializeMethod->addContentLine('$this->setReadConnectionService(\'slave1\');');

        //has many
        $relations = isset($config['relations']['hasMany']) ? $config['relations']['hasMany'] : [];
        foreach ($relations as $fieldName => $tables) {
            foreach ($tables as $tableName => $value) {
                $refTableName = $tableName;
                $refFieldName = $value['column'];
                $refModelName = \Peanut\Text::camelize($refTableName);
                if (1 === preg_match('#parent_#', $fieldName)) {
                    continue;
                }
                $this->hasMany($initializeMethod, $fieldName, $refModelName, $refFieldName);
            }
        }

        //has one
        $relations = isset($config['relations']['hasOne']) ? $config['relations']['hasOne'] : [];
        foreach ($relations as $fieldName => $tables) {
            foreach ($tables as $tableName => $value) {
                $refTableName = $tableName;
                $refFieldName = $value['column'];
                $refModelName = \Peanut\Text::camelize($refTableName);
                if (1 === preg_match('#parent_#', $fieldName)) {
                    continue;
                }
                $this->hasOne($initializeMethod, $fieldName, $refModelName, $refFieldName);
            }
        }

        // belong to
        $relations = isset($config['relations']['belongsTo']) ? $config['relations']['belongsTo'] : [];
        foreach ($relations as $fieldName => $tables) {
            foreach ($tables as $tableName => $value) {
                $refTableName = $tableName;
                $refFieldName = $value['column'];
                $refModelName = \Peanut\Text::camelize($refTableName);
                if (1 === preg_match('#parent_#', $fieldName)) {
                    continue;
                }
                $this->belongsTo($initializeMethod, $fieldName, $refModelName, $refFieldName);
            }
        }
        // belong to
        $relations = isset($config['relations']['belongTo']) ? $config['relations']['belongTo'] : [];
        foreach ($relations as $fieldName => $tables) {
            foreach ($tables as $tableName => $value) {
                $refTableName = $tableName;
                $refFieldName = $value['column'];
                $refModelName = \Peanut\Text::camelize($refTableName);
                if (1 === preg_match('#parent_#', $fieldName)) {
                    continue;
                }
                $this->belongsTo($initializeMethod, $fieldName, $refModelName, $refFieldName);
            }
        }
        $relations = isset($config['relations']['hasManyToMany']) ? $config['relations']['hasManyToMany'] : [];
        foreach ($relations as $fieldName => $tables) {
            foreach ($tables as $tableName => $value) {
                $refTableName = $tableName;
                $refFieldName = $value['column'];
                $refModelName = \Peanut\Text::camelize($refTableName);
                //$varNameMany = SbUtils::getNameMany(lcfirst($refModelName));
                foreach ($value['destination'] as $destinationKey => $destination) {
                    foreach ($destination as $destinationTable => $dest) {
                        $desModelName = \Peanut\Text::camelize($dest['table']);

                        //$aliasModelName = ''.\Peanut\Text::pluralize($refModelName);
                        $aliasModelName = 'Through'.\Peanut\Text::pluralize($desModelName);

                        $initializeMethod->addContentLine('$this->hasManyToMany(');
                        $initializeMethod->addContentLine("    '{$fieldName}',");
                        $initializeMethod->addContentLine("    '{$this->namespace}\\{$refModelName}',");
                        $initializeMethod->addContentLine("    '{$refFieldName}',");
                        $initializeMethod->addContentLine("    '{$destinationKey}',");
                        $initializeMethod->addContentLine("    '{$this->namespace}\\{$desModelName}',");
                        $initializeMethod->addContentLine("    '{$dest['column']}',");
                        $initializeMethod->addContentLine('    [');
                        $initializeMethod->addContentLine("        'alias' => '{$aliasModelName}',");
                        if ($this->reusable) {
                            $initializeMethod->addContentLine("        'reusable' => true,");
                        }
                        if ($this->foreignKey) {
                            if ($this->cascade) {
                                $initializeMethod->addContentLine("        'foreignKey' => [");
                                $initializeMethod->addContentLine("            'action' => \Phalcon\Mvc\Model\Relation::ACTION_CASCADE,");
                                //$initializeMethod->addContentLine("            'message' => 'Cannot delete table a as it still contains table b\'s'");
                                $initializeMethod->addContentLine('        ]');
                            } else {
                                $initializeMethod->addContentLine("        'foreignKey' => true");
                            }
                        }

                        $initializeMethod->addContentLine('    ]');
                        $initializeMethod->addContentLine(');');
                    }
                }
            }
        }

        return $initializeMethod;
    }
    public function belongsTo($initializeMethod, $fieldName, $refModelName, $refFieldName)
    {
        $initializeMethod->addContentLine('$this->belongsTo(');
        $this->relation($initializeMethod, $fieldName, $refModelName, $refFieldName, $refModelName);
    }
    public function hasMany($initializeMethod, $fieldName, $refModelName, $refFieldName)
    {
        $aliasModelName = \Peanut\Text::pluralize($refModelName);
        $initializeMethod->addContentLine('$this->hasMany(');
        $this->relation($initializeMethod, $fieldName, $refModelName, $refFieldName, $aliasModelName);
    }
    public function hasOne($initializeMethod, $fieldName, $refModelName, $refFieldName)
    {
        $initializeMethod->addContentLine('$this->hasOne(');
        $this->relation($initializeMethod, $fieldName, $refModelName, $refFieldName, $refModelName);
    }
    public function hasManyToMany($initializeMethod, $fieldName, $refModelName, $refFieldName)
    {
        $aliasModelName = \Peanut\Text::pluralize($refModelName);
        $initializeMethod->addContentLine('$this->hasManyToMany(');
        $this->relation($initializeMethod, $fieldName, $refModelName, $refFieldName, $aliasModelName);
    }
    public function relation($initializeMethod, $fieldName, $refModelName, $refFieldName, $aliasModelName)
    {
        $initializeMethod->addContentLine("    '{$fieldName}',");
        $initializeMethod->addContentLine("    '{$this->namespace}\\{$refModelName}',");
        $initializeMethod->addContentLine("    '{$refFieldName}',");
        $initializeMethod->addContentLine('    [');
        $initializeMethod->addContentLine("        'alias' => '{$aliasModelName}',");
        if ($this->reusable) {
            $initializeMethod->addContentLine("        'reusable' => true,");
        }
        if ($this->foreignKey) {
            if ($this->cascade) {
                $initializeMethod->addContentLine("        'foreignKey' => [");
                $initializeMethod->addContentLine("            'action' => \Phalcon\Mvc\Model\Relation::ACTION_CASCADE,");
                //$initializeMethod->addContentLine("            'message' => 'Cannot delete table a as it still contains table b\'s'");
                $initializeMethod->addContentLine('        ]');
            } else {
                $initializeMethod->addContentLine("        'foreignKey' => true");
            }
        }
        $initializeMethod->addContentLine('    ]');
        $initializeMethod->addContentLine(');');
    }

    public function getFiles($dir)
    {
        $files;
        if ($handle = opendir($dir)) {
            while (false !== ($file = readdir($handle))) {
                if ($file != '.' && $file != '..') {
                    $files[] = $file;
                }
            }
            closedir($handle);
        }

        return $files;
    }

    public function createFile($filepath, $message)
    {
        try {
            $isInFolder = preg_match("/^(.*)\/([^\/]+)$/", $filepath, $filepathMatches);
            if ($isInFolder) {
                $dir      = $filepathMatches[1];
                $fileName = $filepathMatches[2];
                if (!is_dir($dir)) {
                    $dir_p = explode('/', $dir);
                    for ($a = 1; $a <= count($dir_p); $a++) {
                        @mkdir(implode('/', array_slice($dir_p, 0, $a)));
                    }
                }
            }

            return file_put_contents($filepath, $message);
        } catch (Exception $e) {
            echo "ERR: error writing '$message' to '$filepath', ".$e->getMessage();
        }
    }
    private function _bind(\Closure $callback) : \Closure
    {
        return $callback->bindTo($this);
    }
}
