<?php

namespace Cable\Container\Resolver;


use Cable\Container\Resolver\Argument\ParameterResolver;

class ClosureResolver extends Resolver
{

    /**
     *  resolves the instance
     *
     * @throws \ReflectionException
     * @return mixed
     */
    public function resolve()
    {
        $instance = $this->getInstance()->getInstance();

        /**
         * @var \Closure $instance
         */

        $function = new \ReflectionFunction($instance);

        $parameter = new ParameterResolver($function, $this->getInstance()->getArgs());

        $parameter->setContainer(
            $this->getContainer()
        );


        $parameters = $parameter->resolve();

        return call_user_func_array(
            $instance,
            $parameters
        );
    }
}
