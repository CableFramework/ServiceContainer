<?php

namespace Cable\Container\Resolver;

use Cable\Container\Resolver\Argument\ParameterResolver;

class MethodResolver extends Resolver
{

    use ClassAwareTrait, ArgsAwareTrait;

    private $name;

    /**
     * MethodResolver constructor.
     * @param $class
     */
    public function __construct($class, $name, $args)
    {
        $this->class = $class;
        $this->args = $args;
        $this->name = $name;
    }

    /**
     *  resolves the instance
     *
     * @param array $args
     * @throws \ReflectionException
     * @return mixed
     */
    public function resolve()
    {
        $class = $this->getObjectInstance($this->class->getInstance());

        $method = new \ReflectionMethod(
            $class, $this->name
        );

        $parameterResolver = new ParameterResolver($method, $this->args);

        $parameterResolver->setContainer(
            $this->getContainer()
        )->setInstance(
            $this->class
        );

        $parameters = $parameterResolver->resolve();


        return $method->invokeArgs(
            $class,
            $parameters
        );

        return $method->resolve();
    }


    /**
     * returns object
     *
     * @throws \ReflectionException
     * @param $class
     * @return mixed
     */
    private function getObjectInstance($class){

        if (is_object($class)) {
                return $class;
        }



        $objectResolver = new ObjectResolver($class);

        $objectResolver->setInstance(
            $this->class
        )->setContainer($this->getContainer());

        return $objectResolver->resolve();
    }
}
