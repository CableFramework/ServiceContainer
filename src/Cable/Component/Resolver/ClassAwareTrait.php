<?php

namespace Cable\Container\Resolver;

/**
 * Trait ClassAwareTrait
 * @package Cable\Container\Resolver
 */
trait ClassAwareTrait
{

    /**
     * @var string|object
     */
    private $class;

    /**
     * @return object|string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @param object|string $class
     * @return ClassAwareTrait
     */
    public function setClass($class)
    {
        $this->class = $class;

        return $this;
    }

}