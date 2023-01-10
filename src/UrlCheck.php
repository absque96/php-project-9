<?php

namespace App;

use Carbon\Carbon;

class UrlCheck
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
     * @param array $data
     * @return mixed
     */
    public function addCheck(array $data): mixed
    {
        $data['created_at'] = Carbon::now();

        $sql = "
INSERT INTO url_checks (url_id, status_code, h1, title, description, created_at) 
VALUES (:url_id, :status_code, :h1, :title, :description, :created_at)
";
        $this->db->run($sql, $data);
        $id = $this->db->pdo->lastInsertId();

        return $this->findBy('id', $id);
    }

    /**
     * @param string $attr
     * @param mixed $value
     * @param bool $all
     * @return mixed
     */
    public function findBy(string $attr, mixed $value, bool $all = false): mixed
    {
        $sql = "SELECT * FROM url_checks WHERE {$attr}=:{$attr} ORDER BY id DESC";
        $stmt = $this->db->run($sql, [$attr => $value]);

        if ($all) {
            return $stmt->fetchAll();
        } else {
            return $stmt->fetch();
        }
    }

    /**
     * @return bool|array
     */
    public function getDistinct(): bool|array
    {
        $sql = "SELECT DISTINCT ON (url_id) * FROM url_checks ORDER BY url_id DESC, id DESC";
        return $this->db->run($sql)->fetchAll();
    }
}
