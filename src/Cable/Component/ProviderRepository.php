<?php
namespace Cable\Container;


class ProviderRepository
{
    /**
     * @var array
     */
    private $providers;

    /**
     * @param ServiceProvider $provider
     * @throws ProviderException
     * @return $this
     */
    public function add($provider)
    {
        if ( !is_object($provider)) {
            $provider = new $provider;
        }

        if ( !$provider instanceof ServiceProvider) {
            throw new ProviderException(
                sprintf(
                    '%s provider is not as expected',
                    get_class($provider)
                )
            );
        }

        $this->providers[] = $provider;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getProviders()
    {
        return $this->providers;
    }

    /**
     * @param mixed $providers
     * @return ProviderRepository
     */
    public function setProviders($providers)
    {
        $this->providers = $providers;

        return $this;
    }
}

