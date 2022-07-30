<?php declare(strict_types=1);
namespace mrcore\base;
use Psr\Container\ContainerInterface;

abstract class AbstractLazyItem
{

    private $class

    public function create(): object
    {

    }

}