<?php

namespace Ady\MVP2\models;

use Exception;

class Coins extends \Ady\MVP2\Model
{
    protected $tableName="coins";

    public function insertBatch($params){
        $table = $this->tableName;
        $arr = [];
        foreach ($params as $coin){
            $coin["coin"] = '"'.$coin['coin'].'"';

            $i = implode(",", $coin);
            $fields = '( ' .$i . ' )';
            $arr[] = $fields;
        }
        $res = implode(",", $arr);
        $sql = "INSERT INTO $table (coin,qty, sellerId) VALUES $res;";

        $sth = $this->_db->prepare($sql);

        try {
            $res = $sth->execute();
        } catch (Exception $e){
            echo $e->getMessage();
            die;
            //return false;
        }
        return true;
    }

    public function reset($sellerId){
        $table = $this->tableName;
        $sql = "DELETE FROM $table where sellerId=:sellerId";
        $sth = $this->_db->prepare($sql);
        try {
            $res = $sth->execute(["sellerId" => $sellerId]);
        } catch (Exception $e){
            echo $e->getMessage();
            die;
        }
        //return true;
    }
}