<?php
/**
 * Created by PhpStorm.
 * User: simon
 * Date: 2019-05-06
 * Time: 18:44
 */

namespace App\Lib;

class Requst
{
    /**
     * @var string 请求的控制器.
     */
    public $controller = '';

    /**
     * @var string 请求的方法.
     */
    public $action = '';

    /**
     * @var array 请求的参数
     */
    public $param = [];


    public function __construct($controller, $action, $param)
    {
        $this->controller = $controller;
        $this->action = $action;
        foreach ($param as $key => $value) {
            $this->param[$key] = $value;

        }
    }


}