<?php declare(strict_types=1);
namespace mrcore\base;
use Countable;
use IteratorAggregate;

/**
 * ParameterBag is a container for name/value pairs.
 *
 * @template T
 * @implements IteratorAggregate<string, T>
 */
class ParameterBag implements IteratorAggregate, Countable
{
    /**
     * @param  array<string,T>  $parameters
     */
    public function __construct(protected array $parameters = []) { }

    /**
     * Returns the parameter names.
     */
    public function names(): array
    {
        return array_keys($this->parameters);
    }

    /**
     * Returns true if the parameter is defined.
     */
    public function has(string $name): bool
    {
        return array_key_exists($name, $this->parameters);
    }

    /**
     * @param T|null $default
     * @return T|null
     */
    public function get(string $name, mixed $default = null)
    {
        return array_key_exists($name, $this->parameters) ? $this->parameters[$name] : $default;
    }

    /**
     * @param T $value
     */
    public function set(string $name, mixed $value): static
    {
        $this->parameters[$name] = $value;

        return $this;
    }

    /**
     * Removes a parameter.
     */
    public function remove(string $name): static
    {
        unset($this->parameters[$name]);

        return $this;
    }

    /**
     * Returns an iterator for parameters.
     *
     * @return ArrayIterator<string, T>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->parameters);
    }

    /**
     * Returns the number of parameters.
     */
    public function count(): int
    {
        return count($this->parameters);
    }

}