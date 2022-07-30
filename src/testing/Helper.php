<?php declare(strict_types=1);
namespace mrcore\testing;
use ReflectionClass;

/*__class_static__*/ final class Helper
{
    public static function getStaticProperty(string $className, string $propertyName)
    {
        // try
        {
            return (new ReflectionClass($className))->getStaticPropertyValue($propertyName);
        }
        // catch (ReflectionException $e) { }

        // return null;
    }

    public static function getProperty(object $object, string $propertyName)
    {
        // try
        {
            return (new PrivateProperty($object, $propertyName))->getValue();
        }
        // catch (ReflectionException $e) { }

        // return null;
    }

    public static function setProperty(object $object, string $propertyName, $propertyValue): void
    {
        // try
        {
            (new PrivateProperty($object, $propertyName))->setValue($propertyValue);
        }
        // catch (ReflectionException $e);
    }

    public static function setStaticProperty(string $className, string $propertyName, $propertyValue): void
    {
        // try
        {
            (new ReflectionClass($className))->setStaticPropertyValue($propertyName, $propertyValue);
        }
        // catch (ReflectionException $e) { }
    }

    public static function mergeProperty(object $object, string $propertyName, array $propertyValue): void
    {
        // try
        {
            $property = new PrivateProperty($object, $propertyName);
            $property->setValue(array_replace($property->getValue(), $propertyValue));
        }
        // catch (ReflectionException $e) { }
    }

    public static function setProperties(object $object, array $properties): void
    {
        foreach ($properties as $name => $value)
        {
            self::setProperty($object, $name, $value);
        }
    }

    //public static function invoke(object $object, string $methodName, ...$args)
    //{
    //    // try
    //    {
    //        return (new PrivateMethod($object, $methodName))->invoke(...$args);
    //    }
    //    // catch (ReflectionException $e) { }
    //
    //    // return null;
    //}

}