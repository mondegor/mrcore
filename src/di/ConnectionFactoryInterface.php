<?php declare(strict_types=1);
namespace mrcore\di;
use mrcore\storage\ConnectionInterface;

interface ConnectionFactoryInterface
{

    public static function createConnection(string $class, string $name, array $params): ConnectionInterface;

}