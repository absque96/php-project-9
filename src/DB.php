<?php

namespace App;

use PDO;
use PDOException;
use PDOStatement;

class DB
{
    public PDO $pdo;

    public function __construct()
    {
        $databaseUrl = parse_url($_ENV['DATABASE_URL']);
        $username = $databaseUrl['user'];
        $password = $databaseUrl['pass'];
        $host = $databaseUrl['host'];
        $port = $databaseUrl['port'];
        $dbName = ltrim($databaseUrl['path'], '/');

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ];

        $dsn = "pgsql:host=$host;port=$port;dbname=$dbName;";

        try {
            $this->pdo = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            throw new PDOException($e->getMessage());
        }
    }

    /**
     * @param string $sql
     * @param array|null $args
     * @return PDOStatement
     */
    public function run(string $sql, array $args = null): PDOStatement
    {
        if (!$args) {
            return $this->pdo->query($sql);
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($args);
        return $stmt;
    }
}
