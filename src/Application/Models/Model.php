<?php

namespace App\Application\Models;

use PDO;
use PDOException;

class Model
{
    protected PDO $pdo;
    protected string $tableName = '';
    protected string $whereClause = '';
    protected string $groupByClause = '';
    protected string $limitClause = '';
    protected array $params = [];
    protected string $joinClause = '';
    protected string $sql = "";
    protected $lastException;
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findAll()
    {
        $this->sql = "SELECT * FROM $this->tableName";
        return $this;
    }
    public function find(int $id)
    {
        $this->sql      = "SELECT * FROM $this->tableName WHERE `id`=? ";
        $this->params[] = $id;
        return $this;
    }
    public function insert(array $data)
    {
        // $data should be an associative array with column => value pairs for update
        $columns = implode(', ', array_keys($data));
        $values  = rtrim(str_repeat('?, ', count($data)), ', ');

        $this->sql    = "INSERT INTO $this->tableName ($columns) VALUES ($values)";
        $this->params = array_values($data);

        return $this;
    }
    public function update(array $data)
    {
        // $data should be an associative array with column => value pairs for update
        $this->sql = "UPDATE $this->tableName SET ";

        foreach ($data as $column => $value) {
            $this->sql .= "`$column`=?, ";
            $this->params[] = $value;
        }
        // Remove the trailing comma
        $this->sql = rtrim($this->sql, ', ');

        return $this; // Return the number of affected rows
    }
    public function delete()
    {
        $this->sql = "DELETE FROM $this->tableName {$this->whereClause}";

        return $this;
    }
    public function where(string $column, string $operator, $value)
    {
        if ($operator === 'in' && is_string($value)) {
            $this->whereClause = " WHERE `$column` $operator ($value)";
        } elseif ($operator === 'in' && is_array($value)) {
            $placeholders      = rtrim(str_repeat('?, ', count($value)), ', ');
            $this->whereClause = " WHERE `$column` $operator ($placeholders)";
            $this->params      = array_merge($this->params, $value);
        } else {
            $this->whereClause = " WHERE `$column` $operator ?";
            $this->params[]    = $value;
        }

        return $this;
    }
    public function andWhere(string $column, string $operator, $value)
    {

        if ($operator === 'in' && is_string($value)) {
            $this->whereClause = " AND `$column` $operator ($value)";
        } elseif ($operator === 'in' && is_array($value)) {
            $placeholders      = rtrim(str_repeat('?, ', count($value)), ', ');
            $this->whereClause = " AND `$column` $operator ($placeholders)";
            $this->params      = array_merge($this->params, $value);
        } else {
            $this->whereClause .= " AND `$column` $operator ?";
            $this->params[]    = $value;
        }
        return $this;
    }

    public function orWhere(string $column, string $operator, string $value)
    {
        if ($operator === 'in' && is_string($value)) {
            $this->whereClause = " OR `$column` $operator ($value)";
        } elseif ($operator === 'in' && is_array($value)) {
            $placeholders      = rtrim(str_repeat('?, ', count($value)), ', ');
            $this->whereClause = " OR `$column` $operator ($placeholders)";
            $this->params      = array_merge($this->params, $value);
        } else {
            $this->whereClause .= " OR `$column` $operator ?";
            $this->params[]    = $value;
        }

        return $this;
    }

    public function join(string $table, string $condition)
    {
        $this->joinClause = " JOIN $table ON $condition";
        return $this;
    }

    public function leftJoin(string $table, string $condition)
    {
        $this->joinClause = " LEFT JOIN $table ON $condition";
        return $this;
    }

    public function rightJoin(string $table, string $condition)
    {
        $this->joinClause = " RIGHT JOIN $table ON $condition";
        return $this;
    }
    public function groupBy(string $column)
    {
        $this->groupByClause = " GROUP BY $column";
        return $this;
    }

    public function limit(int $limit, ?int $offset = null)
    {
        $this->limitClause = " LIMIT $limit";
        if ($offset !== null) {
            $this->limitClause .= " OFFSET $offset";
        }
        return $this;
    }
    public function execute(string $fetch = 'fetchAll')
    {
        $this->sql .= "{$this->whereClause}";
        $stmt      = $this->pdo->prepare($this->sql);

        if (!empty($this->params)) {
            foreach ($this->params as $key => $value) {
                $stmt->bindValue($key + 1, $value);
            }
        }

        try {
            $stmt->execute();

            if ($fetch == 'fetchAll') {

                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $data = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            $this->lastException = $e->getMessage();
            error_log($e->getMessage());

            return false;
            // throw $e;
        }

        $this->sql         = "";
        $this->params      = [];
        $this->joinClause  = "";
        $this->whereClause = "";

        return $data;
    }
    public function getLastException()
    {
        return $this->lastException;
    }
}
