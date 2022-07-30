<?php declare(strict_types=1);
namespace mrcore\di;
use Psr\Container\ContainerInterface;

interface ObjectFactoryInterface
{

    public static function createObject(string $objectClass, ContainerInterface $container): object;

}