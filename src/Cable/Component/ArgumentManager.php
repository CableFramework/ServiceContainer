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
     * @param $class
     * @param array $args
     * @return $this
     */
    public function setClassArgs($class, array  $args)
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
    public function setMethodArgs($class, $method,array $args)
    {
        $this->methodArgs[$this->prepareMethodName($class, $method)] =$args;

        return $this;
    }

    /**
     * @param string $class
     * @param string $method
     * @return string
     */
    public function prepareMethodName($class, $method){
        return $class.'.'.$method;
    }
}
