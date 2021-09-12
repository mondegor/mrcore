<?php declare(strict_types=1);
namespace mrcore\db\testdata;
use Closure;
use mrcore\db\Adapter;
use mrcore\debug\DbProfiler;

require_once 'mrcore/db/Adapter.php';
require_once 'mrcore/db/Query.php';
require_once 'mrcore/debug/DbProfiler.php';

class ConcreteAdapter extends Adapter
{

    public static ?DbProfiler $dbProfile = null;

    public function escape($value, bool $like = false): string { }

    public function execQuery(string $sql, $bind = []) { }

    public function fetch($resource, bool $assoc = true): array { }

    public function fetchAll(string $sql, $bind = [], Closure $cbHandler = null): array { }

    public function fetchCol(string $sql, $bind = [], bool $mirror = false): array { }

    public function fetchPairs(string $sql, $bind = []): array { }

    public function fetchRow(string $sql, $bind = [], bool $assoc = true): array { }

    public function fetchOne(string $sql, $bind = []): string { }

    public function getNumRows($resource): int { }

    public function getLastInsertedId(): int { }

    public function getAffectedRows(): int { }

    public function insert(string $table, array $set, bool $escape = true): int { }

    public function update(string $tableName, array $set, $where, bool $escape = true): int { }

    public function delete(string $table, $where, bool $confirmRemove = false): int { }

    public function freeResult($resource) { }

    public function getTableStructure(string $tableName): array { }

    protected function _sqlTriggerError(string $sql): void { }

    public function isConnection(): bool { }

    public function close(): void { }

    protected function _createDbProfiler(): DbProfiler
    {
        return self::$dbProfile;
    }

    public function testEscapeValue($value): string
    {
        return $this->_escapeValue($value);
    }

    public function testSetExpr(array $set, bool $escape): string
    {
        return $this->_setExpr($set, $escape);
    }

    public function testWhereExpr(array $where): string
    {
        return $this->_whereExpr($where);
    }

}

class ConcreteAdapterEscape extends ConcreteAdapter
{

    public function escape($value, bool $like = false): string
    {
        return $value;
    }

}

class ConcreteAdapterEscapeValue extends ConcreteAdapter
{

    protected function _escapeValue($value): string
    {
        return (string)$value;
    }

}