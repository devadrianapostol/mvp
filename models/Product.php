<?php
namespace Ady\MVP2\models;
use Ady\MVP2\Model;
class Product extends Model
{
    protected $tableName="products";

    public function getProductSeller($productId, $sellerId)
    {
        $tname = $this->tableName;
        $sql = "SELECT * FROM $tname WHERE id =:id and sellerId=:sellerId;";
        return $this->fetch($sql, ['id'=> $productId, 'sellerId' => $sellerId]);
    }


}