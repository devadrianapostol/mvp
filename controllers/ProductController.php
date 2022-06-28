<?php

namespace Ady\MVP2\controllers;

use Ady\MVP2\models\Product;
use Ady\MVP2\models\User;
use Ady\MVP2\RestController;
use Klein\App;
use Klein\Exceptions\ValidationException;
use Klein\Request;
use Klein\Response;
use Klein\ServiceProvider;

class ProductController extends RestController {
    // validatorName => function name
    protected $validators = [
        "seller" => "sellerExists",
        "productSeller" => 'isProductSeller',
        "productNameE" => "productAlreadyExists",
        "productNotExist" => "productNotExist"
    ];

    protected $unauthenticated = ['index', 'show'];

    public function isProductSeller($productId, $attr){
        $ps = $this->model->getProductSeller($productId, $this->user['id']);
        return count($ps);
    }

    public function sellerExists($uid, $param){
        if(!isset($this->user['id'])) return false;
        return $this->user['role'] == 'seller';
    }

    public function productAlreadyExists($pname, $param){

        $sid = $param['sellerId'];
        $sql = "SELECT * FROM products WHERE productName=:productName and sellerId=:sellerId;";
        $u = $this->model;
        $res = $u->fetch($sql, ["productName" => $pname, "sellerId" => $sid]);

        return count($res) == 0;
    }

    public function productNotExist($uid, $param){
        $sql = "SELECT * FROM products WHERE id=:id;";
        $u = $this->model;
        $res = $u->fetch($sql, ["id" => $uid]);
        return count($res) != 0;
    }



    public function __construct(Request $request, Response $response, ServiceProvider $service, App $app)
    {
        parent::__construct($request, $response, $service, $app);
        $this->model = new Product();
    }

    public function index()
    {
        return $this->model->fetchAll();
    }

    public function show()
    {
        return $this->model->getById($this->request->id);
    }

    public function create()
    {
        if($this->user['role'] != "seller"){
            return [
                "error" => "User is not seller"
            ];
        }

        try {
            $this->service->validateParam('productName','The product name is required')
                ->isRequired();
            /*$this->service->validateParam('productName','The product name already exists')
                ->isProductNameE();*/
            $this->service->validateParam('amountAvailable','The amount available is required')
                ->isRequired();
            $this->service->validateParam('cost', 'The cost is required')->isRequired();
            //$this->service->validateParam('sellerId', 'The seller id is required')->isRequired();
            $this->service->validateParam('sellerId', 'The user is not seller')->isSeller();

        } catch (ValidationException $e){
            return [
                "error" => $e->getMessage()
            ];
        }

        $data = $this->request->params(['productName', 'amountAvailable', 'cost']);
        $data['sellerId'] = $this->user['id'];
        $res = $this->model->insert($data);
        return $res;
    }

    public function update()
    {
        if($this->user['role'] != "seller"){
            return [
                "error" => "User is not a seller"
            ];
        }
        try {
            $this->service->validateParam('sellerId', 'The seller is invalid')->isSeller();
            $this->service->validateParam('id', 'The id is required')->isRequired();
            $this->service->validateParam('id', 'The product does not belong to seller')
                ->isProductSeller();
        } catch (ValidationException $e){
            return [
                "error" => $e->getMessage()
            ];
        }
        $id = $this->request->id;

        $data = $this->request->params(['productName', 'amountAvailable', 'cost', ]);
        $this->model->update($data, 'id='.$id);
        return $this->model->getById($id);
    }

    public function delete()
    {
        if($this->user['role'] != "seller"){
            return [
                "error" => "User is not seller"
            ];
        }
        try {
            $this->service->validateParam('id','The product with that id does not exist')->isProductNotExist();
            $this->service->validateParam('id', 'The product does not belong to seller')
                ->isProductSeller();
        } catch (ValidationException $e){
            return [
                "error" => $e->getMessage()
            ];
        }
        $res = $this->model->deleteByCondition(
            " id=:id AND sellerId=:sellerId ",[
            'id' => $this->request->id,
            "sellerId" => $this->user['id'],
        ]);
        return [
            "result" => $res
        ];
    }
}