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


        return new Container(
            new BoundManager(),
            new MethodManager(),
            new ArgumentManager(),
            $providerRepository
        );
    }
}
