<?php declare(strict_types=1);
namespace mrcore\testing;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use MrDebug;

require_once 'mrcore/MrDebug.php';

class Snapshot
{

    private static array $snapshots = [
        'env' => [],
        'globals' => [],
        'classes' => [],
    ];

    #################################### Methods #####################################

    public static function storeEnv(array $names): void
    {
        $snapshot = [];

        foreach ($names as $name)
        {
            $snapshot[$name] = (getenv($name, true) ?: getenv($name));
        }

        if (empty($snapshot))
        {
            if (MrDebug::isGroupEnabled('mrcore:0'))
            {
                echo sprintf("ENV :: Snapshot is empty for vars: %s. Skipped...\n", implode(', ', $names));
            }

            return;
        }

        self::$snapshots['env'][] = $snapshot;

        if (MrDebug::isGroupEnabled('mrcore:0'))
        {
            echo sprintf("ENV :: Created snapshot for vars: %s\n", implode(', ', $names));
            MrDebug::dump($snapshot);
            echo "\n";
        }
    }

    public static function restoreEnv(): void
    {
        foreach (self::$snapshots['env'] as $snapshot)
        {
            foreach ($snapshot as $name => $value)
            {
                putenv($name . '=' . (false === $value ? '' : $value));
            }
        }

		if (MrDebug::isGroupEnabled('mrcore:0'))
        {
            echo "The stored vars of ENV have been restored\n";
        }
    }

    public static function storeSuperglobal(string $superglobalName, array $globalNames): void
    {
        $snapshot = [];
        $superglobal = &self::_getSuperglobal($superglobalName);

        foreach ($globalNames as $globalName)
        {
            if (!array_key_exists($globalName, $superglobal))
            {
                $snapshot[$globalName] = 'MRSNAPSHOT_VALUE_NULL';
                continue;
            }

            if (!self::_checkVarType($superglobal[$globalName]))
            {
                trigger_error(sprintf('The value of %s[%s] was skipped for store', $superglobalName, $globalName), E_USER_ERROR);
            }

            /* @noinspection UnserializeExploitsInspection */
            $snapshot[$globalName] = unserialize(serialize($superglobal[$globalName]));
        }

        if (empty($snapshot))
        {
            if (MrDebug::isGroupEnabled('mrcore:0'))
            {
                echo sprintf("%s :: Snapshot is empty for global vars: %s. Skipped...\n", $superglobalName, implode(', ', $globalNames));
            }

            return;
        }

        self::$snapshots['globals'][$superglobalName][] = $snapshot;

        ##################################################################################

		if (MrDebug::isGroupEnabled('mrcore:0'))
        {
            echo sprintf("%s :: Created snapshot for global vars: %s\n", $superglobalName, implode(', ', $globalNames));
            MrDebug::dump($snapshot);
            echo "\n";
        }
    }

    public static function restoreSuperglobal(string $superglobalName): void
    {
        if (!isset(self::$snapshots['globals'][$superglobalName]))
        {
            return;
        }

        $superglobal = &self::_getSuperglobal($superglobalName);

        foreach (self::$snapshots['globals'][$superglobalName] as $snapshot)
        {
            foreach ($snapshot as $globalName => $value)
            {
                if ('MRSNAPSHOT_VALUE_NULL' === $value)
                {
                    // $superglobal[$globalName] = null;
                    unset($superglobal[$globalName]);
                }
                else
                {
                    $superglobal[$globalName] = $value;
                }
            }
        }

        unset(self::$snapshots['globals'][$superglobalName]);

		if (MrDebug::isGroupEnabled('mrcore:0'))
        {
            echo sprintf("The stored global vars of %s have been restored\n", $superglobalName);
        }
    }

    /**
     * @param string $className
     */
    public static function storeStaticProperties(string $className): void
    {
        $snapshot = [];

        try
        {
            $class = new ReflectionClass($className);

            foreach ($class->getProperties() as $property)
            {
                if ($property->isStatic())
                {
                    $name = $property->getName();
                    $property->setAccessible(true);
                    $value = $property->getValue();

                    if (!self::_checkVarType($value))
                    {
                        trigger_error(sprintf('The value of %s::%s was skipped for store', $className, $name), E_USER_ERROR);
                    }

                    /* @noinspection UnserializeExploitsInspection */
                    $snapshot[$name] = unserialize(serialize($value));
                }
            }
        }
        catch (ReflectionException $e) { }

        if (empty($snapshot))
        {
            if (MrDebug::isGroupEnabled('mrcore:0'))
            {
                echo sprintf("%s :: Snapshot for static properties: %s. Skipped...\n", $className, implode(', ', array_keys($snapshot)));
            }

            return;
        }

        self::$snapshots['classes'][$className] = $snapshot;

        ##################################################################################

		if (MrDebug::isGroupEnabled('mrcore:0'))
        {
            echo sprintf("%s :: Created snapshot for static properties: %s\n", $className, implode(', ', array_keys($snapshot)));
            MrDebug::dump($snapshot);
            echo "\n";
        }
    }

    /**
     * @param string $className
     */
    public static function restoreStaticProperties(string $className): void
    {
        if (!isset(self::$snapshots['classes'][$className]))
        {
            return;
        }

        foreach (self::$snapshots['classes'][$className] as $name => $value)
        {
            try
            {
                $property = new ReflectionProperty($className, $name);
                $property->setAccessible(true);
                $property->setValue($value);
            }
            catch (ReflectionException $e) { }
        }

        unset(self::$snapshots['classes'][$className]);

        ##################################################################################

		if (MrDebug::isGroupEnabled('mrcore:0'))
        {
            echo sprintf("The stored static properties of %s have been restored\n", $className);
        }
    }

    public static function restoreAll(): void
    {
        self::restoreEnv();

        foreach (array_keys(self::$snapshots['globals']) as $superglobalName)
        {
            self::restoreSuperglobal($superglobalName);
        }

        foreach (array_keys(self::$snapshots['classes']) as $className)
        {
            self::restoreStaticProperties($className);
        }
    }

    private static function _checkVarType($value): bool
    {
        return (null === $value || is_scalar($value) || is_array($value));
    }

    private static function &_getSuperglobal(string $name): array
    {
        $result = [];

        switch ($name)
        {
            case '$_COOKIE':
                $result = &$_COOKIE;
                break;

            case '$_ENV':
                $result = &$_ENV;
                break;

            case '$_REQUEST':
                $result = &$_REQUEST;
                break;

            case '$_SERVER':
                $result = &$_SERVER;
                break;

            default:
                trigger_error(sprintf('The name %s of the specified superglobal is not supported', $name), E_USER_ERROR);
                break;
        }

        return $result;
    }

}