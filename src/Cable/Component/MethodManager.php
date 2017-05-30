<?php

namespace Cable\Container;


use Cable\Container\Definition\MethodDefiniton;

/**
 * Class MethodManager
 * @package Cable\Container
 */
class MethodManager
{

    /**
     * @var array
     */
    private $methods;

    /**
     * @param string $class
     * @param string $method
     * @return mixed
     */
    public function addMethod($class, $method)
    {
        $definition = new MethodDefiniton($method);
        $this->methods[$class][$method] = $definition;

        return $this->methods[$class][$method];
    }

    /**
     * @param string|object $class
     * @param string $method
     * @return bool
     */
    public function hasMethod($class, $method)
    {
        return isset($this->methods[$class][$method]);
    }



    /**
     * @param string|object $class
     * @param string $method
     * @return mixed
     */
    public function getMethod($class, $method)
    {
        return $this->methods[$class][$method];
    }
}
