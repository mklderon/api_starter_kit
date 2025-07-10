<?php

namespace Core;

/**
 * Class Database
 * 
 * Handles database connections and operations using PDO.
 */
class Database
{
    /** @var \PDO|null The PDO connection instance */
    private $connection;

    /** @var array Database configuration */
    private $config;

    /** @var Logger Logger instance for logging database events */
    private $logger;

    /**
     * Database constructor.
     *
     * @param array $config Database configuration
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->logger = new Logger();
        $this->connect();
    }

    /**
     * Establish a PDO database connection.
     *
     * @throws \Exception If connection fails
     */
    private function connect(): void
    {
        try {
            $dsn = "mysql:host={$this->config['host']};dbname={$this->config['name']};charset={$this->config['charset']}";
            $this->connection = new \PDO($dsn, $this->config['user'], $this->config['pass']);
            $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
            $this->logger->info("Database connection established", ['dsn' => $dsn]);
        } catch (\PDOException $e) {
            // Remove error logging here to avoid duplication
            throw new \Exception("Database connection error: " . $e->getMessage());
        }
    }

    /**
     * Get the PDO connection instance.
     *
     * @return \PDO|null
     */
    public function getConnection(): ?\PDO
    {
        return $this->connection;
    }

    /**
     * Execute a prepared SQL query.
     *
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @return \PDOStatement
     * @throws \Exception If query execution fails
     */
    public function query(string $sql, array $params = []): \PDOStatement
    {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (\PDOException $e) {
            $this->logger->error("Query execution failed: " . $e->getMessage(), [
                'sql' => $sql,
                'params' => $params,
                'code' => $e->getCode()
            ]);
            throw new \Exception("Query execution failed: " . $e->getMessage());
        }
    }

    /**
     * Insert a record into a table.
     *
     * @param string $table Table name
     * @param array $data Data to insert
     * @return bool
     * @throws \Exception If insertion fails
     */
    public function insert(string $table, array $data): bool
    {
        try {
            $fields = implode(',', array_keys($data));
            $placeholders = ':' . implode(', :', array_keys($data));
            $sql = "INSERT INTO {$table} ({$fields}) VALUES ({$placeholders})";
            
            $stmt = $this->connection->prepare($sql);
            $result = $stmt->execute($data);
            
            $this->logger->info("Record inserted into {$table}", ['data' => $data]);
            return $result;
        } catch (\PDOException $e) {
            $this->logger->error("Insert failed: " . $e->getMessage(), [
                'table' => $table,
                'data' => $data,
                'code' => $e->getCode()
            ]);
            throw new \Exception("Insert failed: " . $e->getMessage());
        }
    }

    /**
     * Update records in a table.
     *
     * @param string $table Table name
     * @param array $data Data to update
     * @param string $where Where clause
     * @return bool
     * @throws \Exception If update fails
     */
    public function update(string $table, array $data, string $where): bool
    {
        try {
            $set = implode(' = ?, ', array_keys($data)) . ' = ?';
            $sql = "UPDATE {$table} SET {$set} WHERE {$where}";
            
            $stmt = $this->connection->prepare($sql);
            $result = $stmt->execute(array_values($data));
            
            $this->logger->info("Record updated in {$table}", ['data' => $data, 'where' => $where]);
            return $result;
        } catch (\PDOException $e) {
            $this->logger->error("Update failed: " . $e->getMessage(), [
                'table' => $table,
                'data' => $data,
                'where' => $where,
                'code' => $e->getCode()
            ]);
            throw new \Exception("Update failed: " . $e->getMessage());
        }
    }

    /**
     * Delete records from a table.
     *
     * @param string $table Table name
     * @param string $where Where clause
     * @param array $params Query parameters
     * @return \PDOStatement
     * @throws \Exception If deletion fails
     */
    public function delete(string $table, string $where, array $params = []): \PDOStatement
    {
        try {
            $sql = "DELETE FROM {$table} WHERE {$where}";
            $stmt = $this->query($sql, $params);
            
            $this->logger->info("Record deleted from {$table}", ['where' => $where, 'params' => $params]);
            return $stmt;
        } catch (\Exception $e) {
            $this->logger->error("Delete failed: " . $e->getMessage(), [
                'table' => $table,
                'where' => $where,
                'params' => $params,
                'code' => $e->getCode()
            ]);
            throw $e;
        }
    }

    /**
     * Get the last inserted ID.
     *
     * @return string
     */
    public function lastInsertId(): string
    {
        return $this->connection->lastInsertId();
    }

    /**
     * Begin a database transaction.
     *
     * @return bool
     */
    public function beginTransaction(): bool
    {
        return $this->connection->beginTransaction();
    }

    /**
     * Commit a database transaction.
     *
     * @return bool
     */
    public function commit(): bool
    {
        return $this->connection->commit();
    }

    /**
     * Roll back a database transaction.
     *
     * @return bool
     */
    public function rollBack(): bool
    {
        return $this->connection->rollBack();
    }
}