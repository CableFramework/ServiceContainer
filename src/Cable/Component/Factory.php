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
        if (null === $providerRepository) {
            $providerRepository = new ProviderRepository();

            $providerRepository->add(
                AnnotationServiceProvider::class
            );
        }

        $container = new Container(
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
