<?php

namespace Ady\MVP2;

class RestRequest extends \Klein\Request
{
    private static function isJson($string) {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    public static function createFromGlobals()
    {
        // Create and return a new instance of this
        $body = @file_get_contents('php://input');
        
        if(static::isJson($body)){
            $json = json_decode($body, true);

            $method = $_SERVER['REQUEST_METHOD'];

            if( in_array($method, ['POST', 'PUT']) ){
                $req = new static(
                    $_GET,
                    $json,
                    $_COOKIE,
                    $_SERVER,
                    $_FILES,
                    $body
                );
                return $req;
            }
        } else {
            return parent::createFromGlobals();
        }

    }

}