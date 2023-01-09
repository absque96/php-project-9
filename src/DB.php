<?php

namespace App;

use PDO;
use PDOException;

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
     * @param $sql
     * @param $args
     * @return false|\PDOStatement
     */
    public function run($sql, $args = null): bool|\PDOStatement
    {
        if (!$args) {
            return $this->pdo->query($sql);
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($args);
        return $stmt;
    }
}