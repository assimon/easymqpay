<?php

namespace App\Lib;

class Cookie
{
    /**
     * 获取cookie某key值
     * @param string $name
     * @param bool $cookie
     * @return mixed
     * @throws PayException
     */
    public static function getCookieName($name = 'uid', $cookie = false)
    {
        $getCookie = explode($name . '=', $cookie);
        if (count($getCookie) <= 1) {
            var_dump('cookie错误');
            return;
        }
        if ($name == 'uid') return explode('"', $getCookie[1])[0];
        else return explode(';', $getCookie[1])[0];
    }

}