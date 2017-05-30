<?php

namespace Cable\Container\Resolver\Argument;

use Cable\Container\ExpectationException;
use Cable\Container\NotFoundException;
use Cable\Container\Resolver\ClassAwareTrait;
use Cable\Container\Resolver\Resolver;
use Cable\Container\Resolver\ResolverException;

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
    public function __construct(\ReflectionFunctionAbstract $class, array $args = [])
    {
        $this->args = $args;
        $this->class = $class;
    }

    /**
     *  resolves the instance
     *
     * @throws ArgumentException
     * @throws ExpectationException
     * @throws ResolverException
     * @throws NotFoundException
     * @return array
     */
    public function resolve()
    {
        $parameters = $this->class->getParameters();

        $bonded = [];

        foreach ($parameters as $parameter) {
            $bonded[$parameter->getName()] = $this->resolveParameter($parameter);
        }

        return $bonded;
    }

    /**
     * @param \ReflectionParameter $parameter
     * @throws ArgumentException
     * @throws ExpectationException
     * @throws ResolverException
     * @throws NotFoundException
     * @return null
     */
    private function resolveParameter(\ReflectionParameter $parameter)
    {
        if (null !== ($class = $this->isClass($parameter))) {
            return $this->getContainer()
                ->resolve(
                    $class->getName()
                );
        }

        return $this->resolveParamWithArgs($parameter);
    }


    /**
     * @param \ReflectionParameter $parameter
     * @throws ArgumentException
     * @return null
     */
    private function resolveParamWithArgs(\ReflectionParameter $parameter)
    {
        if ( ! isset($this->args[$parameter->getName()])) {
            $this->throwExceptionIsNotSupportedNull($parameter);


            return null;
        }

        return $this->args[$parameter->getName()];
    }

    /**
     * @param \ReflectionParameter $parameter
     * @throws ArgumentException
     */
    private function throwExceptionIsNotSupportedNull(\ReflectionParameter $parameter)
    {
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
