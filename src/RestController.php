<?php

namespace Ady\MVP2;

abstract class RestController extends Controller
{
    abstract public  function index();

    abstract public  function show();

    abstract  public function create();

    abstract public function update();

    abstract public function delete();

    public function run($method)
    {
        $res = parent::run($method);
        header("Content-Type:application/json");
        echo json_encode($res);
    }

}