<?php

namespace Cable\Container\Resolver;


use Cable\Container\Container;
use Cable\Container\ContainerInterface;
use Cable\Container\Definition\AbstractDefinition;
use Cable\Container\Definition\ClassDefinition;
use Cable\Container\Definition\MethodDefinition;

abstract class Resolver
{

    /**
     * @var Container
     */
    protected $container;

    /**
     * @var AbstractDefinition
     */
    protected $instance;


    /**
     *  resolves the instance
     *
     * @return mixed
     */
    abstract public function resolve();


    /**
     * @param Container $container
     * @return $this
     */
    public function setContainer(Container $container){
        $this->container = $container;

        return $this;
    }

    /**
     * @return Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @return string
     */
    public function getInstance()
    {
        return $this->instance;
    }

    /**
     * @param string $instance
     * @return Resolver
     */
    public function setInstance($instance = null)
    {
        $this->instance = $instance;

        return $this;
    }
}
