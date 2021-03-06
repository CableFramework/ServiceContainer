<?php

namespace Cable\Container;

use Cable\Annotation\Annotation;
use Cable\Annotation\ContainerNotFoundException;
use Cable\Container\Annotations\Provider;
use Cable\Container\Definition\ClassDefinition;
use Cable\Container\Definition\ContextDefinition;
use Cable\Container\Definition\MethodDefinition;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

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
    private $tagged;

    /**
     * @var array
     */
    private $aliases;


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
     * @var array
     */
    private $provided;


    /**
     * Container constructor.
     * @param ProviderRepository|null $providerRepository
     */
    public function __construct(
        ProviderRepository $providerRepository = null
    ) {
        $this->boundManager = new BoundManager();
        $this->methodManager = new MethodManager();
        $this->argumentManager = new ArgumentManager();
        $this->providers = $providerRepository;


        if (null !== $this->providers) {
            $this->handleProviders();
        }
    }

    /**
     * @param array $aliases
     * @param $name
     * @return $this
     */
    public function tag(array $aliases = [], $name)
    {
        $this->tagged[$name] = $aliases;

        return $this;
    }


    /**
     * @param $name
     * @return array
     * @throws NotFoundException
     */
    public function tagged($name)
    {
        if ( ! isset($this->tagged[$name]) || empty($this->tagged[$name])) {
            throw new NotFoundException(
                sprintf(
                    'nothing found on %s tag',
                    $name
                )
            );
        }

        return array_map(array($this, 'make'), $this->tagged[$name]);
    }

    /**
     * @param string $abstract
     * @param string $alias
     * @return $this
     */
    public function alias($abstract, $alias)
    {
        $this->aliases[$abstract] = $alias;

        return $this;
    }

    /**
     *
     * @throws ProviderException
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

        // we need that provider name
        $name = get_class($provider);

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
        // we save it
        $this->provided[] = $name;

        return $this;
    }


    /**
     * @param string $name
     * @return mixed
     */
    public function isProvided($name)
    {
        return array_search($name, $this->provided, true);
    }

    /**
     * this method marks your alias as a singleton
     * so it will be resolve only once and reuse everytime
     *
     * @param string $alias the alias name of instance, you might wanna give an interface name
     * @param mixed $callback the callback can be an object, Closure or the name of class
     * @example add('mysql', 'MysqlInterface')
     * @param bool $share if you put true on this argument, this will shared with other container objects
     * @see Container::share()
     * @example add('mysql', 'MysqlInterface', true);
     * @throws ResolverException
     * @return ClassDefinition
     */
    public function singleton($alias, $callback, $share = false)
    {
        return $this->add($alias, $callback, $share)->setSingleton(true);
    }

    /**
     * @param string $alias the alias name of instance, you might wanna give an interface name
     * @param mixed $callback the callback can be an object, Closure or the name of class
     * @example add('mysql', 'MysqlInterface')
     * @see Container::share()
     * @example add('mysql', 'MysqlInterface', true);
     * @throws ResolverException
     * @return ClassDefinition
     */
    public function add($alias, $callback)
    {
        $alias = $this->getClassName($alias);

        $singleton = false;

        // if $callback is object we will mark it as resolved,
        // we dont need to resolve it anymore
        if (is_object($callback) && ! $callback instanceof \Closure) {
            $this->resolved[$alias] = $callback;

            // we mark that as a singleton, so we wont resolve it.
            $singleton = true;
        } else {
            $this->boundManager->addBond($alias, $callback);
        }


        return (new ClassDefinition($this, $alias))->setSingleton($singleton);
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
     * @param string $alias the name of alias
     * @return ContextDefinition
     */
    public function when($alias)
    {

        // we create a new context definition instance
        // and return it, the user will be abel to access needs and having
        // methods now
        return new ContextDefinition($this, $alias);
    }


    /**
     * @param string $alias
     * @param array $args
     * @throws ContainerExceptionInterface
     *
     * @return mixed
     */
    public function make($alias, array $args = [])
    {
        return $this->resolve($alias, $args);
    }


    /**
     * resolves the given string, Controller@method
     *
     * @param string $name
     * @param array $args
     * @throws ContainerExceptionInterface
     * @throws ReflectionException
     * @throws ArgumentException
     * @throws \ReflectionException
     * @throws NotFoundException
     * @throws ExpectationException
     * @return mixed
     */
    public function dispatch($name, array $args = [])
    {
        if (false === strpos($name, '::')) {
            return $this->resolve($name);
        }


        list($alias, $method) = explode('::', $name);

        return $this->call(
            $this->resolve($alias),
            $method,
            $args
        );
    }


    /**
     * @param $alias
     * @param array $attributes
     * @return mixed
     * @throws PropertyNotFoundException
     * @throws ContainerExceptionInterface
     * @throws ReflectionException
     * @throws ArgumentException
     * @throws \ReflectionException
     * @throws NotFoundException
     * @throws ExpectationException
     */
    public function fill($alias, array $attributes = [])
    {
        $resolved = $this->make($alias);

        if (empty($attributes)) {
            return $resolved;
        }

        $class = new \ReflectionClass($resolved);

        foreach ($attributes as $name => $attribute) {
            if ( ! $class->hasProperty($name)) {
                throw new PropertyNotFoundException(
                    sprintf(
                        '%s property could not found',
                        $name
                    )
                );
            }


            $property = $class->getProperty($name);
            $property->setAccessible(true);
            $property->setValue($resolved, $attribute);
        }


        return $resolved;
    }


    /**
     * resolves the given alias
     *
     * you can give an object, the name of class or the alias name
     *
     *
     * @param string $alias
     * @param array $args
     * @throws ContainerExceptionInterface
     * @return mixed
     */
    public function resolve($alias, array $args = [])
    {

        // if it is an alias we will solve the orjinal one
        if (isset($this->aliases[$alias])) {
            $alias = $this->aliases[$alias];
        }

        $singleton = $this->getBoundManager()->singleton($alias);

        // determine the alias already resolved before or not.
        if ($singleton && $this->hasResolvedBefore($alias)) {

            // we resolved that before, let's reuse it.
            return $this->getAlreadyResolved($alias);
        }

        // we realized we did not add this before
        if (false === $this->boundManager->has($alias)) {
            $this->add($alias, $alias);

            // resolve this service with given args
            return $this->resolve($alias, $args);
        }


        $definition = $this
            ->boundManager
            ->findDefinition($alias);

        // if we add new args, we'll set them into definition
        if ( ! empty($args)) {
            $this->getArgumentManager()->setClassArgs(
                $alias,
                $args
            );
        }

        // determine resolved instance is as we expected or not
        $resolved = $this->checkExpectation(
            $alias,
            $this->resolveObject($definition, $alias)
        );


        // we resolved the definition, now we will add into resolvedBond or sharedResolved
        // and we will reuse they when we want to resolve them
        $this->saveResolved($alias, $resolved);

        // we already resolve and saved it. We don't need this definition anymore.
        // so we will remove it.
        // this will save memory
        if ($singleton === true) {
            $this->removeResolvedFromBound($alias);
        }

        return $resolved;
    }

    /**
     * resolves the instance
     *
     * @param object $definition
     * @param string $alias
     * @throws ContainerExceptionInterface
     * @throws ContainerNotFoundException
     * @throws ReflectionException
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


        try {
            $class = new \ReflectionClass($definition);
        } catch (\ReflectionException $exception) {
            throw new ReflectionException(
                $exception->getMessage()
            );
        }


        $this->resolveProviderAnnotations($class);


        // the given class if is not instantiable throw an exception
        // that happens when you try resolve an interface or abstract class
        // without saving an alias on that class before
        if (false === $class->isInstantiable()) {
            throw new ReflectionException(
                sprintf(
                    '%s class in not instantiable, probably an interface or abstract',
                    $class->getName()
                )
            );
        }

        $constructor = $class->getConstructor();

        $parameters = [];

        if (null !== $constructor) {
            $this->resolveInjectAnnotations($constructor, $alias);

            $parameters = $this->resolveParameters(
                $constructor,
                $this->argumentManager->getClassArgs($alias)
            );
        }

        return $class->newInstanceArgs($parameters);
    }


    /**
     * @param \ReflectionClass $class
     */
    private function resolveProviderAnnotations(\ReflectionClass $class)
    {
        if ('' === $class->getDocComment()) {
            return;
        }

        $annotation = $this->get(Annotation::class);

        /**
         * @var Annotation $annotation
         */

        $execute = $annotation->parse($class->getDocComment())->execute();

        if ( ! isset($execute['Provider'])) {
            return;
        }


        $providers = $execute['Provider'];

        /**
         * @var Provider[] $providers
         */

        foreach ($providers as $provider) {

            /**
             * @var Provider $provider
             */

            $this->resolveProviderAnnotation($provider->provider);
        }
    }

    /**
     * @param array $provider
     *
     * @throws ProviderException
     */
    private function resolveProviderAnnotation($provider)
    {
        if (is_array($provider)) {

            foreach ($provider as $item) {
                $this->resolveProviderAnnotation($item);
            }

        } else {
            if (false === $this->isProvided($provider)) {
                $this->addProvider($provider);
            }
        }
    }

    /**
     * @param \ReflectionFunctionAbstract $abstract
     * @param string $alias the name of alias
     * @throws ContainerNotFoundException
     */
    private function resolveInjectAnnotations(\ReflectionFunctionAbstract $abstract, $alias = '')
    {

        if ( ! $this->has(Annotation::class)) {
            throw new ContainerNotFoundException(
                'You did not provide annotation service provider'
            );
        }

        $annotation = $this->get(Annotation::class);

        /**
         * @var Annotation $annotation
         */

        $parsed = $annotation->executeMethod(
            $abstract
        );


        if ( ! isset($parsed['Inject'])) {
            return;
        }


        foreach ($parsed['Inject'] as $inject) {
            $injectValue = $inject->inject;

            $this->resolveInjectAnnotation($injectValue, $alias);
        }

    }

    /**
     * @param array $inject
     * @param $alias
     */
    private function resolveInjectAnnotation(array $inject, $alias)
    {
        foreach ($inject as $item => $value) {
            if (is_array($value)) {
                $this->resolveInjectAnnotation($value, $alias);
            } else {
                $this->prepareInjectContext($alias, $item, $value);
            }
        }
    }

    /**
     * @param string $alias
     * @param string $argument
     * @param string $give
     */
    private function prepareInjectContext($alias, $argument, $give)
    {
        $this->when($alias)
            ->needs(str_replace('$', '', $argument))
            ->give(
                function () use ($give) {
                    return $this->resolve($give);
                }
            );
    }

    /**
     * @param string $alias
     * @param \Closure $closure
     * @throws ContainerExceptionInterface
     * @return mixed
     */
    private function resolveClosure($alias, \Closure $closure)
    {
        try {
            $reflectionFunction = new \ReflectionFunction($closure);
        } catch (\ReflectionException $exception) {
            throw new ReflectionException(
                $exception->getMessage()
            );
        }

        $parameters = $this->resolveParameters(
            $reflectionFunction,
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
     * @throws ContainerNotFoundException
     * @return mixed
     */
    private function resolveMethod($alias, $method, $instance, \ReflectionMethod $abstract)
    {
        $this->resolveInjectAnnotations($abstract, "$alias.$method");


        $parameters = $this->resolveParameters(
            $abstract,
            $this->getArgumentManager()
                ->getMethodArgs($alias, $method)
        );

        return $abstract->invokeArgs($instance, $parameters);
    }

    /**
     * @param \ReflectionFunctionAbstract $abstract
     * @param array $args
     * @throws ExpectationException
     * @throws NotFoundException
     * @throws \ReflectionException
     * @throws ArgumentException
     * @return array
     */
    private function resolveParameters(\ReflectionFunctionAbstract $abstract, array $args)
    {
        $bounded = [];
        $parameters = $abstract->getParameters();

        foreach ($parameters as $parameter) {
            $name = $parameter->getName();

            if (isset($args[$name])) {
                // we will check the argument is a context or a standart argument
                $bounded[$name] = $this->resolveContextOrArgument(
                    $args[$name]
                );

                continue;
            }



            $bounded[$name] = $this->resolveArgument($parameter->getClass()->name);

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
        if ( ! $arg instanceof ContextDefinition) {
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

            if ( ! $happens) {
                $happens = [];
            }

            return ! empty($happens) ?
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
    private function resolveContextCallback(\Closure $callback, array $happens = [])
    {
        $happens[] = $this;

        return call_user_func_array($callback, $happens);
    }

    /**
     *
     * @param \ReflectionParameter $parameter
     * @throws ContainerExceptionInterface
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

            throw new NotFoundException(
                sprintf(
                    '%s parameter does not found',
                    $parameter->getName()
                )
            );

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

        throw new ArgumentException(
            sprintf(
                '%s parameter does not have a default value and does not allow null',
                $parameter->getName()
            )
        );

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
     * @param string $alias
     * @return $this
     */
    public function remove($alias){
        if ($this->boundManager->has($alias)) {
            $this->removeResolvedFromBound($alias);
        }

        if (isset($this->resolved[$alias])) {
            unset($this->resolved[$alias]);
        }

        return $this;
    }
    /**
     * @param string $shared
     * @param string $alias
     */
    private function removeResolvedFromBound($alias)
    {
        $this->boundManager->deleteBond($alias);
    }

    /**
     * @param $alias
     * @param $resolved
     */
    private function saveResolved($alias, $resolved)
    {
        $this->resolved[$alias] = $resolved;
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

        $expecteds = $this->expected[$alias];

        foreach ($expecteds as $expected) {

            if ( ! $instance instanceof $expected) {
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
        return isset($this->resolved[$alias]) ? $this->resolved[$alias] : null;
    }

    /**
     * @param string $alias
     * @return bool
     */
    public function hasResolvedBefore($alias)
    {
        return isset($this->resolved[$alias]);
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
            $class,
            $method
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
        if ($this->has($alias)) {
            return $this->deleteFromBond($alias);
        }
    }


    /**
     * @param string $alias
     * @return $this
     * @throws NotFoundException
     */
    public function deleteFromBond($alias)
    {
        $this->boundManager->deleteBond($alias);

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
     * @param string $instance the name, instance or alias of class
     * @param string $method the name method
     * @param array $args the args will be passed in to resolver, give empty if you already passed them
     * @return mixed
     * @throws NotFoundException
     * @throws \ReflectionException
     * @throws ExpectationException
     * @throws ContainerNotFoundException
     * @throws ArgumentException
     */
    public function call($instance, $method, array $args = [])
    {
        $alias = is_object($instance) ? $this->getClassName($instance) : $instance;

        if ( ! $this->boundManager->has($alias)) {
            $this->add($alias, $alias);
        }

        // if this method didnt add before, we'll add it and resolve it.
        if ( ! $this->methodManager->hasMethod($alias, $method)) {
            $this->addMethod($alias, $method);

            if ( ! empty($args)) {
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


    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @throws NotFoundExceptionInterface  No entry was found for **this** identifier.
     * @throws ContainerExceptionInterface Error while retrieving the entry.
     *
     *
     * @return mixed Entry.
     */
    public function get($id)
    {
        return $this->resolve($id);
    }

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * `has($id)` returning true does not mean that `get($id)` will not throw an exception.
     * It does however mean that `get($id)` will not throw a `NotFoundExceptionInterface`.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @return bool
     */
    public function has($id)
    {
        return $this->getBoundManager()->has($id) || $this->hasResolvedBefore($id);
    }
}
