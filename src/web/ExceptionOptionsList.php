<?php declare(strict_types=1);
namespace mrcore\web;
use Throwable;

/**
 * Exception options list.
 * Применяется для описания исключений, которые необходимо обрабатывать особо при их перехвате.
 *   displayError - разрешается показать реальное сообщение об ошибке клиенту;
 *   errorCode - с каким кодом ошибки отдавать клиенту;
 *   templateName - название шаблона, который будет использован для формирования ошибки клиенту;
 *
 * @author  Andrey J. Nazarov
 *
 * @template  T_EXCEPTION_OPTIONS=array{displayError: bool, errorCode => int, templateName: string}
 */
class ExceptionOptionsList
{
    /**
     * @param  array<string, T_EXCEPTION_OPTIONS>  $exceptions
     * @param  T_EXCEPTION_OPTIONS  $defaultOptions
     */
    public function __construct(private array $exceptions, private array $defaultOptions)
    {
        assert(3 === count($defaultOptions));
    }

    /**
     * Returns the options by the exception object or the class name.
     *
     * @return T_EXCEPTION_OPTIONS
     */
    public function get(string|Throwable $throwableClass): array
    {
        if (!is_string($throwableClass))
        {
            $throwableClass = get_class($throwableClass);
        }

        if (isset($this->exceptions[$throwableClass]))
        {
            return array_replace($this->defaultOptions, $this->exceptions[$throwableClass]);
        }

        foreach ($this->exceptions as $class => $settings)
        {
            if (is_subclass_of($throwableClass, $class))
            {
                return array_replace($this->defaultOptions, $settings);
            }
        }

        return $this->defaultOptions;
    }

}