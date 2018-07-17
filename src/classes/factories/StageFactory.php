<?php
/**
 * Created by PhpStorm.
 * User: henze
 * Date: 13-7-18
 * Time: 20:28
 */

namespace Pipeline\factories;


use Pipeline\elements\Stage;

class StageFactory
{
    public static function make(string $name): Stage
    {
        return new Stage($name);
    }
}