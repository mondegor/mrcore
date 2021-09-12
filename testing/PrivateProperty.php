<?php declare(strict_types=1);
namespace mrcore\testing;
use ReflectionException;
use ReflectionProperty;

class PrivateProperty
{
    private object $_object;
    private ReflectionProperty $_property;

    public function __construct(object $object, string $propertyName)
    {
        $class = get_class($object);

        do
        {
            try
            {
                $property = new ReflectionProperty($class, $propertyName);
                $property->setAccessible(true);
            }
            catch (ReflectionException $e)
            {
                if (false !== strpos($e->getMessage(), ' does not exist'))
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
        $this->_property = &$property;
    }

    public function getValue()
    {
        return $this->_property->getValue($this->_object);
    }

    public function setValue($value): void
    {
        $this->_property->setValue($this->_object, $value);
    }

}