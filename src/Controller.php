<?php
namespace Ady\MVP2;

use Ady\MVP2\models\User;
use Ady\MVP2\utils\JWT;
use Klein\App;
use Klein\Request;
use Klein\Response;
use Klein\ServiceProvider;

class Controller
{
    protected Request $request;
    protected Response $response;
    protected ServiceProvider $service;
    protected App $app;
    protected $currentMethodName;
    protected $jwt;

    protected $validators = [];
    protected $unauthenticated = [];
    protected $user;

    public function __construct(Request $request, Response $response, ServiceProvider $service, App $app, $currentMethodName="index")
    {
        $this->request = $request;
        $this->response = $response;
        $this->service = $service;
        $this->app = $app;
        $this->currentMethodName = $currentMethodName;
        $this->jwt = new JWT();

        $defaultvalidators = [
            "required" => fn($str) => $str != null,
            "numeric"  => fn($str) => is_numeric($str),
            "integer"  => fn($str) => is_int($str),
        ];
        $validators = array_merge($defaultvalidators, $this->validators);

        foreach ($validators as $n => $validator){

            if(is_callable($validator)) {
                $this->service->addValidator($n, $validator);
            } else if(is_string($validator)){
                $m = [$this];
                $par = $this->request->params();
                $this->service->addValidator($n , function ($str) use ($m, $validator, $par){
                    $t = $m[0];
                    return $t->{$validator}($str, $par);
                });
            }

        }

    }

    public function run($method){
        if(!in_array($method, $this->unauthenticated)){
            $res = $this->authenticateToken();
            if(!$res){
                http_response_code(401);
                die;
            }
        }

        return $this->{$method}();
    }

    private function authenticateToken()
    {
        $headers = $this->request->headers();
        if(!isset($headers['Authorization'])) return false;
        $token = $this->getBearerToken();
        $res = $this->jwt->is_jwt_valid($token);
        if($res['result'] == TRUE){
            $payload = json_decode($res['payload'], true);
            $uM = new User();
            $user = $uM->getById($payload['id']);
            $this->user = $user;
            return true;
        }
        return false;
    }

    private function getBearerToken()
    {
        $headers = $this->request->headers()["Authorization"];
        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
        }
    }


}