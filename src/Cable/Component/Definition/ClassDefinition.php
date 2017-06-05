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
     * @param bool $singleton
     * @param $name
     */
    public function __construct(Container $container, $name, $singleton = false)
    {
        $this->container = $container;
        $this->name = $name;
        $this->singleton = $singleton;
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

    /**
     * @param string $alias
     * @return $this
     */
    public function alias($alias)
    {
        $this->container->alias($alias, $this->getName());
    }

    /**
     * @param bool $singleton
     * @return ClassDefinition
     */
    public function setSingleton($singleton)
    {
        $this->container
            ->getBoundManager()
            ->singleton($this->name, $singleton);

        return $this;
    }


}
