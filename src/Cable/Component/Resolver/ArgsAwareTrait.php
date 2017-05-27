<?php

namespace Cable\Container\Resolver;


trait ArgsAwareTrait
{

    /**
     * @var array
     */
    private $args;

    /**
     * @return array
     */
    public function getArgs()
    {
        return $this->args;
    }

    /**
     * @param array $args
     * @return ArgsAwareTrait
     */
    public function setArgs($args)
    {
        $this->args = $args;

        return $this;
    }
}
