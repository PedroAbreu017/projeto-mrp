<?php
class Database {
    private const HOST = 'localhost';
    private const DB_NAME = 'sistema_mrp';
    private const USERNAME = 'root';
    private const PASSWORD = '123456';
    private const CHARSET = 'utf8mb4';
    
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        $this->connect();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function connect() {
        try {
            $dsn = "mysql:host=" . self::HOST . ";dbname=" . self::DB_NAME . ";charset=" . self::CHARSET;
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];
            
            $this->connection = new PDO($dsn, self::USERNAME, self::PASSWORD, $options);
            
            // Força UTF-8
            $this->connection->exec("SET CHARACTER SET utf8mb4");
            $this->connection->exec("SET NAMES utf8mb4");
            
        } catch (PDOException $e) {
            die("Erro de conexão: " . $e->getMessage());
        }
    }
    
    public function getConnection() {
        if ($this->connection === null) {
            $this->connect();
        }
        return $this->connection;
    }
    
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Erro na query: " . $e->getMessage());
            throw new Exception("Erro ao executar consulta.");
        }
    }
    
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    public function fetch($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    // CORREÇÃO: Método execute() que estava faltando
    public function execute($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount(); // Retorna número de linhas afetadas
        } catch (PDOException $e) {
            error_log("Erro na execução: " . $e->getMessage());
            throw new Exception("Erro ao executar comando no banco.");
        }
    }
    
    // CORREÇÃO: Método lastInsertId() que pode estar faltando
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
    
    // CORREÇÃO: Métodos de transação
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    public function commit() {
        return $this->connection->commit();
    }
    
    public function rollback() {
        return $this->connection->rollback();
    }
    
    public function inTransaction() {
        return $this->connection->inTransaction();
    }
}

function getDB() {
    return Database::getInstance();
}
?>