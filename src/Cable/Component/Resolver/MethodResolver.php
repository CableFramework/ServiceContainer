<?php
namespace Cable\Container\Resolver;

use Cable\Container\Resolver\Argument\ParameterResolver;

class MethodResolver extends Resolver
{

    use ClassAwareTrait, ArgsAwareTrait;

    /**
     * MethodResolver constructor.
     * @param $class
     */
    public function __construct($class, $args)
    {
        $this->class = $class;
        $this->args = $args;
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

        $parameterResolver = new ParameterResolver($this->class, $this->args);

        $parameterResolver->setContainer(
            $this->getContainer()
        )->setInstance(
            $this->getInstance()
        );

        $parameters = $parameterResolver->resolve();



        return $this->class->invokeArgs(
            $this->getInstance()->getInstance(),
            $parameters
        );

        return $method->resolve();
    }
}
