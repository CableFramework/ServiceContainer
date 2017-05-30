<?php

namespace Cable\Container;


class ArgumentManager
{

    /**
     * @var array
     */
    private $methodArgs;

    /**
     * @var array
     */
    private $classArgs;

    /**
     * @return array
     */
    public function getClassArgs($class)
    {

        return isset($this->classArgs[$class]) && !empty($this->classArgs[$class])
            ? $this->classArgs[$class] :
            [];
    }

    /**
     * @param $class
     * @param $method
     * @return mixed
     */
    public function getMethodArgs($class, $method)
    {
        $alias = $this->prepareMethodName($class, $method);

        return isset($this->methodArgs[$alias]) && !empty(
        $this->methodArgs[$alias]
        ) ? $this->methodArgs[$alias] : [];
    }

    /**
     * @param $class
     * @param array $args
     * @return $this
     */
    public function setClassArgs($class, array $args)
    {
        $this->classArgs[$class] = $args;

        return $this;
    }

    /**
     * @param string $class
     * @param string $method
     * @param array $args
     * @return $this
     */
    public function setMethodArgs($class, $method, array $args)
    {
        $this->methodArgs[$this->prepareMethodName($class, $method)] = $args;

        return $this;
    }

    /**
     * @param string $class
     * @param string $method
     * @return string
     */
    public function prepareMethodName($class, $method)
    {
        return $class . '.' . $method;
    }
}
