<?php

namespace Cable\Container\Definition;


use Cable\Container\Container;
use Cable\Container\ContainerInterface;

class ClassDefinition extends Definition
{

    /**
     * @var Container
     */
    private $container;

    /**
     * ClassDefinition constructor.
     * @param Container $container
     * @param $name
     */
    public function __construct(Container $container, $name)
    {
        $this->container = $container;
        $this->name = $name;
    }

    /**
     * @param array $args
     * @return Container
     */
    public function withArgs(array  $args)
    {
        $this->container->getArgumentManager()->setClassArgs(
            $this->name,
            $args
        );

        return $this->container;
    }

    /**
     * @param string $name
     * @return MethodDefinition
     */
    public function withMethod($name)
    {
       return new MethodDefinition($this->container, $this->name, $name);
    }
}
