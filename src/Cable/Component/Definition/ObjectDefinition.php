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

        $this->methods[$name] = $definition;

        return $this->methods[$name];
    }


    /**
     * @param $name
     * @return mixed
     */
    public function getMethod($name)
    {
        return $this->methods[$name];
    }
    /**
     * @param $name
     * @return bool
     */
    public function hasMethod($name)
    {
        return isset($this->methods[$name]);
    }
}
