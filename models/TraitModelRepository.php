<?php
namespace mrcore\models;
use RuntimeException;

/**
 * Данный трейд даёт возможность классу работать с модельными репозиториями.
 *
 * @author     Andrey J. Nazarov <mondegor@gmail.com>
 * @package    mrcore/models
 */
trait TraitModelRepository
{
    /**
     * Кэш для уже созданных модельных репозиториев.
     *
     * @var    array [string => &ModelRepository, ...]
     */
    private array $_repositoryCache = [];

    #################################### Methods #####################################

    /**
     * Возвращается репозиторий модельных объектов.
     *
     * @param      string  $source OPTIONAL
     * @param      bool    $fromCache OPTIONAL
     * @return     ModelRepository
     * @throws     RuntimeException
     */
    public function &getModelRepository(string $source = null, bool $fromCache = true): ModelRepository
    {
        if (null === $source)
        {
            $source = ModelRepository::class;
        }

        if (!isset($this->_repositoryCache[$source]))
        {
            require_once strtr(ltrim($source, '\\'), '\\', '/') . '.php';

            if (!class_exists($source, false))
            {
                throw new RuntimeException(sprintf('Class %s is not found', $source));
            }

            $fromCache = false;
        }

        if (!$fromCache)
        {
            $this->_repositoryCache[$source] = new $source();
        }

        return $this->_repositoryCache[$source];
    }

}