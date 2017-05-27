<?php

namespace Cable\Container;

use Cable\Container\Resolver\ResolverException;

/**
 * Created by PhpStorm.
 * User: My
 * Date: 05/10/2017
 * Time: 18:55
 */
class Container implements ContainerInterface
{


    private static $shared;


    private $providers;


    private $bond;

    /**
     * @var array
     */
    private $resolvers = [
        'closure' => 'Cable\Container\Resolver\ClosureResolver',
        'object' => 'Cable\Container\Resolver\ObjectResolver',
    ];

    /**
     * @var array
     */
    private $expected;

    /**
     * @param string $alias
     * @param mixed $callback
     * @param bool $share
     * @throws ResolverException
     * @return $this
     */
    public function add($alias, $callback, $share = false)
    {
        if (false === $share) {
            return $this->share($alias, $callback);
        }

        $this->bond[$alias] = $this
            ->determineResolver($callback);

        return $this;
    }

    /**
     * @param string $name
     * @param mixed $instance
     * @return Expectation
     */
    public function expect($name, $instance)
    {
        $this->expected[$name] = $instance;

        $expectation = new Expectation(
            $name
        );

        return $expectation->setContainer($this);
    }

    /**
     * @param string $alias
     * @param mixed $callback
     * @throws ResolverException
     * @return $this
     */
    public function share($alias, $callback)
    {
        static::$shared[$alias] = $this->determineResolver($callback);

        return $this;
    }

    /**
     * @param mixed $callback
     * @return mixed
     * @throws ResolverException
     */
    private function determineResolver($callback)
    {
        $type = gettype($callback);

        if ($callback instanceof \Closure) {
            $type = 'closure';
        }

        return $this->prepareResolver($type, $callback);

    }

    /**
     * @param string $type
     * @param mixed $callback
     * @return mixed
     * @throws ResolverException
     */
    private function prepareResolver($type, $callback)
    {
        if ( ! isset($this->resolvers[$type])) {
            throw new ResolverException(
                sprintf(
                    '%s type of resolver is not defined',
                    $type
                )
            );
        }

        $resolver = $this->resolvers[$type];

        return (new $resolver)
            ->setInstance($callback)
            ->setContainer($this);
    }


    /**
     * @param string $alias
     * @param array $args
     * @throws NotFoundException
     * @throws ResolverException
     * @return mixed
     */
    public function resolve(
        $alias,
        array $args = []
    ) {

        if (false === $this->has($alias)) {
            $this->add(
                $alias = $this->getAliasFromInstance($alias),
                $alias,
                $args
            );

            return $this->resolve($alias);
        }

        $instance = isset($this->bond[$alias]) ?
            $this->bond[$alias] :
            static::$shared[$alias];

        return $instance->resolve();
    }


    /**
     * @param string $alias
     * @throws NotFoundException
     */
    private function determineExistsOrThrowException($alias)
    {
        if ( ! $this->has($alias)) {
            throw new NotFoundException(
                sprintf(
                    '%s not found in container',
                    $alias
                )
            );
        }


    }


    /**
     * @param string $alias
     * @return bool
     */
    public function has($alias)
    {
        return isset($this->bond[$alias]) || isset(static::$shared[$alias]);
    }
}
