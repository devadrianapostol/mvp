<?php
namespace Ady\MVP2;
use Exception;
use PDO;
use PDOException;

class DatabaseConfiguration
{
    private string $host;
    private string $username;
    private string $password;
    private string $dbname;

    public function __construct(
        string $host,
        string $username,
        string $password,
        string $dbname
    ) {
        $this->password = $password;
        $this->username = $username;
        $this->host = $host;
        $this->dbname = $dbname;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getDbname(){
        return $this->dbname;
    }
}

class DatabaseConnection
{
    private DatabaseConfiguration $configuration;
    public $pdo;

    public function __construct(DatabaseConfiguration $configuration)
    {
        $this->configuration = $configuration;
        $this->getDb();
    }

    public function getDb()
    {
        // this is just for the sake of demonstration, not a real DSN
        // notice that only the injected config is used here, so there is
        // a real separation of concerns here
        try {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s',
                $this->configuration->getHost(),
                $this->configuration->getDbname(),

            );
            $db = new PDO($dsn, $this->configuration->getUsername(), $this->configuration->getPassword(),);

            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo = $db;
        } catch(PDOEXCEPTION $e){
            echo $e->getMessage();
            exit(0);
        }
    }

    public function prepare($sql)
    {
        return $this->pdo->prepare($sql);
    }
}

class Model {
    protected ?DatabaseConnection $_db = null;
    protected $tableName;
    protected $hidden = [];

    public function __construct()
    {
        $dc = new DatabaseConfiguration(DB_HOST, DB_USER, DB_PASS, DB_NAME );
        $this->_db = new DatabaseConnection($dc);
    }

    // access db connection
    public function db()  {
        return $this->_db->pdo;
    }

    public function insert($params=[]) {
        if(count($params) == 0 ) return false;
        $table = $this->tableName;

        foreach ($params as &$par){
            $par = strval($par);
        }

        $sql = "INSERT INTO $table";
        $fieldnames = array_keys($params);
        $fields = '( ' . implode(' ,', $fieldnames) . ' )';
        $bound = '(:' . implode(', :', $fieldnames) . ' )';
        $sql .= $fields.' VALUES '.$bound;

        $sth = $this->_db->prepare($sql);
        try {
            $sth->execute($params);
        } catch (Exception $e){

            //var_dump($e);
            die;
        }

        $id = $this->_db->pdo->lastInsertId();
        return $this->getById($id);
    }

    public function getById($id){
        $table = $this->tableName;
        $sql = "SELECT * FROM $table WHERE id=:id";
        $res =  $this->fetch($sql , ['id' => $id]);
        if(count($res) == 0) return false;
        $res = $res[0];

        foreach ($this->hidden as $hatt){
            if(in_array( $hatt, array_keys($res) )){
                unset($res[$hatt]);
            }
        }
        return $res;
    }

    public function fetchAll(){
        $table = $this->tableName;
        $res = $this->fetch("SELECT * FROM $table");
        return $res;
    }

    public function update( $params, $condition=""){
        $table = $this->tableName;
        $temp = [];
        foreach ($params as $k => $v){
            $temp[] = $k."=:".$k;
        }
        $fields = join(",", $temp);
        $sql = "UPDATE $table SET ".$fields.( strlen($condition) > 0 ? " WHERE ".$condition : "");

        return $this->query($sql, $params );
    }

    public function query($sql, $params = null){
        $sth = $this->_db->pdo->prepare($sql);

        try {
            if(!$sth)  throw new \Exception('PDO Statement este gol');
          $sth->execute($params);

        } catch (Exception $e){
            //echo "<pre>";
            echo $e->getMessage();
            exit(0);
        }
        //$this->_db = NULL;
        if($sth->rowCount() > 0){
            return true;
        }
        else{
            return false;
        }
    }

    public function delete($table=null, $params=[], $condition=""){
        $table = $table  != null ? $table : $this->tableName;

        $temp = [];
        foreach ($params as $k => $v){
            $temp[] = $k."=:".$k;
        }
        return $this->query("DELETE FROM $table WHERE ".$condition, $params );
    }

    public function deleteById($id){
        return $this->delete($this->tableName, ["id" => $id]);
    }

    public function fetch($sql, $params = null){
        $sth = $this->_db->prepare($sql);

        try {
            $sth->execute($params);

            $res = $sth->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e){
            echo $e->getMessage();
            exit(0);
        }
        return $res;
    }

    public function deleteByCondition($condition, $params)
    {
        $sql = 'DELETE FROM '.$this->tableName." WHERE ".$condition;
        return $this->query($sql, $params);
    }
}