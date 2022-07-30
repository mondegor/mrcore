<?php declare(strict_types=1);
namespace mrcore\storage\db\mysql;
use mrcore\base\EnumType;
use mrcore\storage\db\AbstractFeatures;

/**
 * Вспомагательный объект расширяющий возможности адаптера соединения MYSQL.
 *
 * @author  Andrey J. Nazarov
 */
class MysqlFeatures extends AbstractFeatures
{
    /**
     * @inheritdoc
     */
    public function createCopyTableStructure(string $tableName, string $newTableName, int $options = null): bool
    {
        $query = $this->connDb->execQuery(sprintf("SHOW CREATE TABLE `%s`", $this->connDb->escape($tableName)));

        if (false === $query)
        {
            return false;
        }

        if (null === $options)
        {
            $options = 0;
        }

        $tmp = $query->fetch(false);

        $tmp = preg_replace('/ AUTO_INCREMENT=[0-9]+ /i', ' ', $tmp[1]);
        $temporary = (($options & 1/*TEMPORARY_TABLE*/) > 0 ? ' TEMPORARY ' : '');
        $tmp = str_replace(sprintf('CREATE TABLE `%s`', $tableName), sprintf('CREATE%sTABLE `%s`', $temporary, $newTableName), $tmp);

        if ($options > 1)
        {
            if (($options & 2/*INDEX_PRIMARY_KEY*/) > 0)
            {
                $tmp = preg_replace('/  PRIMARY KEY \([a-z0-9\-_,`()]+\),?/i', ' ', $tmp);
            }

            if (($options & 4/*INDEX_UNIQUE_KEY*/) > 0)
            {
                $tmp = preg_replace('/  UNIQUE KEY `[a-z0-9\-_]+` \([a-z0-9\-_,`()]+\),?/i', ' ', $tmp);
            }

            if (($options & 8/*INDEX_KEY*/) > 0)
            {
                $tmp = preg_replace('/  KEY `[a-z0-9\-_]+` \([a-z0-9\-_,`()]+\),?/i', ' ', $tmp);
            }

            if (($options & 16/*INDEX_FULLTEXT_KEY*/) > 0)
            {
                $tmp = preg_replace('/  FULLTEXT KEY `[a-z0-9\-_]+` \([a-z0-9\-_,`]+\),?/i', ' ', $tmp);
            }

            $tmp = preg_replace('/,[ \n]*\)/i', "\n)", $tmp);
        }

        $query->freeResult();

        return $this->connDb->execQuery($tmp);
    }

    /**
     * @inheritdoc
     */
    public function getTableStructure(string $tableName): ?array
    {
        $query = $this->connDb->execQuery(sprintf("SHOW FULL FIELDS FROM `%s`", $this->connDb->escape($tableName)));

        if (false === $query)
        {
            return null;
        }

        $structure = array
        (
            'primaryKey' => [],
            'fields' => [],
        );

        while ($row = $query->fetch())
        {
            if ('PRI' === $row['Key'])
            {
                $structure['primaryKey'][] = $row['Field'];
            }

            $type = $this->_parseType($row['Type']);

            $structure['fields'][$row['Field']] = array
            (
                'type'      => $type['type'],
                'dbtype'    => $type['dbtype'],
                'length'    => $type['length'],
                'isNull'    => ('YES' === $row['Null']),
                'values'    => $type['values'],
                'default'   => $this->_getDefaultValue($type['type'], $row['Default']),
                'isPrimary' => ('PRI' === $row['Key']),
                'isAutoInc' => ('auto_increment' === $row['Extra']),
            );
        }

        $query->freeResult();

        return $structure;
    }

    ##################################################################################

    /**
     * Определение типа на основе заданной строки.
     *
     * @return  array [type => int, dbtype => string, values => array|null, length => int]
     */
    protected function _parseType(string $string): array
    {
        $type = array
        (
            'type'   => 0,
            'dbtype' => '',
            'values' => null,
            'length' => null,
        );

        if (preg_match('/([a-z]+)\(?([0-9a-z,\'_]*)\)?/i', $string, $m) > 0)
        {
            $type['dbtype'] = $m[1];

            switch ($m[1])
            {
                case 'tinyint':
                    $type['type'] = (1 === $m[2] ? EnumType::BOOL : EnumType::INT);
                    break;

                case 'smallint':
                case 'int':
                case 'mediumint':
                case 'bigint':
                    $type['type'] = EnumType::INT;
                    break;

                case 'char':
                case 'varchar':
                case 'varbinary':
                    $type['type'] = EnumType::STRING;
                    $type['length'] = (int)$m[2];
                    break;

//                case 'tinytext':
//                case 'tinyblob':
//                    $type['type'] = EnumType::STRING;
//                    $type['length'] = 255;
//                    break;

                case 'text':
                case 'blob':
                    $type['type'] = EnumType::STRING;
                    $type['length'] = 65535;
                    break;

                case 'mediumtext':
                case 'mediumblob':
                    $type['type'] = EnumType::STRING;
                    $type['length'] = 16777215;
                    break;

//                case 'longtext':
//                case 'longblob':
//                    $type['type'] = EnumType::STRING;
//                    $type['length'] = 4294967295;
//                    break;

                case 'datetime':
                    $type['type'] = EnumType::DATETIME;
                    $type['length'] = 19;
                    break;

                case 'date':
                    $type['type'] = EnumType::DATE;
                    $type['length'] = 10;
                    break;

                case 'time':
                    $type['type'] = EnumType::TIME;
                    $type['length'] = 8;
                    break;

                case 'enum':
                    $type['type']   = EnumType::ENUM;
                    $type['values'] = explode(',', str_replace("'", '', $m[2]));
                    break;

                case 'set':
                    $type['type']   = EnumType::ESET;
                    $type['values'] = explode(',', str_replace("'", '', $m[2]));
                    break;

                case 'float':
                case 'double':
                case 'decimal':
                    $type['type'] = EnumType::FLOAT;
                    $type['dbtype'] = $string;
                    break;

                default:
                    trigger_error(sprintf('Unknown type %s', $m[1]), E_USER_NOTICE);
                    break;
            }
        }

        return $type;
    }

    /**
     * Возвращается значение по умолчанию.
     */
    protected function _getDefaultValue(int $type, ?string $default): string|int|bool|float|null
    {
        if (null !== $default)
        {
            switch ($type)
            {
                case EnumType::STRING:
                    $default = '';
                    break;

                case EnumType::DATETIME:
                case EnumType::DATE:
                case EnumType::TIME:
                case EnumType::ENUM:
                case EnumType::ESET:
                    break;

                case EnumType::INT:
                    $default = (int)$default;
                    break;

                case EnumType::FLOAT:
                    $default = (float)$default;
                    break;

                case EnumType::BOOL:
                    $default = ('1' === $default);
                    break;

                default:
                    trigger_error(sprintf('Unknown type %s', $type), E_USER_NOTICE);
                    break;
            }
        }

        return $default;
    }

}