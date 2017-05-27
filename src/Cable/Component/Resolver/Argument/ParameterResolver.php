<?php

namespace Cable\Container\Resolver\Argument;

use Cable\Container\Resolver\ClassAwareTrait;
use Cable\Container\Resolver\Resolver;

class ParameterResolver extends Resolver
{
    use ClassAwareTrait;

    /**
     * @var array
     */
    private $args;
    /**
     * ParameterResolver constructor.
     * @param \ReflectionFunctionAbstract $class
     * @param array $args
     */
    public function __construct(\ReflectionFunctionAbstract $class, array  $args = [])
    {
        $this->args = $args;
        $this->class = $class;
    }

    /**
     * @return \ReflectionParameter[]
     */
    public function getInstance()
    {
        return parent::getInstance();
    }

    /**
     *  resolves the instance
     *
     * @return array
     */
    public function resolve()
    {
        $parameters = $this->class->getParameters();

        $bonded = [];

        foreach ($parameters as $parameter) {
            if (null !== ($class = $this->isClass($parameter))) {


                $bond = $this
                    ->getContainer()
                    ->resolve(
                        $class->getName()
                    );
            } else {
                $bond = $this->resolveParamWithArgs($parameter, $this->args);
            }

            $bonded[$parameter->getName()] = $bond;
        }

        return $bonded;
    }

    /**
     * @param \ReflectionParameter $parameter
     * @param $args
     * @throws ArgumentException
     * @return null
     */
    private function resolveParamWithArgs(\ReflectionParameter $parameter, $args)
    {
        if ( ! isset($args[$parameter->getName()])) {
                $this->throwExceptionIsNotSupportedNull($parameter);


            return null;
        }

        return $args[$parameter->getName()];
    }

    /**
     * @param \ReflectionParameter $parameter
     * @throws ArgumentException
     */
    private function throwExceptionIsNotSupportedNull(\ReflectionParameter $parameter){
        if (null === $parameter->getType() || false === $parameter->getType()->allowsNull()) {
            throw new ArgumentException(
                sprintf(
                    '%s argument doesnot support null',
                    $parameter->getName()
                )
            );
        }
    }

    /**
     * @param \ReflectionParameter $parameter
     * @return \ReflectionClass
     */
    public function isClass(\ReflectionParameter $parameter)
    {
        return $parameter->getClass();
    }
}