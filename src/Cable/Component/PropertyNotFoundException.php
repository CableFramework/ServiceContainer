<?php

namespace Cable\Container;


use Exception;
use Psr\Container\ContainerExceptionInterface;

/**
 * Class PropertyNotFoundException
 * @package Cable\Container
 */
class PropertyNotFoundException  extends Exception implements ContainerExceptionInterface{}
