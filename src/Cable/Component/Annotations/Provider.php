<?php

namespace Cable\Container\Annotations;


use Cable\Annotation\Command;

/**
 * Class Provider
 * @package Cable\Container\Annotations
 * @Name("Provider")
 */
class Provider extends Command
{

    /**
     * @var string|array
     * @Annotation()
     * @Required()
     */
    public $provider;
}
