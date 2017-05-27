<?php

namespace Cable\Container;

use Cable\Container\Definition\AbstractDefinition;
use Cable\Container\Definition\ObjectDefinition;
use Cable\Container\Resolver\ResolverException;

/**
 * Created by PhpStorm.
 * User: My
 * Date: 05/10/2017
 * Time: 18:55
 */
class Container implements ContainerInterface
{


    /**
     * @var array
     */
    private static $shared;


    /**
     * @var array
     */
    private $providers;


    /**
     * @var array
     */
    private $bond;


    /**
     * @var array
     */
    private $resolved;

    /**
     * @var array
     */
    private static $sharedResolved;

    /**
     * @var array
     */
    private $resolvers = [
        'closure' => 'Cable\Container\Resolver\ClosureResolver',
        'object' => 'Cable\Container\Resolver\ObjectResolver',
        'string' => 'Cable\Container\Resolver\ObjectResolver',
    ];

    /**
     * @var array
     */
    private $expected;

    /**
     * Container constructor.
     * @param ProviderRepository $repository
     */
    public function __construct(ProviderRepository $repository = null)
    {
        $this->providers = $repository;

        if (null !== $this->providers) {
            $this->handleProviders();
        }
    }

    /**
     *  resolves providers
     */
    private function handleProviders()
    {
        $providers = $this->providers->getProviders();

        foreach ($providers as $provider) {
            $this->addProvider($provider);
        }
    }

    /**
     * @param $provider
     * @return $this
     * @throws ProviderException
     */
    public function addProvider($provider)
    {
        if (is_string($provider)) {
            $provider = new  $provider;
        }

        if ( ! $provider instanceof ServiceProvider) {
            throw new ProviderException(
                sprintf(
                    '%s provider is not as expected',
                    get_class($provider)
                )
            );
        }

        $provider->setContainer($this)
            ->boot();

        $provider->register();

        return $this;
    }


    /**
     * @param string $alias
     * @param mixed $callback
     * @param bool $share
     * @throws ResolverException
     * @return AbstractDefinition
     */
    public function add($alias, $callback, $share = false)
    {
        if (true === $share) {
            return $this->share($alias, $callback);
        }


        $this->bond[$alias] =
        $definition = $this->resolveDefinition(
            $callback
        );


        return $definition;
    }

    /**
     * @param mixed $callback
     * @return Definition\AbstractDefinition
     */
    public function resolveDefinition($callback)
    {

        $definition = new ObjectDefinition();


        return $definition->setInstance(
            $callback
        );

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
        static::$shared[$alias] = $callback;

        return $this;
    }

    /**
     * @param AbstractDefinition $callback
     * @return mixed
     * @throws ResolverException
     */
    private function determineResolver($definition)
    {
        $callback = $definition->getInstance();
        $type = gettype($callback);

        if ($callback instanceof \Closure) {
            $type = 'closure';
        }


        return $this->prepareResolver($type, $callback, $definition);
    }


    /**
     * @param string $type
     * @param mixed $callback
     * @param AbstractDefinition $definition
     * @return mixed
     * @throws ResolverException
     */
    private function prepareResolver($type, $callback, AbstractDefinition $definition)
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

        $resolver = (new $resolver($callback))
            ->setInstance($definition)
            ->setContainer($this);

        return $resolver;
    }


    /**
     * @param string $alias
     * @throws NotFoundException
     * @throws ResolverException
     * @throws ExpectationException
     * @return mixed
     */
    public function resolve($alias)
    {

        if ($this->hasResolvedBefore($alias)) {
            return $this->getAlreadyResolved($alias);
        }


        if (false === $this->has($alias)) {
            $this->add(
                $alias,
                $alias
            );

            return $this->resolve($alias);
        }


        $definition = isset($this->bond[$alias]) ?
            $this->bond[$alias] :
            static::$shared[$alias];



        $resolver = $this->determineResolver($definition);

        $resolved = $this->checkExpectation(
            $alias,
            $resolver->resolve()
        );

        $this->saveResolved($alias, $resolved);

        return $resolved;
    }

    /**
     * @param $alias
     * @param $resolved
     */
    private function saveResolved($alias, $resolved)
    {
        $type = isset($this->bond[$alias]) ? 'not-shared' : 'shared';

        if ($type === 'shared') {
            static::$sharedResolved[$alias] = $resolved;
        } else {
            $this->resolved[$alias] = $resolved;
        }
    }

    /**
     * @param string $alias
     * @param object $instance
     * @return mixed
     * @throws ExpectationException
     */
    private function checkExpectation($alias, $instance)
    {
        if ( ! isset($this->expected[$alias])) {
            return $instance;
        }

        if ( ! $instance instanceof $this->expected[$alias]) {
            throw new ExpectationException(
                sprintf(
                    'in %s alias we were expecting %s, %s returned',
                    $alias,
                    $this->expected[$alias],
                    get_class($instance)
                )
            );
        }

        return $instance;
    }

    /**
     * @param string $alias
     * @return mixed
     */
    public function getAlreadyResolved($alias)
    {
        return isset($this->resolved[$alias]) ?
            $this->resolved[$alias] :
            static::$sharedResolved[$alias];
    }

    /**
     * @param string $alias
     * @return bool
     */
    public function hasResolvedBefore($alias)
    {
        return isset($this->resolved[$alias]) || isset(static::$sharedResolved[$alias]);
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
