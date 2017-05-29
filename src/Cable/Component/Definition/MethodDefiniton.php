<?php

namespace Cable\Container\Definition;


class MethodDefiniton extends AbstractDefinition
{
    protected $type = 'public';

    /**
     * @var string
     */
    protected $name;

    /**
     * @var array
     */
    protected static $types = [
        'public',
        'private',
        'protected',
    ];

    /**
     * MethodDefiniton constructor.
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }



    /**
     * @param string $type
     * @return $this
     * @throws ThrowMethodTypeException
     */
    public function type($type)
    {
        $this->checkTypeExists($type);
        $this->type = $type;

        return $this;
    }

    /**
     * @param $type
     * @throws ThrowMethodTypeException
     */
    private function checkTypeExists($type)
    {
        if ( ! in_array($type, static::$types)) {
            throw new ThrowMethodTypeException(
                sprintf(
                    '%s type is not supported',
                    $type
                )
            );
        }
    }
}
