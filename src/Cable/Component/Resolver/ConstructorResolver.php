<?php
/**
 * Created by PhpStorm.
 * User: My
 * Date: 05/27/2017
 * Time: 22:45
 */

namespace Cable\Container\Resolver;


use Cable\Container\Resolver\Argument\ParameterResolver;

class ConstructorResolver extends Resolver
{

    use ClassAwareTrait, ArgsAwareTrait;

    /**
     * @var \ReflectionMethod
     */
    private $method;


    /**
     * ConstructorResolver constructor.
     * @param $class
     * @param $method
     * @param $args
     */
    public function __construct($class, $method,  $args)
    {
        $this->method = $method;
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

        $parameterResolver = new ParameterResolver($this->method, $this->args);

        $parameterResolver->setContainer(
            $this->getContainer()
        );

        $parameters = $parameterResolver->resolve();
        return $this->class->newInstanceArgs(
            $parameters
        );
    }
}
