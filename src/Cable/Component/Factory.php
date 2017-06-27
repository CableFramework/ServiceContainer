<?php

namespace Cable\Container;

use Cable\Container\Annotations\AnnotationServiceProvider;

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
     * @throws ProviderException
     * @return Container
     */
    public static function create(ProviderRepository $providerRepository = null)
    {
        $container = new Container(
            $providerRepository
        );


        if (!$container->isProvided(AnnotationServiceProvider::class)) {
            $container->addProvider(AnnotationServiceProvider::class);
        }

        $container->add(
            Container::class,
            function () use ($container) {
                return $container;
            }
        )->alias(ContainerInterface::class);

        return $container;
    }
}
