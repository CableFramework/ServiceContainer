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
    private static $shared;

    /**
     * @param string $alias
     * @param null|bool $shared
     * @return array
     */
    public function findDefinition($alias, $shared = null)
    {
        if ($shared === true) {
            return array(Container::SHARED, static::$shared[$alias]);
        }


        return array(
            Container::NOT_SHARED,
            $this->bond[$alias]
        );
    }

    /**
     * @param string $alias
     * @param mixed $callback
     */
    public static function addShare($alias, $callback)
    {
        static::$shared[$alias] = $callback;
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
        return isset($this->bond[$alias]) || isset(static::$shared[$alias]);
    }

    /**
     * @param string $alias
     * @return bool
     */
    public static function hasShare($alias)
    {
        return isset(static::$shared[$alias]);
    }

    /**
     * @param string $alias
     */
    public static function deleteShared($alias)
    {
        unset(static::$shared[$alias]);
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
