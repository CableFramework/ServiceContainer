<?php

namespace Cable\Container\Resolver;


use Cable\Container\ContainerInterface;

abstract class Resolver
{

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var mixed
     */
    protected $instance;



    /**
     *  resolves the instance
     *
     * @param array $args
     * @return mixed
     */
    abstract public function resolve(array $args = []);


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
     * @return mixed
     */
    public function getİnstance()
    {
        return $this->instance;
    }

    /**
     * @param mixed $instance
     * @return Resolver
     */
    public function setİnstance($instance)
    {
        $this->instance = $instance;

        return $this;
    }
}
