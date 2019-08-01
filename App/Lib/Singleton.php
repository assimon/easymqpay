<?php
/**
 * Created by PhpStorm.
 * User: simon
 * Date: 2019-05-06
 * Time: 21:33
 */
namespace App\Lib;

trait Singleton
{

    private static $instance;

    static function getInstance(...$args)
    {
        if (!isset(self::$instance)) {
            self::$instance = new static(...$args);
        }
        return self::$instance;
    }

}