<?php

namespace Cable\Container;

/**
 * Interface ContainerInterface
 * @package Cable\Container
 */
interface ContainerInterface
{
    /**
     * @param string|object $alias
     * @param object|\Closure $callback
     * @param bool $share
     * @return mixed
     */
    public function add($alias, $callback, $share = false);



    /**
     * @param string $alias
     * @param array $args
     * @throws NotFoundException
     * @throws ResolverException
     * @throws ExpectationException
     * @return mixed
     */
    public function resolve($alias, array $args = []);

    /**
     * @param string $intance the name, instance or alias of class
     * @param string $method the name method
     * @param array $args the args will be passed in to resolver, give empty if you already passed them
     * @return mixed
     * @throws NotFoundException
     * @throw ResolverException
     * @throws \ReflectionException
     */
    public function call($instance, $method, array $args = []);

    /**
     * @param string|object $class the name, instance or alias of class
     * @param string $method the name of method
     * @throws ResolverException
     * @return MethodDefiniton
     */
    public function addMethod($class, $method);

    /**
     * @param string $name
     * @param mixed $instance
     * @return Expectation
     */
    public function expect($name, $instance);
}
