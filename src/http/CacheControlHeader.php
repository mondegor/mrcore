<?php declare(strict_types=1);
namespace mrcore\http;

/**
 * Реализация "ленивого" соединения с ресурсом, реальное соединение
 * происходит только в момент вызова метода getConnection().
 *
 * @author  Andrey J. Nazarov
 */
class CacheControlHeader
{

    /**
     * @inheritdoc
     */
    public function setCacheControl(array $options): ActionResponseInterface
    {
        $cacheControl = $this->response->getCacheControl();

        foreach ($options as $directive => $value)
        {
            $cacheControl->add($directive, $value);
        }
    }
    /**
     * @inheritdoc
     */
    public function setCache(array $options): ActionResponseInterface
    {
        $setPublic = false;

        if (isset($options['etag']))
        {
            if (null === $options['etag'])
            {
                $this->response->remove('Etag');
            }
            else
            {
                if (!str_starts_with($options['etag'], '"'))
                {
                    $etag = '"' . $options['etag'] . '"';
                }

                $this->response->addHeader('ETag', $options['etag'], true);
            }

        }

        if (isset($options['last_modified']))
        {
            assert(is_int($options['last_modified']) && $options['last_modified'] >= 0);
            $this->response->addHeader('Last-Modified', gmdate('D, d M Y H:i:s', $options['last_modified']) . ' GMT');
        }

        if (isset($options['max_age']))
        {
            assert(is_int($options['max_age']));
            $this->response->addCacheControlDirective('max-age', $options['max_age']);
        }

        if (isset($options['s_maxage'])) {
            $setPublic = true;
            $this->response->addCacheControlDirective('s-maxage', $options['s_maxage']);
        }

        if (isset($options['stale_while_revalidate'])) {
            $this->response->addCacheControlDirective('stale-while-revalidate', $options['stale_while_revalidate']);
        }

        if (isset($options['stale_if_error']))
        {
            $this->response->addCacheControlDirective('stale-if-error', $options['stale_if_error']);
        }

        foreach (['must_revalidate', 'no_cache', 'no_store', 'no_transform', 'public', 'private', 'proxy_revalidate', 'immutable'] as $directive)
        {
            if (isset($options[$directive]))
            {
                if ($options[$directive])
                {
                    $this->response->addCacheControlDirective(str_replace('_', '-', $directive));
                }
            }
        }



        if (isset($options['public'])) {
            if ($options['public']) {
                $this->headers->addCacheControlDirective('public');
                $this->headers->removeCacheControlDirective('private');
            } else {
                $this->setPrivate();
            }
        }

        if (isset($options['private'])) {
            if ($options['private']) {
                $this->headers->removeCacheControlDirective('public');
                $this->headers->addCacheControlDirective('private');
            } else {
                $this->setPublic();
            }
        }
    }

}
