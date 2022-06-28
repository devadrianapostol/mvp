<?php
namespace Ady\MVP2\models;
use Ady\MVP2\Model;

class User extends Model
{
    protected $tableName = "users";

    protected $hidden = ['password'];

    public function getByUsername($uname){
        $res = $this->fetch("SELECT * FROM users WHERE username =:uname;", ["uname" => $uname]);
        if(count($res) > 0) return $res[0];
        return null;
    }

    public function getSeller(array $params)
    {
        $sql = "SELECT * FROM users WHERE id =:id and role='seller';";
        return $this->fetch($sql, $params);
    }

    public function deposit($uid, int $deposit, $coinsBucket)
    {
        $u = $this->getById($uid);

        $deposit = $u['deposit'] == null ? $deposit : intval($u['deposit']) + $deposit;
        $arr = [];
        foreach ($coinsBucket as $coinValue => $qty){
            $arr[] = ['coin' => $coinValue , 'qty' => $qty, "sellerId" => $uid];
        }

        $cModel = new Coins();
        $cModel->insertBatch($arr);

        return $this->update( ["id" => $uid, "deposit" => $deposit ], 'id=:id');
    }

}