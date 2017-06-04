<?php

namespace Cable\Container\Definition;


use Cable\Container\Container;

/**
 * Class ContextDefinition
 * @package Cable\Container\Definition
 */
class ContextDefinition
{

    /**
     * @var Container
     *
     */
    private $container;

    /**
     * @var string
     */
    public  $alias;

    /**
     * @var string
     */
    public $needs;

    /**
     * @var \Closure
     */
    public $happens;


    /**
     * @var \Closure
     */
    public $callback;

    /**
     * ContextDefinition constructor.
     * @param Container $container
     */
    public function __construct(Container $container, $alias)
    {
        $this->container = $container;
        $this->alias = $alias;
    }


    /**
     * @param string $needs
     * @return $this
     */
    public function needs($needs)
    {
        $this->needs = $needs;

        return $this;
    }

    /**
     * @param \Closure $closure
     * @return ContextDefinition
     */
    public function happens(\Closure $closure)
    {
        $this->happens = $closure;
        return $this;
    }

    /**
     * @param \Closure|object $callback
     * @return Container
     */
    public function give($callback)
    {
        $this->callback = $callback;

        $container = $this->container;

        // we don't need this container instance anymore,
        // we will remove it for memory saving
        $this->container = null;

         $container
            ->getArgumentManager()
            ->give(
               $this->alias,
               $this->needs,

               $this
            );



         return $container;
    }
}
