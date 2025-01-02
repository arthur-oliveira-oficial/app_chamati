<?php

class Database {
    private static $instance = null;
    private $conn;
    
    // Adicionar as propriedades estáticas
    private static $host;
    private static $dbname;
    private static $username;
    private static $password;

    private function __construct() {
        try {
            // Carrega variáveis do .env
            $envFile = dirname(__DIR__) . '/.env';
            if (!file_exists($envFile)) {
                throw new Exception('Arquivo .env não encontrado');
            }

            $env = parse_ini_file($envFile);
            
            // Define as propriedades estáticas
            self::$host = $env['DB_HOST'];
            self::$dbname = $env['DB_NAME'];
            self::$username = $env['DB_USER'];
            self::$password = $env['DB_PASS'];
            
            // Configuração DSN
            $dsn = "mysql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_NAME']};charset=utf8mb4";
            
            // Opções PDO para melhor segurança e performance
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            // Cria conexão PDO
            $this->conn = new PDO($dsn, $env['DB_USER'], $env['DB_PASS'], $options);

        } catch (PDOException $e) {
            die('Erro de conexão: ' . $e->getMessage());
        } catch (Exception $e) {
            die('Erro: ' . $e->getMessage());
        }
    }

    // Previne clonagem do objeto
    private function __clone() {}

    // Método para obter instância
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Método para obter conexão
    public function getConnection() {
        return $this->conn;
    }

    // Método para prepared statements
    public function prepare($sql) {
        return $this->conn->prepare($sql);
    }

    // Método para iniciar transação
    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }

    // Método para commit
    public function commit() {
        return $this->conn->commit();
    }

    // Método para rollback
    public function rollBack() {
        return $this->conn->rollBack();
    }
}