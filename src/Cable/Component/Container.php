<?php

namespace Cable\Container;

use Cable\Container\Definition\AbstractDefinition;
use Cable\Container\Definition\ClassDefinition;
use Cable\Container\Definition\ContextDefinition;
use Cable\Container\Definition\MethodDefinition;
use Cable\Container\Definition\MethodDefiniton;
use Cable\Container\Definition\ObjectDefinition;

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

    /**
     * Container constructor.
     * @param BoundManager $boundManager
     * @param MethodManager $methodManager
     * @param ArgumentManager $argumentManager
     * @param ProviderRepository|null $providerRepository
     */
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
     * @param string|ServiceProvider $provider
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
     * @return ClassDefinition
     */
    public function add($alias, $callback, $share = false)
    {
        $alias = $this->getClassName($alias);

        if (true === $share) {
            return $this->share($alias, $callback);
        }


        // if $callback is object we will mark it as resolved,
        // we dont need to resolve it anymore
        if (is_object($callback) && !$callback instanceof \Closure) {
            $this->resolved[$alias] = $callback;
        } else {
            $this->boundManager->addBond(
                $alias,
                $callback
            );
        }


        return new ClassDefinition($this, $alias);
    }

    /**
     * @param string $name
     * @param mixed $instance
     * @return Expectation|ContainerInterface
     */
    public function expect($name, $instance)
    {
        if (is_array($instance)) {
            foreach ($instance as $item) {
                $this->expect($name, $item);
            }

            return $this;
        }

        $this->expected[$name][] = $instance;

        $expectation = new Expectation(
            $name
        );

        return $expectation->setContainer($this);
    }

    /**
     * @param string $alias
     * @param mixed $callback
     * @throws ResolverException
     * @return ClassDefinition
     *
     */
    public function share($alias, $callback)
    {
        if (is_object($callback)) {
            static::$sharedResolved[$alias] = $callback;
        } else {
            BoundManager::addShare($alias, $callback);
        }

        return new ClassDefinition($alias, $callback);
    }

    /**
     * @param string $alias the name of alias
     * @return ContextDefinition
     */
    public function when($alias)
    {

        // we create a new context definition instance
        // and return it, the user will be abel to access needs and having
        // methods now
        $contextDefinition = new ContextDefinition($this, $alias);

        return $contextDefinition;
    }


    /**
     * @param string $alias
     * @param array $args
     * @param bool|null $shared
     * @throws NotFoundException
     * @throws \ReflectionException
     * @throws ExpectationException
     * @throws ArgumentException
     * @return mixed
     */
    public function resolve($alias, array $args = [], $shared = null)
    {
        // determine the alias already resolved before or not.
        if ($this->hasResolvedBefore($alias)) {

            // we resolved that before, let's reuse it.
            return $this->getAlreadyResolved($alias);
        }

        // we determine we added this before, if we didn't we add it and resolve that
        if (false === $this->boundManager->has($alias)) {
            $this->add($alias, $alias);

            return $this->resolve($alias);
        }


        list($shared, $definition) = $this
            ->boundManager
            ->findDefinition($alias, $shared);

        // if we add new args, we'll set them into definition
        if (!empty($args)) {
            $this->getArgumentManager()->setClassArgs(
                $alias, $args
            );
        }

        // determine resolved instance is as we expected or not
        $resolved = $this->checkExpectation(
            $alias,
            $this->resolveObject($definition, $alias)
        );


        // we resolved the definition, now we will add into resolvedBond or sharedResolved
        // and we will reuse they when we want to resolve them
        $this->saveResolved($shared, $alias, $resolved);

        // we already resolve and saved it. We don't need this definition anymore.
        // so we will remove it.
        // this will save memory
        $this->removeResolvedFromBound($shared, $alias);

        return $resolved;
    }

    /**
     * resolves the instance
     *
     * @param object $definition
     * @param string $alias
     * @throws \ReflectionException
     * @throws NotFoundException
     * @throws ExpectationException
     * @throws ArgumentException
     * @return mixed
     */
    private function resolveObject($definition, $alias)
    {
        if ($definition instanceof \Closure) {
            return $this->resolveClosure(
                $alias,
                $definition
            );
        }

        $class = new \ReflectionClass($definition);


        // the given class if is not instantiable throw an exception
        // that happens when you try resolve an interface or abstract class
        // without saving an alias on that class before
        if (false === $class->isInstantiable()) {
            throw new \ReflectionException(
                sprintf(
                    '%s class in not instantiable, probably an interface or abstract',
                    $class->getName()
                )
            );
        }

        $constructor = $class->getConstructor();

        $parameters = [];

        if (null !== $constructor) {
            $parameters = $this->resolveParameters(
                $constructor->getParameters(),
                $this->argumentManager->getClassArgs($alias)
            );
        }

        return $class->newInstanceArgs($parameters);
    }

    /**
     * @param string $alias
     * @param \Closure $closure
     * @throws \ReflectionException
     * @throws NotFoundException
     * @throws ExpectationException
     * @throws ArgumentException
     * @return mixed
     */
    private function resolveClosure($alias, \Closure $closure)
    {
        $reflectionFunction = new \ReflectionFunction($closure);

        $parameters = $this->resolveParameters(
            $reflectionFunction->getParameters(),
            $this->argumentManager->getClassArgs($alias)
        );

        return call_user_func_array($closure, $parameters);
    }


    /**
     * @param $alias
     * @param $method
     * @param $instance
     * @throws \ReflectionException
     * @throws NotFoundException
     * @throws ExpectationException
     * @throws ArgumentException
     * @param \ReflectionMethod $abstract
     * @return mixed
     */
    private function resolveMethod($alias, $method, $instance, \ReflectionMethod $abstract)
    {
        $parameters = $this->resolveParameters(
            $abstract->getParameters(),
            $this->getArgumentManager()
                ->getMethodArgs($alias, $method)
        );

        return $abstract->invokeArgs($instance, $parameters);
    }

    /**
     * @param \ReflectionParameter[] $parameters
     * @param array $args
     * @throws ExpectationException
     * @throws NotFoundException
     * @throws \ReflectionException
     * @throws ArgumentException
     * @return array
     */
    private function resolveParameters(array $parameters, array $args)
    {
        $bounded = [];

        foreach ($parameters as $parameter) {

            $name = $parameter->getName();
            $class = $parameter->getClass()->getName();


            if (isset($args[$class]) || isset($args[$name])) {

                // we will check the argument is a context or a standart argument
                $bounded[$name] = $this->resolveContextOrArgument(
                    isset($args[$class]) ? $args[$class] : $args[$name]
                );

                continue;
            }

            if (!isset($args[$name])) {
                $bounded[$name] = $this->resolveArgument($parameter);
            } else {
                $bounded[$name] = $args[$name];
            }


        }

        return $bounded;
    }

    /**
     * @param $arg
     * @return bool|mixed
     */
    private function resolveContextOrArgument($arg)
    {
        // now we are checking the argument is defined as a context or not
        if (!$arg instanceof ContextDefinition) {
            return $arg;
        }


        return $this->resolveContext($arg);
    }

    /**
     * @param ContextDefinition $contextDefinition
     * @return bool|mixed
     */
    private function resolveContext(ContextDefinition $contextDefinition)
    {

        $happens = $contextDefinition->happens;

        // we'll determine happens is a closure, if it is we'll resolve it
        // and give the result the context closure
        if (null !== $happens && $happens instanceof \Closure) {
            $happens = $happens($this);

            if (!$happens) {
                $happens = [];
            }

            return !empty($happens) ?
                $this->resolveContextCallback($contextDefinition->callback, $happens) :
                false;
        }


        return $this->resolveContextCallback($contextDefinition->callback);
    }

    /**
     * @param \Closure $callback
     * @param null|mixed $happens
     * @return mixed
     */
    private function resolveContextCallback(\Closure $callback, $happens = [])
    {
        $happens[] = $this;

        return call_user_func_array($callback, $happens);
    }

    /**
     *
     * @param \ReflectionParameter $parameter
     * @throws ExpectationException
     * @throws ArgumentException
     * @throws NotFoundException
     * @throws \ReflectionException
     * @return mixed
     */
    private function resolveArgument(\ReflectionParameter $parameter)
    {
        if (null !== ($class = $this->isClass($parameter))) {
            try {
                return $this->resolve($class->getName());
            } catch (NotFoundException $exception) {
                return $this->resolveParameterIsOptional($parameter);
            }
        }

        // if argument doesnot support null, we'll throw an exception

        try {
            return $parameter->getDefaultValue();
        } catch (\ReflectionException $exception) {

            if ($parameter->allowsNull()) {
                return null;
            }

        }

    }

    /**
     * @param \ReflectionParameter $parameter
     * @return mixed|null
     * @throws ArgumentException
     */
    private function resolveParameterIsOptional(\ReflectionParameter $parameter)
    {
        if ($parameter->isOptional()) {
            return $parameter->getDefaultValue();
        }

        if ($parameter->allowsNull()) {
            return null;
        }

        throw new ArgumentException(sprintf(
            '%s parameter does not have a default value and does not allow null',
            $parameter->getName()
        ));

    }


    /**
     * @param \ReflectionParameter $parameter
     * @return \ReflectionClass
     */
    public function isClass(\ReflectionParameter $parameter)
    {
        return $parameter->getClass();
    }

    /**
     * @param string $shared
     * @param string $alias
     */
    private function removeResolvedFromBound($shared, $alias)
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

        $expecteds = $this->expected[$alias];

        foreach ($expecteds as $expected) {

            if (!$instance instanceof $expected) {
                throw new ExpectationException(
                    sprintf(
                        'in %s alias we were expecting %s, %s returned',
                        $alias,
                        $expected,
                        get_class($instance)
                    )
                );
            }

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
     * @return MethodDefinition
     */
    public function addMethod($class, $method)
    {
        $this->methodManager->addMethod(
            $class, $method
        );


        return new MethodDefinition($this, $class, $method);
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
    public function call($instance, $method, array $args = [])
    {
        $alias = is_object($instance) ? $this->getClassName($instance) : $instance;

        if (!$this->boundManager->has($alias)) {
            $this->add($alias, $alias);
        }

        // if this method didnt add before, we'll add it and resolve it.
        if (!$this->methodManager->hasMethod($alias, $method)) {
            $this->addMethod($alias, $method);

            if (!empty($args)) {
                $this->getArgumentManager()
                    ->setMethodArgs($alias, $method, $args);
            }

            return $this->call($alias, $method);
        }


        return $this->resolveMethod(
            $alias,
            $method,
            is_object($instance) ? $instance : $this->resolve($alias),
            new \ReflectionMethod($instance, $method)
        );
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
