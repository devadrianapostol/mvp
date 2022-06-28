<?php
namespace Ady\MVP2\controllers;

use Ady\MVP2\models\Coins;
use Ady\MVP2\models\User;
use Ady\MVP2\RestController;
use Ady\MVP2\utils\JWT;
use Klein\App;
use Klein\Exceptions\ValidationException;
use Klein\Request;
use Klein\Response;
use Klein\ServiceProvider;


class UserController extends RestController {

    protected $model;
    protected $unauthenticated = ['login'];

    public function __construct(Request $request, Response $response, ServiceProvider $service, App $app)
    {
        parent::__construct($request, $response, $service, $app);
        $this->model = new User();
    }

    // validatorName => function name
    protected $validators = [
        "role" => 'checkRole',
        "deposit" => 'checkDeposit',
        "uniqueUsername" => 'checkUniqueUser',
        "userExists" => "userExists",
        "userNotExists" => "userNotExists",
        "coinValid" => "coinValid"
    ];

    public function checkRole($role){
        return in_array($role, ['seller', 'buyer']);
    }

    public function checkDeposit($v){
        $i = intval($v);
        return $i > 0;
    }

    public function coinValid($amount, $attr){
        $validCoins = [5,10,20,50,100];
        foreach ($amount as $coin){
            if(!isset($coin['deposit']) || !is_int($coin['deposit'])){
                return false;
            }
            if(!in_array($coin['deposit'], $validCoins)){
                return false;
            }
        }
        return true;
    }

    public function checkUniqueUser($uname, $param){
        $res = $this->model->getByUsername($uname);
        if($res==null) return true;
        return count($res) == 0;
    }

    public function userExists($uid, $param){
        $u = $this->model->getById($uid);
        return count($u) > 0;
    }

    public function userNotExists($uname, $param){
        $res = $this->model->getByUsername($uname);
        return count($res) > 0;
    }

    public  function index(){
        $us = $this->model->fetchAll();
        return $us;
    }

    public  function show(){
        $us = $this->model->getById($this->request->id);
        return $us;
    }

    public function create(){
        // TODO: create standalone Validator
        try {
            $this->service->validateParam('username','The username is required')->isRequired();
            $this->service->validateParam('username','The account with that username already exists')->isUniqueUsername();
            $this->service->validateParam('password', 'The password is required')->isRequired();
            $this->service->validateParam('role', 'The role is required')->isRequired();
            $this->service->validateParam('role', 'The role is invalid')->isRole();

        } catch (ValidationException $e){
            return [
                "error" => $e->getMessage()
            ];
        }

        $data = $this->request->params(['username', 'password', 'role']);
        $cleartext = $data['password'];
        $options = [
            'cost' => 11
        ];
        $pass = password_hash($cleartext, PASSWORD_BCRYPT, $options);
        $data['password'] = $pass;
        $res = $this->model->insert($data);
        return $res;
    }

    public function update(){
        try {
            $this->service->validateParam('id','The account with that id does not exist')->isUserExists();
        } catch (ValidationException $e){
            return [
                "error" => $e->getMessage()
            ];
        }
        $id = $this->request->id;
        $oldU = $this->model->getById($id);

        $data = $this->request->params(['username','password', 'role']);
        $this->model->update($data, 'id='.$id);
        return $this->model->getById($id);
    }

    public function delete(){
        try {
            $this->service->validateParam('id','The account with that id does not exist')->isUserExists();
        } catch (ValidationException $e){
            return [
                "error" => $e->getMessage()
            ];
        }
        $res = $this->model->deleteById($this->request->id);
        return [
            "result" => $res
        ];
    }

    public function login(){

        try {
            $this->service->validateParam('username','The username is required')->isRequired();
            $this->service->validateParam('username','The user does not exist with that username')->isUserNotExists();
            $this->service->validateParam('password', 'The password is required')->isRequired();
        } catch (ValidationException $e){
            return [
                "error" => $e->getMessage()
            ];
        }
        $uname = $this->request->username;
        $pass = $this->request->password;
        $user = $this->model->getByUsername($uname);

        if (password_verify($pass, $user['password'])) {
            unset($user['password']);
            $headers = ['alg'=>'HS256','typ'=>'JWT'];
            $payload = [
                'id'=> $user['id'],
                'name'=> $user['username'],
                'role'=> $user['role'],
                'exp'=> JWT_EXPIRE
            ];
            $jwt = new JWT();
            $token = $jwt->generate_jwt($headers, $payload);
            $user['token'] = $token;
            return $user;
        } else {
            return [
                "error" => "Password is invalid"
            ];
        }
    }

    public function deposit(){

        if($this->user['role'] != "buyer") return [
            "error" => "User is not a buyer"
        ];

        try {
            $this->service->validateParam('coins','The deposit is invalid. You need coins nominations of 5,10,20,50 and 100')
                ->isCoinValid();
        } catch (ValidationException $e){
            return [
                "error" => $e->getMessage()
            ];
        }
        $finalAmount = 0;
        $coins = $this->request->param('coins');

        $coinsBucket = [];
        foreach ($coins as $coin){

            $c = $coin['deposit'];
            $qty = $coin['qty'] ?? 1;
            if(!isset($coinsBucket[$c]) ){
                $coinsBucket[$c] = $qty;
            } else {
                $coinsBucket[$c] += $qty;
            }
            $finalAmount += (int)$coin['deposit'] * $qty;
        }

        $res = $this->model->deposit($this->user['id'],$finalAmount, $coinsBucket);

        if($res){
            $user = $this->model->getById($this->user['id']);
            $this->user = $user;
            return $user;
        } else {
            return [
                'error' => "Deposit could not be made"
            ];
        }

    }

    public function reset(){
        if($this->user['role'] != "buyer") return [
            "error" => "User is not a buyer"
        ];
        $uid = $this->user['id'];
        $res = $this->model->update(["deposit" => 0, "id" => $uid], "id=:id");
        $user = $this->model->getById($this->user['id']);

        $coinModel = new Coins();
        $coinModel->reset($uid);

        $this->user = $user;
        return $user;
    }
}