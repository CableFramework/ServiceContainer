<?php

namespace Cable\Container\Resolver;


class StringResolver extends Resolver
{

    /**
     *  resolves the instance
     *
     * @param array $args
     * @return mixed
     */
    public function resolve(array $args = [])
    {
        return class_exists($this->instance, true) ?
            (new ObjectResolver($this->instance))->resolve($args) :
            false;
    }
}