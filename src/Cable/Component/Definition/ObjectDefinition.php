<?php
namespace Cable\Container\Definition;

/**
 * Class ObjectDefinition
 * @package Cable\Container\Definition
 */
class ObjectDefinition extends AbstractDefinition
{


    /**
     * @var array
     */
    protected $methods;

    /**
     * @param string $name
     * @return MethodDefiniton
     */
    public function withMethod($name)
    {
        $definition = new MethodDefiniton($name);

        $definition->setInstance(
            $this->getInstance()
        );

        return $this->methods[$name];
    }
}
