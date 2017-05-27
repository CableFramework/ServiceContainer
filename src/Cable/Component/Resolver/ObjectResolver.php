<?php

namespace Cable\Container\Resolver;

class ObjectResolver extends Resolver
{


    use ClassAwareTrait, ArgsAwareTrait;

    /**
     * ObjectResolver constructor.
     * @param $class
     */
    public function __construct( $class, $args)
    {
        $this->class = $class;
        $this->args = $args;
    }

    /**
     *  resolves the instance
     *
     * @throws \ReflectionException
     * @return mixed
     */
    public function resolve()
    {

        $class = new \ReflectionClass($this->class);


        /**
         * @var $this->class ReflectionClass
         */

        if (null === ($constructor = $class->getConstructor())) {
            return $this->class->newInstance();
        }

        $method = new ConstructorResolver(
            $class,
            $constructor,
            $this->args
        );

        $method->setInstance(
            $this->instance
        )->setContainer(
            $this->getContainer()
        );

        return $method->resolve();
    }
}
