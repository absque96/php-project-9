<?php

namespace App;

use Carbon\Carbon;

class Url
{
    protected DB $db;

    /**
     * @param DB $db
     */
    public function __construct(DB $db)
    {
        $this->db = $db;
    }

    /**
     * @param $name
     * @return mixed
     */
    public function addUrl($name): mixed
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

    /**
     * @return array|false
     */
    public function getAll(): bool|array
    {
        $sql = "SELECT * FROM urls ORDER BY id DESC";
        return $this->db->run($sql)->fetchAll();
    }

    /**
     * @param $attr
     * @param $value
     * @return mixed
     */
    public function findBy($attr, $value): mixed
    {
        $sql = "SELECT * FROM urls WHERE {$attr}=:{$attr}";
        return $this->db->run($sql, [$attr => $value])->fetch();
    }
}