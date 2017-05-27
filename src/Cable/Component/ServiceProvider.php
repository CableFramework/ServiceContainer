<?php
namespace Cable\Container;

abstract  class ServiceProvider
{

    /**
     * register new providers or something
     *
     * @return mixed
     */
    abstract public function boot();

    /**
     * register the content
     *
     * @return mixed
     */
    abstract public function register();


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
