<?php

namespace Cable\Container;

/**
 * Class Factory
 * @package Cable\Container
 */
class Factory
{


    /**
     * create a new container instance
     *
     * @param ProviderRepository|null $providerRepository
     * @return Container
     */
    public static function create(ProviderRepository $providerRepository = null)
    {
        $container = new Container(
            new BoundManager(),
            new MethodManager(),
            new ArgumentManager(),
            $providerRepository
        );

        $container->add(
            Container::class,
            function () use ($container) {
                return $container;
            }
        )->alias(ContainerInterface::class);

        return $container;
    }
}
