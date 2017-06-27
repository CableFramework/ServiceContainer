<?php

namespace Cable\Container\Annotations;


use Cable\Annotation\Command;

/**
 * Class Inject
 * @package Cable\Container\Annotations
 * @Name("Inject")
 */
class Inject extends Command
{

    /**
     * @var array
     *
     * @Annotation()
     * @Required()
     */
    public $inject;
}
