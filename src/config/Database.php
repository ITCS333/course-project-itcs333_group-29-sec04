<?php
class Database{
    private $host = 'localhost';
    private $db   = 'course';
    private $user = 'admin';
    private $pass = 'password123';
    public $pdo;

     public function getConnection(){
        $this->pdo=null;
        try{
            $dsn = "mysql:host=".$this->host.";dbname=".$this->db;
            $pdo=new PDO($dsn,$this->user,$this->pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
        }catch(\PDOException $e){
            throw new \PDOException($e->getMessage(), (int)$e->getCode());
        }
        return $pdo;
     }

}
?>