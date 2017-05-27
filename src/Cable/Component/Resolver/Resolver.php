<?php

namespace Cable\Container\Resolver;


use Cable\Container\ContainerInterface;
use Cable\Container\Definition\AbstractDefinition;

abstract class Resolver
{

    /**
     * @var ContainerInterface
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
     * @param ContainerInterface $container
     * @return $this
     */
    public function setContainer(ContainerInterface $container){
        $this->container = $container;

        return $this;
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @return AbstractDefinition
     */
    public function getInstance()
    {
        return $this->instance;
    }

    /**
     * @param AbstractDefinition $instance
     * @return Resolver
     */
    public function setInstance(AbstractDefinition $instance = null)
    {
        $this->instance = $instance;

        return $this;
    }
}
