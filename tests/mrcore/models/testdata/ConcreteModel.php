<?php declare(strict_types=1);
namespace mrcore\models\testdata;
use mrcore\models\AbstractModel;
use mrcore\services\VarService;

require_once 'mrcore/models/AbstractModel.php';
require_once 'mrcore/services/VarService.php';

class ConcreteModel extends AbstractModel
{

    protected string $_primaryName = 'primaryId';

    protected array $_fields = array
    (
        'primaryId'      => ['dbName' => 'tbl.primary_id', 'type' => VarService::T_INT],
        'boolField'      => ['dbName' => 'bool_field',  'type' => VarService::T_BOOL],
        'intField'       => ['dbName' => 'int_field',  'type' => VarService::T_INT],
        'floatField'     => ['dbName' => 'float_field',  'type' => VarService::T_FLOAT],
        'timeField'      => ['dbName' => 'time_field',  'type' => VarService::T_TIME],
        'dateField'      => ['dbName' => 'date_field',  'type' => VarService::T_DATE],
        'datetimeField'  => ['dbName' => 'datetime_field',  'type' => VarService::T_DATETIME],
        'timestampField' => ['dbName' => 'timestamp_field',  'type' => VarService::T_TIMESTAMP],
        'stringField'    => ['dbName' => 'string_field',  'type' => VarService::T_STRING],
        'enumField'      => ['dbName' => 'enum_field',  'type' => VarService::T_ENUM],
        'esetField'      => ['dbName' => 'eset_field',  'type' => VarService::T_ESET],
        'arrayField'     => ['dbName' => 'array_field',  'type' => VarService::T_ARRAY],
        'ipField'        => ['dbName' => 'ip_field',  'type' => VarService::T_IP],
        'iplongField'    => ['dbName' => 'iplong_field',  'type' => VarService::T_IPLONG],

        'boolFieldNull'      => ['dbName' => 'bool_field',  'type' => VarService::T_BOOL, 'null' => true],
        'intFieldNull'       => ['dbName' => 'int_field',  'type' => VarService::T_INT, 'null' => true],
        'floatFieldNull'     => ['dbName' => 'float_field',  'type' => VarService::T_FLOAT, 'null' => true],
        'timeFieldNull'      => ['dbName' => 'time_field',  'type' => VarService::T_TIME, 'null' => true],
        'dateFieldNull'      => ['dbName' => 'date_field',  'type' => VarService::T_DATE, 'null' => true],
        'datetimeFieldNull'  => ['dbName' => 'datetime_field',  'type' => VarService::T_DATETIME, 'null' => true],
        'timestampFieldNull' => ['dbName' => 'timestamp_field',  'type' => VarService::T_TIMESTAMP, 'null' => true],
        'stringFieldNull'    => ['dbName' => 'string_field',  'type' => VarService::T_STRING, 'null' => true],
        'enumFieldNull'      => ['dbName' => 'enum_field',  'type' => VarService::T_ENUM, 'null' => true],
        'esetFieldNull'      => ['dbName' => 'eset_field',  'type' => VarService::T_ESET, 'null' => true],
        'arrayFieldNull'     => ['dbName' => 'array_field',  'type' => VarService::T_ARRAY, 'null' => true],
        'ipFieldNull'        => ['dbName' => 'ip_field',  'type' => VarService::T_IP, 'null' => true],
        'iplongFieldNull'    => ['dbName' => 'iplong_field',  'type' => VarService::T_IPLONG, 'null' => true],

        'boolFieldNull2'      => ['dbName' => 'bool_field',  'type' => VarService::T_BOOL, 'null' => true, 'emptyToNull' => false],
        'intFieldNull2'       => ['dbName' => 'int_field',  'type' => VarService::T_INT, 'null' => true, 'emptyToNull' => false],
        'floatFieldNull2'     => ['dbName' => 'float_field',  'type' => VarService::T_FLOAT, 'null' => true, 'emptyToNull' => false],
        'timeFieldNull2'      => ['dbName' => 'time_field',  'type' => VarService::T_TIME, 'null' => true, 'emptyToNull' => false],
        'dateFieldNull2'      => ['dbName' => 'date_field',  'type' => VarService::T_DATE, 'null' => true, 'emptyToNull' => false],
        'datetimeFieldNull2'  => ['dbName' => 'datetime_field',  'type' => VarService::T_DATETIME, 'null' => true, 'emptyToNull' => false],
        'timestampFieldNull2' => ['dbName' => 'timestamp_field',  'type' => VarService::T_TIMESTAMP, 'null' => true, 'emptyToNull' => false],
        'stringFieldNull2'    => ['dbName' => 'string_field',  'type' => VarService::T_STRING, 'null' => true, 'emptyToNull' => false],
        'enumFieldNull2'      => ['dbName' => 'enum_field',  'type' => VarService::T_ENUM, 'null' => true, 'emptyToNull' => false],
        'esetFieldNull2'      => ['dbName' => 'eset_field',  'type' => VarService::T_ESET, 'null' => true, 'emptyToNull' => false],
        'arrayFieldNull2'     => ['dbName' => 'array_field',  'type' => VarService::T_ARRAY, 'null' => true, 'emptyToNull' => false],
        'ipFieldNull2'        => ['dbName' => 'ip_field',  'type' => VarService::T_IP, 'null' => true, 'emptyToNull' => false],
        'iplongFieldNull2'    => ['dbName' => 'iplong_field',  'type' => VarService::T_IPLONG, 'null' => true, 'emptyToNull' => false],

        'intReadonlyField'  => ['dbName' => 'int_field',  'type' => VarService::T_INT, 'readonly' => true],
        'string64Field'     => ['dbName' => 'string_field',  'type' => VarService::T_STRING, 'length' => 64],
        'stringSelectField' => ['dbName' => 'string_field', 'dbSelect' => "CONCAT('#', string_field, '#')", 'type' => VarService::T_STRING, 'length' => 64],

        'tagHelperField'     => ['type' => VarService::T_INT, 'tag' => AbstractModel::TAG_HELPER],
        'tagComplexField'    => ['type' => VarService::T_STRING, 'tag' => AbstractModel::TAG_COMPLEX],
        'tagCalculatedField' => ['type' => VarService::T_INT, 'tag' => AbstractModel::TAG_CALCULATED],
    );

    protected function _getFrom(string $selectNames): string
    {
        return 'table1 tbl';
    }

    protected function _create(array &$props): int
    {
        return 1;
    }

    protected function _store(array &$props): bool
    {
        return true;
    }

    protected function _remove(bool $forceRemove = false): bool
    {
        return true;
    }

    public function getPropertiesForTest(): array
    {
        return $this->_props;
    }

}