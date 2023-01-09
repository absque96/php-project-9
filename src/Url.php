<?php

namespace App;

use Carbon\Carbon;

class Url
{
    protected DB $db;

    public function __construct(DB $db)
    {
        $this->db = $db;
    }

    public function addUrl($name)
    {
        $data = [
            'name' => $name,
            'created_at' => Carbon::now(),
        ];
        $sql = "INSERT INTO urls (name, created_at) VALUES (:name, :created_at)";
        $this->db->run($sql, $data);
        $id = $this->db->pdo->lastInsertId();

        return $this->findBy('id', $id);
    }

    public function getAll()
    {
        $sql = "SELECT * FROM urls ORDER BY id DESC";
        return $this->db->run($sql)->fetchAll();
    }

    public function findBy($attr, $value)
    {
        $sql = "SELECT * FROM urls WHERE {$attr}=:{$attr}";
        return $this->db->run($sql, [$attr => $value])->fetch();
    }
}