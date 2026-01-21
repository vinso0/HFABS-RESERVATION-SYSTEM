<?php

class Controller
{
    public function model($m)
    {
        require_once __DIR__ . '/../models/' . $m . '.php';
        return new $m();
    }

    public function view($v, $d = [])
    {
        extract($d);
        require_once __DIR__ . '/../views/' . $v . '.php';
    }
}