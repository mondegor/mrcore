<?php declare(strict_types=1);
namespace mrcore\models\testdata;
use mrcore\models\HelperFieldSet;

require_once 'mrcore/models/HelperFieldSet.php';

class ConcreteHelperFieldSet extends HelperFieldSet
{

    public function testProtectedDbType(string $name): int
    {
        return $this->_dbType($name);
    }

    public function testProtectedIsNull($name): bool
    {
        return $this->_isNull($name);
    }

    public function testProtectedIsEmptyToNull(string $name): bool
    {
        return $this->_isEmptyToNull($name);
    }

    public function testProtectedGetDbValue($value, $zero, bool $isNull, bool $isEmptyToNull)
    {
         return $this->_getDbValue($value, $zero, $isNull, $isEmptyToNull);
    }

}