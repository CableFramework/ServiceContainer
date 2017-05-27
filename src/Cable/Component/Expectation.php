<?php
namespace Cable\Container;


class Expectation
{

    /**
     * @var Container
     */
    private $container;


    /**
     * @var string
     */
    private $name;

    /**
     * Expectation constructor.
     * @param $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * @param Container $container
     * @return $this
     */
    public function setContainer(ContainerInterface $container){
        $this->container = $container;

        return $this;
    }

    /**
     * @param callable $callback
     * @return Container
     */
    public function add($callback)
    {
        return $this->container->add(
            $this->name,
            $callback
        );
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return call_user_func_array(
            [$this->container, $name], $arguments
        );
    }

}