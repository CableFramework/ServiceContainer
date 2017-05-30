<?php

namespace Cable\Container;

use Cable\Container\Definition\AbstractDefinition;
use Cable\Container\Definition\MethodDefiniton;
use Cable\Container\Definition\ObjectDefinition;
use Cable\Container\Resolver\Argument\ArgumentException;
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


    const SHARED = 'shared';
    const NOT_SHARED = 'not-shared';


    /**
     * @var array
     */
    private $providers;


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
     * @var MethodManager
     */
    private $methodManager;


    /**
     * @var BoundManager
     */
    private $boundManager;

    /**
     * @var ArgumentManager
     */
    private $argumentManager;

    public function __construct(BoundManager $boundManager,
                                MethodManager $methodManager,
                                ArgumentManager $argumentManager,
                                ProviderRepository $providerRepository = null
    )
    {
        $this->boundManager = $boundManager;
        $this->methodManager = $methodManager;
        $this->argumentManager = $argumentManager;
        $this->providers = $providerRepository;

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

        if (!$provider instanceof ServiceProvider) {
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
        $alias = $this->getClassName($alias);

        $definition = $this->resolveDefinition(
            $callback
        );

        if ($share === true) {
            BoundManager::addShare($alias, $callback);
        } else {
            $this->boundManager->addBond(
                $alias,
                $definition
            );
        }


        return $definition;
    }

    /**
     * @param mixed $callback
     * @return AbstractDefinition
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
        if (!isset($this->resolvers[$type])) {
            throw new ResolverException(
                sprintf(
                    '%s type of resolver is not defined',
                    $type
                )
            );
        }

        $resolver = $this->resolvers[$type];

        $resolver = (new $resolver($callback))
            ->setContainer($this);

        return $resolver;
    }


    /**
     * @param string $alias
     * @param array $args
     * @throws NotFoundException
     * @throws ResolverException
     * @throws ExpectationException
     * @return mixed
     */
    public function resolve($alias, array $args = [])
    {
        // determine the alias already resolved before or not.
        if ($this->hasResolvedBefore($alias)) {

            // we resolved that before, let's reuse it.
            return $this->getAlreadyResolved($alias);
        }

        // we determine we added this before, if we didn't we add it and resolve that
        if (false === $this->boundManager->has($alias)) {
            $this->add(
                $alias,
                $alias
            );

            return $this->resolve($alias);
        }


        list($shared, $definition) =
            $this->boundManager
                ->findDefinition($alias);

        // if we add new args, we'll set them into definition
        if (!empty($args)) {
            $definition->withArgs($args);
        }

        // we'll find our resolver
        $resolver = $this->determineResolver($definition);

        // determine resolved instance is as we expected or not
        $resolved = $this->checkExpectation(
            $alias,
            $resolver->resolve()
        );


        // we resolved the definition, now we will add into resolvedBond or sharedResolved
        // and we will reuse they when we want to resolve them
        $this->saveResolved($shared, $alias, $resolved);

        // we already resolve and saved it. We don't need this definition anymore.
        // so we will remove it.
        // this will save memory
        $this->removeResolved($shared, $alias);

        return $resolved;
    }


    /**
     * @param string $shared
     * @param string $alias
     */
    private function removeResolved($shared, $alias)
    {
        if ($shared === self::SHARED) {
            BoundManager::deleteShared($alias);
        } else {
            $this->boundManager->deleteBond($alias);
        }
    }

    /**
     * @param $alias
     * @param $resolved
     */
    private function saveResolved($shared, $alias, $resolved)
    {

        if ($shared === static::SHARED) {
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
        if (!isset($this->expected[$alias])) {
            return $instance;
        }

        if (!$instance instanceof $this->expected[$alias]) {
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
     * @param string|object $class
     * @param string $method
     * @throws ResolverException
     * @return MethodDefiniton
     */
    public function addMethod($class, $method)
    {
        return $this->methodManager->addMethod(
            $class, $method
        );
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
        if (!isset($this->bond[$alias])) {
            throw new NotFoundException(
                sprintf(
                    '%s bond not found',
                    $alias
                )
            );
        }

        $this->boundManager->deleteBond($alias);

        return $this;
    }


    /**
     * @param string $alias
     * @return $this
     * @throws NotFoundException
     */
    public function deleteFromShare($alias)
    {
        if (!BoundManager::hasShare($alias)) {
            throw new NotFoundException(
                sprintf(
                    '%s bond not found',
                    $alias
                )
            );
        }

        BoundManager::deleteShared($alias);

        return $this;
    }

    /**
     * @param string|object $class
     * @return string
     */
    private function getClassName($class)
    {
        if (is_object($class)) {
            return get_class($class);
        }

        return $class;
    }

    /**
     * @param string $alias the name, instance or alias of class
     * @param string $method the name method
     * @param array $args the args will be passed in to resolver, give empty if you already passed them
     * @return mixed
     * @throws NotFoundException
     * @throws ResolverException
     * @throws \ReflectionException
     * @throws ExpectationException
     * @throws ArgumentException
     */
    public function method($alias, $method, array $args = [])
    {
        $alias = $this->getClassName($alias);

        if (!$this->boundManager->has($alias)) {
            $this->add($alias, $alias);
        }

        // if this method didnt add before, we'll add it and resolve it.
        if (!$this->methodManager->hasMethod($alias, $method)) {
            $this->methodManager->addMethod($alias, $method)
                ->withArgs($args);

            return $this->method($alias, $method);
        }

        // get the method definition
        $selectedMethod = $this->methodManager->getMethod($alias, $method);

        // if args not empty we'll set over predefined args
        if (!empty($args)) {
            $selectedMethod->withArgs($args);
        }

        // create a new method resolver
        $methodResolver = new MethodResolver(
            $this->resolve($alias),
            $method,
            $selectedMethod->getArgs()
        );

        // set container into resolver
        $methodResolver->setContainer($this);

        return $methodResolver->resolve();
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

    /**
     * @return MethodManager
     */
    public function getMethodManager()
    {
        return $this->methodManager;
    }

    /**
     * @param MethodManager $methodManager
     * @return Container
     */
    public function setMethodManager($methodManager)
    {
        $this->methodManager = $methodManager;
        return $this;
    }

    /**
     * @return BoundManager
     */
    public function getBoundManager()
    {
        return $this->boundManager;
    }

    /**
     * @param BoundManager $boundManager
     * @return Container
     */
    public function setBoundManager($boundManager)
    {
        $this->boundManager = $boundManager;
        return $this;
    }

    /**
     * @return ArgumentManager
     */
    public function getArgumentManager()
    {
        return $this->argumentManager;
    }

    /**
     * @param ArgumentManager $argumentManager
     * @return Container
     */
    public function setArgumentManager($argumentManager)
    {
        $this->argumentManager = $argumentManager;
        return $this;
    }

}
