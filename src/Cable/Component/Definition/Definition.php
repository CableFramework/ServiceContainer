<?php

namespace Cable\Container\Definition;


abstract class Definition
{


    /**
     * @var string
     */
    protected $name;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return MethodDefinition
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }



}