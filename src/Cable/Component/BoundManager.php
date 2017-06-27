<?php

namespace Cable\Container;


class BoundManager
{

    /**
     * @var array
     */
    private $bond;

    /**
     * @var array
     */
    private $singleton;


    /**
     * @param string $alias
     * @return array
     */
    public function findDefinition($alias)
    {
           return  $this->bond[$alias];
    }

    /**
     * @param $name
     * @param null $value
     * @return $this|bool|mixed
     */
    public function singleton($name, $value = null){
        if (null === $value) {
            return isset($this->singleton[$name]) ?
                $this->singleton[$name] :
                false;
        }


        $this->singleton[$name] = $value;

        return $this;
    }



    /**
     * @param string $alias
     * @param mixed $callback
     * @return $this
     */
    public function addBond($alias, $callback)
    {
        $this->bond[$alias] = $callback;

        return $this;
    }


    /**
     * @param $alias
     * @return ObjectDefinition
     */
    public function getBond($alias)
    {
        return $this->bond[$alias];
    }

    /**
     * @param string $alias
     * @return bool
     */
    public function has($alias)
    {
        return isset($this->bond[$alias]);
    }


    /**
     * @param string $alias
     * @return $this
     */
    public function deleteBond($alias)
    {
        unset($this->bond[$alias]);

        return $this;
    }


}
