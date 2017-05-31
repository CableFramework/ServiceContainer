<?php

namespace Cable\Container\Resolver;

class ObjectResolver extends Resolver
{


    use ClassAwareTrait;

    /**
     * ObjectResolver constructor.
     * @param $class
     */
    public function __construct($class)
    {
        $this->class = $class;
    }

    /**
     *  resolves the instance
     *
     * @throws \ReflectionException
     * @return mixed
     */
    public function resolve()
    {

        if (is_object($instance = $this->class)) {
            return $instance;
        }

        $class = new \ReflectionClass($this->class);

        $args = $this->getContainer()->getArgumentManager()->getClassArgs(
            $this->getInstance()->getName()
        );

        /**
         * @var $this->class ReflectionClass
         */

        if (null === ($constructor = $class->getConstructor())) {
            return $class->newInstance();
        }

        $method = new ConstructorResolver(
            $class,
            $constructor,
            $args
        );

        $method->setInstance(
            $this->instance
        )->setContainer(
            $this->getContainer()
        );

        return $method->resolve();
    }
}
