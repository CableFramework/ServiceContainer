<?php

namespace Cable\Container;

use Cable\Container\Definition\AbstractDefinition;
use Cable\Container\Definition\MethodDefiniton;
use Cable\Container\Definition\ObjectDefinition;
use Cable\Container\Resolver\MethodResolver;
use Cable\Container\Resolver\ResolverException;

/**
 * Created by PhpStorm.
 * User: My
 * Date: 05/10/2017
 * Time: 18:55
 */
class Container implements ContainerInterface, \ArrayAccess
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
     * @param string $alias the alias name of instance, you might wanna give an interface name
     * @param mixed $callback the callback can be an object, Closure or the name of class
     * @example add('mysql', 'MysqlInterface')
     * @param bool $share if you put true on this argument, this will shared with other container objects
     * @see Container::share()
     * @example add('mysql', 'MysqlInterface', true);
     * @throws ResolverException
     * @return AbstractDefinition
     */
    public function add($alias, $callback, $share = false)
    {
        $definition = $this->resolveDefinition(
            $callback
        );

        if ($share === true) {
            static::$shared[$alias] = $definition;
        } else {
            $this->bond[$alias] = $definition;
        }


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
     * @return AbstractDefinition
     */
    public function share($alias, $callback)
    {
        return $this->add(
            $alias,
            $callback,
            true
        );
    }

    /**
     * @param AbstractDefinition $definition
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
    public function resolve($alias, array $args = [])
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

        if ( ! empty($args)) {
            $definition->withArgs($args);
        }


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

    /**
     * @param $class
     * @param $method
     * @throws ResolverException
     * @return MethodDefiniton
     */
    public function addMethod($class, $method)
    {
        return $this->add($class, $class)->withMethod($method);
    }

    /**
     * @param string $alias
     * @throws NotFoundException
     * @return mixed
     */
    public function delete($alias)
    {
        if (isset($this->bond[$alias])) {
            return $this->deleteFromBond($alias);
        }

        return $this->deleteFromShare($alias);
    }


    /**
     * @param string $alias
     * @return $this
     * @throws NotFoundException
     */
    public function deleteFromBond($alias)
    {
        if ( ! isset($this->bond[$alias])) {
            throw new NotFoundException(
                sprintf(
                    '%s bond not found',
                    $alias
                )
            );
        }

        unset(
            $this->bond[$alias]
        );

        return $this;
    }


    /**
     * @param string $alias
     * @return $this
     * @throws NotFoundException
     */
    public function deleteFromShare($alias)
    {
        if ( ! isset(static::$shared[$alias])) {
            throw new NotFoundException(
                sprintf(
                    '%s bond not found',
                    $alias
                )
            );
        }

        unset(
            static::$shared[$alias]
        );

        return $this;
    }


    /**
     * @param string $alias the name, instance or alias of class
     * @param string $method the name method
     * @param array $args the args will be passed in to resolver, give empty if you already passed them
     * @return mixed
     * @throws NotFoundException
     * @throw ResolverException
     * @throws \ReflectionException
     */
    public function method($alias, $method, array $args = [])
    {
        if ( ! $this->has($alias)) {
            $this->add($alias, $alias);
        }

        $class = $this->getBond($alias);

        if ( ! $class->hasMethod($method)) {
            throw new NotFoundException(
                sprintf(
                    '%s method not found in %s alias',
                    $method,
                    $alias
                )
            );
        }

        $selectedMethod = $class->getMethod($method);

        if ( ! empty($args)) {
            $selectedMethod->withArgs($args);
        }

        $methodResolver = new MethodResolver(
            $class,
            $method,
            $selectedMethod
        );

        $methodResolver->setContainer($this);

        return $methodResolver->resolve();
    }

    /**
     * @param $alias
     * @return ObjectDefinition
     */
    public function getBond($alias)
    {
        return $this->bond[$alias];
    }

    /**
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     * @since 5.0.0
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     * @since 5.0.0
     */
    public function offsetGet($offset)
    {
        return $this->resolve($offset);
    }

    /**
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     * @throws ResolverException
     * @since 5.0.0
     */
    public function offsetSet($offset, $value)
    {
        $this->add($offset, $value);
    }

    /**
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     * @throws NotFoundException
     * @since 5.0.0
     */
    public function offsetUnset($offset)
    {
        $this->delete($offset);
    }

    /**
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->resolve($name);
    }

    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        $this->add($name, $value);
    }

    /**
     * @param $name
     * @return bool
     */
    public function __isset($name)
    {
        return $this->has($name);
    }
}
