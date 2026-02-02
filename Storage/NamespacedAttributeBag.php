<?php

namespace Sylius\Bundle\FlowBundle\Storage;

use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;

/**
 * Polyfill for removed Symfony NamespacedAttributeBag (removed in Symfony 6.x).
 * Provides structured storage of session attributes using a namespace character in the key
 * (e.g. "domain/step" stores as nested $attributes['domain']['step']).
 * Required for SessionFlowsBag – clear() removes by domain, not by flat key.
 */
class NamespacedAttributeBag extends AttributeBag
{
    private string $namespaceCharacter;

    public function __construct(string $storageKey = '_sf2_attributes', string $namespaceCharacter = '/')
    {
        $this->namespaceCharacter = $namespaceCharacter;
        parent::__construct($storageKey);
    }

    public function has(string $name): bool
    {
        $attributes = $this->resolveAttributePath($name);
        $name = $this->resolveKey($name);

        if (null === $attributes) {
            return false;
        }

        return \array_key_exists($name, $attributes);
    }

    public function get(string $name, mixed $default = null): mixed
    {
        $attributes = $this->resolveAttributePath($name);
        $name = $this->resolveKey($name);

        if (null === $attributes) {
            return $default;
        }

        return \array_key_exists($name, $attributes) ? $attributes[$name] : $default;
    }

    public function set(string $name, mixed $value): void
    {
        $attributes = &$this->resolveAttributePath($name, true);
        $name = $this->resolveKey($name);
        $attributes[$name] = $value;
    }

    public function remove(string $name): mixed
    {
        $retval = null;
        $attributes = &$this->resolveAttributePath($name);
        $name = $this->resolveKey($name);
        if (null !== $attributes && \array_key_exists($name, $attributes)) {
            $retval = $attributes[$name];
            unset($attributes[$name]);
        }

        return $retval;
    }

    /**
     * Resolves a path in attributes and returns it as reference (nested array by "/").
     */
    protected function &resolveAttributePath(string $name, bool $writeContext = false): ?array
    {
        $array = &$this->attributes;
        $name = (str_starts_with($name, $this->namespaceCharacter)) ? substr($name, 1) : $name;

        if ($name === '') {
            return $array;
        }

        $parts = explode($this->namespaceCharacter, $name);
        if (\count($parts) < 2) {
            if (!$writeContext) {
                return $array;
            }
            $array[$parts[0]] = [];

            return $array;
        }

        unset($parts[\count($parts) - 1]);

        foreach ($parts as $part) {
            if (!\array_key_exists($part, $array)) {
                if (!$writeContext) {
                    $null = null;

                    return $null;
                }
                $array[$part] = [];
            }
            $array = &$array[$part];
        }

        return $array;
    }

    protected function resolveKey(string $name): string
    {
        $pos = strrpos($name, $this->namespaceCharacter);
        if (false !== $pos) {
            $name = substr($name, $pos + 1);
        }

        return $name;
    }
}
