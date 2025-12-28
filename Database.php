<?php

    class Database {

        private $host = null;

        private $db   = null;

        private $user = null;

        private $pass = null;

        private $charset = 'utf8mb4';


        private $pdo;

        private static $instance = null;


        private function __construct() {

            $keys = include_once __DIR__."/.ignore/config/db.php";

            $this -> host = $keys['host'];

            $this -> db = $keys['database'];

            $this -> user = $keys['user'];

            $this -> pass = $keys['password'];

            $dsn = "mysql:host={$this->host};

            dbname={$this->db};

            charset={$this->charset}";


            $options = [

                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on errors

                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch associative arrays

                PDO::ATTR_EMULATE_PREPARES   => false,                  // Use native prepares

            ];


            try {

                $this->pdo = new PDO($dsn, $this->user, $this->pass, $options);

            } catch (\PDOException $e) {

                http_response_code(500);

                echo json_encode([
                    'error' => 'Database connection failed',
                    'message' => $e -> getMEssage()
                ]);

                exit();

            }

        }


        // Singleton pattern: one connection only
        public static function getInstance() {

            if (self::$instance === null) {

                self::$instance = new Database();

            }

            return self::$instance;

        }

        //public function getConnection() {
        public function connect() {

            return $this->pdo;

        }

    }

