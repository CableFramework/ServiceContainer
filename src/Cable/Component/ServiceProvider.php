<?php
namespace Cable\Container;

abstract  class ServiceProvider
{

    /**
     * @var Container
     */
    protected $container;

    /**
     * register new providers or something
     *
     * @return void
     */
    abstract public function boot();

    /**
     * register the content
     *
     * @return void
     */
    abstract public function register();

    /**
     * @return Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @param Container $container
     * @return ServiceProvider
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;

        return $this;
    }


    /**
     * @param string $name
     * @param array $arguments
     * @throws \ReflectionException
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        $method = new \ReflectionMethod($container = $this->getContainer(), $name);

        return $method->invokeArgs($container, $arguments);
    }
}
