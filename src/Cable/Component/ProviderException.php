<?php
namespace Cable\Container;

use Exception;
use Psr\Container\ContainerExceptionInterface;

class ProviderException extends Exception implements ContainerExceptionInterface {}
