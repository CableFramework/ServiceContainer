<?php

namespace Cable\Container\Definition;


use Cable\Container\Container;

class MethodDefinition extends Definition
{

    /**
     * @var Container
     */
    private $container;

    /**
     * @var string
     */
    private $class;

    /**
     * ClassDefinition constructor.
     * @param Container $container
     * @param string $class
     * @param string $name
     */
    public function __construct(Container $container,$class, $name)
    {
        $this->container = $container;
        $this->class = $class;
        $this->name = $name;
    }

    /**
     * @param array $args
     * @return Container
     */
    public function withArgs(array  $args)
    {
        $this->container->getArgumentManager()->setMethodArgs(
            $this->class,
            $this->name,
            $args
        );

        return $this->container;
    }


}
