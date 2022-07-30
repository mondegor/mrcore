<?php declare(strict_types=1);
namespace mrcore\testing;
use ReflectionException;
use ReflectionMethod;

class PrivateMethod
{
    private object $_object;
    private ReflectionMethod $_method;

    public function __construct(object $object, string $methodName)
    {
        $class = get_class($object);

        do
        {
            try
            {
                $method = new ReflectionMethod($class, $methodName);
                $method->setAccessible(true);
            }
            catch (ReflectionException $e)
            {
                if (str_contains($e->getMessage(), ' does not exist'))
                {
                    $class = get_parent_class($class);

                    if (false !== $class)
                    {
                        continue;
                    }
                }

                throw $e;
            }

            break;
        }
        while(true);

        $this->_object = &$object;
        $this->_method = &$method;
    }

    public function invoke(...$args)
    {
        return $this->_method->invokeArgs($this->_object, $args);
    }

}