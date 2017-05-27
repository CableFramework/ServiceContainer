<?php

namespace Cable\Container\Definition;


use Cable\Container\Resolver\Resolver;

abstract class AbstractDefinition
{

    /**
     * @var string
     */
    protected $args = [];

    /**
     * @var mixed
     */
    protected $instance;

    /**
     * @param array $args
     * @return $this
     */
    public function withArgs($args)
    {
        $this->args = $args;

        return $this;
    }

    /**
     * @return string
     */
    public function getArgs()
    {
        return $this->args;
    }

    /**
     * @return mixed
     */
    public function getInstance()
    {
        return $this->instance;
    }

    /**
     * @param object $instance
     * @return mixed
     */
    public function setInstance($instance)
    {
        $this->instance = $instance;

        return $this;
    }

}